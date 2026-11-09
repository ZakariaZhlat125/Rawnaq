<?php

namespace App\Http\Controllers;

use App\Services\CartExpirationService;
use Illuminate\Http\Request;
use Modules\Booking\Models\Booking;
use Modules\Booking\Models\BookingService;
use Modules\Package\Models\BookingPackages;
use App\Models\LoyaltyPoint;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\GiftCard;
use Modules\Product\Models\Product;
use Modules\Product\Models\Cart;



class BookingCartController extends Controller
{


    public function sidebarData(Request $request)
    {
        // Guard: unauthenticated users get an empty cart
        if (! auth()->check()) {
            return response()->json($this->emptySidebarResponse());
        }

        $userId = auth()->id();
        app(CartExpirationService::class)->clearExpired($userId);

        // ── 1. Services ────────────────────────
        $services = Booking::with('service.service', 'service.employee')
            ->where('created_by', $userId)
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->where('payment_type', 'cart')
            ->unpaid()
            ->whereNull('deleted_by')
            ->get();

        $servicePrice  = $services->sum(fn($b) => $b->service?->service_price ?? 0);
        $discountTotal = $services->sum(fn($b) => $b->services->sum(fn($s) => $s->discount_amount ?? 0));
        $serviceCount  = $services->filter(fn($b) => $b->service)->count();

        // ── 2. Products ───────────────────────────────────────────────
        $products = Cart::with('product')
            ->where('user_id', $userId)
            ->get();

        $productPrice = $products->sum(function ($item) {
            $price = $item->product->max_price ?? $item->product->min_price ?? 0;
            return $price * ($item->qty ?? 1);
        });

        // ── 3. Gift Cards ─────────────────────────────────────────────
        $gifts     = GiftCard::where('user_id', $userId)->where('payment_status', 0)->get();
        $giftPrice = $gifts->sum(fn($g) => $g->subtotal ?? 0);

        $bookingPackages = BookingPackages::whereHas('booking', function ($query) use ($userId) {
            $query->where('created_by', $userId)
                ->whereNotIn('status', ['cancelled', 'completed'])
                ->where('payment_type', 'cart')
                ->unpaid()
                ->whereNull('deleted_by');
        })->with(['package', 'booking'])->get();

        $packagePrice = $bookingPackages->sum(fn($item) => $item->package?->package_price ?? 0);

        // ── 5. Totals ─────────────────────────────────────────────────
        $cartTotal  = $servicePrice + $productPrice + $giftPrice + $packagePrice;
        $finalPrice = $cartTotal - $discountTotal;

        // ── 5. Build JSON ─────────────────────────────────────────────
        return response()->json([

            // ── Products ──
            'products' => $products->map(function ($item) {
                $product = $item->product;
                $price   = $product->max_price ?? $product->min_price ?? 0;

                return [
                    'id'             => $item->id,
                    'type'           => 'product',
                    'name'           => $product->name ?? '—',
                    'image'          => $product->image
                        ? asset('storage/' . $product->image)
                        : null,
                    'original_price' => $price,
                    'final_price'    => $price,          // apply coupon logic here if you have one
                    'discount_amount'=> 0,
                    'coupon_code'    => null,
                    'qty'            => $item->qty ?? 1,
                    'currency'       => 'ر.س',
                ];
            })->values(),

            // ── Services ──
            'services' => $services->filter(fn($b) => $b->service)->map(function ($booking) {
                $svc      = $booking->service;            // BookingService pivot
                $info     = $svc->service   ?? null;      // Service model
                $employee = $svc->employee  ?? null;

                $originalPrice  = $svc->service_price   ?? 0;
                $discountAmount = $svc->discount_amount  ?? 0;

                return [
                    'id'             => $booking->id,
                    'type'           => 'service',
                    'name'           => $info?->name ?? '—',
                    'image'          => $info?->image
                        ? asset('storage/' . $info->image)
                        : null,
                    'employee_name'  => $employee?->name ?? null,
                    'booking_date'   => $booking->date
                        ? \Carbon\Carbon::parse($booking->date)->translatedFormat('D d M Y')
                        : null,
                    'original_price' => $originalPrice,
                    'final_price'    => $originalPrice - $discountAmount,
                    'discount_amount'=> $discountAmount,
                    'coupon_code'    => $booking->coupon_code ?? null,
                    'qty'            => 1,
                    'currency'       => 'ر.س',
                ];
            })->values(),

            // ── Gift Cards ──
            'gifts' => $gifts->map(function ($gift) {
                return [
                    'id'             => $gift->id,
                    'type'           => 'gift',
                    'name'           => $gift->title ?? __('messages.gift_card'),
                    'image'          => null,
                    'original_price' => $gift->subtotal ?? 0,
                    'final_price'    => $gift->subtotal  ?? 0,
                    'discount_amount'=> 0,
                    'coupon_code'    => null,
                    'qty'            => 1,
                    'currency'       => 'ر.س',
                ];
            })->values(),

            // ── Packages ──
            'packages' => $bookingPackages->filter(fn($bp) => $bp->package)->map(function ($bp) {
                $package = $bp->package;
                $price   = $package->package_price ?? 0;

                return [
                    'id'             => $bp->id,
                    'type'           => 'package',
                    'name'           => $package->name ?? '—',
                    'image'          => null,
                    'original_price' => $price,
                    'final_price'    => $price,
                    'discount_amount'=> 0,
                    'coupon_code'    => null,
                    'qty'            => 1,
                    'delete_url'     => route('cart.destroy', $bp->booking->id ?? 0),
                    'currency'       => 'ر.س',
                ];
            })->values(),

            // ── Summary ──
            'summary' => [
                'products_count' => $products->count(),
                'services_count' => $serviceCount,
                'gifts_count'    => $gifts->count(),
                'packages_count' => $bookingPackages->count(),
                'subtotal'       => number_format($cartTotal,    2, '.', ''),
                'discount'       => number_format($discountTotal, 2, '.', ''),
                'final'          => number_format($finalPrice,   2, '.', ''),
                'currency'       => 'ر.س',
            ],
        ]);
    }

