<?php
namespace App\Models {class Setting {public static array $data=[];public static function get($key,$default=null){return self::$data[$key]??$default;}}}
namespace Modules\Category\Models {class Category {public int $id=49;public string $slug='hair-services';public function getTranslations($key){return ['ar'=>'الحلاقة','en'=>'Hair services'];}}}
namespace {
function resource_path($path){return __DIR__.'/../../resources/'.$path;}
require __DIR__.'/../../app/Services/CategoryPageContent.php';
$service=new \App\Services\CategoryPageContent;$category=new \Modules\Category\Models\Category;
function check($v,$message){if(!$v)throw new \RuntimeException($message);}
$data=$service->get($category);check(count($data['why'])===4,'Legacy why cards');check(count($data['benefits'])===4,'Legacy benefits');check(count($data['faq'])===5,'Legacy FAQ');check($data['why'][0]['title']['en']!=='','English defaults');
\App\Models\Setting::$data['category_page_49']=json_encode(['why'=>[],'show_banner'=>false,'banner_title'=>['ar'=>'عنوان','en'=>'Custom']]);
$data=$service->get($category);check($data['why']===[],'Empty list stays empty');check($data['show_banner']===false,'Hidden section stays hidden');check($data['banner_title']['en']==='Custom','Saved translation');$category->id=50;check(count($service->get($category)['why'])===4,'Category isolation');echo "Passed 8 category content checks.\n";
}
