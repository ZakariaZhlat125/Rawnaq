<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CafeCategory;
use App\Models\CafeItem;
use App\Models\CafeOrder;
use App\Models\CafeTable;
use App\Models\User;
use App\Notifications\NewCafeOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class CafeController extends Controller
{
    /**
     * Menu payload shaped to match the sami-cafe-vue app's CafeDatabase type
     * (cats/drinks/tables/orders/seq) so it works as a drop-in HttpCafeRepository backend.
     */
    public function menu(): JsonResponse
    {
        $categories = CafeCategory::active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $items = CafeItem::available()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $tables = CafeTable::active()->orderBy('id')->get();

        return response()->json([
            'cats' => $categories->map(fn (CafeCategory $category) => [
                'id' => (string) $category->id,
                'n' => $category->name,
                'desc' => $category->description,
                'on' => (bool) $category->status,
            ])->values(),
            'drinks' => $items->map(fn (CafeItem $item) => [
                'id' => (string) $item->id,
                'cat' => (string) $item->cafe_category_id,
                'n' => $item->name,
                'desc' => $item->description ?? '',
                'pr' => (float) $item->price,
                'img' => $item->image_url,
                'on' => $item->status === 'available',
            ])->values(),
            'tables' => $tables->map(fn (CafeTable $table) => [
                'id' => (string) $table->id,
                'n' => (int) $table->id,
                'name' => $table->name,
                'seats' => 4,
                'on' => (bool) $table->status,
            ])->values(),
            'orders' => [],
            'seq' => 0,
        ]);
    }

    public function storeOrder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'table' => ['required', 'integer'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'clientToken' => ['required', 'string', 'max:191'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required'],
            'items.*.q' => ['required', 'integer', 'min:1', 'max:50'],
        ]);

        $existing = CafeOrder::where('client_token', $data['clientToken'])->first();
        if ($existing) {
            return response()->json($this->transformOrder($existing));
        }

        $table = CafeTable::active()->find($data['table']);
        if (! $table) {
            return response()->json(['message' => __('cafe.item_unavailable')], 422);
        }

        $itemIds = collect($data['items'])->pluck('id')->all();
        $availableItems = CafeItem::available()->whereIn('id', $itemIds)->get()->keyBy('id');

        $order = DB::transaction(function () use ($data, $table, $availableItems) {
            $total = 0;

            $orderItems = collect($data['items'])->map(function ($line) use ($availableItems, &$total) {
                $item = $availableItems->get((int) $line['id']);
                abort_if(! $item, 422, __('cafe.item_unavailable'));

                $quantity = (int) $line['q'];
                $subtotal = $quantity * (float) $item->price;
                $total += $subtotal;

                return [
                    'cafe_item_id' => $item->id,
                    'item_name' => $item->name,
                    'item_price' => $item->price,
                    'quantity' => $quantity,
                    'subtotal' => $subtotal,
                ];
            });

            $order = CafeOrder::create([
                'cafe_table_id' => $table->id,
                'order_number' => 'CF-'.now()->format('ymdHis').'-'.random_int(100, 999),
                'client_token' => $data['clientToken'],
                'notes' => $data['notes'] ?? null,
                'status' => 'pending',
                'total' => $total,
            ]);

            $order->items()->createMany($orderItems->all());

            return $order;
        });

        $admins = User::permission('view_cafe_orders')->get();
        if ($admins->isNotEmpty()) {
            Notification::send($admins, new NewCafeOrder($order->fresh('table')));
        }

        return response()->json($this->transformOrder($order->fresh('items')), 201);
    }

    public function showOrder(string $id): JsonResponse
    {
        $order = CafeOrder::with('items.item')->find($id);

        if (! $order) {
            return response()->json(null);
        }

        return response()->json($this->transformOrder($order));
    }

    private function transformOrder(CafeOrder $order): array
    {
        $order->loadMissing('items.item');

        return [
            'id' => (string) $order->id,
            'ref' => $order->order_number,
            'table' => (int) $order->cafe_table_id,
            'ts' => $order->created_at?->valueOf() ?? now()->valueOf(),
            'status' => $this->mapStatus($order->status),
            'items' => $order->items->map(fn ($line) => [
                'id' => (string) ($line->cafe_item_id ?? $line->id),
                'n' => $line->item_name,
                'q' => (int) $line->quantity,
                'pr' => (float) $line->item_price,
                'img' => $line->item?->image_url ?? '',
            ])->values(),
            'notes' => $order->notes ?? '',
            'clientToken' => $order->client_token,
        ];
    }

    private function mapStatus(string $status): string
    {
        return match ($status) {
            'pending' => 'new',
            'preparing' => 'prep',
            'ready' => 'ready',
            'delivered', 'cancelled' => 'done',
            default => 'new',
        };
    }
}
