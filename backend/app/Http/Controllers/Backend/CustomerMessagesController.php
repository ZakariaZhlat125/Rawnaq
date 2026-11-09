<?php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\CustomerMessages;
use App\Services\TaqnyatSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CustomerMessagesController extends Controller
{
    public function index(CustomerMessages $messages)
    {
        $campaigns = DB::table('customer_message_campaigns')->orderByDesc('id')->paginate(20);
        $stats = DB::table('customer_message_deliveries')->whereIn('campaign_id', $campaigns->pluck('id'))
            ->selectRaw('campaign_id, status, COUNT(*) as total')->groupBy('campaign_id', 'status')->get()->groupBy('campaign_id');
        return view('backend.customer-messages.index', [
            'customers' => $messages->customers()->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'mobile']),
            'campaigns' => $campaigns, 'stats' => $stats,
        ]);
    }

    public function store(Request $request, CustomerMessages $messages, TaqnyatSmsService $gateway)
    {
        $data = $request->validate([
            'occasion' => 'required|string|max:255', 'message' => 'required|string|max:4000',
            'audience' => 'required|in:all,selected',
            'customer_ids' => 'required_if:audience,selected|array',
            'customer_ids.*' => ['integer', 'distinct', Rule::exists('users', 'id')],
        ]);
        $customers = $messages->customers();
        if ($data['audience'] === 'selected') {
            $customers->whereIn('users.id', $data['customer_ids']);
            if ($customers->count() !== count($data['customer_ids'])) {
                return back()->withErrors(['customer_ids' => 'يجب اختيار عملاء نشطين فقط.'])->withInput();
            }
        }
        if (! $customers->exists()) {
            return back()->withErrors(['customer_ids' => 'لا يوجد عملاء لإرسال الرسالة إليهم.'])->withInput();
        }
        $campaignId = $messages->create($data['occasion'], $data['message'], $customers);
        $messages->sendCampaign($campaignId, $gateway);
        $counts = DB::table('customer_message_deliveries')->where('campaign_id', $campaignId)
            ->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $summary = 'تم الإرسال: '.($counts['sent'] ?? 0).'، فشل الإرسال: '.($counts['failed'] ?? 0);
        if ($counts['simulated'] ?? 0) $summary .= '، محاكاة محلية: '.$counts['simulated'];
        return back()->with(($counts['failed'] ?? 0) || ($counts['simulated'] ?? 0) ? 'warning' : 'success', $summary);
    }

    public function birthdaySettings(Request $request)
    {
        $data = $request->validate(['birthday_message' => 'required|string|max:4000', 'enabled' => 'nullable|boolean']);
        Setting::set('birthday_messages_enabled', $request->boolean('enabled') ? '1' : '0');
        Setting::set('birthday_message', $data['birthday_message']);
        return back()->with('success', 'تم حفظ إعدادات رسائل عيد الميلاد.');
    }
}
