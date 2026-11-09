<?php

namespace App\Http\Controllers\Auth\Trait\API;

use App\Http\Controllers\Auth\Trait\AuthTrait;
use App\Http\Controllers\Controller;
use App\Http\Resources\LoginResource;
use App\Http\Resources\RegisterResource;
use App\Models\User;
use App\Services\TaqnyatSmsService;
use App\Services\WheelSpinService;
use Illuminate\Support\Facades\Validator;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AuthController extends Controller
{
    use AuthTrait;
    /**
     * Login api
     *
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile' => ['required', 'string', 'max:20'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => __('Validation Error'),
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        $smsService = new TaqnyatSmsService();
        $phone = $smsService->validatePhoneNumber($validated['mobile']);

        if (! $phone) {
            return $this->sendError(__('messagess.invalid_phone'), [], 422);
        }

        $user = User::where('mobile', $phone)->first();

        if (! $user) {
            return $this->sendError(__('messages.register_before_login'));
        }

        if ($user->is_banned == 1 || $user->status == 0) {
            return $this->sendError(__('messages.login_error'));
        }

        $dailyKey = 'login_otp_count_' . $phone . '_' . date('Y-m-d');
        $dailyCount = (int) Cache::get($dailyKey, 0);

        if ($dailyCount >= 711) {
            return $this->sendError(__('messagess.sms_daily_limit_reached'), [], 429);
        }

        $otp = app()->environment('local') ? '1234' : (string) random_int(1000, 9999);

        Cache::put('login_otp_' . $phone, [
            'otp' => $otp,
            'user_id' => $user->id,
        ], now()->addMinutes(5));

        $message = __('messagess.otp_sms', ['code' => $otp]);
        $sent = app()->environment('local') ? true : $smsService->sendSms($phone, $message);

        if ($sent === false) {
            Cache::forget('login_otp_' . $phone);

            return $this->sendError(__('messagess.sms_failed'), [], 500);
        }

        Cache::put($dailyKey, $dailyCount + 1, now()->endOfDay());

        return $this->sendResponse([
            'mobile' => $phone,
            'expires_in' => 300,
        ], __('messages.otp_sent'));
    }

    public function resendLoginOtp(Request $request)
    {
        return $this->login($request);
    }

    public function verifyLoginOtp(Request $request)
    {
        $validated = $request->validate([
            'mobile' => ['required', 'string', 'max:20'],
            'otp' => ['required', 'digits:4'],
        ]);

        $smsService = new TaqnyatSmsService();
        $phone = $smsService->validatePhoneNumber($validated['mobile']);

        if (! $phone) {
            return $this->sendError(__('messagess.invalid_phone'), [], 422);
        }

        $cached = Cache::get('login_otp_'.$phone);

        if (! $cached || ! isset($cached['otp'], $cached['user_id'])) {
            return $this->sendError(__('messagess.session_expired'), [], 422);
        }

        $attemptKey = 'login_otp_attempts_'.$phone;
        $attempts = (int) Cache::get($attemptKey, 0);

        if ($attempts >= 5) {
            Cache::forget('login_otp_'.$phone);
            Cache::forget($attemptKey);

            return $this->sendError(__('auth.throttle', ['seconds' => 300, 'minutes' => 5]), [], 429);
        }

        if ((string) $cached['otp'] !== (string) $validated['otp']) {
            Cache::put($attemptKey, $attempts + 1, now()->addMinutes(5));

            return $this->sendError(__('messagess.invalid_otp'), [], 422);
        }

        $user = User::where('id', $cached['user_id'])->where('mobile', $phone)->first();

        if (! $user) {
            Cache::forget('login_otp_'.$phone);
            Cache::forget($attemptKey);

            return $this->sendError(__('messages.user_notfound'), [], 404);
        }

        if ($user->is_banned == 1 || $user->status == 0) {
            Cache::forget('login_otp_'.$phone);
            Cache::forget($attemptKey);

            return $this->sendError(__('messages.login_error'));
        }

        Cache::forget('login_otp_'.$phone);
        Cache::forget($attemptKey);

        $linkedSpin = app(WheelSpinService::class)->linkIpToUser($user, $request->ip());
        if ($linkedSpin) {
            $user->wheel_reward = [
                'won' => $linkedSpin->won,
                'type' => $linkedSpin->reward_type,
                'value' => (float) $linkedSpin->reward_value,
            ];
        }

        $user['api_token'] = $user->createToken(setting('app_name'))->plainTextToken;

        return $this->sendResponse(new LoginResource($user), __('messages.user_login'));
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => true,
            'message' => __('messages.user_logout')
        ]);
    }
    public function sendRegisterOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'referral_code' => ['nullable', 'string', 'max:20'],
            'username' => ['required', 'string', 'max:191', 'unique:users,username'],
            'mobile' => ['required', 'string', 'max:20', 'unique:users,mobile'],
        ]);

        if ($validator->fails()) {
            return $this->sendError(
                $validator->errors()->first(),
                $validator->errors(),
                422
            );
        }

        $validated = $validator->validated();
        $referrals = app(\App\Services\ReferralService::class);
        $referralCode = $referrals->normalize($validated['referral_code'] ?? null);
        $referrals->owner($referralCode);
        $smsService = new TaqnyatSmsService();
        $phone = $smsService->validatePhoneNumber($validated['mobile']);
    
        if (! $phone) {
            return $this->sendError(__('messagess.invalid_phone'), [], 422);
        }
    
        $dailyKey = 'register_otp_count_'.$phone.'_'.date('Y-m-d');
        $dailyCount = (int) Cache::get($dailyKey, 0);
    
        if ($dailyCount >= 3) {
            return $this->sendError(__('messagess.sms_daily_limit_reached'), [], 429);
        }

        $otp = app()->environment('local') ? '1234' : (string) random_int(1000, 9999);
    
        Cache::put('register_otp_'.$phone, [
            'username' => $validated['username'],
            'referral_code' => $referralCode,
            'otp' => $otp,
        ], now()->addMinutes(5));
        Cache::put($dailyKey, $dailyCount + 1, now()->endOfDay());
    
        $message = __('messagess.otp_sms', ['code' => $otp]);
        $sent = app()->environment('local') ? true : $smsService->sendSms($phone, $message);
    
        if ($sent === false) {
            return $this->sendError(__('messagess.sms_failed'), [], 500);
        }
    
        return $this->sendResponse([
            'mobile' => $phone,
            'expires_in' => 300,
        ], __('messages.otp_sent'));
    }

    public function resendRegisterOtp(Request $request)
    {
        return $this->sendRegisterOtp($request);
    }

    public function verifyRegisterOtp(Request $request)
    {
        $validated = $request->validate([
            'mobile' => ['required', 'string', 'max:20'],
            'otp' => ['required', 'digits:4'],
        ]);
    
        $smsService = new TaqnyatSmsService();
        $phone = $smsService->validatePhoneNumber($validated['mobile']);
    
        if (! $phone) {
            return $this->sendError(__('messagess.invalid_phone'), [], 422);
        }
    
        $cached = Cache::get('register_otp_'.$phone);
    
        if (! $cached || ! isset($cached['otp'], $cached['username'])) {
            return $this->sendError(__('messagess.session_expired'), [], 422);
        }
    
        $attemptKey = 'register_otp_attempts_'.$phone;
        $attempts = (int) Cache::get($attemptKey, 0);
    
        if ($attempts >= 5) {
            Cache::forget('register_otp_'.$phone);
            Cache::forget($attemptKey);
            return $this->sendError(__('auth.throttle', ['seconds'=>300, 'minutes'=>5]), [], 429);
        }
        if ((string) $cached['otp'] !== (string) $validated['otp']) {
            Cache::put($attemptKey, $attempts + 1, now()->addMinutes(5));
            return $this->sendError('Invalid OTP code.', [], 422);
        }
        $user = Cache::lock('register_account_'.$phone, 30)->block(5, function () use ($phone, $cached) {
            return \Illuminate\Support\Facades\DB::transaction(function () use ($phone, $cached) {
                if (User::withTrashed()->where('mobile', $phone)->exists() || User::withTrashed()->where('username', $cached['username'])->exists()) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['mobile'=>__('validation.unique', ['attribute'=>'mobile/username'])]);
                }
                $user = User::create([
                    'first_name'=>$cached['username'], 'last_name'=>'', 'username'=>$cached['username'],
                    'mobile'=>$phone, 'email_verified_at'=>now(), 'status'=>1,
                ]);
                $user->assignRole('user');
                app(\App\Services\ReferralService::class)->award($user, $cached['referral_code'] ?? null);
                return $user;
            });
        });
        Cache::forget('register_otp_'.$phone);
        Cache::forget($attemptKey);

        $linkedSpin = app(WheelSpinService::class)->linkIpToUser($user, $request->ip());
        if ($linkedSpin) {
            $user->wheel_reward = [
                'won' => $linkedSpin->won,
                'type' => $linkedSpin->reward_type,
                'value' => (float) $linkedSpin->reward_value,
            ];
        }

        $user['api_token'] = $user->createToken(setting('app_name'))->plainTextToken;

        return $this->sendResponse(new RegisterResource($user), __('messages.register_successfull'));
    }

    
    public function updateProfile(Request $request)
    {
        $user = \Auth::user();
        if ($request->has('id') && ! empty($request->id)) {
            $user = User::where('id', $request->id)->first();
        }
        if ($user == null) {
            return response()->json([
                'message' => __('messages.no_record'),
            ], 400);
        }
        $user->fill($request->all())->update();

        $user_data = User::find($user->id);
        if ($request->has('profile_image')) {
            $request->file('profile_image');

            storeMediaFile($user_data, $request->file('profile_image'), 'profile_image');
        }

        $user_data->save();

        $message = __('messages.profile_update');
        $user_data['user_role'] = $user->getRoleNames();
        $user_data['profile_image'] = $user->profile_image;
        unset($user_data['roles']);
        unset($user_data['media']);

        return response()->json([
            'status' => true,
            'data' => $user_data,
            'message' => $message,
        ], 200);
    }

    public function userDetails(Request $request)
    {
        $userID = $request->id;
        $user = User::find($userID);
        if (! $user) {
            return response()->json(['status' => false, 'message' => __('messages.user_notfound')], 404);
        }

        return response()->json(['status' => true, 'data' => $user, 'message' => __('messages.user_details_successfull')]);
    }

    public function profileDetails(Request $request)
    {
        $user = $request->user()->load(['profile', 'addresses']);

        $data = [
            'id' => $user->id,
            'username' => $user->username,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'full_name' => $user->full_name,
            'email' => $user->email,
            'mobile' => $user->mobile,
            'gender' => $user->gender,
            'date_of_birth' => $user->date_of_birth,
            'address' => $user->address,
            'city' => $user->city,
            'country' => $user->country,
            'login_type' => $user->login_type,
            'status' => $user->status,
            'is_banned' => $user->is_banned,
            'is_subscribe' => $user->is_subscribe,
            'email_verified_at' => $user->email_verified_at,
            'last_notification_seen' => $user->last_notification_seen,
            'profile_image' => $user->profile_image,
            'user_role' => $user->getRoleNames(),
            'profile' => $user->profile,
            'addresses' => $user->addresses,
        ];

        return $this->sendResponse($data, __('messages.user_details_successfull'));
    }

    public function updateMyProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'username' => ['sometimes', 'nullable', 'string', 'max:191', 'unique:users,username,' . $user->id],
            'first_name' => ['sometimes', 'nullable', 'string', 'max:191'],
            'last_name' => ['sometimes', 'nullable', 'string', 'max:191'],
            'email' => ['sometimes', 'nullable', 'email', 'max:191', 'unique:users,email,' . $user->id],
            'mobile' => ['sometimes', 'nullable', 'string', 'max:20', 'unique:users,mobile,' . $user->id],
            'gender' => ['sometimes', 'nullable', 'string', 'max:50'],
            'date_of_birth' => ['sometimes', 'nullable', 'date'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'nullable', 'string', 'max:255'],
            'country' => ['sometimes', 'nullable', 'string', 'max:255'],
            'profile_image' => ['sometimes', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'about_self' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'expert' => ['sometimes', 'nullable', 'string', 'max:255'],
            'facebook_link' => ['sometimes', 'nullable', 'url', 'max:255'],
            'instagram_link' => ['sometimes', 'nullable', 'url', 'max:255'],
            'twitter_link' => ['sometimes', 'nullable', 'url', 'max:255'],
            'dribbble_link' => ['sometimes', 'nullable', 'url', 'max:255'],
        ]);

        $userData = collect($validated)->only([
            'username',
            'first_name',
            'last_name',
            'email',
            'mobile',
            'gender',
            'date_of_birth',
            'address',
            'city',
            'country',
        ])->all();

        if (!empty($userData)) {
            $user->update($userData);
        }

        if ($request->hasFile('profile_image')) {
            storeMediaFile($user, $request->file('profile_image'), 'profile_image');
            $user->save();
        }

        $profileKeys = ['about_self', 'expert', 'facebook_link', 'instagram_link', 'twitter_link', 'dribbble_link'];
        if ($request->hasAny($profileKeys)) {
            $profileData = collect($validated)->only($profileKeys)->all();
            $user->profile()->updateOrCreate([], $profileData);
        }

        return $this->sendResponse(
            $user->fresh()->load(['profile', 'addresses']),
            __('messages.profile_update')
        );
    }

    public function deleteAccount(Request $request)
    {
        $user_id = \Auth::user()->id;
        $user = User::where('id', $user_id)->first();
        if ($user == null) {
            $message = __('messages.user_not_found');

            return response()->json([
                'status' => false,
                'message' => $message,
            ], 200);
        }
        $user->booking()->forceDelete();
        $user->forceDelete();
        $message = __('messages.delete_account');

        return response()->json([
            'status' => true,
            'message' => $message,
        ], 200);
    }
}