    private function emptySidebarResponse(): array
    {
        return [
            'products' => [],
            'services' => [],
            'gifts'    => [],
            'packages' => [],
            'summary'  => [
                'products_count' => 0,
                'services_count' => 0,
                'gifts_count'    => 0,
                'packages_count' => 0,
                'subtotal'       => '0.00',
                'discount'       => '0.00',
                'final'          => '0.00',
                'currency'       => 'ر.س',
            ],
        ];
    }

    public function index(Request $request)
    {
        $userId = auth()->user()->id;
        app(CartExpirationService::class)->clearExpired($userId);

        $services = Booking::with('service.service', 'service.employee')
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->where('payment_type', 'cart')
            ->unpaid()
            ->where('created_by', $userId)
            ->whereNull('deleted_by')
            ->get();

        $servicePrice = $services->sum(function ($item) {
            return $item->service ? ($item->service->service_price ?? 0) : 0;
        });

        $bookingPackages = BookingPackages::whereHas('booking', function ($query) use ($userId) {
            $query->where('created_by', $userId)
                ->whereNotIn('status', ['cancelled', 'completed'])
                ->where('payment_type', 'cart')
                ->unpaid()
                ->whereNull('deleted_by');
        })->with('package')->get();

        $packagePrice = $bookingPackages->sum(function ($item) {
            return $item->package ? ($item->package->package_price ?? 0) : 0;
        });

        $products = Cart::with('product')->where(['user_id' => $userId])->get();

        $productPrice = $products->sum(function ($item) {
            $price = $item->product->max_price ?? $item->product->min_price ?? 0;
            return $price * ($item->qty ?? 1);
        });

        $gifts = GiftCard::where('user_id', $userId)->where('payment_status', 0 )->get();

        $GiftPrice = $gifts->sum(fn($g) => $g->subtotal ?? 0);

        $cartTotal = $servicePrice + $productPrice + $GiftPrice +$packagePrice;

        $discountTotal = $services->sum(fn($item) =>
            $item->services->sum(fn($s) => $s->discount_amount ?? 0)
        );


        $finalPrice = $cartTotal - $discountTotal;

        $serviceCount = $services->sum(fn($item) => $item->service ? 1 : 0);
        $productCount = $products->count();
        $packagesCount = $bookingPackages->count();


        return view('frontend.cart.index', compact('packagesCount','bookingPackages','services' , 'products' , 'finalPrice' , 'discountTotal' , 'serviceCount' , 'productCount', 'gifts'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $data = $request->all();
        $btn_value = $request->btn_value;
        $branch = $data['branch'];
        if (!$user) {
            session()->put('temp_booking', [
                'data' => $data,
                'btn_value' => $btn_value,
                'created_at' => now(),
            ]);
            return response()->json([
                'success' => false,
                'need_login' => true,
                'login_url' => route('signin'),
                'message' => 'يرجى تسجيل الدخول لإكمال الحجز.'
            ], 200);
        }
            if (!empty($data['services'])) {
                foreach ($data['services'] as $service) {
                    if (!empty($service['subServices'])) {
                        foreach ($service['subServices'] as $sub) {
                            $subId = $sub['id'];
                            $date = $sub['date'];
                            $time = $sub['time'];
                            $duration = $sub['duration'];
                            $price = $sub['price'];
                            $staffId = $sub['staffId'];
                            $startDateTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $time);

                            $booking = new Booking();
                            if($data['branch'] != 0){
                                $booking->note = 'العميل: ' . $user->first_name .
                                    '، الجوال: ' .  $user->mobile .
                                    '، الخدمة: ' . $subId;
                            }else{
                                $booking->note =  'اسم العميل ' . $data['customerName'] . 'رقم العميل ' . $data['mobileNo'] . 'الحي ' . $data['neighborhood'] ;
                            }
                            $booking->start_date_time = $startDateTime;
                            $booking->user_id         = $user->id;
                            $booking->branch_id       = $data['branch'] ?? 1;
                            $booking->created_by      = $user->id;
                            $booking->status          = 'pending';
                            $booking->payment_type       =  $btn_value;
                            $booking->save();

                            //  الحجز التاني
                            $bookingService = new BookingService();
                            $bookingService->booking_id       = $booking->id;
                            $bookingService->service_id       = $subId;
                            $bookingService->employee_id      = $staffId;
                            $bookingService->start_date_time  = $startDateTime;
                            $bookingService->service_price    = \Modules\Service\Models\Service::find($subId)->default_price ?? 0;
                            $bookingService->duration_min     = $duration;
                            $bookingService->sequance         = 1;
                            $bookingService->created_by      = $user->id;
                            $bookingService->save();

                            $loyalty = \App\Models\LoyaltyPoint::firstOrCreate(
                                ['user_id' => $user->id],
                                ['points' => 0]
                            );
                        }
                    }
                }
            }
            return response()->json([
                'success' => true,
                'message' => __('messages.booking_added_to_cart')
            ], 201);
    }

    public function destroy($id)
    {
        $user = auth()->user();

        $booking = Booking::find($id);

        if (!$booking) {
            return response()->json(['message' => __('Cart item not found')], 404);
        }

        $booking->delete();

        return redirect()->back()->with('success', __('messages.item_removed_from_cart'));
    }

    public function destroy_product($id)
    {
        $user = auth()->user();

        $product = Cart::findOrFail($id);

        if (!$product) {
            return response()->json(['message' => __('Cart item not found')], 404);
        }

        $product->delete();

        return redirect()->back()->with('success', __('messages.item_removed_from_cart'));
    }

    public function updateProductQty(Request $request, $id)
    {
        $user = auth()->user();

        $cartItem = Cart::where('id', $id)->where('user_id', $user->id)->first();
        if (! $cartItem) {
            return response()->json(['message' => __('Cart item not found')], 404);
        }

        $qty = (int) $request->input('qty', 1);

        if ($qty < 1) {
            $cartItem->delete();
            return response()->json([
                'status' => 'deleted',
                'qty' => 0,
            ]);
        }

        $product = $cartItem->product ?? Product::find($cartItem->product_id);
        $stock = (int) ($product->stock_qty ?? 0);

        if ($qty > $stock) {
            return response()->json([
                'status' => 'failed',
                'message' => __('messages.cart_quantity_unavailable'),
            ], 422);
        }

        $cartItem->update([
            'qty' => $qty,
        ]);

        return response()->json([
            'status' => 'success',
            'qty' => $qty,
        ]);
    }

    public function destroy_gift($id)
    {
        $user = auth()->user();

        $gift = GiftCard::findOrFail($id);

        if (!$gift) {
            return response()->json(['message' => __('Cart item not found')], 404);
        }

        $gift->delete();

        return redirect()->back()->with('success', __('messages.item_removed_from_cart'));
    }


    public function destroy_All()
    {
        $user = auth()->user();

        $bookings = Booking::with('services', 'products')
            ->where('user_id', $user->id)
            ->unpaid()
            ->get();

        foreach ($bookings as $booking) {
            $booking->services()->delete();

            $booking->products()->delete();

            $booking->delete();
        }

        Cart::where('user_id', $user->id)->delete();

        GiftCard::where('user_id', $user->id)->where('payment_status', 0)->delete();

        return redirect()->back()->with('success', __('messages.items_removed_from_cart'));
    }


    public function balance(Request $request)
    {
        $user = $request->user(); 

        $points = DB::table('loyalty_points')
                    ->where('user_id', $user->id)
                    ->sum('points'); 

        return response()->json([
            'user_id' => $user->id,
            'loyalty_points' => $points,
        ]);
    }

    public function checkLoyaltyPoints(Request $request)
    {
        $user = auth()->user();
        $points = LoyaltyPoint::where('user_id', $user->id)->value('points') ?? 0;

        return response()->json([
            'points' => $points,
        ]);
    }

    public function addToCart(Request $request, $id)
    {
        $qty = (int) $request->query('qty', 1);

        if ($qty < 1) {
            return response()->json([
                'status' => 'failed',
                'message' => __('messages.cart_invalid_quantity'),
            ], 422);
        }

        $product = Product::findOrFail($id);
        $stock = (int) ($product->stock_qty ?? 0);

        if ($qty > $stock) {
            return response()->json([
                'status' => 'failed',
                'message' => __('messages.cart_quantity_unavailable'),
            ], 422);
        }

        $exist = Cart::where([
            'user_id' => auth()->id(),
            'product_id' => $id,
        ])->first();

        if ($exist) {
            $newQty = ((int) $exist->qty) + $qty;

            if ($newQty > $stock) {
                return response()->json([
                    'status' => 'failed',
                    'message' => __('messages.cart_quantity_unavailable'),
                ], 422);
            }

            $exist->update([
                'qty' => $newQty,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => __('messages.cart_product_quantity_increased'),
            ]);
        }

        Cart::create([
            'user_id' => auth()->id(),
            'location_id' => 1,
            'product_id' => $id,
            'product_variation_id' => null,
            'qty' => $qty,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => __('messages.cart_product_added'),
        ]);
    }

}
