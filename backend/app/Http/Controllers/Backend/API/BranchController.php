<?php

namespace App\Http\Controllers\Backend\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\BranchDetailResource;
use App\Http\Resources\BranchEmployeeResource;
use App\Http\Resources\BranchGalleryResource;
use App\Http\Resources\BranchResource;
use App\Http\Resources\ServiceResource;
use App\Models\Branch;
use App\Models\BranchGallery;
use App\Models\User;
use Illuminate\Http\Request;
use Modules\Booking\Models\Booking;
use Modules\BussinessHour\Models\BussinessHour;
use Modules\Employee\Models\BranchEmployee;
use Modules\Employee\Models\EmployeeRating;
use Modules\Employee\Transformers\EmployeeResource;
use Modules\Employee\Transformers\EmployeeReviewResource;
use Modules\Service\Models\ServiceBranches;
use Modules\Tax\Models\Tax;
use Modules\Category\Models\Category;

class BranchController extends Controller
{
    public function baseBranches(Request $request)
    {
        $categoryId = (int) $request->query('category_id');
    
        $category = null;
    
        if ($categoryId > 0) {
            $category = Category::select('id', 'is_visible')
                ->where('status', 1)
                ->whereNull('deleted_at')
                ->find($categoryId);
        }
    
        $branches = Branch::query()
            ->whereNull('deleted_by')
            ->when($categoryId > 0, function ($query) use ($categoryId) {
                $query->whereHas('services', function ($serviceQuery) use ($categoryId) {
                    $serviceQuery
                        ->where('services.status', 1)
                        ->whereNull('services.deleted_at')
                        ->where('services.category_id', $categoryId);
                });
            })
            ->get();
    
        return response()->json([
            'branches' => $branches,
            'category' => $category,
            'is_visible' => $category?->is_visible
        ]);
    }

    public function branchList(Request $request)
    {
        $perPage = $request->input('per_page', 10); // Get the number of items per page from the request (default: 10)

        $branches = Branch::with('businessHours', 'address', 'media')->paginate($perPage);

        if ($branches->isEmpty()) {
            return response()->json(['status' => true, 'message' => __('branch.branch_isempty')]);
        }

        $branchCollection = BranchResource::collection($branches);

        return response()->json([
            'status' => true,
            'data' => $branchCollection,
            'current_page' => $branches->currentPage(),
            'last_page' => $branches->lastPage(),
            'message' => __('branch.branch_list'),
        ], 200);
    }

public function branchDetails($id)
{
    $branch = Branch::withTrashed()->with('businessHours', 'media', 'gallerys')->find($id);

    if (!$branch) {
        return response()->json(['status' => false, 'message' => __('branch.branch_notfound')]);
    }

    $employeeIds = BranchEmployee::where('branch_id', $id)
        ->distinct()
        ->pluck('employee_id');

    $averageRating = EmployeeRating::whereIn('employee_id', $employeeIds)->avg('rating');
    $totalReview = EmployeeRating::whereIn('employee_id', $employeeIds)->count();

    $branch->average_rating = $averageRating;
    $branch->total_review = $totalReview;

    $branchDetails = new BranchDetailResource($branch);

    return response()->json([
        'status' => true,
        'data' => $branchDetails,
        'message' => __('branch.branch_details')
    ]);
}


    public function branchService(Request $request)
    {
        $branchId = $request->input('branch_id');

        // $branchServices = ServiceBranches::where('branch_id', $branchId)->get();
        $branch = Branch::find($branchId);

        if (! $branch) {
            return response()->json(['status' => true, 'message' => __('branch.branch_noservice')]);
        }

        $serviceDetails = ServiceResource::collection($branch->services);

        return response()->json(['status' => true, 'data' => $serviceDetails, 'message' => __('branch.branch_service')]);
    }

