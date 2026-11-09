<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyPoint;
use App\Models\LoyaltyPointTransaction;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wheel;
use App\Services\TaqnyatSmsService;
use App\Services\WheelCooldownService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Wallet\Models\Wallet;
use Modules\Wallet\Models\WalletHistory;

class WheelController extends Controller
{
    public function __construct(private readonly WheelCooldownService $wheelCooldownService)
    {
    }

    public function index(){

        $prizes = Wheel::all();
        $wheelDisplayIntervalDays = (int) Setting::get('wheel_display_interval_days', 1);
        $wheelEnabled = (bool) Setting::get('wheel_enabled', true);

        return view('backend.wheel.index_datatable', compact('prizes', 'wheelDisplayIntervalDays', 'wheelEnabled'));

    }

    public function store(Request $request){
        if ($request->input('form_type') === 'toggle') {
            $request->validate([
                'wheel_enabled' => 'required|boolean',
            ]);

            $wheelEnabled = (bool) $request->boolean('wheel_enabled');
            Setting::set('wheel_enabled', $wheelEnabled ? 1 : 0, 'boolean');

            return redirect()->back()->with('success', $wheelEnabled
                ? __('wheel.wheel_enabled_successfully')
                : __('wheel.wheel_disabled_successfully'));
        }

        if ($request->input('form_type') === 'interval') {
            $request->validate([
                'wheel_display_interval_days' => 'required|integer|min:1|max:365',
            ]);

            Setting::set('wheel_display_interval_days', (int) $request->wheel_display_interval_days, 'integer');

            return redirect()->back()->with('success', __('wheel.interval_saved_successfully'));
        }

        $request->validate([
            'gift_type' => 'required|in:points,wallet_balance',
            'reward_value' => 'required|numeric|min:1',
        ], [
            'gift_type.required' => 'برجاء اختيار نوع الهدية.',
            'gift_type.in' => 'نوع الهدية يجب أن يكون نقاط ولاء أو رصيد محفظة.',
            'reward_value.required' => 'برجاء إدخال قيمة المكافأة.',
            'reward_value.numeric' => 'قيمة المكافأة يجب أن تكون رقمًا.',
            'reward_value.min' => 'قيمة المكافأة يجب ألا تقل عن 1.',
        ]);

        Wheel::create([
            'type' =>  $request->gift_type,
            'reward_value' => $request->reward_value
        ]);

        return redirect()->back()->with('success', __('wheel.The_award_has_been_added_successfully'));
    }

