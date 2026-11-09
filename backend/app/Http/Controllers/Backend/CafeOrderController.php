<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\CafeOrder;
use Illuminate\Http\Request;

class CafeOrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view_cafe_orders')->only(['index', 'show']);
        $this->middleware('permission:update_cafe_orders')->only('updateStatus');
    }

    public function index()
    {
        $orders = CafeOrder::with('table', 'items')->latest('id')->paginate(20);

        return view('backend.cafe.orders.index', compact('orders'));
    }

    public function show(CafeOrder $order)
    {
        $order->load('table', 'items');

        return view('backend.cafe.orders.show', compact('order'));
    }

    public function updateStatus(Request $request, CafeOrder $order)
    {
        $data = $request->validate([
            'status' => ['required', 'in:pending,preparing,ready,delivered,cancelled'],
        ]);

        $order->update($data);

        return redirect()->back()->with('success', __('cafe.status_updated'));
    }
}
