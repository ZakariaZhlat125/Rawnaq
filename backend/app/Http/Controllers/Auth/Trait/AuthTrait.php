<?php

namespace App\Http\Controllers\Auth\Trait;

use App\Events\Auth\UserLoginSuccess;
use App\Events\Frontend\UserRegistered;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;

trait AuthTrait
{
    protected function loginTrait($request)
    {
        $request->validate([
            'username' => ['required', 'string', 'max:191'],
            'mobile'   => ['required', 'string', 'max:20'],
        ]);

        $name   = $request->username;
        $mobile = $request->mobile;

        // البحث عن المستخدم
        $user = User::where('username', $name)
                    ->where('mobile', $mobile)
                    ->first();

        if ($user && $user->status == 1) {
            Auth::login($user);
            $request->session()->regenerate();
            event(new UserLoginSuccess($request, $user));
            return $user;
        }

        return false;
    }


    protected function registerTrait($request, $model = null)
    {
        return 0;
        $request->validate([
            'username' => ['required', 'string', 'max:191'],
            'mobile' => ['required', 'string', 'max:20', 'unique:users'],
        ]);

        $arr = [
            'username' => $request->username,
            'mobile' => $request->mobile,
            'gender' => 'female', // يمكن تغييره لاحقاً
        ];

        $user = User::create($arr);

        $user->syncRoles(['user']);

        \Artisan::call('cache:clear');

        $user->save();

        event(new Registered($user));
        event(new UserRegistered($user));

        return $user;
    }
}
