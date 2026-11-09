<?php

namespace Modules\Frontend\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\WheelCooldownService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Service\Models\Service;
use Modules\Category\Models\Category;
use Modules\Package\Models\Package;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductCategory;
use App\Models\Term;
use App\Models\Ad;
use App\Models\Branch;
use App\Models\Wheel;
use App\Models\Setting;


class FrontendController extends Controller
{
    public function __construct(private readonly WheelCooldownService $wheelCooldownService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Fetch active categories for the homepage
        $categories = Category::where('status', 1)->whereNull('parent_id')
            ->with(['services' => function($query) {
                $query->where('status', 1);
            }])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->take(6)
            ->get();


        // Fetch active packages for the homepage
        $packages = Package::with(['service', 'service.services', 'media'])
            ->where('status', 1)
            ->activeBasePackages()
            ->take(6)
            ->get();

        // Fetch active products for the homepage
        $products = Product::with(['media' , 'categories'])
            ->where('status', 1)
            ->where('is_featured', 1)
            ->where('deleted_at', null)
            ->take(6)
            ->get();

        // Fetch active services for the homepage
        $services = Service::with(['category', 'media'])
            ->where('status', 1)
            ->take(6)
            ->get();

        // Fetch Wheel homepage
        $prizes = Wheel::query()
            ->where('reward_value', '>', 0)
            ->select('reward_value', 'type')
            ->get();

        $intervalDays = max((int) Setting::get('wheel_display_interval_days', 1), 1);
        $shouldShowWheel = $this->shouldShowWheel($intervalDays);

        $setting = DB::table('settings')->where('name', 'service_duration_visibility')->first();
        $showDuration = $setting ? (bool) $setting->val : false;

        return view('frontend::index', compact('showDuration','services', 'categories', 'packages' , 'products' , 'prizes', 'shouldShowWheel', 'intervalDays'));
    }

    private function shouldShowWheel(int $intervalDays): bool
    {
        if (! (bool) Setting::get('wheel_enabled', true)) {
            return false;
        }

        if (Auth::check()) {
            return $this->wheelCooldownService->shouldShowWheel(
                intervalDays: $intervalDays,
                userId: Auth::id()
            );
        }

        return true;
    }

    /**
     * Display the about page.
     */
    public function about()
    {
        return view('frontend::about');
    }

    public function branches()
    {
        return view('frontend::branches');
    }

    public function Packages(Request $request)
    {
        $ads = Ad::where('page' , 'packages')->where('status', 1)->get();
        $selectedBranchId = (int) $request->query('branch_id', 0);

        $basePackagesQuery = Package::where('status', 1)
            ->activeBasePackages()
            ->whereNotNull('branch_id');

        $branchIds = (clone $basePackagesQuery)->distinct()->pluck('branch_id')->filter()->values();
        $branches = Branch::query()
            ->whereIn('id', $branchIds)
            ->where('status', 1)
            ->orderBy('id')
            ->get();

    $packages = Package::with([
        'service',
        'service.services',
        'media',
        'branch' // أضفنا علاقة الفرع
    ])
    ->where('status', 1)
    ->activeBasePackages()
    ->when($selectedBranchId > 0, fn ($query) => $query->where('branch_id', $selectedBranchId))
    ->get();

    return view('frontend::Packages', [
        'packages' => $packages,
        'ads' => $ads,
        'branches' => $branches,
        'selectedBranchId' => $selectedBranchId
    ]);
    }


    public function Ouroffers()
    {
        $packages = Package::with(['service', 'service.services', 'media', 'branch'])
            ->where('status', 1)
            ->offerPackages()
            ->get();
        return view('frontend::Ouroffers', ['packages' => $packages]);
    }

    public function TermsAndConditions()
    {
        $terms = Term::all();
        return view('frontend::TermsAndConditions' , compact('terms'));
    }

