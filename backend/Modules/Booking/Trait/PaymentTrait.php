<?php

namespace Modules\Booking\Trait;

use App\Models\Setting;
use App\Models\User;
use Modules\Booking\Models\Booking;
use Modules\Booking\Models\BookingProduct;
use Modules\Booking\Models\BookingService;
use Modules\Booking\Models\BookingTransaction;
use Modules\Package\Models\BookingPackages;
use Modules\Package\Models\PackageService;
use Modules\Package\Models\UserPackage;
use Modules\Booking\Transformers\BookingResource;
use Modules\Commission\Models\CommissionEarning;
use Modules\Currency\Models\Currency;
use Modules\Package\Models\UserPackageServices;
use Modules\Promotion\Models\Coupon;
use Modules\Promotion\Models\UserCouponRedeem;
use Modules\Package\Models\BookingPackageService;
use Razorpay\Api\Api;
use Modules\Promotion\Models\Promotion;
use Carbon\Carbon;
trait PaymentTrait
{
    public function getpayment_method($data, $booking_id)
    {
        return \Illuminate\Support\Facades\DB::transaction(fn () => $this->processBookingPayment($data, $booking_id));
    }

    private function processBookingPayment($data, $booking_id)
    {

        $data['booking_id'] = $booking_id;
        $data['transaction_type'] = $data['payment_method'];
        $data['tip_amount'] = 0;
        $data['tax_percentage'] = $data['taxes'];
        $data['coupon_code'] = $data['coupon_code'];

        $booking_transaction_data = BookingTransaction::where('booking_id', $booking_id)->first();

        if (!empty($booking_transaction_data)) {

            $booking_transaction_data->update($data);

            $booking_transaction = BookingTransaction::where('booking_id', $booking_id)->first();
        } else {
            $booking_transaction = BookingTransaction::create($data);


            $earning_data = $this->commissionData($booking_transaction);
            $booking = Booking::where('id', $data['booking_id'])->first();

            if (isset($earning_data['commission_data'])) {
                $commission_data = $earning_data['commission_data'];
                $commission_data['employee_id'] = $earning_data['employee_id'];

                $booking->commission()->save(new CommissionEarning($earning_data['commission_data']));
            }

        }
        
        $couponDiscountamount = $this->couponDiscount(0, $data['coupon_code'] ?? '', $booking_id);
        $total_amount = $this->getTotalAmount($booking_id, $data['taxes'], $couponDiscountamount);
        $data['couponDiscountamount'] = $couponDiscountamount;
        $data['$total_amount'] = $total_amount;

        $currency = Currency::where('is_primary', 1)->first();

        switch ($data['transaction_type']) {
            case 'razorpay':

                $razorpay_key = $this->getrazorpaykey();

                $responseData = [
                    'status' => true,
                    'booking_transaction_id' => $booking_transaction['id'],
                    'total_amount' => $total_amount,
                    'currency' => $currency['currency_code'],
                    'payment_method' => $data['payment_method'],
                    'public_key' => isset($razorpay_key['razorpay_publickey']) ? $razorpay_key['razorpay_publickey'] : '',

                ];

                break;

            case 'stripe':

                $stripe_key = $this->getstripekey();

                $responseData = [

                    'booking_transaction_id' => $booking_transaction['id'],
                    'total_amount' => $total_amount,
                    'currency' => $currency['currency_code'],
                    'payment_method' => $data['payment_method'],
                    'public_key' => isset($stripe_key['stripe_secretkey']) ? $stripe_key['stripe_secretkey'] : '',

                ];

                break;

            default:

                $responseData = $this->getcashpayments($data, $booking_transaction['id']);

                break;
        }

        return $responseData;
    }

    //GET TOTAL AMOUNT

