<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\CenterInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class CenterInfoController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:manage_center_info');
    }

    public function index()
    {
        $centerInfo = CenterInfo::current();

        return view('backend.center-info.index', compact('centerInfo'));
    }

    public function update(Request $request)
    {
        $centerInfo = CenterInfo::current();

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'about' => ['nullable', 'string'],
            'support_phone' => ['nullable', 'string', 'max:50'],
            'customer_service_phone' => ['nullable', 'string', 'max:50'],
            'whatsapp_url' => ['nullable', 'string', 'max:255'],
            'instagram_url' => ['nullable', 'string', 'max:255'],
            'facebook_url' => ['nullable', 'string', 'max:255'],
            'x_url' => ['nullable', 'string', 'max:255'],
            'tiktok_url' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'map_url' => ['nullable', 'string', 'max:255'],
            'services_info' => ['nullable', 'string'],
            'extra_info' => ['nullable', 'string'],
            'cover_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:4096'],
            'profile_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:4096'],
            'status' => ['nullable', 'boolean'],
        ]);

        $data['status'] = $request->boolean('status');
        $coverImage = $this->storeImage($request, $centerInfo, 'cover_image');
        $profileImage = $this->storeImage($request, $centerInfo, 'profile_image');

        if ($coverImage) {
            $data['cover_image'] = $coverImage;
        }

        if ($profileImage) {
            $data['profile_image'] = $profileImage;
        }

        $centerInfo->update($data);

        return redirect()->route('backend.center-info.index')->with('success', __('center_info.saved_successfully'));
    }

    protected function storeImage(Request $request, CenterInfo $centerInfo, string $field): ?string
    {
        if (! $request->hasFile($field)) {
            return null;
        }

        if (! File::exists(public_path('uploads/center-info'))) {
            File::makeDirectory(public_path('uploads/center-info'), 0777, true);
        }

        if ($centerInfo->{$field} && File::exists(public_path($centerInfo->{$field}))) {
            File::delete(public_path($centerInfo->{$field}));
        }

        $image = $request->file($field);
        $imageName = time().'_'.uniqid().'.'.$image->extension();
        $image->move(public_path('uploads/center-info'), $imageName);

        return 'uploads/center-info/'.$imageName;
    }
}
