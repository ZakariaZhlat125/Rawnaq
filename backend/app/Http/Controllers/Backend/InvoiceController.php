<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    
    public function __construct()
    {
        $this->middleware('permission:view_invoice')->only(['index']);
        $this->middleware('permission:delete_invoice')->only(['destroy']);
    }
    
    public function index(Request $request)
    {
        $module_action = 'List';
        $module_title = 'Invoice Cards';
    
        $query = Invoice::query()->with('user');
    
        if ($request->filled('customer_name')) {
            $query->whereHas('user', function ($q) use ($request) {
                $search = '%' . $request->customer_name . '%';
                $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", [$search]);
            });
        }
    
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }
    
        $invoices = $query->orderBy('created_at', 'desc')->get();
    
        return view('backend.invoice.index_datatable', compact('module_action', 'invoices', 'module_title'));
    }

    
    public function destroy($id)
    {
        $invoice = Invoice::findOrFail($id);
        $invoice->delete();

    return redirect()->back()->with('success', __('messages.deleted_successfully'));
    }
}







