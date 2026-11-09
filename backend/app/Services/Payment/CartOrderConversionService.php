<?php

namespace App\Services\Payment;

use App\Models\User;
use Modules\Product\Models\Cart;
use Modules\Product\Models\Order;
use Modules\Product\Models\OrderGroup;
use Modules\Product\Models\OrderItem;
use Modules\Product\Models\Product;

class CartOrderConversionService
{
    public function convertUserCartToPaidOrders(int $userId): array
    {
        $carts = Cart::with('product')
            ->where('user_id', $userId)
            ->lockForUpdate()
            ->get();

        if ($carts->isEmpty()) {
            return [
                'order_group_ids' => [],
                'order_ids' => [],
            ];
        }

        $productIds = $carts->pluck('product_id')->unique()->values();
        $products = Product::whereIn('id', $productIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($carts as $cart) {
            $product = $products->get($cart->product_id);
            $stock = (int) ($product->stock_qty ?? 0);

            if ($cart->qty > $stock) {
                return [
                    'error' => __('messages.cart_product_out_of_stock', [
                        'product' => $cart->product->name,
                    ]),
                ];
            }
        }

        $user = User::find($userId);

        $orderGroup = new OrderGroup();
        $orderGroup->user_id = $userId;
        $orderGroup->phone_no = $user?->mobile;
        $orderGroup->alternative_phone_no = null;
        $orderGroup->sub_total_amount = getSubTotal($carts, false, '', false);
        $orderGroup->total_tax_amount = 0;
        $orderGroup->type = 'online';
        $orderGroup->total_shipping_cost = 0;
        $orderGroup->total_tips_amount = 0;
        $orderGroup->payment_status = 'paid';
        $orderGroup->grand_total_amount = $orderGroup->sub_total_amount;
        $orderGroup->save();

        $order = new Order();
        $order->order_group_id = $orderGroup->id;
        $order->user_id = $userId;
        $order->total_admin_earnings = $orderGroup->grand_total_amount;
        $order->shipping_cost = 0;
        $order->tips_amount = 0;
        $order->payment_status = 'paid';
        $order->save();

        foreach ($carts as $cart) {
            $product = $products->get($cart->product_id);

            $item = new OrderItem();
            $item->order_id = $order->id;
            $item->product_variation_id = 0;
            $item->product_id = $cart->product_id;
            $item->qty = $cart->qty;
            $item->unit_price = $product->min_price ?? $product->max_price ?? 0;
            $item->total_tax = 0;
            $item->total_price = $item->unit_price * $item->qty;
            $item->save();

            $product->total_sale_count += $item->qty;
            $product->stock_qty -= $item->qty;
            $product->save();

            $cart->delete();
        }

        return [
            'order_group_ids' => [$orderGroup->id],
            'order_ids' => [$order->id],
        ];
    }
}
