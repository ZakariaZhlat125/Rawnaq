@csrf

<div class="row g-3">
    <div class="col-md-8">
        <label class="form-label">{{ __('blog.title_field') }}</label>
        <input type="text" name="title" class="form-control" value="{{ old('title', $blog->title ?? '') }}" required>
    </div>

    <div class="col-md-4">
        <label class="form-label">{{ __('blog.published_at') }}</label>
        <input type="datetime-local" name="published_at" class="form-control" value="{{ old('published_at', isset($blog) && $blog->published_at ? $blog->published_at->format('Y-m-d\TH:i') : '') }}">
    </div>

    <div class="col-md-8">
        <label class="form-label">{{ __('blog.image') }}</label>
        <input type="file" name="image" class="form-control" accept="image/*">
    </div>

    <div class="col-md-4">
        <label class="form-label d-block">{{ __('blog.status') }}</label>
        <div class="form-check form-switch mt-2">
            <input type="checkbox" name="status" value="1" class="form-check-input" id="blog-status" @checked(old('status', $blog->status ?? true))>
            <label class="form-check-label" for="blog-status">{{ __('blog.active') }}</label>
        </div>
    </div>

    @isset($blog)
        @if ($blog->image)
            <div class="col-md-3">
                <img src="{{ $blog->image_url }}" alt="{{ $blog->title }}" class="rounded" style="width: 120px; height: 120px; object-fit: cover;">
            </div>
        @endif
    @endisset

    <div class="col-12">
        <label class="form-label">{{ __('blog.excerpt') }}</label>
        <textarea name="excerpt" class="form-control" rows="3" maxlength="500">{{ old('excerpt', $blog->excerpt ?? '') }}</textarea>
    </div>

    <div class="col-12">
        <label class="form-label">{{ __('blog.content') }}</label>
        <textarea name="content" class="form-control" rows="12" required>{{ old('content', $blog->content ?? '') }}</textarea>
    </div>

    <div class="col-12 d-flex gap-2">
        <button class="btn btn-primary" type="submit">{{ __('blog.save') }}</button>
        <a class="btn btn-light" href="{{ route('backend.blogs.index') }}">{{ __('blog.cancel') }}</a>
    </div>
</div>
