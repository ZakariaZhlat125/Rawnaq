<?php

namespace Modules\Booking\Trait;

use App\Jobs\BulkNotification;
use Modules\Booking\Models\BookingProduct;
use Modules\Booking\Models\BookingService;
use Modules\BussinessHour\Models\BussinessHour;
use Modules\Package\Models\BookingPackageService;
use Modules\Promotion\Models\UserCouponRedeem;
use Modules\Package\Models\UserPackage;
use Modules\Package\Models\BookingPackages;
use Modules\Package\Models\UserPackageRedeem;
use Modules\Package\Models\UserPackageServices;
use Modules\Package\Models\PackageService;
use Modules\Service\Models\Service;

trait BookingTrait
{
    public function updateBookingService($data, $booking_id)
    {
        $serviceData = collect($data);
        $serviceId = $serviceData->pluck('service_id')->toArray();
        $defaultDurations = Service::query()
            ->whereIn('id', $serviceId)
            ->pluck('duration_min', 'id');
        $bookingService = BookingService::where('booking_id', $booking_id);
        if (count($serviceId) > 0) {
            $bookingService = $bookingService->whereNotIn('service_id', $serviceId);
        }
        $bookingService->delete();
        foreach ($serviceData as $key => $value) {
            $defaultDuration = max(1, (int) ($defaultDurations[(int) $value['service_id']] ?? 30));
            $resolvedDuration = max(1, (int) ($value['duration_min'] ?? 0));
            if ($resolvedDuration <= 0) {
                $resolvedDuration = $defaultDuration;
            }

            BookingService::updateOrCreate(['booking_id' => $booking_id, 'service_id' => $value['service_id']], [
                'sequance' => $key,
                'start_date_time' => $value['start_date_time'],
                'booking_id' => $booking_id,
                'service_id' => $value['service_id'],
                'employee_id' => $value['employee_id'],
                'service_price' => $value['service_price'] ?? 0,
                'duration_min' => $resolvedDuration,
            ]);
        }
    }

    public function pakageApply($booking_id, $packageApplied)
    {

        $packageApplied = collect($packageApplied);
        foreach ($packageApplied as $key => $packageValue) {
            $booking = BookingService::updateOrCreate(['booking_id' => $booking_id, 'service_id' => $packageValue['service_id']], [
                'service_price' => 0,
            ]);
            PackageService::where('package_id', $packageValue['package_id'])
                ->where('service_id', $packageValue['service_id'])
                ->decrement('qty', 1);
        }
    }

    public function updateBookingPackage($data, $booking_id)
    {

        $packageData = collect($data);
        $packageId = $packageData->pluck('package_id')->toArray();

        $bookingPackage = BookingPackages::where('booking_id', $booking_id);
        if (count($packageId) > 0) {
            $bookingPackage = $bookingPackage->whereNotIn('package_id', $packageId);
        }
        BookingPackageService::whereIn('booking_package_id', $bookingPackage->pluck('id'))->delete();

        $bookingPackage->delete(); 
        foreach ($packageData as $key => $value) {

            $bookingPackage = BookingPackages::updateOrCreate(['booking_id' => $booking_id, 'package_id' => $value['package_id'], 'is_reclaim' => $value['is_reclaim']], [
                'sequance' => $key,
                'booking_id' => $booking_id,
                'package_id' => $value['package_id'],
                'user_id' => $value['user_id'],
                'employee_id' => $value['employee_id'],
                'package_price' => $value['package_price'] ?? 0,
                'is_reclaim' => $value['is_reclaim'] ?? 0,
            ]);

            BookingPackageService::where('booking_package_id', $bookingPackage->id)->delete();

            $services = UserPackageServices::where('user_id', $value['user_id'])
                ->where('package_id', $value['package_id'])->with('packageService')
                ->get();
            if ($services->isEmpty()) {
                $services = PackageService::where('package_id', $value['package_id'])->with('services')->get();
            }

            foreach ($services as $service) {
                $bookingPackageService = BookingPackageService::updateOrCreate([
                    'booking_id' => $booking_id,
                    'package_id' => $value['package_id'],
                    'user_id' => $value['user_id'],
                    'package_service_id' => $service->package_service_id ?? $service->id,
                    'booking_package_id' => $bookingPackage->id,
                    'service_name' => $service->service_name,
                    'qty' => $service->qty - 1,
                    'service_id' => $service->service_id ?? $service->packageService->service_id,
                ]);
            }

        }


    }


