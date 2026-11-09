<?php

namespace Modules\Customer\Http\Controllers\Backend;

use App\Authorizable;
use App\Http\Controllers\Controller;
use App\Models\LoyaltyPointTransaction;
use App\Models\User;
use Carbon\Carbon;
use Currency;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Booking\Models\Booking;
use Modules\Customer\Http\Requests\CustomerRequest;
use Modules\CustomField\Models\CustomField;
use Modules\CustomField\Models\CustomFieldGroup;
use Yajra\DataTables\DataTables;

class CustomersController extends Controller
{
    // use Authorizable;
    protected string $exportClass = '\App\Exports\CustomerExport';

    public function __construct()
    {
        // Page Title
        $this->module_title = 'customer.title';

        // module name
        $this->module_name = 'customers';

        // directory path of the module
        $this->module_path = 'customer::backend';

        view()->share([
            'module_title' => $this->module_title,
            'module_icon' => 'fa-regular fa-sun',
            'module_name' => $this->module_name,
            'module_path' => $this->module_path,
        ]);
        $this->middleware(['permission:view_customer'])->only('index');
        $this->middleware(['permission:edit_customer'])->only('edit', 'update');
        $this->middleware(['permission:add_customer'])->only('store');
        $this->middleware(['permission:delete_customer'])->only('destroy');
        $this->middleware(['permission:view_loyalty'])->only('loyalty_history');
    }
    
    public function loyalty_history($id)
    {
        $customer = User::findOrFail($id);

        $transactions = LoyaltyPointTransaction::where('user_id', $customer->id)
            ->orderByDesc('id')
            ->paginate(25);

        $module_action = 'Loyalty History';

        return view('customer::backend.customers.loyalty_history', compact('customer', 'transactions', 'module_action'));
    }

