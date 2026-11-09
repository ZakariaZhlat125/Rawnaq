<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\MembershipTier;
use App\Models\UserMembership;
use App\Services\MembershipService;
use Illuminate\Http\Request;

class MembershipController extends Controller
{
    public function __construct(protected MembershipService $membershipService) {}

    // ── Tiers CRUD ────────────────────────────────────────────────────────────

    public function tiersIndex()
    {
        $tiers = MembershipTier::orderBy('level')->get();

        return view('backend.membership.tiers', compact('tiers'));
    }

    public function tiersStore(Request $request)
    {
        $request->validate([
            'name_en'             => 'required|string|max:100',
            'name_ar'             => 'required|string|max:100',
            'level'               => 'required|integer|unique:membership_tiers,level',
            'min_points'          => 'required|integer|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'benefits_ar'         => 'nullable|string',
            'benefits_en'         => 'nullable|string',
            'wallet_bonus'        => 'nullable|numeric|min:0|max:100000',
            'wallet_bonus_expiry_days' => 'nullable|integer|min:1|max:3650',
        ]);

        $benefitsAr = array_filter(array_map('trim', explode("\n", $request->benefits_ar ?? '')));
        $benefitsEn = array_filter(array_map('trim', explode("\n", $request->benefits_en ?? '')));

        MembershipTier::create([
            'name_en'             => $request->name_en,
            'name_ar'             => $request->name_ar,
            'level'               => $request->level,
            'min_points'          => $request->min_points,
            'discount_percentage' => $request->discount_percentage ?? 0,
            'benefits'            => $this->benefits($request, $benefitsAr, $benefitsEn),
        ]);

        return back()->with('success', 'تم إنشاء الفئة بنجاح');
    }

    public function tiersUpdate(Request $request, MembershipTier $tier)
    {
        $request->validate([
            'name_en'             => 'required|string|max:100',
            'name_ar'             => 'required|string|max:100',
            'min_points'          => 'required|integer|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'benefits_ar'         => 'nullable|string',
            'benefits_en'         => 'nullable|string',
            'wallet_bonus'        => 'nullable|numeric|min:0|max:100000',
            'wallet_bonus_expiry_days' => 'nullable|integer|min:1|max:3650',
        ]);

        $benefitsAr = array_filter(array_map('trim', explode("\n", $request->benefits_ar ?? '')));
        $benefitsEn = array_filter(array_map('trim', explode("\n", $request->benefits_en ?? '')));

        $tier->update([
            'name_en'             => $request->name_en,
            'name_ar'             => $request->name_ar,
            'min_points'          => $request->min_points,
            'discount_percentage' => $request->discount_percentage ?? 0,
            'benefits'            => $this->benefits($request, $benefitsAr, $benefitsEn),
        ]);

        return back()->with('success', 'تم تحديث الفئة بنجاح');
    }

    /** Benefit lines plus the wallet bonus granted once when a member reaches the tier. */
    private function benefits(Request $request, array $ar, array $en): array
    {
        return array_filter([
            'ar' => array_values($ar),
            'en' => array_values($en),
            'wallet_bonus' => $request->filled('wallet_bonus') ? (float) $request->wallet_bonus : null,
            'wallet_bonus_expiry_days' => $request->filled('wallet_bonus_expiry_days') ? (int) $request->wallet_bonus_expiry_days : null,
        ], fn ($v) => $v !== null);
    }

    public function tiersDestroy(MembershipTier $tier)
    {
        if ($tier->userMemberships()->count() > 0) {
            return back()->with('error', 'لا يمكن حذف الفئة لأنها مرتبطة بأعضاء');
        }
        $tier->delete();

        return back()->with('success', 'تم حذف الفئة بنجاح');
    }

    public function seedTiers()
    {
        $this->membershipService->seedDefaultTiers();

        return back()->with('success', 'تم إنشاء الفئات الافتراضية (Member, Silver, Gold, Platinum) بنجاح');
    }

    // ── Members Management ────────────────────────────────────────────────────

    public function membersIndex(Request $request)
    {
        $query = UserMembership::with(['user', 'tier'])
            ->when($request->tier_id, fn($q) => $q->where('membership_tier_id', $request->tier_id))
            ->when($request->search, function ($q) use ($request) {
                $q->whereHas('user', function ($uq) use ($request) {
                    $uq->where('first_name', 'like', "%{$request->search}%")
                       ->orWhere('last_name',  'like', "%{$request->search}%")
                       ->orWhere('mobile',     'like', "%{$request->search}%");
                });
            })
            ->latest();

        $memberships = $query->paginate(20)->withQueryString();
        $tiers        = MembershipTier::orderBy('level')->get();

        return view('backend.membership.members', compact('memberships', 'tiers'));
    }

    public function addPoints(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'points'  => 'required|integer|min:1',
            'reason'  => 'nullable|string|max:255',
        ]);

        $user   = \App\Models\User::find($request->user_id);
        $result = $this->membershipService->addPoints($user, (int) $request->points);

        $message = $result['upgraded']
            ? "تمت إضافة {$request->points} نقطة وترقية العضوية إلى " . $result['new_tier']->name_ar
            : "تمت إضافة {$request->points} نقطة بنجاح";

        return back()->with('success', $message);
    }
}