    public function getTotalAmount($booking_id, $tax = [], $pendingCouponDiscount = null)
    {
        $booking_services = BookingService::where('booking_id', $booking_id)->get();
        $total_service_amount = $booking_services->sum('service_price');
        $booking_products = BookingProduct::where('booking_id', $booking_id)->with('product')->get();
        $booking_packages = BookingPackages::where('booking_id', $booking_id)->get();
        $total_package_amount = $booking_packages->sum('package_price');
        $discounted_product_amount = getproductDiscountAmount($booking_products);
        $total_product_amount = BookingProduct::where('booking_id', $booking_id)->sum(\DB::raw('product_qty * product_price'));
        $product_amount = $total_product_amount - $discounted_product_amount;

        $coupon_discount = UserCouponRedeem::where('booking_id', $booking_id)->value('discount');
        $coupon_discount = max(0, (float) ($coupon_discount ?? $pendingCouponDiscount ?? 0));
        $fromServices = min($total_service_amount, $coupon_discount);
        $total_service_amount -= $fromServices;
        $total_package_amount = max(0, $total_package_amount - ($coupon_discount - $fromServices));

        $tax_amount = 0;
        if ($tax != '') {
            foreach ($tax as $tax_value) {
                if ($tax_value['type'] == 'percent') {
                    $tax_amount = $tax_amount + (($total_service_amount + $product_amount + $total_package_amount) * $tax_value['percent'] / 100);
                } elseif ($tax_value['type'] == 'fixed') {
                    $tax_amount = $tax_amount + $tax_value['tax_amount'];
                }
            }
        }
        $total_amount = $total_service_amount + $tax_amount + $product_amount + $total_package_amount;
        $total_amount = number_format($total_amount, 2, '.', '');

        return $total_amount;
    }

    public function couponDiscount($total_amount, $coupon_code, $booking_id)
    {
        if (!filled($coupon_code)) return 0;
        $quote = app(\App\Services\BookingCoupon::class)->quoteBooking($coupon_code, (int) $booking_id);
        if (isset($quote['error'])) throw \Illuminate\Validation\ValidationException::withMessages(['coupon_code' => $quote['error']]);
        return $quote['discount'];
    }

    public function couponExpired($coupon_code, $couponDiscountamount, $booking_id)
    {
        if (!filled($coupon_code)) return;
        \Illuminate\Support\Facades\DB::transaction(function () use ($coupon_code, $booking_id) {
            $coupon = Coupon::where('coupon_code', $coupon_code)->lockForUpdate()->firstOrFail();
            if (UserCouponRedeem::where('booking_id', $booking_id)->where('coupon_id', $coupon->id)->exists()) return;
            if (UserCouponRedeem::where('booking_id', $booking_id)->exists()) throw \Illuminate\Validation\ValidationException::withMessages(['coupon_code' => 'يوجد كوبون مستخدم لهذا الحجز بالفعل.']);
            $pending = \App\Models\PaymentAttempt::where('coupon_code', $coupon_code)->whereIn('status', ['initiated','redirected'])->count();
            if ($coupon->use_limit <= $pending) throw \Illuminate\Validation\ValidationException::withMessages(['coupon_code' => 'استخدامات الكوبون المتاحة محجوزة لعمليات دفع قيد التنفيذ.']);
            $quote = app(\App\Services\BookingCoupon::class)->quoteBooking($coupon_code, (int) $booking_id);
            if (isset($quote['error'])) throw \Illuminate\Validation\ValidationException::withMessages(['coupon_code' => $quote['error']]);
            $coupon->decrement('use_limit');
            UserCouponRedeem::create(['coupon_code' => $coupon_code, 'coupon_id' => $coupon->id, 'booking_id' => $booking_id,
                'user_id' => Booking::findOrFail($booking_id)->user_id, 'discount' => $quote['discount']]);
        });
    }
    function storeApiUserPackage($booking_id)
    {
        $packages = BookingPackages::where('booking_id', $booking_id)->get();
        foreach ($packages as $key => $value) {
            $existingUserPackage = UserPackage::where('user_id', $value['user_id'])->where('package_id', $value['package_id'])->exists();
            ;
            if (!$existingUserPackage) {

            
                $userPackage = UserPackage::create([
                    'sequance' => $key,
                    'booking_id' => $booking_id,
                    'package_id' => $value['package_id'],
                    'purchase_date' => Carbon::now(),
                    'employee_id' => $value['employee_id'],
                    'package_price' => $value['package_price'],
                    'user_id' => $value['user_id'],
                ]);

    
                $packageService = PackageService::where('package_id', $value['package_id'])->get();
                $UserPackageServices = userPackageServices::where('user_id', $value['user_id'])->where('package_id', $value['package_id'])->get();
                foreach ($UserPackageServices as $service) {
                    $service->user_package_id = $userPackage->id;
                    $service->save();
                }

    

                $remainingServices = UserPackageServices::where('user_package_id', $userPackage->id)->count();

                
                // if ($remainingServices == 0 && $userPackage) {
                //     $userPackage->delete();
                // }
            }
        }
    }



