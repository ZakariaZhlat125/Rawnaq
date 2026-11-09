<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Auth\Trait\AuthTrait;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    use AuthTrait;

    /**
     * عرض صفحة تسجيل الدخول
     */
    public function create()
    {
        return view('auth.login');
    }

    /**
     * معالجة تسجيل الدخول
     */
   public function store(Request $request)
{
    $user = $this->loginTrait($request);

    if (!$user) {
        return back()->withErrors([
            'username' => 'These credentials do not match our records.',
        ])->onlyInput('username');
    }

    // التعامل مع جلسات الحجز المؤقتة
    if (\App\Services\EmployeeAccess::limited(auth()->user())) return redirect()->route('backend.employee.bookings');

    if ($request->session()->has('package_booking')) {
        return redirect()->route('package.booking.complete');
    }

    if ($request->session()->has('temp_booking')) {
        $temp = $request->session()->get('temp_booking');
        $data = $temp['data'];
        $btn_value = $temp['btn_value'];

        // إكمال الحجز المؤقت
        if (method_exists($this, 'complateTempBookings')) {
            $this->complateTempBookings($data, $btn_value);
        }

        session()->forget('temp_booking');

        if ($btn_value === 'cart') {
            return redirect()->to('/cart')->with('success', 'تم تحويل الحجز بنجاح');
        } elseif ($btn_value === 'payment') {
            return redirect()->to('/payment?is_buy_now=1')->with('success', 'تم تحويل الحجز بنجاح');
        }
    }


    // **التوجيه النهائي بعد تسجيل الدخول**
    // شرط لتوجيه الموظفين لصفحاتهم الخاصة
    if (auth()->user()->is_employee ?? false) {
        return redirect()->to('/app/bookings')->with('success', 'تم تسجيل الدخول بنجاح');
    }

    // المستخدم العادي يذهب لأي صفحة عامة (لا تظهر له 403)
    return redirect()->to(
        $request->get('redirect') === 'gift'
            ? route('gift.page')
            : route('frontend.home')
    )->with('success', 'تم تسجيل الدخول بنجاح');
}

    /**
     * تسجيل الخروج
     */
    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/signin')->with('success', 'تم تسجيل الخروج بنجاح');
    }
}