    public function bulk_action(Request $request)
    {
        $ids = explode(',', $request->rowIds);

        $actionType = $request->action_type;

        $message = __('messages.bulk_update');

        // dd($actionType, $ids, $request->status);
        switch ($actionType) {
            case 'change-status':
                $customer = User::whereIn('id', $ids)->update(['status' => $request->status]);
                $message = __('messages.bulk_customer_update');
                break;

            case 'delete':
                if (config('sami.is_demo')) {
                    return response()->json(['message' => __('messages.permission_denied'), 'status' => false], 200);
                }
                User::whereIn('id', $ids)->delete();
                $message = __('messages.bulk_customer_delete');
                break;

            default:
                return response()->json(['status' => false, 'message' => __('branch.invalid_action')]);
                break;
        }

        return response()->json(['status' => true, 'message' => __('messages.bulk_update')]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index()
    {
        $module_action = 'List';
        $columns = CustomFieldGroup::columnJsonValues(new User());
        $customefield = CustomField::exportCustomFields(new User());

        $export_import = true;
        $export_columns = [
            [
                'value' => 'first_name',
                'text' => 'First Name',
            ],
            [
                'value' => 'last_name',
                'text' => 'Last Name',
            ],
            [
                'value' => 'email',
                'text' => 'E-mail',
            ],
            [
                'value' => 'varification_status',
                'text' => 'Verification Status',
            ],
            [
                'value' => 'is_banned',
                'text' => 'Banned',
            ],
            [
                'value' => 'status',
                'text' => 'Status',
            ],
        ];
        $export_url = route('backend.customers.export');

        return view('customer::backend.customers.index', compact('module_action', 'columns', 'customefield', 'export_import', 'export_columns', 'export_url'));
    }

    public function update_status(Request $request, User $id)
    {
        $id->update(['status' => $request->status]);

        return response()->json(['status' => true, 'message' => __('branch.status_update')]);
    }

    public function index_data(Datatables $datatable, Request $request)
    {
        $module_name = $this->module_name;
        $query = User::role('user')->with('wallet');

        $filter = $request->filter;

        if (isset($filter)) {
            if (isset($filter['column_status'])) {
                $query->where('status', $filter['column_status']);
            }
        }

        $datatable = $datatable->eloquent($query)
            ->addColumn('check', function ($row) {
                return '<input type="checkbox" class="form-check-input select-table-row"  id="datatable-row-'.$row->id.'"  name="datatable_ids[]" value="'.$row->id.'" onclick="dataTableRowCheck('.$row->id.')">';
            })
            ->addColumn('action', function ($data) use ($module_name) {
                return view('customer::backend.customers.action_column', compact('module_name', 'data'));
            })

            // ->editColumn('image', function ($data) {
            //     return "<img src='".$data->profile_image."'class='avatar avatar-50 rounded-pill'>";
            // })

            ->addColumn('user_id', function ($data) {
                $Profile_image = optional($data)->profile_image ?? default_user_avatar();
                $name = optional($data)->full_name ?? default_user_name();
                $email = optional($data)->email ?? '--';
                $url = route('app.invoice') . '?customer_name=' . urlencode(optional($data)->full_name) . '&date=' ;
            
                return '
                    <a href="'.$url.'" class="d-flex align-items-center text-decoration-none" style="color:#c39b61;">
                        <div class="me-3">
                            <img src="'.$Profile_image.'" class="avatar avatar-md rounded-circle" alt="'.$name.'" width="40" height="40">
                        </div>
                        <div class="d-flex flex-column">
                            <span class="fw-bold">'.$name.'</span>
                            <small class="text-muted">'.$email.'</small>
                        </div>
                    </a>
                ';
            })


            ->orderColumn('user_id', function ($query, $order) {
                $query->orderBy('users.first_name', $order) // Ordering by first name
                      ->orderBy('users.last_name', $order); // Optional: also order by last name
            }, 1)
            ->filterColumn('user_id', function ($query, $keyword) {
                if (!empty($keyword)) {
                    // Assuming 'users' table has first_name and last_name
                    $query->where(function ($query) use ($keyword) {
                        $query->where('first_name', 'like', '%' . $keyword . '%')
                              ->orWhere('last_name', 'like', '%' . $keyword . '%') // Filtering by last name
                              ->orWhere('email', 'like', '%' . $keyword . '%');
                    });
                }
            })
            
            
            // ->editColumn('user_id', function ($data) {
            //     return  $data->first_name . ' ' . $data->last_name;
            // })

            ->editColumn('email_verified_at', function ($data) {
                $checked = '';
                if ($data->email_verified_at) {
                    return '<span class="badge bg-soft-success"><i class="fa-solid fa-envelope" style="margin-right: 2px"></i> '.__('customer.msg_verified').'</span>';
                }

                return '<button  type="button" data-url="'.route('backend.customers.verify-customer', $data->id).'" data-token="'.csrf_token().'" class="button-status-change btn btn-text-danger btn-sm  bg-soft-danger"  id="datatable-row-'.$data->id.'"  name="is_verify" value="'.$data->id.'" '.$checked.'>Verify</button>';
            })
            ->addColumn('wallet_balance', function ($data) {
                return '<a href="' . route('wallet.history', ['id' => $data->id]) . '">' . Currency::format(optional($data->wallet)->amount) . '</a>';
            })

            ->editColumn('is_banned', function ($data) {
                $checked = '';
                if ($data->is_banned) {
                    $checked = 'checked="checked"';
                }

                return '
                    <div class="form-check form-switch ">
                        <input type="checkbox" data-url="'.route('backend.customers.block-customer', $data->id).'" data-token="'.csrf_token().'" class="switch-status-change form-check-input"  id="datatable-row-'.$data->id.'"  name="is_banned" value="'.$data->id.'" '.$checked.'>
                    </div>
                 ';
            })

            ->editColumn('updated_at', function ($data) {
                $module_name = $this->module_name;

                $diff = Carbon::now()->diffInHours($data->updated_at);

                if ($diff < 25) {
                    return $data->updated_at->diffForHumans();
                } else {
                    return $data->updated_at->isoFormat('llll');
                }
            })
            
            // ->filterColumn('user_id', function ($query, $keyword) {
            //     if (!empty($keyword)) {   
            //             $query->whereRaw('CONCAT(first_name, " ", last_name) LIKE ?', ['%' . $keyword . '%']);
            //     }
            // })

            ->orderColumns(['id'], '-:column $1');

        // Custom Fields For export
        $customFieldColumns = CustomField::customFieldData($datatable, User::CUSTOM_FIELD_MODEL, null);

        return $datatable->rawColumns(array_merge(['user_id','action', 'status', 'is_banned', 'email_verified_at', 'check', 'image', 'wallet_balance'], $customFieldColumns))
            ->toJson();
    }
    
    public function show($id){
        
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CustomerRequest $request)
    {
        $data = $request->all();

        $data['password'] = Hash::make($data['password']);

        $data = User::create($data);

        $data->syncRoles(['user']);

        \Artisan::call('cache:clear');

        if ($request->custom_fields_data) {
            $data->updateCustomFieldData(json_decode($request->custom_fields_data));
        }

        if ($request->has('profile_image')) {
            $request->file('profile_image');

            storeMediaFile($data, $request->file('profile_image'), 'profile_image');
        }

        $message = __('messages.create_form', ['form' => __('customer.singular_title')]);

        return response()->json(['message' => $message, 'status' => true], 200);
    }

    public function edit($id)
    {
        $data = User::findOrFail($id);

        if (! is_null($data)) {
            $custom_field_data = $data->withCustomFields();
            $data['custom_field_data'] = collect($custom_field_data->custom_fields_data)
                ->filter(function ($value) {
                    return $value !== null;
                })
                ->toArray();
        }

        $data['profile_image'] = $data->profile_image;

        return response()->json(['data' => $data, 'status' => true]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(CustomerRequest $request, $id)
    {
        $data = User::findOrFail($id);

        $request_data = $request->except('profile_image');

        $data->update($request_data);

        if ($request->custom_fields_data) {
            $data->updateCustomFieldData(json_decode($request->custom_fields_data));
        }

        if ($request->hasFile('profile_image')) {
            storeMediaFile($data, $request->file('profile_image'), 'profile_image');
        }
        if ($request->profile_image == null) {
            $data->clearMediaCollection('profile_image');
        }
        $message = __('messages.update_form', ['form' => __('customer.singular_title')]);

        return response()->json(['message' => $message, 'status' => true], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        if (config('sami.is_demo')) {
            return response()->json(['message' => __('messages.permission_denied'), 'status' => false], 200);
        }
        $data = User::findOrFail($id);
        
        $booking = Booking::where('user_id', $id)->where('status', '!=', 'completed')->update(['status' => 'cancelled']);

        $data->tokens()->delete();

        $data->forceDelete();

        $message = __('messages.delete_form', ['form' => __('customer.singular_title')]);

        return response()->json(['message' => $message, 'status' => true], 200);
    }

    /**
     * List of trashed ertries
     * works if the softdelete is enabled.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function trashed()
    {
        $module_name = $this->module_name;

        $module_name_singular = Str::singular($module_name);

        $module_action = 'Trash List';

        $data = User::onlyTrashed()->orderBy('deleted_at', 'desc')->paginate();

        return view('customer::backend.customers.trash', compact('data', 'module_name_singular', 'module_action'));
    }

    /**
     * Restore a soft deleted entry.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function restore($id)
    {
        $module_action = 'Restore';

        $data = User::withTrashed()->find($id);
        $data->restore();

        return redirect('app/customers');
    }

    // public function change_password(Request $request)
    // {
    //     // dd('Hello');
    //     $data = $request->all();

    //     $user_id = $data['user_id'];

    //     $data = User::findOrFail($user_id);

    //     $request_data = $request->only('password');
    //     $request_data['password'] = Hash::make($request_data['password']);

    //     $data->update($request_data);

    //     $message = __('messages.password_update');

    //     return response()->json(['message' => $message, 'status' => true], 200);
    // }
    
    public function change_password(Request $request)
    {
        // Log what we received
        \Log::info('Password change request received:', $request->all());
        
        try {
            $data = $request->all();
            $user_id = $data['user_id'] ?? null;
            
            if (!$user_id) {
                \Log::error('No user_id provided');
                return response()->json(['message' => __('User ID is required'), 'status' => false], 400);
            }
            
            $user = User::findOrFail($user_id);
            
            $request_data = $request->only('password');
            $request_data['password'] = Hash::make($request_data['password']);
            
            $user->update($request_data);
            
            \Log::info('Password updated successfully for user: ' . $user_id);
            
            $message = __('messages.password_update');
            
            return response()->json(['message' => $message, 'status' => true], 200);
            
        } catch (\Exception $e) {
            \Log::error('Password change error: ' . $e->getMessage());
            return response()->json(['message' => 'Error: ' . $e->getMessage(), 'status' => false], 500);
        }
    }

    public function block_customer(Request $request, User $id)
    {
        $id->update(['is_banned' => $request->status]);

        if ($request->status == 1) {
            $message = __('messages.google_blocked');
        } else {
            $message = __('messages.google_unblocked');
        }

        return response()->json(['status' => true, 'message' => $message]);
    }

    public function verify_customer(Request $request, $id)
    {
        $data = User::findOrFail($id);

        $current_time = Carbon::now();

        $data->update(['email_verified_at' => $current_time]);

        return response()->json(['status' => true, 'message' => __('messages.customer_verify')]);
    }

    public function uniqueEmail(Request $request)
    {
        $email = $request->input('email');
        $userId = $request->input('user_id');

        $isUnique = User::where('email', $email)
                        ->where(function ($query) use ($userId) {
                            if ($userId) {
                                $query->where('id', '!=', $userId);
                            }
                        })
                        ->doesntExist();

        return response()->json(['isUnique' => $isUnique]);
    }
}
