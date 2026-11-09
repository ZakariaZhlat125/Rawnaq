<?php

namespace App\Models;

use App\Models\Presenters\UserPresenter;
use App\Models\Traits\HasHashedMediaTrait;
use App\Trait\CustomFieldsTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Booking\Models\Booking;
use Modules\Booking\Models\BookingService;
use Modules\Commission\Models\CommissionEarning;
use Modules\Commission\Models\EmployeeCommission;
use Modules\Earning\Models\EmployeeEarning;
use Modules\Employee\Models\BranchEmployee;
use Modules\Employee\Models\EmployeeRating;
use Modules\Package\Models\BookingPackages;
use Modules\Service\Models\ServiceEmployee;
use Modules\Subscriptions\Models\Subscription;
use Modules\Tip\Models\TipEarning;
use Modules\Product\Models\Cart;
use Spatie\MediaLibrary\HasMedia;
use Spatie\Permission\Traits\HasRoles;
use Modules\Wallet\Models\Wallet;
use Modules\BussinessHour\Models\Shift;
use Modules\Affiliate\Models\Affiliate;
use Modules\Tracking\Models\Conversion;
use App\Models\GiftCard;
use App\Models\UserMembership;

class User extends Authenticatable implements HasMedia, MustVerifyEmail
{
    use CustomFieldsTrait;
    use HasApiTokens;
    use HasFactory;
    use HasHashedMediaTrait;
    use HasRoles;
    use Notifiable;
    use SoftDeletes;
    use UserPresenter;

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            do { $code = 'SC'.strtoupper(bin2hex(random_bytes(6))); }
            while (static::withTrashed()->where('referral_code', $code)->exists());
            $user->referral_code = $code;
        });
    }

    const CUSTOM_FIELD_MODEL = 'App\Models\User';

    protected $fillable = [
        'username',
        'first_name',
        'last_name',
        'email',
        'mobile',
        'login_type',
        'gender',
        'date_of_birth',
        'is_manager',
        'show_in_home_booking',
        'show_in_calender',
        'email_verified_at',
        'password',
        'avatar',
        'is_banned',
        'is_subscribe',
        'status',
        'address',
        'city',
        'country',
        'last_notification_seen',
        'user_setting',
        'referred_by_affiliate_id',
    ];

    protected $guarded = [
        'id',
        'updated_at',
        '_token',
        '_method',
        'password_confirmation',
    ];

    protected $dates = [
        'deleted_at',
        'date_of_birth',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'user_setting' => 'array',
    ];

    protected $appends = ['full_name', 'profile_image'];

    public function getFullNameAttribute() // notice that the attribute name is in CamelCase.
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function providers()
    {
        return $this->hasMany('App\Models\UserProvider');
    }

    /**
     * Get the list of users related to the current User.
     *
     * @return [array] roels
     */
    public function getRolesListAttribute()
    {
        return array_map('intval', $this->roles->pluck('id')->toArray());
    }

    /**
     * Route notifications for the Slack channel.
     *
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return string
     */
    public function routeNotificationForSlack($notification)
    {
        return config('sami.slack_notification_webhook');
    }

    /**
     * Get all of the branches for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function subscriptionPackage()
    {
        return $this->hasOne(Subscription::class, 'user_id', 'id')->where('status', config('constant.SUBSCRIPTION_STATUS.ACTIVE'));
    }

    public function address()
    {
        return $this->morphOne(Address::class, 'addressable');
    }

    public function addresses()
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1)->where('is_banned', 0);
    }

    public function booking()
    {
        return $this->hasMany(Booking::class, 'user_id', 'id');
    }

    public function createdBookings()
    {
        return $this->hasMany(Booking::class, 'created_by', 'id');
    }

    public function buyNowBookings()
    {
        return $this->createdBookings()
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->where('payment_type', 'payment')
            ->unpaid()
            ->whereNull('deleted_by');
    }

    public function cartBookings()
    {
        return $this->createdBookings()
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->where('payment_type', 'cart')
            ->unpaid()
            ->whereNull('deleted_by');
    }

    public function cartProducts()
    {
        return $this->hasMany(Cart::class, 'user_id', 'id');
    }

    public function pendingGiftCards()
    {
        return $this->hasMany(GiftCard::class, 'user_id', 'id')
            ->where('payment_status', 0)
            ->where('gift_status', GiftCard::STATUS_PENDING_PAYMENT); // a cancelled unpaid gift leaves the cart
    }

    public function scopeCalenderResource($query)
    {
        $query->where('show_in_calender', 1);
    }

    protected function getProfileImageAttribute()
    {
        $media = $this->getFirstMediaUrl('profile_image');

        return isset($media) && !empty($media) ? $media : asset(config('app.avatar_base_path') . 'avatar.png');
    }

    // Employee Relations
    public function commission_earning()
    {
        return $this->hasMany(CommissionEarning::class, 'employee_id');
    }

    public function branches()
    {
        return $this->hasMany(BranchEmployee::class, 'employee_id');
    }

    public function branch()
    {
        return $this->hasOne(BranchEmployee::class, 'employee_id')->with('getBranch');
    }

    public function shift()
    {
        return $this->hasOne(BranchEmployee::class, 'employee_id')->with('getShift');
    }

    public function mainBranch()
    {
        return $this->hasManyThrough(Branch::class, BranchEmployee::class, 'employee_id', 'id', 'id', 'branch_id');
    }

    public function mainShift()
    {
        return $this->hasManyThrough(Shift::class, BranchEmployee::class, 'employee_id', 'id', 'id', 'shift_id');
    }

    public function services()
    {
        return $this->hasMany(ServiceEmployee::class, 'employee_id');
    }

    public function employeeBooking()
    {
        return $this->hasMany(BookingService::class, 'employee_id');
    }
    public function bookingPackages()
    {
        return $this->hasMany(BookingPackages::class, 'employee_id');
    }
    public function employeeEarnings()
    {
        return $this->hasMany(EmployeeEarning::class, 'employee_id');
    }

    public function commissions()
    {
        return $this->hasMany(EmployeeCommission::class, 'employee_id')->with('mainCommission');
    }

    public function wishlist()
    {
        return $this->belongsToMany(Product::class, 'wishlist', 'user_id', 'product_id');
    }

    public function scopeEmployee($query)
    {
        $query->role('employee');
    }

    public function scopeBranch($query)
    {
        $branch_id = request()->selected_session_branch_id;
        if (isset($branch_id)) {
            $query->join('branch_employee', 'users.id', '=', 'branch_employee.employee_id')
                ->where('branch_employee.branch_id', $branch_id);
        }
    }

    public function scopeVarified($query)
    {
        return $query->whereNotNull('email_verified_at');
    }

    public function scopeBookingEmployeesList($query)
    {
        return $query->select('users.*')
            ->active()
            ->varified()
            ->calenderResource()->employee()->branch()->orderBy('id', 'ASC');
    }

    public function rating()
    {
        return $this->hasMany(EmployeeRating::class, 'employee_id', 'id')->orderBy('updated_at', 'desc');
    }

    public function bookings()
    {
        return $this->hasManyThrough(Booking::class, BookingService::class, 'booking_id', 'id', 'id', 'employee_id');
    }



    public function profile()
    {
        return $this->hasOne(UserProfile::class);
    }

    // Report Query
    public static function staffReport()
    {
        return self::role(['manager', 'employee'])->select('users.id', 'users.first_name', 'users.last_name', 'users.email', 'users.mobile', 'users.updated_at')
            ->withCount('employeeBooking')
            ->withSum('employeeBooking', 'service_price')
            ->withSum('commission_earning', 'commission_amount');
    }

    public function scopeWithTotalUnpaidServiceAmount($query)
    {
        return $query->leftJoin('commission_earnings', 'users.id', '=', 'commission_earnings.employee_id')
            ->leftJoin('booking_services', 'booking_services.booking_id', '=', 'commission_earnings.commissionable_id')
            ->leftJoin('booking_packages', 'booking_packages.booking_id', '=', 'commission_earnings.commissionable_id')
            ->where('commission_earnings.commission_status', 'unpaid')
            ->selectRaw('users.id as user_id,
                         COALESCE(SUM(booking_services.service_price), 0) + COALESCE(SUM(booking_packages.package_price), 0) as total_service_amount')
            ->groupBy('users.id');
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    public function affiliate()
    {
        return $this->hasOne(Affiliate::class);
    }

    public function referredByAffiliate()
    {
        return $this->belongsTo(Affiliate::class, 'referred_by_affiliate_id');
    }

    public function referralConversions()
    {
        return $this->hasMany(Conversion::class, 'referred_user_id');
    }

    public function isAffiliate()
    {
        return $this->affiliate && $this->affiliate->status === 'active';
    }

    public function userMembership()
    {
        return $this->hasOne(UserMembership::class, 'user_id', 'id');
    }
}