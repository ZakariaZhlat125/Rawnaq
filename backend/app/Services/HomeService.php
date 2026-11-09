<?php
namespace App\Services;
use App\Models\Setting;
use Modules\World\Models\City;
use Illuminate\Validation\ValidationException;
class HomeService {
 public function settings(): array {
  return array_merge(['enabled'=>true,'latitude'=>21.5433,'longitude'=>39.1728,'radius_km'=>50], json_decode(Setting::get('home_service_settings','{}'),true) ?: []);
 }
 public static function distance(float $lat, float $lng, float $centerLat, float $centerLng): float {
  $a = sin(deg2rad($lat-$centerLat)/2)**2 + cos(deg2rad($lat))*cos(deg2rad($centerLat))*sin(deg2rad($lng-$centerLng)/2)**2;
  return 6371 * 2 * asin(sqrt(min(1, $a)));
 }
 public function quote(array $data): City {
  $s=$this->settings();
  if (!$s['enabled']) throw ValidationException::withMessages(['branch'=>'الخدمة المنزلية متوقفة حالياً.']);
  $city=City::where('status',1)->find($data['city_id'] ?? null);
  if (!$city) throw ValidationException::withMessages(['city_id'=>'اختر مدينة متاحة.']);
  if (!isset($data['latitude'],$data['longitude']) || self::distance((float)$data['latitude'],(float)$data['longitude'],(float)$s['latitude'],(float)$s['longitude']) > (float)$s['radius_km'])
   throw ValidationException::withMessages(['latitude'=>'الموقع خارج نطاق تغطية الخدمة المنزلية.']);
  return $city;
 }
}
