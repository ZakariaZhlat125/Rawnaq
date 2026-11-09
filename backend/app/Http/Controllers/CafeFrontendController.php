<?php

namespace App\Http\Controllers;

use App\Models\CafeCategory;
use App\Models\CafeItem;
use App\Models\CafeOrder;
use App\Models\CafeTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CafeFrontendController extends Controller
{
    public function index()
    {
        $categories = $this->menuQuery()->get();

        return view('frontend.cafe.index', compact('categories'));
    }

    public function table(string $code)
    {
        $table = CafeTable::active()->where('code', $code)->firstOrFail();
        $categories = $this->menuQuery()->get();

        return view('frontend.cafe.table', compact('table', 'categories'));
    }

    public function storeOrder(Request $request, string $code)
    {
        $table = CafeTable::active()->where('code', $code)->firstOrFail();

        $data = $request->validate([
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'exists:cafe_items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:50'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
        ]);

        $order = DB::transaction(function () use ($data, $table) {
            $itemIds = collect($data['items'])->pluck('id')->all();
            $items = CafeItem::available()->whereIn('id', $itemIds)->get()->keyBy('id');
            $total = 0;

            $orderItems = collect($data['items'])->map(function ($line) use ($items, &$total) {
                $item = $items->get((int) $line['id']);
                abort_if(! $item, 422, __('cafe.item_unavailable'));

                $quantity = (int) $line['quantity'];
                $subtotal = $quantity * (float) $item->price;
                $total += $subtotal;

                return [
                    'cafe_item_id' => $item->id,
                    'item_name' => $item->name,
                    'item_price' => $item->price,
                    'quantity' => $quantity,
                    'subtotal' => $subtotal,
                    'notes' => $line['notes'] ?? null,
                ];
            });

            $order = CafeOrder::create([
                'cafe_table_id' => $table->id,
                'order_number' => 'CF-'.now()->format('ymdHis').'-'.random_int(100, 999),
                'customer_name' => $data['customer_name'] ?? null,
                'customer_phone' => $data['customer_phone'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => 'pending',
                'total' => $total,
            ]);

            $order->items()->createMany($orderItems->all());

            return $order;
        });

        return redirect()->route('cafe.table', $table->code)->with('success', __('cafe.order_created', ['number' => $order->order_number]));
    }

    protected function menuQuery()
    {
        return CafeCategory::active()
            ->with(['availableItems' => fn ($query) => $query->orderBy('sort_order')->orderBy('name')])
            ->whereHas('availableItems')
            ->orderBy('sort_order')
            ->orderBy('name');
    }
}
