<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\File;
class SettingController extends Controller
{
    public function __construct()
    {
        // Page Title
        $this->module_title = 'settings.title';

        // module name
        $this->module_name = 'settings';

        // module icon
        $this->module_icon = 'fas fa-cogs';

        $this->global_booking = false;

        view()->share([
            'module_title' => $this->module_title,
            'module_name' => $this->module_name,
            'module_icon' => $this->module_icon,
            'global_booking' => $this->global_booking,
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $module_action = 'List';

        return view('backend.settings.index', compact('module_action'));
    }

    public function index_data(Request $request)
    {
        if (! isset($request->fields)) {
            return response()->json($data, 404);
        }

        $fields = explode(',', $request->fields);

        $data = Setting::whereIn('name', $fields)->get();

        $newData = [];

        foreach ($fields as $field) {
            $newData[$field] = setting($field);
            if (in_array($field, ['logo', 'mini_logo', 'mini_logo', 'dark_logo', 'dark_mini_logo', 'favicon'])) {
                $newData[$field] = asset(setting($field));
            }
        }

        $newData['quick_booking_url'] = route('app.quick-booking');

        return response()->json($newData, 200);
    }

    public function store(Request $request)
    {
        if ($request->input('settings_section') === 'whatsapp') {
            $number = strtr(trim((string) $request->input('whatsapp_number', '')), [
                '٠'=>'0', '١'=>'1', '٢'=>'2', '٣'=>'3', '٤'=>'4', '٥'=>'5', '٦'=>'6', '٧'=>'7', '٨'=>'8', '٩'=>'9',
                '۰'=>'0', '۱'=>'1', '۲'=>'2', '۳'=>'3', '۴'=>'4', '۵'=>'5', '۶'=>'6', '۷'=>'7', '۸'=>'8', '۹'=>'9',
            ]);
            $number = preg_replace('/[+ ()-]/', '', $number);
            $request->merge(['whatsapp_number' => preg_replace('/^00/', '', $number)]);
            $validated = $request->validate([
                'whatsapp_number' => ['nullable', 'regex:/^[1-9][0-9]{6,14}$/'],
            ]);
            Setting::add('whatsapp_number', $validated['whatsapp_number'] ?? '', 'string');
            Setting::flushCache();
            return redirect()->back()->with('status', 'تم حفظ رقم الواتساب');
        }
        $data = $request->all();
        if ($request->hasFile('json_file')) {
            $file = $request->file('json_file');
            if ($file) {
                $fileName = $file->getClientOriginalName();
                $directoryPath = storage_path('app/data');

                if (!File::isDirectory($directoryPath)) {
                    File::makeDirectory($directoryPath, 0777, true, true);
                }

                $files = File::files($directoryPath);

                foreach ($files as $existingFile) {
                    if (strtolower($existingFile->getExtension()) === 'json') {
                        File::delete($existingFile->getPathname());
                    }
                }
                $file->move($directoryPath, $fileName);
            }
        }

        unset($data['json_file']);
        if ($request->wantsJson()) {
            $rules = Setting::getSelectedValidationRules(array_keys($request->all()));
        } else {
            $rules = Setting::getValidationRules();
        }

        $data = $this->validate($request, $rules);

        $validSettings = array_keys($rules);

        foreach ($data as $key => $val) {
            if (in_array($key, $validSettings)) {
                $mimeTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/vnd.microsoft.icon'];
                if (gettype($val) == 'object') {
                    if ($val->getType() == 'file' && in_array($val->getmimeType(), $mimeTypes)) {
                        $setting = Setting::add($key, '', Setting::getDataType($key));
                        $mediaItems = $setting->addMedia($val)->toMediaCollection($key);
                        $setting->update(['val' => $mediaItems->getUrl()]);
                    }
                } else {
                    $setting = Setting::add($key, $val, Setting::getDataType($key));
                }
            }
        }
        if ($request->wantsJson()) {
            $message = __('settings.save_setting');

            return response()->json(['message' => $message, 'status' => true], 200);
        } else {
            return redirect()->back()->with('status', __('messages.setting_save'));
        }
    }

    public function clear_cache()
    {
        Setting::flushCache();

        $message = __('messages.cache_cleard');

        return response()->json(['message' => $message, 'status' => true], 200);
    }

    public function verify_email(Request $request)
    {
        $mailObject = $request->all();
        try {
            \Config::set('mail', $mailObject);
            Mail::raw('This is a smtp mail varification test mail!', function ($message) use ($mailObject) {
                $message->to($mailObject['email'])->subject('Test Email');
            });

            return response()->json(['message' => __('Verification Successful'), 'status' => true], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => __('Verification Failed'), 'status' => false], 500);
        }
    }
}
