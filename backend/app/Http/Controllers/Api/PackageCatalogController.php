<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Package\Models\Package;
use Modules\Package\Models\PackageService;

class PackageCatalogController extends Controller
{
    public function packages(Request $request): JsonResponse
    {
        return $this->listByType($request, Package::TYPE_PACKAGE, __('package.package_list'));
    }

    public function packageDetails(int $id): JsonResponse
    {
        return $this->showByType($id, Package::TYPE_PACKAGE, __('messagess.package_details'));
    }

    public function offers(Request $request): JsonResponse
    {
        return $this->listByType($request, Package::TYPE_OFFER, __('package.package_list'));
    }

    public function offerDetails(int $id): JsonResponse
    {
        return $this->showByType($id, Package::TYPE_OFFER, __('messagess.package_details'));
    }

    private function listByType(Request $request, string $type, string $message): JsonResponse
    {
        $perPage = max((int) $request->integer('per_page', 20), 1);

        $query = Package::query()
            ->with(['branch', 'service.services'])
            ->where('status', 1);

        $this->applyTypeScope($query, $type);

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->integer('branch_id'));
        }

        if ($request->filled('service_id')) {
            $serviceIds = collect(explode(',', (string) $request->input('service_id')))
                ->map(fn ($id) => (int) trim($id))
                ->filter(fn ($id) => $id > 0)
                ->values()
                ->all();

            if (! empty($serviceIds)) {
                $query->whereHas('service', function ($serviceQuery) use ($serviceIds) {
                    $serviceQuery->whereIn('service_id', $serviceIds);
                });
            }
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($searchQuery) use ($search) {
                $searchQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('is_featured')) {
            $query->where('is_featured', (int) $request->boolean('is_featured'));
        }

        $packages = $query->latest('id')->paginate($perPage);

        return response()->json([
            'status' => true,
            'data' => collect($packages->items())->map(fn (Package $package) => $this->transformPackage($package))->values(),
            'pagination' => [
                'current_page' => $packages->currentPage(),
                'last_page' => $packages->lastPage(),
                'per_page' => $packages->perPage(),
                'total' => $packages->total(),
            ],
            'message' => $message,
        ]);
    }

    private function showByType(int $id, string $type, string $message): JsonResponse
    {
        $query = Package::query()
            ->with(['branch', 'service.services'])
            ->where('id', $id)
            ->where('status', 1);

        $this->applyTypeScope($query, $type);

        $package = $query->first();

        if (! $package) {
            return response()->json([
                'status' => false,
                'message' => __('messages.no_record'),
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $this->transformPackage($package),
            'message' => $message,
        ]);
    }

    private function applyTypeScope($query, string $type): void
    {
        if ($type === Package::TYPE_OFFER) {
            $query->offerPackages();

            return;
        }

        $query->activeBasePackages();
    }

    private function transformPackage(Package $package): array
    {
        $type = $package->type ?: Package::TYPE_PACKAGE;
        $branchName = $this->localizedValue($package->branch?->name);

        return [
            'id' => $package->id,
            'type' => $type,
            'name' => $this->localizedValue($package->name),
            'name_translations' => $package->getTranslations('name'),
            'description' => $this->localizedValue($package->description),
            'description_translations' => is_array($package->description) ? $package->description : null,
            'package_price' => (float) $package->package_price,
            ...$package->pricingSummary(),
            'status' => (int) $package->status,
            'start_date' => $package->start_date,
            'end_date' => $package->end_date,
            'is_featured' => (bool) $package->is_featured,
            'is_active' => $package->isActiveForFrontend(),
            'feature_image' => $package->feature_image,
            'branch' => [
                'id' => $package->branch_id,
                'name' => $branchName,
                'name_translations' => $package->branch?->getTranslations('name'),
                'feature_image' => $package->branch?->feature_image,
            ],
            'services' => $package->service
                ->filter(fn (PackageService $service) => (int) $service->qty > 0)
                ->values()
                ->map(fn (PackageService $service) => $this->transformPackageService($service, $type))
                ->all(),
        ];
    }

    private function transformPackageService(PackageService $service, string $packageType): array
    {
        $serviceModel = $service->services;
        $servicePrice = (float) ($service->service_price ?? 0);
        $discountedPrice = (float) ($service->discounted_price ?? 0);

        return [
            'id' => $service->id,
            'service_id' => $service->service_id,
            'service_name' => $this->localizedValue($serviceModel?->name) ?: $service->service_name,
            'service_name_translations' => $serviceModel?->getTranslations('name'),
            'qty' => (int) $service->qty,
            'service_price' => $servicePrice,
            'discounted_price' => $discountedPrice,
            'saved_amount' => max($servicePrice - $discountedPrice, 0),
            'duration_min' => (int) ($serviceModel?->duration_min ?? 0),
            'feature_image' => $serviceModel?->feature_image,
            'is_offer_service' => $packageType === Package::TYPE_OFFER,
        ];
    }

    private function localizedValue(mixed $value): ?string
    {
        if (is_array($value)) {
            $locale = app()->getLocale();

            return $value[$locale] ?? $value['ar'] ?? $value['en'] ?? reset($value) ?: null;
        }

        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $locale = app()->getLocale();

                return $decoded[$locale] ?? $decoded['ar'] ?? $decoded['en'] ?? reset($decoded) ?: null;
            }
        }

        return filled($value) ? (string) $value : null;
    }
}
