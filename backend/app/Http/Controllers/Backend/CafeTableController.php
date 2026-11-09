<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\CafeTable;
use Illuminate\Http\Request;

class CafeTableController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:manage_cafe_tables');
    }

    public function index()
    {
        $tables = CafeTable::withCount('orders')->latest('id')->paginate(20);

        return view('backend.cafe.tables.index', compact('tables'));
    }

    public function create()
    {
        return view('backend.cafe.tables.create');
    }

    public function store(Request $request)
    {
        CafeTable::create($this->validatedData($request));

        return redirect()->route('backend.cafe.tables.index')->with('success', __('cafe.saved_successfully'));
    }

    public function edit(CafeTable $table)
    {
        return view('backend.cafe.tables.edit', compact('table'));
    }

    public function update(Request $request, CafeTable $table)
    {
        $table->update($this->validatedData($request));

        return redirect()->route('backend.cafe.tables.index')->with('success', __('cafe.saved_successfully'));
    }

    public function destroy(CafeTable $table)
    {
        $table->delete();

        return redirect()->route('backend.cafe.tables.index')->with('success', __('cafe.deleted_successfully'));
    }

    protected function validatedData(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'status' => ['nullable', 'boolean'],
        ]);

        $data['status'] = $request->boolean('status');

        return $data;
    }
}
