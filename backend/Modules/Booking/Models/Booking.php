<?php

namespace Modules\Booking\Models;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Modules\Package\Models\BookingPackages;
use Modules\Package\Models\UserPackageServices;
use Modules\Package\Models\BookingPackageService;
use Modules\Promotion\Models\UserCouponRedeem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Commission\Trait\CommissionTrait;
use Modules\Service\Models\Service;
use Modules\Package\Models\UserPackageRedeem;
use Modules\Package\Models\UserPackage;
use Illuminate\Support\Facades\DB;


class Booking extends BaseModel
{
    use CommissionTrait;
    use HasFactory;
    use SoftDeletes;

    protected $table = 'bookings';

    protected $casts = [
        'quiet_hours_points' => 'integer',
        'quiet_hours_credited_at' => 'datetime',
        'user_id' => 'integer',
        'branch_id' => 'integer',
        'arrived_at' => 'datetime',
        'arrival_confirmed_by' => 'integer',
    ];

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \Modules\Booking\database\factories\BookingFactory::new();
    }

    public function branch()
    {
        return $this->belongsTo(\App\Models\Branch::class, 'branch_id')->withTrashed();
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function services()
    {
        $locale = app()->getLocale();
        return $this->hasMany(BookingService::class, 'booking_id')->with('employee')
            ->leftJoin('services', 'booking_services.service_id', 'services.id')
            ->selectRaw("
                            JSON_UNQUOTE(JSON_EXTRACT(services.name, '$.\"$locale\"')) as service_name,
                            'services.*',
                            booking_services.*
                        ");    
    }

    public function packages()
    {
        return $this->hasMany(BookingPackages::class, 'booking_id')->with('employee')
            ->leftJoin('packages', 'booking_packages.package_id', 'packages.id')
            ->select('packages.name as name', 'packages.description', 'booking_packages.*');
    }

    public function userPackageReddem()
    {
        return $this->hasMany(userPackageRedeem::class)->with('package');
    }


    public function products()
    {
        return $this->hasMany(BookingProduct::class, 'booking_id')
            ->leftJoin('products', 'booking_products.product_id', 'products.id')
            ->selectRaw('IFNULL(CONCAT(products.name, " - ", booking_products.variation_name), products.name) as product_name, booking_products.*')
            ->with('employee')->with('product.media');
    }

    public function booking_service()
    {
        return $this->hasMany(BookingService::class, 'booking_id', 'id')->with('employee', 'service');

    }

    public function service()
    {
        return $this->hasOne(BookingService::class, 'booking_id', 'id')->with(['employee', 'service']);
    }

    public function mainServices()
    {
        return $this->hasManyThrough(Service::class, BookingService::class, 'booking_id', 'id', 'id', 'service_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function arrivalConfirmedBy()
    {
        return $this->belongsTo(User::class, 'arrival_confirmed_by')->withTrashed();
    }

    public function bookingTransaction()
    {
        return $this->hasOne(BookingTransaction::class)->where('payment_status', 1)->latest('id');
    }

    public function transactions()
    {
        return $this->hasMany(BookingTransaction::class);
    }

    public function payment()
    {
        return $this->hasOne(BookingTransaction::class)->latest('id');
    }

    public function bookingService()
    {
        return $this->hasMany(BookingService::class);
    }


    public function userCouponRedeem()
    {
        return $this->hasOne(UserCouponRedeem::class, 'booking_id');
    }

    public function userPackages()
    {
        return $this->hasMany(UserPackage::class);
    }
    public function bookingPackages()
    {
        $locale = app()->getLocale();
        return $this->hasMany(BookingPackages::class, 'booking_id', 'id')
            ->leftJoin('packages', 'booking_packages.package_id', 'packages.id')
            ->selectRaw("
                JSON_UNQUOTE(JSON_EXTRACT(packages.name, '$.\"$locale\"')) as name,
                packages.description,
                packages.start_date,
                packages.end_date,
                booking_packages.*
            ");
    }

    public function scopeBranch($query)
    {
        $branch_id = request()->selected_session_branch_id;
        if (isset($branch_id)) {
            return $query->where('branch_id', $branch_id);
        } else {
            return $query->whereNotNull('branch_id');
        }
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->whereHas('transactions', function (Builder $transactionQuery) {
            $transactionQuery->where('payment_status', 1);
        });
    }

    public function scopeUnpaid(Builder $query): Builder
    {
        return $query->whereDoesntHave('transactions', function (Builder $transactionQuery) {
            $transactionQuery->where('payment_status', 1);
        });
    }

    public static function cleanupExpiredBookings()
    {
        try {
            $tenMinutesAgo = \Carbon\Carbon::now()->subMinutes(10);
            $expiredBookings = static::where('status', 'pending')
                ->whereIn('payment_type', ['cart', 'payment'])
                ->where('created_at', '<=', $tenMinutesAgo)
                ->unpaid()
                ->get();

            foreach ($expiredBookings as $booking) {
                $booking->bookingService()->delete();
                $booking->packages()->delete();
                $booking->products()->delete();
                $booking->transactions()->delete();
                $booking->userCouponRedeem()->delete();
                $booking->delete();
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Failed to auto-delete expired bookings: " . $e->getMessage());
        }
    }

    public static function userBaseQuery(int $userId, array $relations = []): Builder
    {
        return static::with($relations)->where('created_by', $userId)->whereNull('deleted_by');
    }

    public static function getUserIncompleteBookings(int $userId,?string $paymentType = null,array $relations = ['service.service', 'service.employee']): Collection {
        static::cleanupExpiredBookings();

        return static::userBaseQuery($userId, $relations)
            ->when($paymentType, fn (Builder $query) => $query->where('payment_type', $paymentType))
            ->unpaid()
            ->whereNotIn('status', ['completed', 'cancelled', 'canceled'])
            ->get();
    }

    public static function getCompletedBookings(int $userId,?string $paymentType = null,array $relations = ['service.service', 'service.employee']): Collection {
        return static::userBaseQuery($userId, $relations)
            ->when($paymentType, fn (Builder $query) => $query->where('payment_type', $paymentType))
            ->paid()
            ->where('status', 'completed')
            ->get();
    }

    // Reports Query
    public static function dailyReport()
    {
        return self::select(
            DB::raw('DATE(bookings.start_date_time) AS start_date_time'),
            DB::raw('COUNT(DISTINCT bookings.id) AS total_booking'),
            DB::raw('COUNT(DISTINCT CONCAT(booking_services.booking_id, "-", booking_services.service_id)) AS total_service'),
            DB::raw('SUM(CASE WHEN booking_services.service_id = (SELECT   service_id FROM booking_services AS bs2 WHERE bs2.booking_id = bookings.id LIMIT 1) THEN booking_services.service_price ELSE 0 END) AS total_service_amount_per_service'),
            DB::raw('SUM(CASE
              WHEN JSON_UNQUOTE(JSON_EXTRACT(tx.tax_info, \'$.type\')) = \'percent\' THEN booking_services.service_price * JSON_UNQUOTE(JSON_EXTRACT(tx.tax_info, \'$.percent\')) / 100
              WHEN JSON_UNQUOTE(JSON_EXTRACT(tx.tax_info, \'$.type\')) = \'fixed\' THEN JSON_UNQUOTE(JSON_EXTRACT(tx.tax_info, \'$.tax_amount\'))
              ELSE 0
          END) AS total_tax_amount'),
          DB::raw('
          COALESCE(SUM(DISTINCT booking_services.service_price), 0) +
          SUM(CASE
              WHEN JSON_UNQUOTE(JSON_EXTRACT(tx.tax_info, \'$.type\')) = \'percent\'
              THEN booking_services.service_price * JSON_UNQUOTE(JSON_EXTRACT(tx.tax_info, \'$.percent\')) / 100
              WHEN JSON_UNQUOTE(JSON_EXTRACT(tx.tax_info, \'$.type\')) = \'fixed\'
              THEN JSON_UNQUOTE(JSON_EXTRACT(tx.tax_info, \'$.tax_amount\'))
              ELSE 0
          END) AS total_amount')
        )
            ->leftJoin('booking_services', 'bookings.id', '=', 'booking_services.booking_id')
            ->leftJoin(DB::raw('(SELECT
                  booking_id,
                  CONCAT(
                      \'{ "type": "\', jt.type, \'", "percent": \', jt.percent, \', "tax_amount": \', jt.tax_amount, \' }\'
                  ) AS tax_info
              FROM (
                  SELECT
                      booking_id,
                      JSON_UNQUOTE(JSON_EXTRACT(tax_percentage, CONCAT(\'$[\', idx, \'].type\'))) AS type,
                      JSON_UNQUOTE(JSON_EXTRACT(tax_percentage, CONCAT(\'$[\', idx, \'].percent\'))) AS percent,
                      JSON_UNQUOTE(JSON_EXTRACT(tax_percentage, CONCAT(\'$[\', idx, \'].tax_amount\'))) AS tax_amount
                  FROM booking_transactions
                  CROSS JOIN (
                      SELECT 0 AS idx UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3
                  ) AS indices
                  WHERE idx < JSON_LENGTH(tax_percentage)
              ) AS jt
              GROUP BY booking_id, jt.type, jt.percent, jt.tax_amount) AS tx'), 'bookings.id', '=', 'tx.booking_id')
            ->where('bookings.status', 'completed')
            ->groupBy('start_date_time');
    }

    public static function totalservice($taxAmount)
    {
        return self::select(
            DB::raw('DATE(bookings.start_date_time) AS start_date_time'),
            DB::raw('COUNT(DISTINCT bookings.id) AS total_bookings'),
            DB::raw('COALESCE(SUM(booking_services.service_price), 0) as total_service_amount'),
            DB::raw('
                COALESCE(SUM(booking_services.service_price), 0) +
                ' . $taxAmount . ' AS total_amount')
        )
            ->leftJoin('booking_services', 'bookings.id', '=', 'booking_services.booking_id')
            ->where('bookings.status', 'completed')
            ->groupBy(DB::raw('DATE(bookings.start_date_time)'));
    }

    public static function overallReport()
    {
        return self::select(
            'bookings.id as id',
            DB::raw('COALESCE(SUM(DISTINCT booking_services.service_price), 0) as total_service_amount'),
            DB::raw('COUNT(DISTINCT booking_services.service_id) AS total_service'),

            DB::raw('SUM(CASE
              WHEN JSON_UNQUOTE(JSON_EXTRACT(tx.tax_info, \'$.type\')) = \'percent\' THEN booking_services.service_price * JSON_UNQUOTE(JSON_EXTRACT(tx.tax_info, \'$.percent\')) / 100
              WHEN JSON_UNQUOTE(JSON_EXTRACT(tx.tax_info, \'$.type\')) = \'fixed\' THEN JSON_UNQUOTE(JSON_EXTRACT(tx.tax_info, \'$.tax_amount\'))
              ELSE 0
          END) AS total_tax_amount'),
            DB::raw('COALESCE(SUM(DISTINCT booking_services.service_price), 0) +
          SUM(CASE
              WHEN JSON_UNQUOTE(JSON_EXTRACT(tx.tax_info, \'$.type\')) = \'percent\' THEN booking_services.service_price * JSON_UNQUOTE(JSON_EXTRACT(tx.tax_info, \'$.percent\')) / 100
              WHEN JSON_UNQUOTE(JSON_EXTRACT(tx.tax_info, \'$.type\')) = \'fixed\' THEN JSON_UNQUOTE(JSON_EXTRACT(tx.tax_info, \'$.tax_amount\'))
              ELSE 0
          END) AS total_amount'),
            'bookings.start_date_time'
        )
            ->leftJoin('booking_services', 'bookings.id', '=', 'booking_services.booking_id')
            ->leftJoin(DB::raw('(SELECT
                  booking_id,
                  CONCAT(
                      \'{ "type": "\', jt.type, \'", "percent": \', jt.percent, \', "tax_amount": \', jt.tax_amount, \' }\'
                  ) AS tax_info
              FROM (
                  SELECT
                      booking_id,
                      JSON_UNQUOTE(JSON_EXTRACT(tax_percentage, CONCAT(\'$[\', idx, \'].type\'))) AS type,
                      JSON_UNQUOTE(JSON_EXTRACT(tax_percentage, CONCAT(\'$[\', idx, \'].percent\'))) AS percent,
                      JSON_UNQUOTE(JSON_EXTRACT(tax_percentage, CONCAT(\'$[\', idx, \'].tax_amount\'))) AS tax_amount
                  FROM booking_transactions
                  CROSS JOIN (
                      SELECT 0 AS idx UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3
                  ) AS indices
                  WHERE idx < JSON_LENGTH(tax_percentage)
              ) AS jt
              GROUP BY booking_id, jt.type, jt.percent, jt.tax_amount) AS tx'), 'bookings.id', '=', 'tx.booking_id')
            ->where('bookings.status', 'completed')
            ->groupBy('bookings.id', 'bookings.start_date_time');
    }

    public function calculateServiceDuration()
    {
        $bookingServiceDuration = BookingService::where('booking_id', $this->id)
            ->sum('duration_min');

        if ($bookingServiceDuration > 0) {
            return $bookingServiceDuration;
        }

        // return BookingPackageService::where('booking_id', $this->id)->with('services')->sum('services.duration_min');
        $bookingPackageServices = BookingPackageService::where('booking_id', $this->id)
            ->with('services')
            ->get();

        $totalDuration = $bookingPackageServices->sum(function ($bookingService) {
            return $bookingService->services->duration_min ?? 0;
        });
        return $totalDuration;
    }

    public function userPackageServices()
    {
        return $this->hasManyThrough(
            UserPackageServices::class, // Target model
            BookingPackages::class, // Intermediate model
            'booking_id', // Foreign key on BookingPackage
            'package_id', // Foreign key on UserPackageService
            'id', // Local key on Booking
            'package_id' // Local key on BookingPackage
        )->with('packageService.services');
    }

    public function bookedPackageService()
    {
        return $this->hasMany(BookingPackageService::class, 'booking_id', 'id');
    }


}
