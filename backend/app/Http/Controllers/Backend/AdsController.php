<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ad;

class AdsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view_ads')->only(['index']);
        $this->middleware('permission:store_ads')->only(['store']);
        $this->middleware('permission:delete_ads')->only(['destroy']);
        $this->middleware('permission:update_ads_status')->only(['updateStatus']);
        $this->middleware('permission:update_ads_link')->only(['update_link']);
    }

    
    public function index(){   
        $home = Ad::where('page' , 'home')->get();     
        $services = Ad::where('page' , 'services')->get();     
        $packages = Ad::where('page' , 'packages')->get();     
        $shop = Ad::where('page' , 'shop')->get();     
        $gift = Ad::where('page' , 'gift_page')->get();     
        return view('backend.Ads.index_datatable' , compact('home' , 'services' , 'packages' , 'shop' , 'gift'));
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'page'   => 'required',
            'images' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);
        
        $img = $request->file('images');
        
        if (!file_exists(public_path('uploads/ads'))) {
            mkdir(public_path('uploads/ads'), 0777, true);
        }
    
        if ($request->hasFile('images')) {
            $imageName = time() . '_' . uniqid() . '.' . $img->extension();
            $img->move(public_path('uploads/ads'), $imageName);
            Ad::create([
                'page'  => $request->page,
                'status'=> 1,
                'image' => 'uploads/ads/' . $imageName,
            ]);
        }
        
        return redirect()->back()->with('success', 'تم حفظ الصور بنجاح ✅');
    }
    
    public function destroy($id){
        $Ad = Ad::findOrFail($id);
        $Ad->delete();
        
        return response()->json(['success' => true]);
    }
    
    public function updateStatus(Request $request, $id){
        $ad = Ad::findOrFail($id);
        $ad->status = $request->status;
        $ad->save();
    
        return response()->json(['success' => true]);
    }

    public function update_link(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:ads,id',
            'link' => 'nullable|url|max:255',
        ]);

        $ad = Ad::findOrFail($request->id);
        $ad->link = $request->link;
        $ad->save();

        return response()->json([
            'success' => true,
            'message' => __('messagess.link_updated')
        ]);
    }

}







