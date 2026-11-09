<?php

namespace App\Services;

use App\Models\GiftCard;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Modules\Booking\Models\Booking;
use Modules\Booking\Models\BookingProduct;
use Modules\Booking\Models\BookingService;
use Modules\Package\Models\BookingPackageService;
use Modules\Package\Models\BookingPackages;
use Modules\Package\Models\UserPackage;
use Modules\Package\Models\UserPackageServices;
use Modules\Product\Models\Cart;

class CartExpirationService
{
    protected static bool $processedAllUsers = false;

    protected static array $processedUsers = [];

    public function clearExpired(?int $userId = null): array
    {
        if ($userId === null && self::$processedAllUsers) {
            return $this->emptyResult();
        }

        if ($userId !== null && in_array($userId, self::$processedUsers, true)) {
            return $this->emptyResult();
        }

        $duration = $this->getDurationInMinutes();

        if ($duration <= 0) {
            $this->markProcessed($userId);

            return $this->emptyResult();
        }

        $cutoff = now()->subMinutes($duration);

        $result = [
            'duration' => $duration,
            'expired_before' => $cutoff->toDateTimeString(),
            'bookings' => 0,
            'packages' => 0,
            'user_packages' => 0,
        ];

        DB::transaction(function () use ($userId, $cutoff, &$result) {
            $bookingIds = Booking::query()
                ->where('created_at', '<', $cutoff)
                ->whereNotIn('status', ['cancelled', 'completed'])
                ->unpaid()
                ->whereNull('deleted_by')
                ->when($userId !== null, fn ($query) => $query->where('created_by', $userId))
                ->pluck('id');

            if ($bookingIds->isNotEmpty()) {
                $userPackageIds = UserPackage::query()
                    ->whereIn('booking_id', $bookingIds)
                    ->pluck('id');

                if ($userPackageIds->isNotEmpty()) {
                    UserPackageServices::whereIn('user_package_id', $userPackageIds)->delete();
                    $result['user_packages'] = UserPackage::whereIn('id', $userPackageIds)->delete();
                }

                app(\App\Services\ServiceRepeatRewards::class)->release($bookingIds->all());
                BookingPackageService::whereIn('booking_id', $bookingIds)->delete();
                BookingPackages::whereIn('booking_id', $bookingIds)->delete();
                BookingService::whereIn('booking_id', $bookingIds)->delete();
                $result['bookings'] = Booking::whereIn('id', $bookingIds)->delete();
                $result['packages'] = $bookingIds->count();
            }
        });

        $this->markProcessed($userId);

        return $result;
    }

    public function getDurationInMinutes(): int
    {
        return max(0, (int) Setting::get('service_duration_minutes', 0));
    }

    protected function markProcessed(?int $userId): void
    {
        if ($userId === null) {
            self::$processedAllUsers = true;

            return;
        }

        self::$processedUsers[] = $userId;
    }

    protected function emptyResult(): array
    {
        return [
            'duration' => $this->getDurationInMinutes(),
            'expired_before' => null,
            'bookings' => 0,
            'products' => 0,
            'gifts' => 0,
            'packages' => 0,
            'user_packages' => 0,
        ];
    }
}
