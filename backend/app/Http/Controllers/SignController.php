<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Services\TaqnyatSmsService;
use Modules\Booking\Models\Booking;
use Modules\Booking\Models\BookingService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class SignController extends Controller
{
    public function index()
    {
        return view("auth.register");
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string|max:191|unique:users,username',
            'mobile'   => 'required|string|max:20|unique:users,mobile',
        ]);
        $smsService = new TaqnyatSmsService();
        $phone = $smsService->validatePhoneNumber($validated["mobile"]);

        if (!$phone) {
            return redirect()->back()->with('error', __('messagess.invalid_phone'));
        }

        Session::put('mobile', $phone);
        Session::put('username', $validated['username']);

        $smsCountKey = 'sms_count_' . $phone . '_' . date('Y-m-d');
        $sentCount = Cache::get($smsCountKey, 0);

        if ($sentCount >= 3) {
            return redirect()->back()->with('error',  __('messagess.sms_daily_limit_reached'));
        }

        $otp = 1111;

        Cache::put('otp_' . $phone, $otp, now()->addMinutes(5));

        $message = __('messagess.otp_sms', ['code' => $otp]);
        $this->logOtp('register', $phone, $otp);

        if (! $this->shouldUseLocalOtp()) {
            try {
                $smsService->sendSms($phone, $message);
            } catch (\Exception $e) {
                return redirect()->back()->with('error', __('messagess.error_sending_sms'));
            }
        }

        Cache::put($smsCountKey, $sentCount + 1, now()->endOfDay());
        return redirect()
            ->route('verify.mobile', ['mobile' => $phone])
            ->with('local_otp', $this->shouldUseLocalOtp() ? $otp : null);
    }

    public function login()
    {
        return view("auth.login");
    }

    public function verify(Request $request)
    {
        $request->validate([
            'mobile' => 'required|string|max:20',
        ]);

        $smsService = new TaqnyatSmsService();
        $phone = $smsService->validatePhoneNumber($request->mobile);

        if (!$phone) {
            return back()->with('error', __('messagess.invalid_phone'));
        }

        $user = \App\Models\User::where('mobile', $phone)->first();

        if (!$user) {
            return back()->with('error', __('messages.invalid_credentials'));
        }

        $otp = '1111';

        Cache::put('login_otp_' . $phone, $otp, now()->addMinutes(5));

        Session::put('login_mobile', $phone);

        $message = __('messagess.otp_sms', ['code' => $otp]);
        $this->logOtp('login', $phone, $otp);

        if (! $this->shouldUseLocalOtp()) {
            try {
                $sent = $smsService->sendSms($phone, $message);
                if ($sent === false) {
                    Cache::forget('login_otp_' . $phone);
                    Session::forget('login_mobile');

                    return back()->with('error', __('messagess.error_sending_sms'));
                }
            } catch (\Exception $e) {
                Cache::forget('login_otp_' . $phone);
                Session::forget('login_mobile');

                return back()->with('error', __('messagess.error_sending_sms'));
            }
        }

        return redirect()
            ->route('login.verify.form')
            ->with('success', __('messages.otp_sent'))
            ->with('local_otp', $this->shouldUseLocalOtp() ? $otp : null);
    }

    public function showVerifyForm()
    {
        if (auth()->check()) {
            return redirect('/');
        }
        return view('verify.OTPlogin');
    }

    public function showMobileVerifyForm(Request $request)
    {
        if (auth()->check()) {
            return redirect('/');
        }

        $mobile = $request->mobile;

        return view('verify.mobile', compact('mobile'));
    }

    public function verifyOTP(Request $request)
    {
        $request->validate([
            'otp1' => 'required|digits:1',
            'otp2' => 'required|digits:1',
            'otp3' => 'required|digits:1',
            'otp4' => 'required|digits:1',
        ]);
        $otpInput = $request->otp1 . $request->otp2 . $request->otp3 . $request->otp4;
        $phone = Session::get('login_mobile');

        if (!$phone) {
            return redirect()->route('login')->with('error', __('messages.session_expired'));
        }

        $cachedOtp = Cache::get('login_otp_' . $phone);

        if (!$cachedOtp || $cachedOtp != $otpInput) {
            return back()->withErrors([
                'otp' => __('messages.invalid_otp'),
            ])->withInput();
        }

        Cache::forget('login_otp_' . $phone);

        $user = \App\Models\User::where('mobile', $phone)->first();

        Auth::login($user);
        $request->session()->regenerate();
        if ($request->session()->has('temp_gift_booking')) {
            return redirect()->route('gift.create');
        }
        if ($request->session()->has('temp_booking')) {
            $temp = $request->session()->get('temp_booking');
            $data = $temp['data'];
            $btn_value = $temp['btn_value'];
            $this->complateTempBookings($data, $btn_value);
            session()->forget('temp_booking');
            if ($btn_value == 'cart') {
                return redirect()->to('/cart')->with('success', 'تم تحويل الحجز بنجاح');
            } elseif ($btn_value == 'payment') {
                return redirect()->to('/payment?is_buy_now=1')->with('success', 'تم تحويل الحجز بنجاح');
            }
        }

        if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('employee')) {
            return redirect()->to('/app')->with('success', __('messages.login_successfully'));
        } else {
            return redirect()->to('/')->with('success', __('messages.login_successfully'));
        }
    }

    public function verifyMobileOtp(Request $request)
    {
        $request->validate([
            'otp1' => 'required|digits:1',
            'otp2' => 'required|digits:1',
            'otp3' => 'required|digits:1',
            'otp4' => 'required|digits:1',
        ]);

        $otpInput = $request->otp1 . $request->otp2 . $request->otp3 . $request->otp4;
        $mobile = Session::get('mobile');

        if (! $mobile) {
            return redirect()->route('login')->withErrors(['otp' => 'Session expired']);
        }

        $cacheOtp = Cache::get('otp_' . $mobile);

        if (! $cacheOtp) {
            return back()->withErrors([
                'otp1' => 'Verification code expired, please resend the code.',
            ]);
        }

        $username = Session::get('username');

        if ($otpInput == $cacheOtp) {
            Cache::forget('otp_' . $mobile);
            Session::forget(['mobile', 'username']);

            $user = User::where('mobile', $mobile)->first();

            if (! $user) {
                $user = User::create([
                    'username' => $username,
                    'first_name' => $username,
                    'last_name' => $username,
                    'mobile' => $mobile,
                    'email_verified_at' => now(),
                ]);
            } else {
                $user->update([
                    'email_verified_at' => now(),
                ]);
            }

            auth()->login($user);

            return redirect()->route('frontend.home')->with('success', __('Mobile verified successfully!'));
        }

        return back()->withErrors(['otp1' => 'The OTP is incorrect.'])->withInput();
    }

    public function resendMobileOtp(Request $request)
    {
        $mobile = $request->input('mobile');

        if (! $mobile) {
            return redirect()->route('login')->withErrors(['otp' => __('messagess.session_expired')]);
        }

        $cacheKey = "otp_resend_count_{$mobile}";
        $resendCount = Cache::get($cacheKey, 0);

        if ($resendCount >= 3) {
            return back()->withErrors([
                'otp' => __('messagess.otp_resend_limit'),
            ]);
        }

        Cache::put($cacheKey, $resendCount + 1, now()->addMinutes(5));

        $otp = 1111;
        Cache::put('otp_' . $mobile, $otp, now()->addMinutes(5));

        $message = __('messagess.otp_sms', ['code' => $otp]);
        $this->logOtp('register_resend', $mobile, $otp);

        if (! $this->shouldUseLocalOtp()) {
            try {
                (new TaqnyatSmsService())->sendSms($mobile, $message);
            } catch (\Throwable $e) {
                return back()->withErrors([
                    'otp' => __('messagess.sms_failed'),
                ]);
            }
        }

        return back()
            ->with('success', __('messagess.otp_resent'))
            ->with('local_otp', $this->shouldUseLocalOtp() ? $otp : null);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/signin')->with('success', 'تم تسجيل الخروج بنجاح');
    }

    public function apiStore(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'mobile' => 'required|string|unique:users,mobile',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'mobile' => $validated['mobile'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'gender' => 'female',
            'status' => 1,
        ]);

        return response()->json([
            'message' => __('User registered successfully'),
            'user' => $user
        ], 201);
    }
    private function complateTempBookings($data, $btn_value)
    {
        $user = auth()->user();
        if (!empty($data['services'])) {
            foreach ($data['services'] as $service) {
                if (!empty($service['subServices'])) {
                    foreach ($service['subServices'] as $sub) {
                        $subId = $sub['id'];
                        $date = $sub['date'];
                        $time = $sub['time'];
                        $duration = $sub['duration'];
                        $price = $sub['price'];
                        $staffId = $sub['staffId'];
                        $startDateTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $time);

                        $booking = new Booking();
                        $booking->note = 'العميل: ' . $user->first_name . '، الجوال: ' .  $user->mobile . '، الخدمة: ' . $subId;
                        $booking->start_date_time = $startDateTime;
                        $booking->user_id         = $user->id;
                        $booking->branch_id       = $data['branch'] ?? 1;
                        $booking->created_by      = $user->id;
                        $booking->status          = 'pending';
                        $booking->payment_type       =  $btn_value;
                        $booking->save();

                        //  الحجز التاني
                        $bookingService = new BookingService();
                        $bookingService->booking_id       = $booking->id;
                        $bookingService->service_id       = $subId;
                        $bookingService->employee_id      = $staffId;
                        $bookingService->start_date_time  = $startDateTime;
                        $bookingService->service_price    = \Modules\Service\Models\Service::find($subId)->default_price ?? 0;
                        $bookingService->duration_min     = $duration;
                        $bookingService->sequance         = 1;
                        $bookingService->created_by      = $user->id;
                        $bookingService->save();

                        $loyalty = \App\Models\LoyaltyPoint::firstOrCreate(
                            ['user_id' => $user->id],
                            ['points' => 0]
                        );
                    }
                }
            }
        }
    }

    private function shouldUseLocalOtp(): bool
    {
        return app()->environment('local');
    }

    private function logOtp(string $type, string $phone, string $otp): void
    {
        Log::channel('local_otp')->info('OTP generated', [
            'environment' => app()->environment(),
            'type' => $type,
            'phone' => $phone,
            'otp' => $otp,
        ]);
    }
}