    function storeUserPackage($booking_id)
    {
        $packages = BookingPackages::where('booking_id', $booking_id)->get();
        foreach ($packages as $key => $value) {
            $existingUserPackage = UserPackage::where('user_id', $value['user_id'])->where('package_id', $value['package_id'])->exists();
            if (!$existingUserPackage) {
                $userPackage = UserPackage::create([
                    'sequance' => $key,
                    'booking_id' => $booking_id,
                    'package_id' => $value['package_id'],
                    'purchase_date' => Carbon::now(),
                    'employee_id' => $value['employee_id'],
                    'package_price' => $value['package_price'],
                    'user_id' => $value['user_id'],
                ]);

                $packageServices = PackageService::where('package_id', $value['package_id'])->get();
                BookingPackageService::where('booking_package_id', $value['id'])->delete();
                foreach ($packageServices as $service) {
                    $userPackageService = UserPackageServices::create([
                        'package_service_id' => $service->id,
                        'package_id' => $service->package_id,
                        'user_id' => $value['user_id'],
                        'qty' => $service->qty - 1,
                        'service_name' => $service->service_name,
                        'user_package_id' => $userPackage->id,
                    ]);

                    $bookingPackageService = BookingPackageService::Create([
                        'booking_id' => $booking_id,
                        'package_id' => $userPackageService->package_id,
                        'package_service_id' => $userPackageService->package_service_id,
                        'user_id' => $userPackageService->user_id,
                        'service_name' => $userPackageService->service_name,
                        'booking_package_id' => $value['id'],
                        'qty' => $userPackageService->qty,
                        'service_id' => $service['service_id'],
                    ]);
                    if ($userPackageService) {
                        if ($userPackageService->qty == 0) {
                            $userPackageService->delete();
                        }
                    }

                }
                // $remainingServices = UserPackageServices::where('user_package_id', $userPackage->id)->count();
                // if ($remainingServices == 0 && $userPackage) {
                //     $userPackage->delete();
                // }
            } else {
                if ($value['is_reclaim'] === 1) {
                    BookingPackageService::where('booking_package_id', $value['id'])->delete();
                    $userPackage = UserPackage::where('user_id', $value['user_id'])->where('package_id', $value['package_id'])->first();
                    $userPackageService = UserPackageServices::where('user_package_id', $userPackage->id)->get();
                    foreach ($userPackageService as $service) {

                        if ($service) {
                            if ($service->qty >= 1) {

                                $bookingPackageService = BookingPackageService::Create([
                                    'booking_id' => $booking_id,
                                    'package_id' => $service->package_id,
                                    'package_service_id' => $service->package_service_id,
                                    'user_id' => $service->user_id,
                                    'service_name' => $service->service_name,
                                    'booking_package_id' => $value['id'],
                                    'qty' => $service->qty - 1,
                                    'service_id' => $service->packageService->service_id,
                                ]);
                                $service->qty -= 1;
                                $service->save();
                            }
                            if ($service->qty == 0) {
                                $service->delete();
                            }
                        }
                    }
                    $userPackage->type = 'reclaimed';
                    $userPackage->save();
                    $remainingServices = UserPackageServices::where('user_package_id', $userPackage->id)->count();

                    if ($remainingServices == 0) {

                        $userPackage->delete();
                    } else {
                        $userPackage->type = 'reclaimed';
                        $userPackage->save();
                    }

                }
            }
        }
    }
    //CASH PAYMNET DATA