    public function updateAPIBookingPackage($data, $booking_id, $employee_id, $user_id, $is_reclaim)
    {

        $packageData = collect($data);

        $packageId = $packageData->pluck('id')->toArray();
        $is_reclaim = filter_var($is_reclaim, FILTER_VALIDATE_BOOLEAN);

        $bookingPackage = BookingPackages::where('booking_id', $booking_id);
        if (count($packageId) > 0) {
            $bookingPackage = $bookingPackage->whereNotIn('package_id', $packageId);
        }
        $bookingPackage->delete();
        foreach ($packageData as $key => $value) {

            $bookingPackage = BookingPackages::updateOrCreate(['booking_id' => $booking_id, 'package_id' => $value['id'], 'employee_id' => $employee_id, 'user_id' => $user_id], [
                'sequance' => $key,
                'booking_id' => $booking_id,
                'package_id' => $value['id'],
                'user_id' => $user_id,
                'employee_id' => $employee_id,
                'package_price' => $value['package_price'] ?? 0,
                'is_reclaim' => $is_reclaim ?? 0,
            ]);

            // Check if UserPackageServices already exist
            $userPackageServiceExists = UserPackageServices::where('user_id', $user_id)
                ->where('package_id', $value['id'])
                ->exists();

            if (!$userPackageServiceExists) {
                $packageServices = PackageService::where('package_id', $value['id'])->get();
                foreach ($packageServices as $service) {
                    $userPackageService = UserPackageServices::create([
                        'package_service_id' => $service->id,
                        'package_id' => $service->package_id,
                        'user_id' => $user_id,
                        'qty' => $service->qty - 1,
                        'service_name' => $service->service_name,
                    ]);

                    $bookingPackageService = BookingPackageService::Create([
                        'booking_id' => $booking_id,
                        'package_id' => $value['id'],
                        'package_service_id' => $service->id,
                        'user_id' => $user_id,
                        'service_name' => $service->service_name,
                        'booking_package_id' => $bookingPackage->id,
                        'qty' => $service->qty - 1,
                        'service_id' => $service['service_id'],
                    ]);

                    if ($userPackageService) {
                        if ($userPackageService->qty == 0) {
                            $userPackageService->delete();
                        }
                    }
                }


            }
            if ($bookingPackage->is_reclaim == true) {
                $bookingPackage->package_price = 0;
                $bookingPackage->save();
            }

        }
    }


