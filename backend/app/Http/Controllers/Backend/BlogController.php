<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class BlogController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view_blog')->only(['index']);
        $this->middleware('permission:add_blog')->only(['create', 'store']);
        $this->middleware('permission:edit_blog')->only(['edit', 'update']);
        $this->middleware('permission:delete_blog')->only(['destroy']);
    }

    public function index()
    {
        $blogs = Blog::query()
            ->latest('published_at')
            ->latest('id')
            ->paginate(15);

        return view('backend.blogs.index', compact('blogs'));
    }

    public function create()
    {
        return view('backend.blogs.create');
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $data['slug'] = Blog::makeUniqueSlug($data['title']);
        $data['status'] = $request->boolean('status');
        $data['published_at'] = $data['published_at'] ?: now();
        $data['image'] = $this->storeImage($request);

        Blog::create($data);

        return redirect()->route('backend.blogs.index')->with('success', __('blog.saved_successfully'));
    }

    public function edit(Blog $blog)
    {
        return view('backend.blogs.edit', compact('blog'));
    }

    public function update(Request $request, Blog $blog)
    {
        $data = $this->validatedData($request);
        $data['slug'] = Blog::makeUniqueSlug($data['title'], $blog->id);
        $data['status'] = $request->boolean('status');
        $data['published_at'] = $data['published_at'] ?: $blog->published_at;

        $image = $this->storeImage($request, $blog);
        if ($image) {
            $data['image'] = $image;
        }

        $blog->update($data);

        return redirect()->route('backend.blogs.index')->with('success', __('blog.saved_successfully'));
    }

    public function destroy(Blog $blog)
    {
        $blog->delete();

        return redirect()->route('backend.blogs.index')->with('success', __('blog.deleted_successfully'));
    }

    protected function validatedData(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:4096'],
            'published_at' => ['nullable', 'date'],
        ]);
    }

    protected function storeImage(Request $request, ?Blog $blog = null): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        if (! File::exists(public_path('uploads/blogs'))) {
            File::makeDirectory(public_path('uploads/blogs'), 0777, true);
        }

        if ($blog && $blog->image && File::exists(public_path($blog->image))) {
            File::delete(public_path($blog->image));
        }

        $image = $request->file('image');
        $imageName = time().'_'.uniqid().'.'.$image->extension();
        $image->move(public_path('uploads/blogs'), $imageName);

        return 'uploads/blogs/'.$imageName;
    }
}
