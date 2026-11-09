<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Modules\Booking\Models\Booking;
use Modules\Booking\Models\BookingService;

class PackageDetailsController extends Controller
{
public function show($id)
{
    $package = DB::table('packages')
        ->leftJoin('branches', 'packages.branch_id', '=', 'branches.id')
        ->select(
            'packages.*',
            'branches.name as branch_name',
            'branches.description as branch_description'
        )
        ->where('packages.id', $id)
        ->first();

    if (!$package) {
        abort(404, __('Package not found'));
    }

    $package = (array) $package;
    $package['name'] = json_decode($package['name'], true);

    $services = DB::table('package_services')
        ->join('services', 'package_services.service_id', '=', 'services.id')
        ->select(
            'package_services.id as package_service_id',
            'package_services.qty', // ✅ الكمية
            'package_services.service_price',
            'package_services.discounted_price',
            'services.id as service_id',
            'services.name as service_name',
            'services.duration_min',
            'services.default_price'
        )
        ->where('package_services.package_id', $id)
        ->get();

    $media = DB::table('media')
        ->where('model_type', 'Modules\\Package\\Models\\Package')
        ->where('model_id', $package['id'])
        ->where('collection_name', 'package_image')
        ->first();

    $package['image'] = $media
        ? asset('storage/uploads/' . $media->id . '/' . $media->file_name)
        : default_feature_image();

    $currentLocale = app()->getLocale();

    $services->transform(function ($service) use ($currentLocale) {

        $name = json_decode($service->service_name, true);
        $service->service_name = $name[$currentLocale] ?? '';

        // ✅ السعر النهائي حسب الكمية
        $service->total_price = $service->discounted_price * $service->qty;

        return $service;
    });

    // مجموع الأسعار بدون خصم
    $totalServicePrice = $services->sum(function ($service) {
        return $service->service_price * $service->qty;
    });

    // مجموع الأسعار بعد الخصم
    $totalService = $services->sum(function ($service) {
        return $service->discounted_price * $service->qty;
    });

    $branchDes = $package['branch_description'] ?? '';
    $branchName = json_decode($package['branch_name'], true)[$currentLocale] ?? '';

    return response()->json([
        'status' => true,
        'data' => [
            'package' => $package,
            'services' => $services,
            'total_service_price' => $totalServicePrice,
            'total_service' => $totalService,
            'branch_description' => $branchDes,
            'branch_name' => $branchName,
        ],
    ]);
}
    public function getUserCart()
    {
        $user = auth()->user();
        $cartItems = Booking::with('service.service', 'products.product', 'service.employee')
            ->where('created_by', $user->id)
            ->where('status', 'pending')
            ->where('payment_type', 'payment')
            ->whereNull('deleted_by')
            ->unpaid()
            ->get();

        return response()->json($cartItems);
    }

    public function remove($id)
    {
        $booking = Booking::find($id);

        if ($booking) {
            $booking->delete();

            return response()->json(['success' => true]);
        }

        $bookingServices = BookingService::find($booking->id);
        $bookingServices->delete();

        return response()->json(['success' => false]);
    }
}
