<?php
namespace App\Http\Controllers\Backend;
use App\Http\Controllers\Controller;
use App\Services\ServiceRepeatRewards;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Service\Models\Service;
class ServiceRepeatController extends Controller {
    public function __construct() {
        $this->middleware('permission:view_loyalty')->only('index');
        $this->middleware('permission:store_loyalty')->only('store','toggle');
    }
    public function index() {
        abort_unless(app(ServiceRepeatRewards::class)->available(),503,__('service_repeat.migrate'));
        $services = Service::orderBy('id')->get();
        $rules = DB::table('service_repeat_rules')->get()->keyBy('service_id');
        $rewards = DB::table('service_repeat_rewards as r')->leftJoin('users as u','u.id','=','r.user_id')
            ->select('r.*','u.first_name','u.last_name')->orderByDesc('r.id')->paginate(30);
        $enabled = app(ServiceRepeatRewards::class)->enabled();
        return view('backend.service-repeat.index',compact('services','rules','rewards','enabled'));
    }
    public function toggle(Request $request) {
        abort_unless(app(ServiceRepeatRewards::class)->available(),503,__('service_repeat.migrate'));
        $data = $request->validate(['enabled'=>['required','boolean']]);
        DB::transaction(function() use($data) {
            $settings = DB::table('service_repeat_settings')->where('id',1)->lockForUpdate()->first();
            DB::table('service_repeat_settings')->where('id',1)->update(['enabled'=>(bool)$data['enabled'],
                'starts_at'=>$settings->starts_at ?: ($data['enabled'] ? now() : null)]);
        });
        return back()->with('success',__('service_repeat.saved'));
    }
    public function store(Request $request) {
        abort_unless(app(ServiceRepeatRewards::class)->available(),503,__('service_repeat.migrate'));
        $data = $request->validate(['service_id'=>['required','integer','exists:services,id'], 'enabled'=>['required','boolean'], 'required_visits'=>['required','integer','between:1,1000']]);
        DB::transaction(function() use($data) {
            // Serialize concurrent edits even when the rule does not exist yet.
            Service::whereKey($data['service_id'])->lockForUpdate()->firstOrFail();
            $rule = DB::table('service_repeat_rules')->where('service_id',$data['service_id'])->first();
            $values = ['enabled'=>(bool)$data['enabled'],'required_visits'=>$data['required_visits'],'updated_at'=>now()];
            if (!$rule || !$rule->starts_at) $values['starts_at'] = $data['enabled'] ? now() : null;
            if (!$rule) $values['created_at'] = now();
            DB::table('service_repeat_rules')->updateOrInsert(['service_id'=>$data['service_id']],$values);
        });
        return back()->with('success',__('service_repeat.saved'));
    }
}
