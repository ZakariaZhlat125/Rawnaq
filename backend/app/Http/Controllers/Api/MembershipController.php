<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MembershipTier;
use App\Services\MembershipService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MembershipController extends Controller
{
    public function __construct(protected MembershipService $membershipService) {}

    // ─────────────────────────────────────────────
    //  Public Endpoints
    // ─────────────────────────────────────────────

    /**
     * GET /api/membership/tiers
     * Return all available membership tiers (public).
     */
    public function tiers()
    {
        $tiers = $this->membershipService->getAllTiers();

        return response()->json([
            'status' => true,
            'data'   => $tiers->map(fn($t) => [
                'id'                  => $t->id,
                'name_ar'             => $t->name_ar,
                'name_en'             => $t->name_en,
                'level'               => $t->level,
                'min_points'          => $t->min_points,
                'discount_percentage' => $t->discount_percentage,
                'benefits'            => $t->benefits,
            ]),
        ]);
    }

    /**
     * POST /api/membership/scan-qr
     * Scan a member's QR code at a branch.
     */
    public function scanQr(Request $request)
    {
        $request->validate(['token' => 'required|string']);

        $result = $this->membershipService->scanQrCode($request->token);

        return response()->json([
            'status' => $result['valid'],
            'data'   => $result,
        ], $result['valid'] ? 200 : 404);
    }

    // ─────────────────────────────────────────────
    //  Authenticated Endpoints
    // ─────────────────────────────────────────────

    /**
     * GET /api/membership/my-card
     * Return the authenticated user's full membership details.
     */
    public function myCard()
    {
        $user    = Auth::user();
        $details = $this->membershipService->getMembershipDetails($user);

        return response()->json([
            'status' => true,
            'data'   => [
                'member_name'         => $user->full_name,
                'tier_ar'             => $details['current_tier']->name_ar,
                'tier_en'             => $details['current_tier']->name_en,
                'tier_level'          => $details['current_tier']->level,
                'discount'            => $details['current_tier']->discount_percentage,
                'benefits'            => $details['current_tier']->benefits,
                'points_balance'      => $details['points_balance'],
                'total_points_earned' => $details['total_points_earned'],
                'next_tier'           => $details['next_tier'] ? [
                    'name_ar'    => $details['next_tier']->name_ar,
                    'name_en'    => $details['next_tier']->name_en,
                    'min_points' => $details['next_tier']->min_points,
                ] : null,
                'points_to_next_tier' => $details['points_to_next_tier'],
                'progress_percentage' => $details['progress_percentage'],
                'qr_token'            => $details['qr_token'],
                // Same figures the Apple/Google Wallet pass shows (section 17); kept in sync via WalletBalanceChanged.
                'wallet'              => app(\App\Services\Wallet\WalletLedger::class)->summary($user->id),
                'all_tiers'           => $details['all_tiers']->map(fn($t) => [
                    'level'      => $t->level,
                    'name_ar'    => $t->name_ar,
                    'name_en'    => $t->name_en,
                    'min_points' => $t->min_points,
                    'is_current' => $t->id === $details['current_tier']->id,
                    'is_reached' => $details['membership']->total_points_earned >= $t->min_points,
                ]),
            ],
        ]);
    }

    /**
     * POST /api/membership/regenerate-qr
     * Regenerate the authenticated user's QR code token.
     */
    public function regenerateQr()
    {
        $user       = Auth::user();
        $membership = $this->membershipService->regenerateQrCode($user);

        return response()->json([
            'status'    => true,
            'message'   => 'تم تجديد رمز QR بنجاح',
            'qr_token'  => $membership->qr_code_token,
        ]);
    }

    /**
     * POST /api/membership/add-points  (Admin / Internal use)
     * Manually add points to a user's membership.
     */
    public function addPoints(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'points'  => 'required|integer|min:1',
        ]);

        $user   = \App\Models\User::find($request->user_id);
        $result = $this->membershipService->addPoints($user, (int) $request->points);

        return response()->json([
            'status'    => true,
            'message'   => $result['upgraded']
                ? 'تمت إضافة النقاط وترقية العضوية إلى ' . $result['new_tier']->name_ar
                : 'تمت إضافة النقاط بنجاح',
            'upgraded'  => $result['upgraded'],
            'new_tier'  => $result['new_tier'] ? $result['new_tier']->name_ar : null,
            'data'      => [
                'points_balance'      => $result['membership']->points_balance,
                'total_points_earned' => $result['membership']->total_points_earned,
                'current_tier'        => $result['membership']->tier->name_ar,
            ],
        ]);
    }

    /**
     * POST /api/membership/redeem-points
     * Redeem points for the authenticated user.
     */
    public function redeemPoints(Request $request)
    {
        $request->validate([
            'points' => 'required|integer|min:1',
        ]);

        $user   = Auth::user();
        $result = $this->membershipService->redeemPoints($user, (int) $request->points);

        return response()->json([
            'status'  => $result['success'],
            'message' => $result['message'],
            'data'    => [
                'points_balance' => $result['membership']->points_balance,
            ],
        ], $result['success'] ? 200 : 422);
    }

    // ─────────────────────────────────────────────
    //  Admin Endpoints
    // ─────────────────────────────────────────────

    /**
     * POST /api/admin/membership/tiers  (Admin)
     * Create a new membership tier.
     */
    public function storeTier(Request $request)
    {
        $request->validate([
            'name_en'             => 'required|string|max:100',
            'name_ar'             => 'required|string|max:100',
            'level'               => 'required|integer|unique:membership_tiers,level',
            'min_points'          => 'required|integer|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'benefits'            => 'nullable|array',
        ]);

        $tier = MembershipTier::create($request->validated());

        return response()->json([
            'status'  => true,
            'message' => 'تم إنشاء الفئة بنجاح',
            'data'    => $tier,
        ], 201);
    }

    /**
     * PUT /api/admin/membership/tiers/{id}  (Admin)
     * Update an existing membership tier.
     */
    public function updateTier(Request $request, int $id)
    {
        $tier = MembershipTier::findOrFail($id);

        $request->validate([
            'name_en'             => 'sometimes|string|max:100',
            'name_ar'             => 'sometimes|string|max:100',
            'min_points'          => 'sometimes|integer|min:0',
            'discount_percentage' => 'sometimes|numeric|min:0|max:100',
            'benefits'            => 'sometimes|array',
        ]);

        $tier->update($request->validated());

        return response()->json([
            'status'  => true,
            'message' => 'تم تحديث الفئة بنجاح',
            'data'    => $tier->fresh(),
        ]);
    }

    /**
     * DELETE /api/admin/membership/tiers/{id}  (Admin)
     */
    public function destroyTier(int $id)
    {
        $tier = MembershipTier::findOrFail($id);

        if ($tier->userMemberships()->count() > 0) {
            return response()->json([
                'status'  => false,
                'message' => 'لا يمكن حذف الفئة لأنها مرتبطة بأعضاء',
            ], 422);
        }

        $tier->delete();

        return response()->json([
            'status'  => true,
            'message' => 'تم حذف الفئة بنجاح',
        ]);
    }

    /**
     * GET /api/admin/membership/members  (Admin)
     * List all members with their membership info.
     */
    public function allMembers(Request $request)
    {
        $query = \App\Models\UserMembership::with(['user', 'tier'])
            ->when($request->tier_id, fn($q) => $q->where('membership_tier_id', $request->tier_id))
            ->latest();

        $members = $query->paginate($request->per_page ?? 20);

        return response()->json([
            'status' => true,
            'data'   => $members->through(fn($m) => [
                'id'             => $m->id,
                'user_id'        => $m->user_id,
                'name'           => $m->user->full_name,
                'mobile'         => $m->user->mobile,
                'tier'           => $m->tier->name_ar,
                'points_balance' => $m->points_balance,
                'total_points'   => $m->total_points_earned,
                'qr_token'       => $m->qr_code_token,
                'is_active'      => $m->is_active,
                'joined_at'      => $m->created_at->format('Y-m-d'),
            ]),
        ]);
    }

    /**
     * POST /api/admin/membership/seed-tiers  (Admin)
     * Seed default tiers (Member, Silver, Gold, Platinum).
     */
    public function seedTiers()
    {
        $this->membershipService->seedDefaultTiers();

        return response()->json([
            'status'  => true,
            'message' => 'تم إنشاء الفئات الافتراضية بنجاح',
            'data'    => $this->membershipService->getAllTiers(),
        ]);
    }
}
