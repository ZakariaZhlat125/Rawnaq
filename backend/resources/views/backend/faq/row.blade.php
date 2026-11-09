<div class="card faq-editor-row"><div class="card-body">
    <div class="row g-3">
    @foreach(['ar' => 'العربية', 'en' => 'English'] as $locale => $label)
        <div class="col-md-6" dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}">
            <label>{{ __('faq.question') }} ({{ $label }})<input class="form-control" name="items[{{ $index }}][question][{{ $locale }}]" value="{{ $row['question'][$locale] ?? '' }}" maxlength="500" {{ $locale === 'ar' ? 'required' : '' }}></label>
            <label class="d-block mt-2">{{ __('faq.answer') }} ({{ $label }})<textarea class="form-control" name="items[{{ $index }}][answer][{{ $locale }}]" rows="4" maxlength="5000" {{ $locale === 'ar' ? 'required' : '' }}>{{ $row['answer'][$locale] ?? '' }}</textarea></label>
        </div>
    @endforeach
    </div>
    <div class="d-flex flex-wrap gap-2 mt-3 align-items-center">
        <input type="hidden" name="items[{{ $index }}][active]" value="0">
        <label><input type="checkbox" name="items[{{ $index }}][active]" value="1" @checked($row['active'] ?? true)> {{ __('faq.active') }}</label>
        <button type="button" class="btn btn-outline-secondary" data-faq-up>{{ __('faq.up') }}</button>
        <button type="button" class="btn btn-outline-secondary" data-faq-down>{{ __('faq.down') }}</button>
        <button type="button" class="btn btn-outline-danger" data-faq-remove>{{ __('faq.remove') }}</button>
    </div>
</div></div>
