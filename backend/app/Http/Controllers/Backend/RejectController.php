<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\reject;
use Illuminate\Http\Request;

class RejectController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view_reject_reasons')->only(['index']);
        $this->middleware('permission:store_reject_reasons')->only(['store']);
        $this->middleware('permission:update_reject_reasons')->only(['update']);
        $this->middleware('permission:delete_reject_reasons')->only(['destroy']);
    }

    public function index()
    {
        $reasons = reject::all();
        return view('backend.cancellation-of-reservation.index_datatable', compact('reasons'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'reasonAR' => 'required',
            'reasonEN' => 'required',
        ]);
        reject::create([
            'name' => [
                'ar' => $request->reasonAR,
                'en' => $request->reasonEN,
            ],
        ]);
        return redirect()->back()->with('success', __('messagess.reason_added_success'));
    }

    public function destroy($id)
    {
        $reasons = reject::findOrFail($id);
        $reasons->delete();
        return redirect()->back()->with('success', __('messagess.reason_deleted_success'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'reasonAR' => 'required',
            'reasonEN' => 'required',
        ]);
        $reasons = reject::findOrFail($id);
        $reasons->update([
            'name' => [
                'ar' => $request->reasonAR,
                'en' => $request->reasonEN,
            ],
        ]);
        return redirect()->back()->with('success', __('messagess.reason_updated_success'));
    }
}