    public function getcashpayments($data, $booking_transaction_id)
    {

        // $this->storeUserPackage($data['booking_id']);
        $this->couponExpired($data['coupon_code'], $data['couponDiscountamount'], $data['booking_id']);
        BookingTransaction::where('id', $booking_transaction_id)->get()->each->update(['external_transaction_id' => '', 'payment_status' => 1]);
        Booking::where('id', $data['booking_id'])->get()->each->update(['status' => 'completed']);
        $queryData = Booking::with('services', 'products', 'user')->findOrFail($data['booking_id']);
        $queryData['detail'] = $this->bookingDetail($queryData);
        $messageTemplate = 'New booking #[[booking_id]] has been booked.';
        $notify_message = str_replace('[[booking_id]]', $data['booking_id'], $messageTemplate);
        $this->sendNotificationOnBookingUpdate('complete_booking', $notify_message, $queryData);        
        $responseData = [
            'message' => __('booking.payment_successfull'),
            'payment_method' => $data['payment_method'],
            'data' => new BookingResource($queryData),
            'status' => true,
        ];

        return $responseData;
    }

    // RAZORPAY PAYMENT DATA

    public function getrazorpaypayments($data, $booking_transaction_id)
    {
        $rezorpay_key_data = $this->getrazorpaykey();

        $key_id = $rezorpay_key_data['razorpay_publickey'];
        $secret = $rezorpay_key_data['razorpay_secretkey'];

        try {
            $currency = $data['response']['currency'];

            $floatTotalAmount = floatval($data['response']['total_amount']);
            $totalamount = $floatTotalAmount * 100;
            $api = new Api($key_id, $secret);
            $api->payment->fetch($data['response']['razorpay_payment_id'])->capture(['amount' => $totalamount, 'currency' => $currency]);
            $data = BookingTransaction::where('id', $booking_transaction_id)->get()->each->update(['external_transaction_id' => $data['response']['razorpay_payment_id'], 'payment_status' => 1]);

            $booking_transaction = BookingTransaction::where('id', $booking_transaction_id)->first();
            Booking::where('id', $booking_transaction['booking_id'])->get()->each->update(['status' => 'completed']);

            $queryData = Booking::with('services', 'user')->findOrFail($booking_transaction['booking_id']);

            $responseData = [
                'message' => __('booking.payment_successfull'),
                'booking' => new BookingResource($queryData),
                'status' => true,
            ];
        } catch (\Exception $e) {
            $message = $e->getMessage();

            $responseData = [
                'message' => $message,
                'status' => false,
            ];
        }

        return $responseData;
    }

    //OPEN STRIPE CHECKOUT PAGE

