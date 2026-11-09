<?php
namespace App\Http\Controllers { class Controller {} }
namespace App\Services { class HomeService { public static $enabled=true; public function settings(){return ['enabled'=>self::$enabled];} } }
namespace Modules\Category\Models { class Category { public static function where($key,$value){return new \TestQuery([(object)['id'=>49]]);} } }
namespace App\Models { class Blog { public static function published(){return new \TestQuery([(object)['slug'=>'published & article']]);} } }
namespace {
class TestQuery { public function __construct(private array $rows){} public function orderBy($key){return $this;} public function cursor(){return $this->rows;} }
class TestResponse { public array $headers=[]; public function __construct(public string $body){} public function header($k,$v){$this->headers[$k]=$v;return $this;} }
function config($key){return $key==='seo_site.url' ? 'https://sami-care.sa' : require __DIR__.'/../../config/frontend_seo.php';}
function app($class){return new $class;}
function response($body,$status){if($status!==200)throw new RuntimeException('Invalid status');return new TestResponse($body);}
require __DIR__.'/../../app/Http/Controllers/Api/SitemapController.php';
$controller=new \App\Http\Controllers\Api\SitemapController;
$result=$controller();$checks=0;
function check($ok,$label){global $checks;if(!$ok)throw new RuntimeException($label);$checks++;}
check(str_contains($result->headers['Content-Type'],'application/xml'),'XML content type');
check(str_contains($result->body,'https://sami-care.sa/services/49'),'Active category included');
check(str_contains($result->body,'published%20%26%20article'),'Article slug URL encoded');
foreach(['/booking<','/booking-receipt<','/account<','/gift-recipient<'] as $private) check(!str_contains($result->body,$private),'Private page excluded');
check(str_contains($result->body,'/home-service</loc>'),'Enabled home service included');
\App\Services\HomeService::$enabled=false;
check(!str_contains($controller()->body,'/home-service</loc>'),'Disabled home service excluded');
$xml=simplexml_load_string($result->body);
check($xml!==false,'Valid sitemap XML');
$urls=[];foreach($xml->url as $url)$urls[]=(string)$url->loc;
check(count($urls)===count(array_unique($urls)),'No duplicate canonical URLs');
echo "Sitemap: $checks checks passed.\n";
}
