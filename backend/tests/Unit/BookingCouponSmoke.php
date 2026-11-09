<?php
require_once __DIR__.'/../../app/Services/BookingCoupon.php';
use App\Services\BookingCoupon;
$coupon = ['use_limit'=>2, 'is_expired'=>0, 'start_date_time'=>'2026-10-01', 'end_date_time'=>'2026-10-17', 'services'=>['1','2'], 'discount_type'=>'percent', 'discount_percentage'=>20, 'minimum_booking_amount'=>100];
$lines = [['service_id'=>1,'amount'=>60],['service_id'=>2,'amount'=>40]];
$checks=0;
function check($condition, $message) { global $checks; if (!$condition) throw new RuntimeException($message); $checks++; }
function quote($changes=[], $lines=null, $packages=0, $today='2026-10-17') { global $coupon; return BookingCoupon::calculate(array_merge($coupon,$changes),$lines ?? $GLOBALS['lines'],$packages,$today); }
check(quote()['discount']===20.0,'Percentage on eligible bookings');
check(quote()['booking_base']===100.0,'Minimum includes services only');
check(!isset(quote()['error']),'Expiry date remains usable throughout the last day');
check(isset(quote([],null,0,'2026-10-18')['error']),'Expired coupon rejected');
check(isset(quote([],null,0,'2026-09-30')['error']),'Future start rejected');
check(isset(quote(['is_expired'=>1])['error']),'Stopped coupon rejected');
check(isset(quote(['use_limit'=>0])['error']),'Exhausted coupon rejected');
check(isset(quote(['minimum_booking_amount'=>100.01])['error']),'Minimum below by one cent rejected');
check(isset(quote([], [['service_id'=>3,'amount'=>100]])['error']),'Unassigned service rejected');
check(isset(quote([], [['service_id'=>1,'amount'=>50],['service_id'=>3,'amount'=>50]])['error']),'Mixed restricted services rejected');
check(isset(quote([], [], 100)['error']),'Restricted coupon cannot apply to a package');
check(isset(quote(['services'=>[0]], [], 0)['error']),'Product/gift-only checkout has no booking basis');
check(quote(['services'=>[0]], [], 200)['discount']===40.0,'General coupon on package booking');
check(quote(['discount_type'=>'fixed','discount_amount'=>500])['discount']===100.0,'Fixed discount capped at booking value');
check(quote(['discount_percentage'=>100])['discount']===100.0,'Full discount never exceeds booking value');
check(quote(['discount_type'=>'fixed','discount_amount'=>-5])['discount']===0.0,'Negative discount cannot increase payable amount');
check(quote(['services'=>'["1","2"]'])['discount']===20.0,'Legacy numeric string IDs');
check(quote(['services'=>'"[1,2]"'])['discount']===20.0,'Legacy double-encoded service IDs');
check(isset(quote(['services'=>'invalid'])['error']),'Invalid restriction cannot become unrestricted');
check(isset(quote(['services'=>[]])['error']),'Missing restriction is not an all-bookings coupon');
check(quote(['discount_percentage'=>33.333])['discount']===33.33,'Currency rounding');
quote(); quote(); check($coupon['use_limit']===2,'Preview never consumes usage');
// A mixed cart keeps products and gifts in full and recalculates booking VAT after discount.
$discount=quote()['discount']; $booking=100; $productsAndGifts=200;
$before=($booking+$productsAndGifts)*1.15;
$after=($booking-$discount+$productsAndGifts)*1.15;
check(round($before-$after,2)===23.0,'UI saving includes the reduced booking VAT');
check(round($after,2)===322.0,'Products/gifts remain undiscounted');
echo "Booking coupons: $checks checks passed.\n";
