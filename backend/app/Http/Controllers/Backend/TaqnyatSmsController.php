<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\TaqnyatSmsService;
use Illuminate\Http\Request;

class TaqnyatSmsController extends Controller
{
    protected $taqnyatService;

    public function __construct(TaqnyatSmsService $taqnyatService)
    {
        $this->taqnyatService = $taqnyatService;
    }
    
    /**
     * عرض صفحة إعدادات SMS
     */
    public function index(Request $request)
    {
        $module_action = 'SMS Settings';
        
        return view('backend.sms-settings.index', compact('module_action'));
    }

    /**
     * إرسال رسالة تجريبية
     */
    public function sendTestMessage(Request $request)
    {
        $request->validate([
            'test_phone' => 'required|string',
        ]);

        try {
            $phone = $this->taqnyatService->validatePhoneNumber($request->test_phone);

            if (!$phone) {
                return redirect()->back()->with('error', __('messages.invalid_phone'));
            }
            $result = $this->taqnyatService->sendSms($phone, "رساله اختباريه من المطور عبدالرحمن برجاء ارسال هذا الرقم 7777 للتاكد من ان تم تفعيل البوابه بنجاح في الموقع");
            
            if ($result) {
                return redirect()->back()->with('success', __('messages.sms_sent_success'));
            } else {
                return redirect()->back()->with('error', __('messages.sms_sent_failed'));
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('messages.sms_error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * حفظ إعدادات SMS
     */
    public function store(Request $request)
    {
        $request->validate([
            'is_taqnyat_sms' => 'boolean',
            'taqnyat_api_key' => 'required_if:is_taqnyat_sms,1|string|max:500',
            'taqnyat_sender' => ['required_if:is_taqnyat_sms,1', 'string', 'max:50', 'not_regex:/\[\[/'],
            'taqnyat_welcome_message' => 'nullable|string|max:2000',
            'taqnyat_booking_created' => 'nullable|string|max:2000',
            'taqnyat_payment_service_booking' => 'nullable|string|max:2000',
            'taqnyat_payment_package_booking' => 'nullable|string|max:2000',
            'taqnyat_payment_product_order' => 'nullable|string|max:2000',
            'taqnyat_payment_gift_card' => 'nullable|string|max:2000',
            'taqnyat_gift_sender_message' => 'nullable|string|max:2000',
            'taqnyat_recipient' => 'nullable|string|max:2000',
            'taqnyat_booking_cancelled' => 'nullable|string|max:2000',
            'taqnyat_booking_reminder' => 'nullable|string|max:2000',
            'taqnyat_password_reset' => 'nullable|string|max:2000',
        ]);

        try {
            foreach ($request->all() as $key => $value) {
                if (str_starts_with($key, 'taqnyat_') || $key === 'is_taqnyat_sms') {
                    setting([$key => $value]);
                }
            }
            return redirect()->back()->with('success', __('messages.sms_settings_saved'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error',__('messages.sms_settings_error', ['error' => $e->getMessage()]));
        }
    }
}