    public function updateUserPackageRedeem($data, $booking_id)
    {
        $packageData = collect($data);
        $bookingPackage = BookingPackages::where('booking_id', $booking_id)->first();

        foreach ($packageData as $service) {
            $UserPackages = UserPackage::find($service['user_package_id']);
            $userPackageService = UserPackageServices::where('user_package_id', $service['user_package_id'])
                ->whereHas('packageService', function ($query) use ($service) {
                    $query->where('service_id', $service['service_id']);
                })->first();
            if ($userPackageService) {
                if ($userPackageService->qty >= 1) {

                    if ($bookingPackage) {
                        $bookingPackageService = BookingPackageService::Create([
                            'booking_id' => $booking_id,
                            'package_id' => $userPackageService->package_id,
                            'package_service_id' => $userPackageService->package_service_id,
                            'user_id' => $userPackageService->user_id,
                            'service_name' => $userPackageService->service_name,
                            'booking_package_id' => $bookingPackage->id,
                            'qty' => $userPackageService->qty - $service['qty'],
                            'service_id' => $service['service_id'],
                        ]);
                    }

                    $userPackageService->qty = $userPackageService->qty - $service['qty'];
                    $userPackageService->save();
                }
                if ($userPackageService->qty == 0) {
                    $userPackageService->delete();
                }
            }
            $remainingServices = UserPackageServices::where('user_package_id', $service['user_package_id'])->count();
            if ($remainingServices == 0 && $UserPackages) {
                $UserPackages->delete();
            } else {
                $UserPackages->type = 'reclaimed';
                $UserPackages->save();
            }
        }
    }
    public function updateBookingProduct($data, $booking_id)
    {
        $serviceData = collect($data);
        $serviceId = $serviceData->pluck('product_variation_id')->toArray();
        $bookingProduct = BookingProduct::where('booking_id', $booking_id);
        if (count($serviceId) > 0) {
            $bookingProduct = $bookingProduct->whereNotIn('product_variation_id', $serviceId);
        }
        $bookingProduct->delete();
        foreach ($serviceData as $key => $value) {
            BookingProduct::updateOrCreate(['booking_id' => $booking_id, 'product_variation_id' => $value['product_variation_id'], 'employee_id' => $value['employee_id']], [
                'booking_id' => $booking_id,
                'product_id' => $value['product_id'],
                'product_variation_id' => $value['product_variation_id'],
                'employee_id' => $value['employee_id'],
                'product_qty' => $value['product_qty'] ?? 1,
                'product_price' => $value['product_price'] ?? 0,
                'discounted_price' => $value['discounted_price'] ?? 0,
                'discount_value' => $value['discount_value'] ?? 0,
                'discount_type' => $value['discount_type'] ?? null,
                'variation_name' => $value['variation_name'] ?? null,

            ]);
        }
    }

