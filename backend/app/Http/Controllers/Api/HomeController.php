<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Category\Models\Category;
use Modules\Product\Models\Product;
use Modules\Product\Transformers\ProductResource;
use Modules\Package\Models\Package;
use App\Models\Branch;
use App\Models\BookingReview;
use App\Models\Ouroffersection;
use App\Models\Wheel;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        // 1. Categories (Services)
        $categories = Category::where('status', 1)
            ->whereNull('parent_id')
            ->with(['media', 'services' => function ($query) {
                $query->where('status', 1)->with('media');
            }])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->take(6)
            ->get();

        // 2. Offers
        $offers = Package::query()
            ->with(['media', 'branch.media', 'service.services.media'])
            ->where('status', 1)
            ->offerPackages()
            ->latest('id')
            ->take(6)
            ->get()
            // نفس شكل الباقات (صورة + توفير) عشان كروت العروض في الرئيسية تعرض الصورة والسعر صح
            ->map(fn(Package $offer) => $this->transformPackage($offer) + ['end_date' => $offer->end_date]);

        // 3. Products
        $products = Product::with('media', 'categories', 'brand', 'unit', 'product_variations', 'product_review')
            ->where('status', 1)
            ->where('is_featured', 1)
            ->whereNull('deleted_at')
            ->take(6)
            ->get();

        // 4. Packages
        $packages = Package::query()
            ->with(['media', 'service'])
            ->where('status', 1)
            ->activeBasePackages()
            ->latest('id')
            ->take(6)
            ->get()
            ->map(fn(Package $package) => $this->transformPackage($package));


        // 5. Branches
        $branches = Branch::with(['address.state_data', 'media'])
            ->where('status', 1)
            ->whereNull('deleted_by')
            ->take(6)
            ->get()
            ->map(fn(Branch $branch) => $this->transformBranch($branch));

        // Banner displayed beside the branches section.
        $branchBanner = Ouroffersection::query()
            ->active()
            ->whereDate('start_date', '<=', now()->toDateString())
            ->whereDate('end_date', '>=', now()->toDateString())
            ->latest('id')
            ->first();


        // 6. Customer Reviews
        $reviews = BookingReview::with(['user', 'booking.branch'])
            ->where('status', 1)
            ->where('is_approved', 1)
            ->latest()
            ->take(6)
            ->get()
            ->map(fn(BookingReview $review) => $this->transformReview($review));


        // 7. Wheel of Fortune Prizes
        $wheel_prizes = Wheel::query()
            ->where('reward_value', '>', 0)
            ->orderBy('id')
            ->get()
            ->map(function (Wheel $wheel) {
                return [
                    'id' => $wheel->id,
                    'type' => $wheel->type,
                    'reward_value' => (float) $wheel->reward_value,
                ];
            });

        $categories->each(function ($category) {
            $category->setAttribute('page_content', app(\App\Services\CategoryPageContent::class)->get($category));
        });

        return response()->json([
            'status' => true,
            'data' => [
                'home_content' => app(\App\Services\HomePageContent::class)->get(),
                'faqs' => app(\App\Services\FaqContent::class)->published(),
                'categories' => $categories,
                'offers' => $offers,
                'products' => ProductResource::collection($products),
                'packages' => $packages,
                'branches' => $branches,
                'branch_banner' => $branchBanner ? $this->transformBranchBanner($branchBanner) : null,
                'reviews' => $reviews,
                'wheel_prizes' => $wheel_prizes,
            ],
            'message' => __('Home page data retrieved successfully'),
        ]);
    }

    private function transformBranchBanner(Ouroffersection $banner): array
    {
        return [
            'id' => $banner->id,
            'title' => $banner->title,
            'description' => $banner->description,
            'discount_type' => $banner->discount_type,
            'discount_value' => (float) $banner->discount_value,
            'color' => $banner->color,
            'image' => $banner->image ? asset($banner->image) : null,
            'link' => $banner->link,
            'overlay' => (bool) $banner->overlay,
        ];
    }
    private function transformPackage(Package $package): array
    {
        return [
            'id' => $package->id,
            'name' => $package->getTranslations('name'),
            'description' => $package->getTranslations('description'),
            'feature_image' => $package->image ?: $package->feature_image,
            'package_price' => (float) $package->package_price,
            ...$package->pricingSummary(),
        ];
    }

    private function transformBranch(Branch $branch): array
    {
        $address = $branch->address;

        $addressParts = array_filter([
            $address?->address_line_1,
            $address?->state_data?->name,
        ]);

        return [
            'id' => $branch->id,
            'name' => $branch->getTranslations('name'),
            'address' => $addressParts ? implode('، ', $addressParts) : null,
            'description' => $branch->description,
            'description_en' => $branch->description_en,
            'image' => $branch->feature_image,
            'image' => $branch->feature_image,
        ];
    }

    private function transformReview(BookingReview $review): array
    {
        $branch = $review->booking?->branch;

        return [
            'id' => $review->id,
            'rating' => (int) $review->rating,
            'name' => $review->user?->full_name ?: 'عميل رونق',
            'place' => $branch?->getTranslation('name', app()->getLocale()),
            'text' => $review->review_text,
            'when' => $review->created_at?->diffForHumans(),
        ];
    }
}
