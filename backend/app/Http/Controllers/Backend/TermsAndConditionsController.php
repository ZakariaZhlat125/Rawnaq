<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Term;

class TermsAndConditionsController extends Controller
{
    public function index(){
        $term = Term::all();

        return view('backend.TermsAndConditions.index_datatable');
    }

        public function terms_api(Request $request)
        {
            $terms = Term::all()->map(function ($term, $index) {
                return [
                    'id' => $term->id,
                    'order' => $index + 1,

                    'title' => [
                        'ar' => $term->title['ar'] ?? '',
                        'en' => $term->title['en'] ?? '',
                    ],

                    'points' => [
                        'ar' => $term->points['ar'] ?? [],
                        'en' => $term->points['en'] ?? [],
                    ],
                ];
            });

            return response()->json([
                'status' => true,
                'message' => __('Terms fetched successfully'),
                'data' => $terms
            ]);
        }

    public function store(Request $request){
        $request->validate([
            'title_ar' => 'required|max:255',
            'title_en' => 'required|max:255',
            'points'   => 'required|array',
        ]);
        
         $title = [
            'ar' => $request->input('title_ar'),
            'en' => $request->input('title_en'),
        ];
        $points = [];
        foreach ($request->points as $point) {
            $points['ar'][] = $point['ar'];
            $points['en'][] = $point['en'];
        }
        $term = Term::create([
            'title'     => $title,
            'points'    => $points,
        ]);
        return redirect()->back()->with('success', 'تم حفظ السياسة بنجاح ✅');
    }
    
    public function destroy($id){
        $term = Term::findOrFail($id);
        $term->delete();
        return redirect()->back()->with('success', 'تم حذف السياسة بنجاح ✅');
    }
    
    public function update(Request $request, $id){
        $request->validate([
            'title_ar' => 'required|max:255',
            'title_en' => 'required|max:255',
            'points'   => 'required|array',
        ]);
         $title = [
            'ar' => $request->input('title_ar'),
            'en' => $request->input('title_en'),
        ];
        $points = [];
        foreach ($request->points as $point) {
            $points['ar'][] = $point['ar'];
            $points['en'][] = $point['en'];
        }
        $term = Term::findOrFail($id);
        $term->update([
            'title'     => $title,
            'points'    => $points,
        ]);
        return redirect()->back()->with('success', 'تم حفظ السياسة بنجاح ✅');
    }
}







