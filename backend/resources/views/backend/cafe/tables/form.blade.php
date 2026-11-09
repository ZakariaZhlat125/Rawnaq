@csrf
<div class="row g-3">
    <div class="col-md-8">
        <label class="form-label">{{ __('cafe.table_name') }}</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $table->name ?? '') }}" required>
    </div>
    <div class="col-md-4 d-flex align-items-end">
        <div class="form-check form-switch mb-2">
            <input type="hidden" name="status" value="0">
            <input class="form-check-input" type="checkbox" name="status" value="1" id="status" @checked(old('status', $table->status ?? true))>
            <label class="form-check-label" for="status">{{ __('cafe.active') }}</label>
        </div>
    </div>
    <div class="col-12 d-flex gap-2">
        <button class="btn btn-primary" type="submit">{{ __('cafe.save') }}</button>
        <a class="btn btn-light" href="{{ route('backend.cafe.tables.index') }}">{{ __('cafe.cancel') }}</a>
    </div>
</div>