    public function spin_old(Request $request): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json([
                'status' => false,
                'message' => __('auth.unauthenticated'),
            ], 401);
        }

        if (! $this->isWheelEnabled()) {
            return response()->json([
                'status' => false,
                'message' => __('messagess.wheel_not_available_now'),
            ], 403);
        }

        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
        ]);

        $authUser = Auth::user();
        $name = trim((string) ($data['name'] ?? trim(($authUser->first_name ?? '').' '.($authUser->last_name ?? ''))));
        $phone = (string) ($data['phone'] ?? $authUser->mobile ?? '');
        $normalizedPhone = $this->normalizePhone($phone);

        if ($normalizedPhone === null) {
            return response()->json([
                'status' => false,
                'message' => __('messagess.invalid_phone'),
            ], 422);
        }

        $intervalDays = max((int) Setting::get('wheel_display_interval_days', 1), 1);
        $now = Carbon::now();

        return DB::transaction(function () use ($authUser, $name, $normalizedPhone, $intervalDays, $now) {
            $user = User::query()->lockForUpdate()->findOrFail($authUser->id);

            $lastSpinAt = $this->wheelCooldownService->getLastSpinAt(userId: $user->id);
            if ($lastSpinAt) {
                $nextAt = $lastSpinAt->copy()->addDays($intervalDays);
                if ($nextAt->isFuture()) {
                    return response()->json([
                        'status' => false,
                        'cooldown' => true,
                        'next_eligible_at' => $nextAt->toDateTimeString(),
                        'message' => __('messagess.wheel_try_again_on', ['date' => $nextAt->format('d-m-Y')]),
                    ], 429);
                }
            }

            $segment = random_int(0, 11);
            $winningSegments = [1, 3, 5, 7, 9, 11];
            $isWinningSegment = in_array($segment, $winningSegments, true);

            $availableRewards = Wheel::query()->where('reward_value', '>', 0)->select('id', 'reward_value', 'type')->get()
                ->map(fn (Wheel $item) => [
                    'id' => $item->id,
                    'value' => (float) $item->reward_value,
                    'type' => $item->type ?: 'points',
                ])
                ->filter(fn (array $reward) => $reward['value'] > 0)
                ->values();

                if($availableRewards->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => __('messagess.wheel_not_available_now'),
                ], 404);
            }

            $won = $isWinningSegment;
            $rewardValue = 0.0;
            $rewardType = null;
            $rewardId = null;
            $pointsBalance = (int) (optional(LoyaltyPoint::where('user_id', $user->id)->first())->points ?? 0);
            $walletBalance = (float) (optional(Wallet::where('user_id', $user->id)->first())->amount ?? 0);
            $balanceAfter = null;
            $message = __('messagess.wheel_not_winner');
            $note = __('messagess.wheel_history_no_reward');

            if ($won) {
                $winner = $availableRewards->random();
                $rewardId = $winner['id'];
                $rewardValue = $winner['value'];
                $rewardType = $winner['type'];

                if ($rewardType === 'wallet_balance') {
                    // Wheel winnings are promotional: bonus bucket, not withdrawable.
                    $entry = app(\App\Services\Wallet\WalletLedger::class)->credit($user->id, (float) $rewardValue, 'reward', [
                        'bucket' => 'bonus',
                        'activity_type' => 'wheel_win',
                        'message' => "Won {$rewardValue} from lucky wheel",
                        'source_type' => 'wheel',
                        'meta' => [
                            'segment' => $segment,
                            'reward_id' => $rewardId,
                            'reward_type' => $rewardType,
                            'reward_value' => $rewardValue,
                        ],
                    ]);

                    $walletBalance = (float) $entry->balance_after;
                    $message = __('messagess.wheel_congrats_wallet', [
                        'amount' => $this->formatRewardNumber($rewardValue),
                    ]);
                } else {
                    $rewardType = 'points';
                    $pointsToAdd = (int) round($rewardValue);

                    $loyalty = LoyaltyPoint::firstOrNew(['user_id' => $user->id]);
                    $loyalty->points = (int) ($loyalty->points ?? 0) + $pointsToAdd;
                    $loyalty->save();

                    $rewardValue = $pointsToAdd;
                    $pointsBalance = (int) $loyalty->points;
                    $balanceAfter = $pointsBalance;
                    $message = __('messagess.wheel_congrats', ['points' => $pointsToAdd]);
                }

                $note = $message;
            }

            LoyaltyPointTransaction::create([
                'user_id' => $user->id,
                'action' => 'add',
                'points' => $rewardType === 'points' ? (int) $rewardValue : 0,
                'balance_after' => $balanceAfter,
                'source' => 'wheel',
                'source_id' => $rewardId,
                'meta' => [
                    'mobile' => $normalizedPhone,
                    'name' => $name,
                    'spun_at' => $now->toDateTimeString(),
                    'rewarded_at' => $won ? $now->toDateTimeString() : null,
                    'segment' => $segment,
                    'won' => $won,
                    'reward_type' => $rewardType,
                    'reward_value' => $won ? $rewardValue : 0,
                    'wallet_balance_after' => $rewardType === 'wallet_balance' ? $walletBalance : null,
                    'note' => $note,
                ],
            ]);

            return response()->json([
                'status' => true,
                'won' => $won,
                'segment' => $segment,
                'reward_points' => $rewardType === 'points' ? $rewardValue : 0,
                'reward_value' => $won ? $rewardValue : 0,
                'reward_type' => $rewardType,
                'message' => $message,
                'balances' => [
                    'loyalty_points' => $pointsBalance,
                    'wallet_balance' => $walletBalance,
                ],
            ]);
        });
    }

    public function spin(Request $request): JsonResponse
    {
        if (! $this->isWheelEnabled()) {
            return response()->json([
                'status' => false,
                'message' => __('messagess.wheel_not_available_now'),
            ], 403);
        }

        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
        ]);

        $authUser = Auth::user();
        $name = trim((string) ($data['name'] ?? trim(($authUser?->first_name ?? '').' '.($authUser?->last_name ?? ''))));
        $phone = (string) ($data['phone'] ?? $authUser->mobile ?? '');
        $normalizedPhone = $this->normalizePhone($phone);

        if (! $authUser && $name === '') {
            return response()->json([
                'status' => false,
                'message' => app()->getLocale() === 'ar' ? 'يرجى إدخال الاسم' : 'Please enter your name.',
            ], 422);
        }

        if ($normalizedPhone === null) {
            return response()->json([
                'status' => false,
                'message' => __('messagess.invalid_phone'),
            ], 422);
        }

        if (! $authUser && User::query()->where('mobile', $normalizedPhone)->exists()) {
            return response()->json([
                'status' => false,
                'login_required' => true,
                'login_url' => route('signin'),
                'message' => app()->getLocale() === 'ar'
                    ? 'رقم الجوال مسجل مسبقًا، يرجى تسجيل الدخول لتجربة العجلة وحفظ الهدية في حسابك.'
                    : 'This mobile number is already registered. Please sign in to spin the wheel and save the gift to your account.',
            ], 409);
        }

        $intervalDays = max((int) Setting::get('wheel_display_interval_days', 1), 1);
        $now = Carbon::now();

        return DB::transaction(function () use ($request, $authUser, $name, $normalizedPhone, $intervalDays, $now) {
            $accountCreated = false;

            if ($authUser) {
                $user = User::query()->lockForUpdate()->findOrFail($authUser->id);
            } else {
                $existingUser = User::query()
                    ->where('mobile', $normalizedPhone)
                    ->lockForUpdate()
                    ->first();

                if ($existingUser) {
                    return response()->json([
                        'status' => false,
                        'login_required' => true,
                        'login_url' => route('signin'),
                        'message' => app()->getLocale() === 'ar'
                            ? 'رقم الجوال مسجل مسبقًا، يرجى تسجيل الدخول لتجربة العجلة وحفظ الهدية في حسابك.'
                            : 'This mobile number is already registered. Please sign in to spin the wheel and save the gift to your account.',
                    ], 409);
                }

                $user = User::create([
                    'first_name' => $name,
                    'last_name' => '',
                    'username' => $this->generateUniqueUsername($name, $normalizedPhone),
                    'mobile' => $normalizedPhone,
                    'email_verified_at' => now(),
                    'status' => 1,
                ]);

                $user->assignRole('user');
                Auth::login($user);
                $request->session()->regenerate();
                $accountCreated = true;
            }

            $lastSpinAt = $this->wheelCooldownService->getLastSpinAt(
                userId: $user->id,
                phone: $normalizedPhone
            );
            if ($lastSpinAt) {
                $nextAt = $lastSpinAt->copy()->addDays($intervalDays);
                if ($nextAt->isFuture()) {
                    return response()->json([
                        'status' => false,
                        'cooldown' => true,
                        'next_eligible_at' => $nextAt->toDateTimeString(),
                        'message' => __('messagess.wheel_try_again_on', ['date' => $nextAt->format('d-m-Y')]),
                    ], 429);
                }
            }

            $segment = random_int(0, 11);
            $winningSegments = [1, 3, 5, 7, 9, 11];
            $isWinningSegment = in_array($segment, $winningSegments, true);
//            $isWinningSegment=true;
            $availableRewards = Wheel::query()
                ->where('reward_value', '>', 0)
                ->select('id', 'reward_value', 'type')
                ->get()
                ->map(fn (Wheel $item) => [
                    'id'    => $item->id,
                    'value' => (float) $item->reward_value,
                    'type'  => $item->type ?: 'points',
                ])
                ->filter(fn (array $reward) => $reward['value'] > 0)
                ->values();

            if ($availableRewards->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => __('messagess.wheel_not_available_now'),
                ], 404);
            }

            $won          = $isWinningSegment;
            $rewardValue  = 0.0;
            $rewardType   = null;
            $rewardId     = null;
            $pointsBalance = (int) (optional(LoyaltyPoint::where('user_id', $user->id)->first())->points ?? 0);
            $walletBalance = (float) (optional(Wallet::where('user_id', $user->id)->first())->amount ?? 0);
            $balanceAfter = null;
            $message      = __('messagess.wheel_not_winner');
            $note         = __('messagess.wheel_history_no_reward');

            if ($won) {
                $winner      = $availableRewards->random();
                $rewardId    = $winner['id'];
                $rewardValue = $winner['value'];
                $rewardType  = $winner['type'];

                // ── WALLET BALANCE ───────────────────────────────────────
                if ($rewardType === 'wallet_balance') {
                    // Wheel winnings are promotional: bonus bucket, not withdrawable.
                    $entry = app(\App\Services\Wallet\WalletLedger::class)->credit($user->id, (float) $rewardValue, 'reward', [
                        'bucket' => 'bonus',
                        'activity_type' => 'wheel_win',
                        'message' => "Won {$rewardValue} from lucky wheel",
                        'source_type' => 'wheel',
                        'meta' => [
                            'segment'      => $segment,
                            'reward_id'    => $rewardId,
                            'reward_type'  => $rewardType,
                            'reward_value' => $rewardValue,
                        ],
                    ]);

                    $walletBalance = (float) $entry->balance_after;
                    $message = __('messagess.wheel_congrats_wallet', [
                        'amount' => $this->formatRewardNumber($rewardValue),
                    ]);

                    // ── DISCOUNT RATE ────────────────────────────────────────
                } elseif ($rewardType === 'discount_rate') {

                    $message = __('messagess.wheel_congrats_discount', [
                        'rate' => $this->formatRewardNumber($rewardValue),
                    ]);

                    // ── POINTS (default) ─────────────────────────────────────
                } else {
                    $rewardType   = 'points';
                    $pointsToAdd  = (int) round($rewardValue);

                    $loyalty = LoyaltyPoint::firstOrNew(['user_id' => $user->id]);
                    $loyalty->points = (int) ($loyalty->points ?? 0) + $pointsToAdd;
                    $loyalty->save();

                    $rewardValue   = $pointsToAdd;
                    $pointsBalance = (int) $loyalty->points;
                    $balanceAfter  = $pointsBalance;
                    $message = __('messagess.wheel_congrats', ['points' => $pointsToAdd]);
                }

                $note = $message;
            }

            LoyaltyPointTransaction::create([
                'user_id'       => $user->id,
                'action'        => 'add',
                'points'        => $rewardType === 'points' ? (int) $rewardValue : 0,
                'balance_after' => $balanceAfter,
                'source'        => 'wheel',
                'source_id'     => $rewardId,
                'meta'          => [
                    'mobile'               => $normalizedPhone,
                    'name'                 => $name,
                    'spun_at'              => $now->toDateTimeString(),
                    'rewarded_at'          => $won ? $now->toDateTimeString() : null,
                    'segment'              => $segment,
                    'won'                  => $won,
                    'reward_type'          => $rewardType,
                    'reward_value'         => $won ? $rewardValue : 0,
                    'wallet_balance_after' => $rewardType === 'wallet_balance' ? $walletBalance : null,
                    'discount_rate'        => $rewardType === 'discount_rate' ? $rewardValue : null,
                    'note'                 => $note,
                ],
            ]);

            return response()->json([
                'status'        => true,
                'won'           => $won,
                'segment'       => $segment,
                'reward_type'   => $rewardType,
                'reward_value'  => $won ? $rewardValue : 0,
                'reward_points' => $rewardType === 'points' ? $rewardValue : 0,
                'discount_rate' => $rewardType === 'discount_rate' ? $rewardValue : null,
                'message'       => $message,
                'account_created' => $accountCreated,
                'profile_url'    => $accountCreated ? route('profile') : null,
                'balances'      => [
                    'loyalty_points' => $pointsBalance,
                    'wallet_balance' => $walletBalance,
                ],
            ]);
        });
    }

    private function generateUniqueUsername(string $name, string $phone): string
    {
        $base = Str::slug($name, '_');
        $base = $base !== '' ? $base : 'wheel_user';
        $base .= '_' . substr(preg_replace('/\D+/', '', $phone), -4);

        $username = $base;
        $counter = 1;

        while (User::query()->where('username', $username)->exists()) {
            $username = $base . '_' . $counter;
            $counter++;
        }

        return $username;
    }

    private function normalizePhone(string $phone): ?string
    {
        $phone = trim($phone);
        if ($phone === '') {
            return null;
        }

        $smsService = new TaqnyatSmsService();
        $validatedPhone = $smsService->validatePhoneNumber($phone);

        if ($validatedPhone) {
            return $validatedPhone;
        }

        return null;
    }

    private function formatRewardNumber(float|int $value): float|int
    {
        return floor((float) $value) == (float) $value ? (int) $value : round((float) $value, 2);
    }

    private function isWheelEnabled(): bool
    {
        return (bool) Setting::get('wheel_enabled', true);
    }

    public function destroy_all(){
        Wheel::truncate();
        return redirect()->back()->with('success', __('wheel.success_delete_all'));
    }

    public function destroy($id){
        $prize = Wheel::findOrFail($id);
        $prize->delete();
        return redirect()->back()->with('success', __('wheel.The_award_has_been_deleted_successfully'));
    }
}