    public function getSlots($date, $day, $branch_id, $serviceDuration=0, $employee_id = null)
    {
        $slotDay = BussinessHour::where(['day' => strtolower($day), 'branch_id' => $branch_id])->first();

        $slots[] = [
            'value' => '',
            'label' => 'No Slot Available',
            'disabled' => true,
        ];

        if (isset($slotDay)) {
            $start_time = strtotime($slotDay->start_time);
            $end_time = strtotime($slotDay->end_time);
            $slot_duration = setting('slot_duration');

            $slot_parts = explode(':', $slot_duration);
            $slot_hours = intval($slot_parts[0]);
            $slot_minutes = intval($slot_parts[1]);

            $slot_duration_minutes = $slot_hours * 60 + $slot_minutes;
            if ($slot_duration_minutes <= 0) {
                $slot_duration_minutes = 15;
            }

            // 1. Resolve booked times for the employee
            $bookedMinutes = [];
            if ($employee_id) {
                // Fetch service bookings
                $serviceBookings = BookingService::where('employee_id', $employee_id)
                    ->whereDate('start_date_time', $date)
                    ->whereHas('booking', function ($query) {
                        $query->where('status', '!=', 'cancelled');
                    })
                    ->get(['start_date_time', 'duration_min']);

                foreach ($serviceBookings as $booking) {
                    $start_ts = strtotime($booking->start_date_time);
                    $duration = max(1, (int) ($booking->duration_min ?? 0));
                    for ($i = 0; $i < $duration; $i++) {
                        $bookedMinutes[date('H:i', $start_ts + ($i * 60))] = true;
                    }
                }

                // Fetch package bookings
                $packageBookings = BookingPackages::with('booking', 'services')
                    ->where('employee_id', $employee_id)
                    ->whereHas('booking', function ($query) use ($date) {
                        $query->whereDate('start_date_time', $date)
                            ->where('status', '!=', 'cancelled');
                    })
                    ->get();

                foreach ($packageBookings as $package) {
                    $start_ts = strtotime($package->booking->start_date_time);
                    $duration = (int) $package->services->sum('duration_min');
                    $duration = max(1, $duration);
                    for ($i = 0; $i < $duration; $i++) {
                        $bookedMinutes[date('H:i', $start_ts + ($i * 60))] = true;
                    }
                }
            }

            $current_time = $start_time;
            $slots = [];

            while ($current_time < $end_time) {
                // Skip slots that overlap with break hours
                $is_break_hour = false;
                foreach ($slotDay->breaks as $break) {
                    $start_break = strtotime($break['start_break']);
                    $end_break = strtotime($break['end_break']);
                    if ($current_time >= $start_break && $current_time < $end_break) {
                        $current_time = $end_break;
                        $is_break_hour = true;
                        break;
                    }
                }

                if ($is_break_hour) {
                    continue; // Skip this iteration and proceed to the next slot
                }
                
                $resolvedDuration = $serviceDuration > 0 ? $serviceDuration : $slot_duration_minutes;
                $slot_end_time = $current_time + ($resolvedDuration * 60);
             
                if ($slot_end_time > $end_time) {
                    break; 
                }

                // Verify breaks overlap with the candidate slot
                $overlaps_break = false;
                foreach ($slotDay->breaks as $break) {
                    $start_break = strtotime($break['start_break']);
                    $end_break = strtotime($break['end_break']);
                    if ($current_time < $end_break && $slot_end_time > $start_break) {
                        $overlaps_break = true;
                        break;
                    }
                }

                if ($overlaps_break) {
                    $current_time += $slot_duration_minutes * 60;
                    continue;
                }

                // Verify existing appointments overlap with the candidate slot
                $is_booked = false;
                if ($employee_id) {
                    for ($t = 0; $t < $resolvedDuration; $t++) {
                        $minute_str = date('H:i', $current_time + ($t * 60));
                        if (isset($bookedMinutes[$minute_str])) {
                            $is_booked = true;
                            break;
                        }
                    }
                }

                if (!$is_booked) {
                    $slots[] = [
                        'value' => date('Y-m-d H:i:s', strtotime(date('Y-m-d', strtotime($date)) . ' ' . date('H:i:s', $current_time))),
                        'label' => date('h:i A', $current_time),
                        'disabled' => false,
                    ];
                }

                $current_time += $slot_duration_minutes * 60;
            }
        }

        return $slots;
    }

    public function hasSlotConflict(int $staffId, $startDateTime, int $duration, int $ignoreBookingId = 0): bool
    {
        $start = \Carbon\Carbon::parse($startDateTime);
        $requestedEnd = $start->copy()->addMinutes(max(1, $duration));

        // 1. Check Service Booking Conflicts
        $existingSlots = BookingService::where('employee_id', $staffId)
            ->whereDate('start_date_time', $start->toDateString())
            ->whereHas('booking', function ($query) use ($ignoreBookingId) {
                $query->whereIn('status', ['pending', 'confirmed', 'check_in']);
                if ($ignoreBookingId > 0) {
                    $query->where('id', '!=', $ignoreBookingId);
                }
            })
            ->get(['start_date_time', 'duration_min']);

        foreach ($existingSlots as $slot) {
            $existingStart = \Carbon\Carbon::parse($slot->start_date_time);
            $existingDuration = max(1, (int) ($slot->duration_min ?? 0));
            $existingEnd = $existingStart->copy()->addMinutes($existingDuration);

            if ($existingStart->lt($requestedEnd) && $existingEnd->gt($start)) {
                return true;
            }
        }

        // 2. Check Package Booking Conflicts
        $existingPackages = BookingPackages::with('booking', 'services')
            ->where('employee_id', $staffId)
            ->whereHas('booking', function ($query) use ($start, $ignoreBookingId) {
                $query->whereDate('start_date_time', $start->toDateString())
                    ->whereIn('status', ['pending', 'confirmed', 'check_in']);
                if ($ignoreBookingId > 0) {
                    $query->where('id', '!=', $ignoreBookingId);
                }
            })
            ->get();

        foreach ($existingPackages as $package) {
            $existingStart = \Carbon\Carbon::parse($package->booking->start_date_time);
            $existingDuration = (int) $package->services->sum('duration_min');
            $existingDuration = max(1, $existingDuration);
            $existingEnd = $existingStart->copy()->addMinutes($existingDuration);

            if ($existingStart->lt($requestedEnd) && $existingEnd->gt($start)) {
                return true;
            }
        }

        return false;
    }


