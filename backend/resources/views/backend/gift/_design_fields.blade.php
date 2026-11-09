@php $d = $d ?? null; $occasions = ['general', 'birthday', 'eid', 'ramadan', 'national_day', 'wedding', 'christmas', 'thanks']; $t = fn ($k) => __('gift_system.admin.'.$k); @endphp
        <div class="col-md-3"><label class="form-label small">{{ $t('name_ar') }}</label><input name="name_ar" value="{{ $d?->name_ar }}" class="form-control form-control-sm" required></div>
        <div class="col-md-3"><label class="form-label small">{{ $t('name_en') }}</label><input name="name_en" value="{{ $d?->name_en }}" class="form-control form-control-sm"></div>
        <div class="col-md-2">
            <label class="form-label small">{{ $t('occasion') }}</label>
            <select name="occasion" class="form-select form-select-sm">
                @foreach ($occasions as $o)<option value="{{ $o }}" @selected(($d?->occasion ?? 'general') === $o)>{{ __('gift_system.occasion.'.$o) }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-2"><label class="form-label small">{{ $t('foreground') }}</label><input type="color" name="foreground" value="{{ $d?->foreground ?? '#5FD3C0' }}" class="form-control form-control-sm form-control-color w-100"></div>
        <div class="col-md-1"><label class="form-label small">{{ $t('icon') }}</label><input name="icon" value="{{ $d?->icon }}" maxlength="4" class="form-control form-control-sm"></div>
        <div class="col-md-1"><label class="form-label small">{{ $t('sort_order') }}</label><input type="number" name="sort_order" value="{{ $d?->sort_order ?? 0 }}" min="0" class="form-control form-control-sm"></div>
        <div class="col-md-6"><label class="form-label small">{{ $t('background') }}</label><input name="background" value="{{ $d?->background ?? 'linear-gradient(150deg,#1D1810,#070604 72%)' }}" class="form-control form-control-sm" dir="ltr" required></div>
        <div class="col-md-3"><label class="form-label small">{{ $t('image') }}</label><input type="file" name="image" accept="image/png,image/jpeg,image/webp" class="form-control form-control-sm"></div>
        <div class="col-md-3 d-flex align-items-end gap-3">
            <label class="form-check mb-1"><input type="checkbox" name="is_active" value="1" class="form-check-input" @checked($d?->is_active ?? true)> {{ $t('active') }}</label>
            @if ($d?->image_path)<label class="form-check mb-1"><input type="checkbox" name="remove_image" value="1" class="form-check-input"> {{ $t('remove_image') }}</label>@endif
        </div>
    
