@csrf
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">{{ __('cafe.name') }}</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $category->name ?? '') }}" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">{{ __('cafe.sort_order') }}</label>
        <input type="number" min="0" name="sort_order" class="form-control" value="{{ old('sort_order', $category->sort_order ?? 0) }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">{{ __('minimum duration') }}</label>
        <input type="number" min="0" name="durMin" class="form-control" value="{{ old('durMin', $category->durMin ?? 0) }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">{{ __('maximum duration') }}</label>
        <input type="number" min="0" name="durMax" class="form-control" value="{{ old('durMax', $category->durMax ?? 0) }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">{{ __('price from') }}</label>
        <input type="number" min="0" name="price_from" class="form-control" value="{{ old('price_from', $category->price_from ?? 0) }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">{{ __('image') }}</label>
        <input type="file" name="image" class="form-control">
    </div>
    <div class="col-md-3 d-flex align-items-end">
        <div class="form-check form-switch mb-2">
            <input type="hidden" name="status" value="0">
            <input class="form-check-input" type="checkbox" name="status" value="1" id="status" @checked(old('status', $category->status ?? true))>
            <label class="form-check-label" for="status">{{ __('cafe.active') }}</label>
        </div>
    </div>
    <div class="col-12">
        <label class="form-label">{{ __('cafe.description') }}</label>
        <textarea name="description" class="form-control" rows="4">{{ old('description', $category->description ?? '') }}</textarea>
    </div>
    <div class="col-12 d-flex gap-2">
        <button class="btn btn-primary" type="submit">{{ __('cafe.save') }}</button>
        <a class="btn btn-light" href="{{ route('backend.cafe.categories.index') }}">{{ __('cafe.cancel') }}</a>
    </div>
</div>
