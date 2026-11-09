<?php
namespace App\Http\Controllers\Backend;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\SitemapController;
use Illuminate\Support\Facades\Storage;

class SitemapManagerController extends Controller
{
    private const FILE = 'seo/sitemap.xml';
    public function index()
    {
        $disk=Storage::disk('local');
        $generatedAt=$disk->exists(self::FILE) ? $disk->lastModified(self::FILE) : null;
        return view('backend.frontend-seo.sitemap',compact('generatedAt'));
    }
    public function generate(SitemapController $generator)
    {
        $xml=$generator()->getContent();
        $disk=Storage::disk('local');
        $temporary='seo/sitemap-'.bin2hex(random_bytes(8)).'.xml';
        abort_unless($disk->put($temporary,$xml),500,'تعذر حفظ ملف خريطة الموقع.');
        abort_unless($disk->move($temporary,self::FILE),500,'تعذر تحديث ملف خريطة الموقع.');
        return back()->with('success','تم توليد وتحديث ملف sitemap من المحتوى الحالي. يمكنك تحميله الآن.');
    }
    public function download()
    {
        abort_unless(Storage::disk('local')->exists(self::FILE),404);
        return Storage::disk('local')->download(self::FILE,'sitemap.xml',['Content-Type'=>'application/xml; charset=UTF-8','Cache-Control'=>'no-store']);
    }
}