    /**
     * Display the services page.
     */
    public function services()
    {
        $ads = Ad::where('page' , 'services')->where('status', 1)->get();
         // Fetch active services for the homepage
         $services = Service::with(['category', 'media'])
         ->where('status', 1)
         ->take(6)
         ->get();

        // Fetch active categories for the homepage
        $categories = Category::where('status', 1)
            ->whereNull('parent_id')
            ->with(['services' => function($query) {
                $query->where('status', 1);
            }])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->take(6)
            ->get();

        // Fetch active packages for the homepage
        $packages = Package::with(['service', 'service.services', 'media'])
            ->where('status', 1)
            ->activeBasePackages()
            ->take(6)
            ->get();
        $setting = DB::table('settings')->where('name', 'service_duration_visibility')->first();
        $showDuration = $setting ? (bool) $setting->val : false;
        return view('frontend::services', compact('showDuration','categories', 'services', 'packages' , 'ads'));
    }

    /**
     * Display the shop page.
     */
    public function shop(Request $request)
    {
        $ads = Ad::where('page' , 'shop')->where('status', 1)->get();
        $selectedBranchId = (int) $request->query('shop_branch_id', 0);
        $hasProductBranchColumn = Schema::hasColumn('products', 'branch_id');

        $branches = collect();
        if ($hasProductBranchColumn) {
            $branches = Branch::query()
                ->where('status', 1)
                ->whereIn('id', Product::query()
                    ->select('branch_id')
                    ->where('status', 1)
                    ->whereNull('deleted_at')
                    ->whereNotNull('branch_id')
                    ->distinct()
                )
                ->orderBy('id')
                ->get();
        }

        // Fetch active products for the homepage
        $categories = ProductCategory::with(['products' => function ($q) use ($selectedBranchId, $hasProductBranchColumn) {
            $q->with(['categories', 'branch'])
              ->where('products.status', 1)
              ->whereNull('products.deleted_at')
              ->when($hasProductBranchColumn && $selectedBranchId > 0, function ($query) use ($selectedBranchId) {
                  $query->where(function ($branchQuery) use ($selectedBranchId) {
                      $branchQuery->where('products.branch_id', $selectedBranchId)
                          ->orWhereNull('products.branch_id');
                  });
              });
        }])
        ->whereNull('product_categories.deleted_by')
        ->whereNull('product_categories.deleted_at')
        ->where('product_categories.status', 1)
        ->get();

        $products = Product::with(['categories', 'branch'])
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->when($hasProductBranchColumn && $selectedBranchId > 0, function ($query) use ($selectedBranchId) {
                $query->where(function ($branchQuery) use ($selectedBranchId) {
                    $branchQuery->where('branch_id', $selectedBranchId)
                        ->orWhereNull('branch_id');
                });
            })
            ->orderByDesc('total_sale_count')
            ->take(4)
            ->get();

        return view('frontend::shop', compact('categories', 'ads', 'products', 'branches', 'selectedBranchId'));
    }

    public function productDetails($id)
    {
        // Fetch active products for the homepage
        $product = Product::with(['media' , 'categories'])->where('status', 1)->where('id', $id)->where('deleted_at', null)->first();
        $suggest = Product::with(['media' , 'categories'])->where('status', 1)->where('is_featured', 1)->where('deleted_at', null)->get();

        return view('frontend::product-details', compact('product', 'suggest'));
    }

    /**
     * Display the category details page with its services.
     */
    public function categoryDetails($id)
    {
        $category = Category::with(['services' => function($query) {
                $query->where('status', 1);
            }, 'services.category', 'services.sub_category', 'services.media', 'services.branches'])
            ->where('status', 1)
            ->findOrFail($id);
        // return $category;
        // Get related categories
        $relatedCategories = Category::where('status', 1)
            ->where('id', '!=', $id)
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->take(4)
            ->get();

        $allCat = Category::where('status', 1)
            ->whereNull('parent_id')
            ->with(['services' => function($query) {
                $query->where('status', 1);
            }])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        $setting = DB::table('settings')->where('name', 'service_duration_visibility')->first();
        $showDuration = $setting ? (bool) $setting->val : false;

        return view('frontend::category-details', compact('id','showDuration','category', 'relatedCategories' , 'allCat'));
    }