    public function getstripepayments($data)
    {
        $baseURL = config('app.url');

        $stripe_key_data = $this->getstripekey();

        $stripe_secret = $stripe_key_data['stripe_secretkey'];

        try {
            $stripe = new \Stripe\StripeClient($stripe_secret);
            $checkout_session = $stripe->checkout->sessions->create([

                'success_url' => $baseURL . '/app/bookings/payment_success/' . $data['booking_transaction_id'],
                'payment_method_types' => ['card'],
                'billing_address_collection' => 'required',
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => $data['currency'],
                            'product_data' => [
                                'name' => 'T-shirt',
                            ],
                            'unit_amount' => $data['total_amount'] * 100,
                        ],
                        'quantity' => 1,
                    ],
                ],
                'mode' => 'payment',
            ]);
        } catch (\Exception $e) {
            $message = $e->getMessage();

            $checkout_session = [
                'message' => $message,
                'status' => false,
            ];
        }

        return $checkout_session;
    }

    //GET STRIPE PAYMENT DATA

    public function getstripePaymnetId($request_token)
    {
        $stripe_key_data = $this->getstripekey();

        $stripe_secret = $stripe_key_data['stripe_secretkey'];

        $stripe = new \Stripe\StripeClient($stripe_secret);
        $session_object = $stripe->checkout->sessions->retrieve($request_token, []);

        return $session_object;
    }

    //GET RAZORPAY KEY DATA FROM DB

    public function getrazorpaykey()
    {
        $rezorpay_key = Setting::where('type', 'razor_payment_method')->get();

        $rezorpay_key_data = [];

        if ($rezorpay_key != '') {
            foreach ($rezorpay_key as $rezorpay) {
                $rezorpay_key_data[$rezorpay->name] = $rezorpay->val;
            }
        }

        return $rezorpay_key_data;
    }

    //GET STRIPE KEY DATA

    public function getstripekey()
    {
        $stripe_key = Setting::where('type', 'str_payment_method')->get();

        $stripe_key_data = [];

        if ($stripe_key != '') {
            foreach ($stripe_key as $stripe) {
                $stripe_key_data[$stripe->name] = $stripe->val;
            }
        }

        return $stripe_key_data;
    }

    public function commissionData($data)
    {

        $booking_id = $data['booking_id'];

        $booking_service = BookingService::where('booking_id', $booking_id)->first();
        $booking_packages = BookingPackages::where('booking_id', $booking_id)->first();

        if ($booking_packages) {
            $employee_id = $booking_packages['employee_id'];
        } else {
            $employee_id = $booking_service['employee_id'];
        }



        $employee = User::role('employee')->where('id', $employee_id)->with('commissions')->first();

        $commission_amount = 0;
        $finalComissionAmount = 0;
        $commission_data = [];
        if (isset($employee->commissions)) {
            if ($booking_packages) {
                $booking_packages = BookingPackages::where('booking_id', $booking_id)->get();
                $total_service_amount = $booking_packages->sum('package_price');
            } else {
                $booking_services = BookingService::where('booking_id', $booking_id)->get();
                $total_service_amount = $booking_services->sum('service_price');
            }

            foreach ($employee->commissions as $key => $value) {
                if (isset($value->mainCommission)) {
                    $commission_type = $value->mainCommission->commission_type;

                    $commission_value = $value->mainCommission->commission_value;
                    if ($commission_type == 'fixed') {
                        $finalComissionAmount += $commission_value;
                    } else {
                        $commission_amount = $commission_value * $total_service_amount / 100;
                        $finalComissionAmount += $commission_amount;
                    }

                }
            }
        }

        if ($finalComissionAmount >= 0) {
            $commission_data = [
                'employee_id' => $employee_id,
                'commission_amount' => $finalComissionAmount,
                'commission_status' => 'unpaid',
                'payment_date' => null,
            ];
        }
        $data = [
            'commission_data' => $commission_data ?? null,
            'employee_id' => $employee_id,
        ];
        return $data;
    }

    protected function bookingDetail($booking)
    {
        $bookingTransaction = BookingTransaction::where('booking_id', $booking->id)->where('payment_status', 1)->first();
        $booking_product = BookingProduct::where('booking_id', $booking->id);

        $sumDiscountedPrice = 0;

        $coupon_discount = $booking->userCouponRedeem['discount'] ?? 0;


        if ($booking_product != '') {
            $sumDiscountedPrice = $booking_product->sum('discounted_price');
            $total_product_amount = $booking_product->sum(\DB::raw('product_qty * product_price'));
        }

        $serviceAmount = $booking->services->sum('service_price');
        $packageAmount = $booking->packages->sum('package_price');

        $tax_amount = 0;
        if (!empty($bookingTransaction)) {
            foreach ($bookingTransaction->tax_percentage as $key => $tax) {
                if ($tax['type'] == 'percent') {
                    $tax_amount += ((($serviceAmount - $coupon_discount) + $total_product_amount + $packageAmount) * $tax['percent']) / 100;
                } else {
                    $tax_amount += $tax['tax_amount'];
                }
            }
        }

        return [
            'serviceAmount' => $serviceAmount,
            'bookingTransaction' => $bookingTransaction,
            'sumDiscountedPrice' => $total_product_amount,
            'tax_amount' => $tax_amount,
            'coupon_discount' => $coupon_discount,
            'packageAmount' => $packageAmount,
            'grand_total' => ($tax_amount + $total_product_amount + $serviceAmount + $packageAmount) - $coupon_discount,
        ];
    }

    public function ExpireCoupon($data)
    {
        $this->couponExpired($data['coupon_code'] ?? '', 0, $data['booking_id']);
        return response()->json(['status' => true]);
    }
}