    protected function sendNotificationOnBookingUpdate($type, $notify_message, $booking, $notify = true)
    {
        $data = mail_footer($type, $notify_message, $booking);

        $address = [
            'address_line_1' => $booking->branch->address->address_line_1,
            'address_line_2' => $booking->branch->address->address_line_2,
            'city' => $booking->branch->address->city_data->name ?? 'city',
            'state' => $booking->branch->address->state_data->name ?? 'state',
            'country' => $booking->branch->address->country_data->name ?? 'country',
            'postal_code' => $booking->branch->address->postal_code,
        ];

        $data['booking'] = [
            'id' => $booking->id,
            // 'logo' => config('setting_fields')['app']['elements'][8],
            'description' => $booking->note ?? 'Testing Note',
            'user_id' => $booking->user_id,
            'user_name' => optional($booking->user)->full_name ?? default_user_name(),
            'employee_id' => $booking->branch->employee->id,
            'employee_name' => $booking->services->first()->employee->full_name ?? 'Staff',
            'booking_date' => date('d/m/Y', strtotime($booking->start_date_time)),
            'booking_time' => date('h:i A', strtotime($booking->start_date_time)),
            'booking_duration' => $booking->services->sum('duration_min') ?? 0,
            'venue_address' => implode(', ', $address),
            'email' => $booking->user->email ?? null,
            'mobile' => $booking->user->mobile ?? null,
            'transaction_type' => optional($booking->payment)->transaction_type ?? 'default_value',
            'package_name' => implode(', ', $booking->packages->pluck('name')->toArray()),
            'service_name' => implode(', ', $booking->mainServices->pluck('name')->toArray()),
            'service_price' => isset($booking->services[0]['service_price']) ? $booking->services[0]['service_price'] : 0,
            'serviceAmount' => $booking->detail['serviceAmount'] ?? 0,
            // 'services_total_amount' =>$booking->services->sum('service_price'),
            'product_name' => implode(', ', $booking->products->pluck('product_name')->toArray()),
            'product_price' => isset($booking->products[0]['product_price']) ? $booking->products[0]['product_price'] : 0,
            'product_qty' => isset($booking->products[0]['product_qty']) ? $booking->products[0]['product_qty'] : 0,
            // 'product_amount' => isset($booking->products[0]['product_amount']) ? $booking->products[0]['product_amount'] : 0,
            'name' => implode(', ', $booking->packages->pluck('name')->toArray()),
            'branch_name' => optional($booking->branch)->name ?? default_user_name(),
            'branch_number' => optional($booking->branch)->contact_number ?? default_user_name(),
            'branch_email' => optional($booking->branch)->contact_email ?? default_user_name(),

            'package_price' => isset($booking->packages[0]['package_price']) ? $booking->packages[0]['package_price'] : 0,
            'tip_amount' => 0,
            'tax_amount' => $booking->detail['tax_amount'] ?? 0,
            'grand_total' => $booking->detail['grand_total'] ?? 0,
            'coupon_discount' => $booking->userCouponRedeem['discount'] ?? 0,
            'extra' => [
                'services' => $booking->services ? $booking->services->toArray() : [],
                'products' => $booking->products ? $booking->products->toArray() : [],
                'packages' => $booking->packages ? $booking->packages->toArray() : [],
                'detail' => $booking->detail ? $booking->detail : []
            ]
        ];

        if ($notify) {
            BulkNotification::dispatch($data);
        } else {
            return $data;
        }
    }
}
