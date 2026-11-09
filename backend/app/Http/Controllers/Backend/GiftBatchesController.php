<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\GiftCard;
use App\Models\GiftCardBatch;
use App\Models\GiftDesign;
use App\Services\Gifts\GiftCardService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Package\Models\Package;
use Modules\Service\Models\Service;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GiftBatchesController extends Controller
{
    public function __construct(private GiftCardService $gifts)
    {
        $this->middleware('permission:manage_gift_batches');
    }

    public function index()
    {
        $batches = GiftCardBatch::with('creator:id,first_name,last_name')
            ->withCount([
                'giftCards as used_count' => fn ($q) => $q->whereNotNull('redeemed_at'),
                'giftCards as cancelled_count' => fn ($q) => $q->whereIn('gift_status', [GiftCard::STATUS_CANCELLED, GiftCard::STATUS_REFUNDED, GiftCard::STATUS_EXPIRED]),
            ])
            ->orderByDesc('id')
            ->paginate(20);

        return view('backend.gift.batches', [
            'batches' => $batches,
            'services' => Service::where('status', 1)->orderBy('id')->get(['id', 'name', 'default_price']),
            'packages' => Package::where('status', 1)->orderBy('id')->get(['id', 'name', 'package_price']),
            'designs' => GiftDesign::active()->get(['slug', 'name_ar']),
            'branches' => \App\Models\Branch::where('status', 1)->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'source' => 'required|in:admin,corporate,campaign',
            'type' => 'required|in:value,service,package',
            'quantity' => 'required|integer|min:1|max:1000',
            'value_amount' => 'required_if:type,value|nullable|numeric|min:1|max:100000',
            'requested_services' => 'required_if:type,service|array',
            'requested_services.*' => 'integer|exists:services,id',
            'requested_packages' => 'required_if:type,package|array',
            'requested_packages.*' => 'integer|exists:packages,id',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'design' => ['nullable', Rule::exists('gift_designs', 'slug')],
            'expires_at' => 'nullable|date|after:today',
            'note' => 'nullable|string|max:1000',
        ]);

        // Recorded value of a service/package card, for reports.
        $data['subtotal'] = match ($data['type']) {
            'service' => (float) Service::whereIn('id', $data['requested_services'])->sum('default_price'),
            'package' => (float) Package::whereIn('id', $data['requested_packages'])->sum('package_price'),
            default => (float) $data['value_amount'],
        };

        $batch = $this->gifts->createBatch($data, auth()->id());

        return redirect()->route('gifts.batches')->with('success', __('gift_system.admin.batch_created', ['count' => $batch->quantity]));
    }

    /** CSV of the batch's codes (UTF-8 with BOM so Excel shows Arabic correctly). */
    public function exportCodes(GiftCardBatch $batch): StreamedResponse
    {
        $batch->increment('export_count');

        return response()->streamDownload(function () use ($batch) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['gift_id', 'code', 'type', 'value', 'status', 'expires_at', 'redeemed_at']);
            $batch->giftCards()->orderBy('id')->chunk(500, function ($gifts) use ($out) {
                foreach ($gifts as $gift) {
                    fputcsv($out, [
                        $gift->id,
                        $gift->redeemCode(),
                        $gift->type,
                        $gift->faceValue(),
                        $gift->gift_status,
                        $gift->expires_at?->toDateString(),
                        $gift->redeemed_at?->toDateTimeString(),
                    ]);
                }
            });
            fclose($out);
        }, 'gift-codes-batch-'.$batch->id.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
