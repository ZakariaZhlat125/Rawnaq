<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Services\BookingCoupon;
use App\Services\Payment\PendingCheckoutService;
use App\Services\Payment\CheckoutType;
use Illuminate\Http\Request;
use Modules\Booking\Models\Booking;
use Modules\Service\Models\Service;
class CouponValidationController extends Controller
{
 public function validateCoupon(Request $request) { return $this->validateInvoiceCoupon($request); }
 public function validateInvoiceCoupon(Request $request)
 {
  $data=$request->validate([
   'coupon_code'=>['required','string','max:255'],
   'service_ids'=>['sometimes','array','min:1','max:100'], 'service_ids.*'=>['integer','distinct'],
   'booking_id'=>['sometimes','integer'],
  ]);
  $lines=[];
  if(isset($data['booking_id'])) {
   abort_unless($request->user(),401);
   $booking=Booking::where('created_by',$request->user()->id)->unpaid()->with('bookingService')->findOrFail($data['booking_id']);
   $fee=max(0,(float)($booking->home_service_fee??0));
   $lines=$booking->bookingService->map(function($s) use (&$fee) {
    $price=max(0,(float)$s->service_price-(float)$s->discount_amount); $excluded=min($price,$fee);$fee-=$excluded;
    return ['service_id'=>$s->service_id,'amount'=>$price-$excluded];
   })->all();
  } elseif(isset($data['service_ids'])) {
   $services=Service::whereIn('id',$data['service_ids'])->where('status',1)->get();
   if($services->count()!==count($data['service_ids'])) return response()->json(['valid'=>false,'message'=>'بعض الخدمات غير متاحة.']);
   $lines=$services->map(fn($s)=>['service_id'=>$s->id,'amount'=>(float)$s->default_price])->all();
  } else {
   abort_unless($request->user(),401);
   $checkout=app(PendingCheckoutService::class)->getPendingCheckoutForCurrentUser(CheckoutType::CART,$data['coupon_code']);
   if(isset($checkout['error'])) return response()->json(['valid'=>false,'message'=>$checkout['error']]);
   $without=app(PendingCheckoutService::class)->getPendingCheckoutForCurrentUser(CheckoutType::CART);
   return response()->json(['valid'=>true,'discount_amount'=>$checkout['discountAmount'],'effective_discount'=>round($without['total']-$checkout['total'],2)]);
  }
  $quote=app(BookingCoupon::class)->quote($data['coupon_code'],$lines);
  if(isset($quote['error'])) return response()->json(['valid'=>false,'message'=>$quote['error']]);
  $taxBefore=(float)(getBookingTaxamount($quote['booking_base'],0,null)['total_tax_amount']??0);
  $taxAfter=(float)(getBookingTaxamount($quote['booking_base']-$quote['discount'],0,null)['total_tax_amount']??0);
  return response()->json(['valid'=>true,'discount_amount'=>$quote['discount'],'effective_discount'=>round($quote['discount']+$taxBefore-$taxAfter,2)]);
 }
}