    public function branchReviews(Request $request)
    {
        $branchId = $request->branch_id;

        $perPage = $request->input('per_page', 10);

        $employeeIds = BranchEmployee::where('branch_id', $branchId)
            ->distinct()
            ->pluck('employee_id');

        $reviews = EmployeeRating::with('user')
            ->whereIn('employee_id', $employeeIds);

        $reviews = $reviews->orderBy('updated_at', 'desc')->paginate($perPage);
        $review = EmployeeReviewResource::collection($reviews);

        return response()->json([
            'status' => true,
            'data' => $review,
            'message' => __('branch.branch_review'),
        ]);
    }

public function branchEmployee(Request $request)
{
    $branchId = $request->input('branch_id');
    $serviceId = $request->input('service_id'); // إضافة هذه السطر

    $branchEmployees = BranchEmployee::where('branch_id', $branchId)->pluck('employee_id');
    
    $employee = User::with(['media', 'branches', 'services'])
        ->where('status', 1)
        ->whereIn('id', $branchEmployees);
    
    // إضافة تصفية حسب الخدمة إذا وجدت
    if ($serviceId) {
        $employee->whereHas('services', function($q) use ($serviceId) {
            $q->where('service_id', $serviceId);
        });
    }
    
    $employee = $employee->paginate($request->input('per_page', 10));
    
    return response()->json([
        'status' => true,
        'data' => EmployeeResource::collection($employee),
        'message' => __('employee.employee_list'),
    ], 200);
}

    public function branchGallery(Request $request)
    {
        $branchId = $request->input('branch_id');

        $branchGalleries = BranchGallery::where('branch_id', $branchId)->get();

        if ($branchGalleries->isEmpty()) {
            return response()->json(['status' => true, 'message' => __('branch.branch_nogallery')]);
        }

        $galleryData = BranchGalleryResource::collection($branchGalleries);

        return response()->json(['status' => true, 'data' => $galleryData, 'message' => __('branch.branch_gallery')]);
    }

    public function assign_list($id)
    {
        $branch_user = BranchEmployee::with('employee')->where('branch_id', $id)->get();
        $branch_user = $branch_user->each(function ($data) {
            $data['name'] = $data->employee->name;
            $data['avatar'] = $data->employee->avatar;

            return $data;
        });

        return response()->json(['status' => true, 'data' => $branch_user]);
    }

    public function assign_update($id, Request $request)
    {
        BranchEmployee::where('branch_id', $id)->delete();
        foreach ($request->users as $key => $value) {
            BranchEmployee::create([
                'branch_id' => $id,
                'employee_id' => $value['employee_id'],
                'is_primary' => $value['is_primary'],
            ]);
        }

        return response()->json(['status' => true, 'message' => __('branch.branch_update')]);
    }

    public function branchConfig(Request $request)
    {
        $branch_id = $request->branch_id;

        $branch_slot = BussinessHour::where('branch_id', $branch_id)->get();

        $branch_tax = Tax::active()
            ->whereNull('module_type')
            ->orWhere('module_type', 'services')
            ->where('status',1)->get()
            ->map(function ($tax) {
                return [
                    'name' => $tax->title,
                    'type' => $tax->type,
                    'percent' => $tax->type == 'percent' ? $tax->value : 0,
                    'tax_amount' => $tax->type != 'percent' ? $tax->value : 0,
                ];
            })
            ->toArray();
        $tax = $branch_tax;

        $response = [
            'slot' => $branch_slot,
            'tax' => $tax,
            'slot_duration' => setting('slot_duration'),
        ];

        return response()->json(['status' => true, 'data' => $response], 200);
    }

    public function verifySlot(Request $request)
    {
        $employee_id = $request->employee_id;
        $start_date_time = $request->start_date_time;

        $booking = Booking::with('bookingService')->where('start_date_time', $start_date_time)
            ->whereHas('bookingService', function ($query) use ($employee_id) {
                $query->where('employee_id', $employee_id);
            });
        if ($booking->count() > 0) {
            return response()->json(['status' => false, 'message' => __('branch.branch_reserved')]);
        } else {
            return response()->json(['status' => true, 'message' => '']);
        }
    }
    
public function getAvailableDates($id)
{
    $branch = Branch::find($id);

    if (!$branch) {
        return response()->json([
            'status' => false,
            'message' => __('branch.branch_notfound')
        ], 404);
    }

    // احصل على مواعيد العمل للفرع
    $businessHours = BussinessHour::where('branch_id', $id)->get();

    // حساب التواريخ المتاحة (30 يومًا قادمًا كمثال)
    $availableDates = [];
    for ($i = 0; $i < 30; $i++) {
        $date = now()->addDays($i)->format('Y-m-d');
        $availableDates[] = $date;
    }

    return response()->json([
        'status' => true,
        'data' => $availableDates,
        'message' => __('branch.available_dates')
    ]);
}
}