    /**
     * Display the service details page.
     */
    public function serviceDetails($id)
    {
        $service = Service::with(['category', 'sub_category', 'media', 'branches'])
            ->where('status', 1)
            ->findOrFail($id);

        // Get related services from the same category
        $relatedServices = Service::with(['category', 'media'])
            ->where('status', 1)
            ->where('id', '!=', $id)
            ->where('category_id', $service->category_id)
            ->take(4)
            ->get();

        return view('frontend::service-details', compact('service', 'relatedServices'));
    }

    /**
     * Display the contact page.
     */
    public function contact()
    {
        return view('frontend::contact');
    }

    public function samiCareCard()
    {
        return view('frontend::sami-care-card');
    }

    public function samiCareQr()
    {
        return view('frontend::sami-care-qr');
    }

    /**
     * Get services for API (for AJAX requests)
     */
    public function getServices(Request $request)
    {
        $query = Service::with(['category', 'sub_category', 'media'])
            ->where('status', 1);

        // Filter by category if provided
        if ($request->has('category_id') && $request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by subcategory if provided
        if ($request->has('subcategory_id') && $request->subcategory_id) {
            $query->where('sub_category_id', $request->subcategory_id);
        }

        // Search by name if provided
        if ($request->has('search') && $request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $services = $query->get();

        return response()->json([
            'status' => true,
            'data' => $services,
            'message' => 'Services retrieved successfully'
        ]);
    }

    /**
     * Get service details
     */
    public function getServiceDetails($id)
    {
        $service = Service::with(['category', 'sub_category', 'media', 'branches'])
            ->where('status', 1)
            ->findOrFail($id);

        return response()->json([
            'status' => true,
            'data' => $service,
            'message' => 'Service details retrieved successfully'
        ]);
    }

    /**
     * Get packages for API (for AJAX requests)
     */
    public function getPackages(Request $request)
    {
        $query = Package::with(['service', 'service.services', 'media'])
            ->where('status', 1);

        $type = $request->get('type');
        if ($type === 'offers') {
            $query->offerPackages();
        } elseif ($type === 'base') {
            $query->activeBasePackages();
        } else {
            $query->activeForFrontend();
        }

        // Filter by service if provided
        if ($request->has('service_id') && $request->service_id) {
            $serviceIds = explode(',', $request->service_id);
            $query->whereHas('service', function ($q) use ($serviceIds) {
                $q->whereIn('service_id', $serviceIds);
            });
        }

        // Search by name if provided
        if ($request->has('search') && $request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $packages = $query->get();

        return response()->json([
            'status' => true,
            'data' => $packages,
            'message' => 'Packages retrieved successfully'
        ]);
    }

    /**
     * Get package details
     */
    public function getPackageDetails($id)
    {
        $package = Package::with(['service', 'service.services', 'media', 'branch'])
            ->where('status', 1)
            ->activeForFrontend()
            ->findOrFail($id);

        return response()->json([
            'status' => true,
            'data' => $package,
            'message' => 'Package details retrieved successfully'
        ]);
    }
     public function becomeAffiliate()
    {
        $user = auth()->user();

        if ($user->affiliate && $user->affiliate->status === 'active') {
             return redirect()->route('affiliate.dashboard')
                ->with('info', 'أنت بالفعل مسوّق لدينا');
        }

        return view('frontend::become-affiliate');
    }

    public function activateAffiliate()
    {
        $user = auth()->user();

        if ($user->affiliate) {
             $user->affiliate->update([
                'status' => 'active'
            ]);
            return redirect()->back()->with('info', 'أنت بالفعل مسوّق.');

        }else {
            $user->affiliate()->create([
                'user_id' => $user->id,
                'status'   => 'active',
            ]);
        }
        return redirect()->route('affiliate.dashboard')
            ->with('success', 'تم تحويل حسابك إلى مسوّق بنجاح');
    }

}
