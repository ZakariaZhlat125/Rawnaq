<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\GiftCard;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Http\Request;
use Modules\Booking\Models\BookingTransaction;
use Modules\Product\Models\OrderGroup;

class CustomerActivityReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:overall_booking_report');
    }

    public function index(Request $request)
    {
        $filters = $request->validate([
            'from' => 'nullable|date_format:Y-m-d',
            'to' => ['nullable', 'date_format:Y-m-d', ...($request->filled('from') ? ['after_or_equal:from'] : [])],
            'sort' => 'nullable|in:spending,activity',
        ]);
        $inPeriod = function ($date) use ($filters) {
            return $date && (empty($filters['from']) || $date->toDateString() >= $filters['from'])
                && (empty($filters['to']) || $date->toDateString() <= $filters['to']);
        };
        $rows = [];
        $add = function ($userId, $date, $amount, $counts) use (&$rows, $inPeriod) {
            if (!$userId || !$inPeriod($date)) {
                return;
            }
            $rows[$userId] ??= ['user_id' => $userId, 'spending' => 0, 'bookings' => 0, 'gifts' => 0, 'orders' => 0];
            $rows[$userId]['spending'] += (float) $amount;
            foreach ($counts as $key => $count) {
                $rows[$userId][$key] += $count;
            }
        };

        // Invoices contain the final paid total, including discounts and taxes.
        // Collect references across all dates so legacy rows are never counted twice.
        $bookingIds = $giftIds = $orderIds = [];
        foreach (Invoice::cursor() as $invoice) {
            $bookings = array_unique($invoice->cart_ids ?? []);
            $gifts = array_unique($invoice->gift_ids ?? []);
            $orders = array_unique($invoice->product_ids ?? []);
            foreach ($bookings as $id) $bookingIds[$id] = true;
            foreach ($gifts as $id) $giftIds[$id] = true;
            foreach ($orders as $id) $orderIds[$id] = true;
            $add($invoice->user_id, $invoice->created_at, $invoice->final_total, [
                'bookings' => count($bookings), 'gifts' => count($gifts), 'orders' => count($orders),
            ]);
        }

        // Gift redemption bookings represent consumption, not another purchase.
        $gifts = GiftCard::where('payment_status', 1)->get();
        foreach ($gifts as $gift) {
            foreach ($gift->booking_ids ?? [] as $id) $bookingIds[$id] = true;
            if (!isset($giftIds[$gift->id])) {
                $add($gift->user_id, $gift->created_at, $gift->subtotal, ['gifts' => 1]);
            }
        }
        foreach (OrderGroup::where('payment_status', 'paid')->cursor() as $order) {
            if (!isset($orderIds[$order->id])) {
                $add($order->user_id, $order->created_at, $order->grand_total_amount, ['orders' => 1]);
            }
        }
        $transactions = BookingTransaction::with(['booking.services', 'booking.products', 'booking.bookingPackages'])
            ->where('payment_status', 1)->orderByDesc('id')->get()->unique('booking_id');
        foreach ($transactions as $transaction) {
            $booking = $transaction->booking;
            if (!$booking || isset($bookingIds[$booking->id]) || in_array($booking->status, ['cancelled', 'canceled'])) continue;
            $products = $booking->products->sum(function ($product) {
                $price = $product->discounted_price > 0 ? $product->discounted_price : $product->product_price;
                return $price * ($product->product_qty ?? 1);
            });
            $amount = $booking->services->sum('service_price') + $booking->bookingPackages->sum('package_price')
                + $products - (float) $transaction->discount_amount;
            $add($booking->user_id, $transaction->created_at, $amount, ['bookings' => 1]);
        }
        $users = User::whereIn('id', array_keys($rows))->get()->keyBy('id');
        $customers = collect($rows)->map(function ($row) use ($users) {
            $row['user'] = $users->get($row['user_id']);
            $row['activity'] = $row['bookings'] + $row['gifts'] + $row['orders'];
            return $row;
        })->sortBy([[(($filters['sort'] ?? 'spending') === 'activity' ? 'activity' : 'spending'), 'desc'], ['user_id', 'asc']]);
        $customers = new \Illuminate\Pagination\LengthAwarePaginator(
            $customers->forPage(max(1, (int) $request->input('page', 1)), 25)->values(), $customers->count(), 25,
            max(1, (int) $request->input('page', 1)), ['path' => $request->url(), 'query' => $request->query()]
        );
        $module_title = __('customer_activity.title');
        return view('backend.reports.customer-activity', compact('customers', 'module_title'));
    }
}
