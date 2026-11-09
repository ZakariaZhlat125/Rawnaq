<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use Illuminate\Support\Collection;

class AdController extends Controller
{
    public function index()
    {
        $pages = ['services', 'packages', 'shop'];

        $ads = Ad::query()
            ->where('status', 1)
            ->whereIn('page', $pages)
            ->orderByDesc('id')
            ->get()
            ->groupBy('page');

        return response()->json([
            'status' => true,
            'data' => collect($pages)->mapWithKeys(function (string $page) use ($ads) {
                return [$page => $this->transformAds($ads->get($page, collect()), $page)];
            }),
        ]);
    }

    private function transformAds(Collection $ads, string $page): array
    {
        return $ads->map(function (Ad $ad) use ($page) {
            return [
                'id' => $ad->id,
                'page' => $page,
                'image' => $ad->image,
                'image_url' => $this->resolveImageUrl($ad->image),
                'link' => $ad->link,
                'status' => (bool) $ad->status,
            ];
        })->values()->all();
    }

    private function resolveImageUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        return preg_match('/^https?:\/\//i', $path) ? $path : asset($path);
    }
}
