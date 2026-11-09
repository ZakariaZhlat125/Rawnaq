<?php

namespace Modules\BussinessHour\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\BussinessHour\Models\Shift;
use Yajra\DataTables\DataTables;

class ShiftController extends Controller
{
    public function __construct()
    {
        $this->module_title = 'Shifts';
        $this->module_name = 'shifts';
        $this->module_path = 'bussinesshour::backend';

        view()->share([
            'module_title' => $this->module_title,
            'module_icon' => 'fa-regular fa-sun',
            'module_name' => $this->module_name,
            'module_path' => $this->module_path,
        ]);
    }
    // public function index(Request $request)
    // {
    //     $filter = [
    //         'status' => $request->status,
    //     ];

    //     $module_action = 'List';

    //     return view('world::backend.country.index_datatable', compact('module_action', 'filter'));
    // }
    public function index()
    {
        // dd('asdasd');
        // $filter = [
        //     'status' => $request->status,
        // ];
        $module_action = 'List';
        // return view('world::backend.country.index_datatable', compact('module_action', 'filter'));
        return view("{$this->module_path}.shifts.index_datatable", compact('module_action', 'filter'));
    }

    public function index_list(Request $request)
    {
        $shifts =Shift::whereStatus(true)->get();
        return response()->json($shifts);
        // return response()->json(['data' => $shifts, 'status' => true]);
    }

    public function index_data()
    {
        $query = Shift::query();

        return Datatables::of($query)
            ->addColumn('action', function ($data) {
                return view("{$this->module_path}.shifts.action_column", compact('data'));
            })
            ->editColumn('status', function ($data) {
                return $data->getStatusLabelAttribute(); // تأكد من وجودها في الموديل
            })
            ->editColumn('updated_at', function ($data) {
                $diff = Carbon::now()->diffInHours($data->updated_at);

                return $diff < 25
                    ? $data->updated_at->diffForHumans()
                    : $data->updated_at->isoFormat('llll');
            })
            ->rawColumns(['action', 'status'])
            ->orderColumns(['id'], '-:column $1')
            ->make(true);
    }

    public function store(Request $request)
    {
        $data = $request->all();

        $shift = Shift::updateOrCreate(
            ['id' => $request->id ?? null],
            $data
        );

        return response()->json([
            'message' => __('messages.shift_added'),
            'data' => $shift,
            'status' => true
        ], 200);
    }

    public function show($id)
    {
        $module_action = 'Show';
        $data = Shift::findOrFail($id);
        return view("{$this->module_path}.shifts.show", compact('module_action', 'data'));
    }

    public function edit($id)
    {
        $module_action = 'Edit';
        $data = Shift::findOrFail($id);

        if (request()->wantsJson()) {
            return response()->json(['data' => $data, 'status' => true]);
        }

        return view("{$this->module_path}.shifts.edit", compact('module_action', 'data'));
    }

    public function update(Request $request, $id)
    {
        $shift = Shift::findOrFail($id);
        $shift->update($request->all());

        return response()->json([
            'message' => __('messages.shift_updated'),
            'status' => true
        ], 200);
    }

    public function destroy($id)
    {
        if (config('sami.is_demo')) {
            return response()->json(['message' => __('messages.permission_denied'), 'status' => false], 200);
        }

        $shift = Shift::findOrFail($id);
        $shift->delete();

        return response()->json([
            'message' => __('messages.shift_deleted'),
            'status' => true
        ], 200);
    }

    // public function trashed()
    // {
    //     $module_action = 'Trash List';
    //     $module_name_singular = Str::singular($this->module_name);
    //     $data = Shift::onlyTrashed()->orderBy('deleted_at', 'desc')->paginate();

    //     return view("{$this->module_path}.shifts.trash", compact('data', 'module_name_singular', 'module_action'));
    // }

    public function restore($id)
    {
        $data = Shift::withTrashed()->findOrFail($id);
        $data->restore();

        return response()->json([
            'message' => __('messages.shift_restored'),
            'status' => true
        ], 200);
    }
}
