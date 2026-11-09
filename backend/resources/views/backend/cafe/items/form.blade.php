@csrf
@once
    <style>
        .cafe-select-fix {
            background-position: right .75rem center;
            padding-right: 2.25rem;
        }

        [dir="rtl"] .cafe-select-fix {
            background-position: left .75rem center;
            padding-left: 2.25rem;
            padding-right: .75rem;
        }
    </style>
@endonce

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">{{ __('cafe.name') }}</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $item->name ?? '') }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ __('cafe.category') }}</label>
        <select name="cafe_category_id" class="form-select cafe-select-fix" required>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected(old('cafe_category_id', $item->cafe_category_id ?? '') == $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __('cafe.price') }}</label>
        <input type="number" min="0" step="0.01" name="price" class="form-control" value="{{ old('price', $item->price ?? '') }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __('cafe.status') }}</label>
        <select name="status" class="form-select cafe-select-fix" required>
            <option value="available" @selected(old('status', $item->status ?? 'available') === 'available')>{{ __('cafe.available') }}</option>
            <option value="unavailable" @selected(old('status', $item->status ?? '') === 'unavailable')>{{ __('cafe.unavailable') }}</option>
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __('cafe.sort_order') }}</label>
        <input type="number" min="0" name="sort_order" class="form-control" value="{{ old('sort_order', $item->sort_order ?? 0) }}">
    </div>
    <div class="col-md-8">
        <label class="form-label">{{ __('cafe.image') }}</label>
        <input type="file" name="image" class="form-control" accept="image/*">
    </div>
    @isset($item)
        <div class="col-md-4">
            <img src="{{ $item->image_url }}" alt="{{ $item->name }}" class="rounded" style="width: 96px; height: 96px; object-fit: cover;">
        </div>
    @endisset
    <div class="col-12">
        <label class="form-label">{{ __('cafe.description') }}</label>
        <textarea name="description" class="form-control" rows="4">{{ old('description', $item->description ?? '') }}</textarea>
    </div>
    <div class="col-12 d-flex gap-2">
        <button class="btn btn-primary" type="submit">{{ __('cafe.save') }}</button>
        <a class="btn btn-light" href="{{ route('backend.cafe.items.index') }}">{{ __('cafe.cancel') }}</a>
    </div>
</div>
