@extends('backend.layouts.app')

@section('title')
    بنرات العروض
@endsection

@push('after-styles')
<style>
    /* ========== Page Header ========== */
    .offer-page-header {
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
        border-radius: 16px;
        padding: 28px 32px;
        margin-bottom: 28px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: 0 8px 32px rgba(0,0,0,0.15);
    }
    .offer-page-header h2 {
        color: #BF9456;
        margin: 0;
        font-size: 1.6rem;
        font-weight: 700;
        border: none;
        padding: 0;
    }
    .offer-page-header p {
        color: rgba(255,255,255,0.7);
        margin: 4px 0 0;
        font-size: 0.9rem;
    }
    .btn-add-offer {
        background: linear-gradient(135deg, #BF9456, #d4a96a);
        color: #fff;
        border: none;
        padding: 12px 28px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 15px rgba(191,148,86,0.4);
    }
    .btn-add-offer:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(191,148,86,0.5);
        background: linear-gradient(135deg, #d4a96a, #BF9456);
        color: #fff;
    }

    /* ========== Form Card ========== */
    .offer-form-card {
        background: #fff;
        border-radius: 16px;
        padding: 32px;
        margin-bottom: 28px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        border: 1px solid #f0ece4;
        display: none;
    }
    .offer-form-card.show {
        display: block;
        animation: slideDown 0.35s ease;
    }
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-15px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .offer-form-card h3 {
        color: #1a1a2e;
        font-size: 1.3rem;
        font-weight: 700;
        margin-bottom: 24px;
        padding-bottom: 12px;
        border-bottom: 2px solid #BF9456;
        display: inline-block;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }
    .form-grid .full-width {
        grid-column: span 2;
    }
    .form-group label {
        display: block;
        font-weight: 600;
        color: #333;
        margin-bottom: 6px;
        font-size: 0.9rem;
    }
    .form-group label .required {
        color: #e74c3c;
    }
    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 10px 14px;
        border: 1.5px solid #e0dcd4;
        border-radius: 10px;
        font-size: 0.95rem;
        transition: border-color 0.3s, box-shadow 0.3s;
        background: #fafaf8;
    }
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        border-color: #BF9456;
        box-shadow: 0 0 0 3px rgba(191,148,86,0.15);
        outline: none;
    }
    .form-group textarea {
        resize: vertical;
        min-height: 80px;
    }
    .form-actions {
        display: flex;
        gap: 12px;
        justify-content: flex-end;
        margin-top: 24px;
        padding-top: 20px;
        border-top: 1px solid #eee;
    }
    .btn-save {
        background: linear-gradient(135deg, #BF9456, #d4a96a);
        color: #fff;
        border: none;
        padding: 12px 32px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .btn-save:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 15px rgba(191,148,86,0.4);
    }
    .btn-cancel {
        background: #f5f5f5;
        color: #666;
        border: 1px solid #ddd;
        padding: 12px 28px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .btn-cancel:hover {
        background: #eee;
    }

    /* ========== Color Picker ========== */
    .color-picker-wrapper {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .color-picker-wrapper input[type="color"] {
        width: 48px;
        height: 48px;
        padding: 2px;
        border-radius: 10px;
        cursor: pointer;
        border: 2px solid #e0dcd4;
    }
    .color-picker-wrapper .color-hex {
        flex: 1;
    }

    /* ========== Image Upload ========== */
    .image-upload-zone {
        border: 2px dashed #d0cbc2;
        border-radius: 12px;
        padding: 24px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        background: #fafaf8;
        position: relative;
    }
    .image-upload-zone:hover {
        border-color: #BF9456;
        background: #fdf9f3;
    }
    .image-upload-zone i {
        font-size: 2rem;
        color: #BF9456;
        margin-bottom: 8px;
    }
    .image-upload-zone p {
        color: #888;
        margin: 0;
        font-size: 0.85rem;
    }
    .image-upload-zone input {
        position: absolute;
        inset: 0;
        opacity: 0;
        cursor: pointer;
    }
    .image-upload-zone .preview-img {
        max-width: 200px;
        max-height: 120px;
        object-fit: cover;
        border-radius: 8px;
        margin-top: 10px;
    }

    /* ========== Offers Grid ========== */
    .offers-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(480px, 1fr));
        gap: 24px;
    }

    /* ========== Offer Card ========== */
    .offer-card {
        background: #fff;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        border: 1px solid #f0ece4;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .offer-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 30px rgba(0,0,0,0.1);
    }

    /* Banner Preview */
    .offer-banner-preview {
        position: relative;
        padding: 24px 28px;
        min-height: 130px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        overflow: hidden;
        border-radius: 12px;
        margin: 12px;
        direction: rtl;
    }
    .offer-banner-preview .banner-bg-image {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        z-index: 0;
    }
    .offer-banner-preview .banner-overlay {
        position: absolute;
        inset: 0;
        background: rgba(0,0,0,0.5);
        z-index: 1;
    }
    .offer-banner-preview .banner-content {
        position: relative;
        z-index: 2;
        flex: 1;
    }
    .offer-banner-preview .banner-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: rgba(191,148,86,0.2);
        color: #BF9456;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        margin-bottom: 8px;
    }
    .offer-banner-preview .banner-title {
        color: #fff;
        font-size: 1.4rem;
        font-weight: 700;
        margin: 4px 0;
    }
    .offer-banner-preview .banner-desc {
        color: rgba(255,255,255,0.8);
        font-size: 0.85rem;
        margin: 0;
    }
    .offer-banner-preview .banner-cta {
        display: inline-block;
        background: linear-gradient(135deg, #BF9456, #d4a96a);
        color: #fff;
        padding: 10px 24px;
        border-radius: 25px;
        font-weight: 600;
        font-size: 0.85rem;
        margin-top: 12px;
        text-decoration: none;
    }
    .offer-banner-preview .discount-circle {
        position: relative;
        z-index: 2;
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: linear-gradient(135deg, #BF9456, #d4a96a);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: #fff;
        box-shadow: 0 4px 15px rgba(191,148,86,0.4);
        flex-shrink: 0;
        margin-right: 20px;
    }
    .discount-circle .discount-value {
        font-size: 1.5rem;
        font-weight: 800;
        line-height: 1;
    }
    .discount-circle .discount-label {
        font-size: 0.65rem;
        font-weight: 600;
        opacity: 0.9;
    }

    /* Card Details */
    .offer-card-details {
        padding: 16px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-top: 1px solid #f0ece4;
    }
    .offer-meta {
        display: flex;
        gap: 16px;
        align-items: center;
        flex-wrap: wrap;
    }
    .offer-meta-item {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.8rem;
        color: #666;
    }
    .offer-meta-item i {
        color: #BF9456;
        font-size: 0.85rem;
    }
    .offer-meta-item .status-badge {
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .status-badge.active {
        background: #e8f5e9;
        color: #2e7d32;
    }
    .status-badge.inactive {
        background: #fce4ec;
        color: #c62828;
    }
    .status-badge.expired {
        background: #fff3e0;
        color: #e65100;
    }

    .offer-actions {
        display: flex;
        gap: 8px;
    }
    .offer-actions button {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.25s ease;
        font-size: 0.85rem;
    }
    .btn-edit-offer {
        background: #e8f0fe;
        color: #1a73e8;
    }
    .btn-edit-offer:hover {
        background: #1a73e8;
        color: #fff;
    }
    .btn-toggle-status {
        background: #e8f5e9;
        color: #2e7d32;
    }
    .btn-toggle-status.inactive {
        background: #fce4ec;
        color: #c62828;
    }
    .btn-toggle-status:hover {
        background: #2e7d32;
        color: #fff;
    }
    .btn-toggle-status.inactive:hover {
        background: #c62828;
        color: #fff;
    }
    .btn-delete-offer {
        background: #fce4ec;
        color: #c62828;
    }
    .btn-delete-offer:hover {
        background: #c62828;
        color: #fff;
    }

    /* ========== Empty State ========== */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #999;
    }
    .empty-state i {
        font-size: 4rem;
        color: #d0cbc2;
        margin-bottom: 16px;
    }
    .empty-state h4 {
        color: #666;
        font-weight: 600;
        margin-bottom: 8px;
    }
    .empty-state p {
        font-size: 0.9rem;
    }

    /* ========== Switch Toggle ========== */
    .switch-label {
        display: flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
    }
    .switch-input {
        display: none;
    }
    .switch-slider {
        width: 44px;
        height: 24px;
        background: #ccc;
        border-radius: 12px;
        position: relative;
        transition: background 0.3s;
    }
    .switch-slider::after {
        content: '';
        position: absolute;
        width: 20px;
        height: 20px;
        background: #fff;
        border-radius: 50%;
        top: 2px;
        left: 2px;
        transition: transform 0.3s;
        box-shadow: 0 1px 3px rgba(0,0,0,0.2);
    }
    .switch-input:checked + .switch-slider {
        background: #BF9456;
    }
    .switch-input:checked + .switch-slider::after {
        transform: translateX(20px);
    }

    /* ========== Alerts ========== */
    .alert-custom {
        padding: 14px 20px;
        border-radius: 12px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 500;
        animation: slideDown 0.35s ease;
    }
    .alert-custom.success {
        background: #e8f5e9;
        color: #2e7d32;
        border: 1px solid #c8e6c9;
    }
    .alert-custom.error {
        background: #fce4ec;
        color: #c62828;
        border: 1px solid #f8bbd0;
    }

    /* ========== Responsive ========== */
    @media (max-width: 768px) {
        .offer-page-header {
            flex-direction: column;
            gap: 16px;
            text-align: center;
        }
        .form-grid {
            grid-template-columns: 1fr;
        }
        .form-grid .full-width {
            grid-column: span 1;
        }
        .offers-grid {
            grid-template-columns: 1fr;
        }
        .offer-banner-preview {
            flex-direction: column;
            text-align: center;
            gap: 16px;
        }
        .discount-circle {
            margin-right: 0 !important;
        }
    }
</style>
@endpush

@section('content')
    {{-- Success/Error Messages --}}
    @if(session('success'))
        <div class="alert-custom success">
            <i class="fas fa-check-circle"></i>
            {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="alert-custom error">
            <i class="fas fa-exclamation-circle"></i>
            {{ $errors->first() }}
        </div>
    @endif

    {{-- Page Header --}}
    <div class="offer-page-header">
        <div>
            <h2><i class="fas fa-tags" style="margin-left: 8px;"></i> بنرات العروض</h2>
            <p>إدارة العروض والخصومات الخاصة التي تظهر للعملاء</p>
        </div>
        <button class="btn-add-offer" onclick="toggleForm()">
            <i class="fas fa-plus"></i>
            إضافة عرض جديد
        </button>
    </div>

    {{-- Add/Edit Form --}}
    <div class="offer-form-card" id="offerForm">
        <h3 id="formTitle">
            <i class="fas fa-plus-circle" style="margin-left: 6px; color: #BF9456;"></i>
            إضافة عرض جديد
        </h3>

        <form id="offerFormElement" action="{{ route('offer-banners.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">
            <div class="form-grid">
                {{-- Title Arabic --}}
                <div class="form-group">
                    <label>عنوان العرض (عربي) <span class="required">*</span></label>
                    <input type="text" name="title_ar" id="title_ar" placeholder="مثال: عرض الافتتاح" required>
                </div>

                {{-- Title English --}}
                <div class="form-group">
                    <label>عنوان العرض (إنجليزي)</label>
                    <input type="text" name="title_en" id="title_en" placeholder="{{ __('e.g. Opening Offer') }}">
                </div>

                {{-- Description Arabic --}}
                <div class="form-group">
                    <label>وصف العرض (عربي) <span class="required">*</span></label>
                    <textarea name="desc_ar" id="desc_ar" placeholder="مثال: خصم 25% على جميع الباقات هذا الأسبوع" required></textarea>
                </div>

                {{-- Description English --}}
                <div class="form-group">
                    <label>وصف العرض (إنجليزي)</label>
                    <textarea name="desc_en" id="desc_en" placeholder="{{ __('e.g. 25% off all packages this week') }}"></textarea>
                </div>

                {{-- Discount Type --}}
                <div class="form-group">
                    <label>نوع الخصم <span class="required">*</span></label>
                    <select name="discount_type" id="discount_type" required>
                        <option value="percent">نسبة مئوية (%)</option>
                        <option value="fixed">مبلغ ثابت (ر.س)</option>
                    </select>
                </div>

                {{-- Discount Value --}}
                <div class="form-group">
                    <label>قيمة الخصم <span class="required">*</span></label>
                    <input type="number" name="discount_value" id="discount_value" placeholder="مثال: 25" min="0" step="0.01" required>
                </div>

                {{-- Start Date --}}
                <div class="form-group">
                    <label>تاريخ البداية <span class="required">*</span></label>
                    <input type="date" name="start_date" id="start_date" required>
                </div>

                {{-- End Date --}}
                <div class="form-group">
                    <label>تاريخ النهاية <span class="required">*</span></label>
                    <input type="date" name="end_date" id="end_date" required>
                </div>

                {{-- Color --}}
                <div class="form-group">
                    <label>لون خلفية البنر</label>
                    <div class="color-picker-wrapper">
                        <input type="color" id="color_picker" value="#1a1a2e" onchange="document.getElementById('color').value = this.value">
                        <input type="text" name="color" id="color" class="color-hex" value="#1a1a2e" placeholder="#1a1a2e" onchange="document.getElementById('color_picker').value = this.value">
                    </div>
                </div>

                {{-- Link --}}
                <div class="form-group">
                    <label>الرابط الموجّه إليه</label>
                    <input type="text" name="link" id="link" placeholder="https://example.com/offer">
                </div>

                {{-- Image Upload --}}
                <div class="form-group">
                    <label>صورة البنر</label>
                    <div class="image-upload-zone" onclick="document.getElementById('image').click()">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>اضغط لرفع صورة أو اسحبها هنا</p>
                        <p style="font-size: 0.75rem; color: #aaa; margin-top: 4px;">PNG, JPG, WEBP (حد أقصى 2MB)</p>
                        <input type="file" name="image" id="image" accept="image/*" onchange="previewImage(this)">
                        <img id="imagePreview" class="preview-img" style="display: none;">
                    </div>
                </div>

                {{-- Overlay Toggle --}}
                <div class="form-group" style="display: flex; align-items: flex-end;">
                    <label class="switch-label">
                        <input type="checkbox" name="overlay" id="overlay" class="switch-input" value="1">
                        <span class="switch-slider"></span>
                        <span>تفعيل التعتيم (Overlay)</span>
                    </label>
                </div>
            </div>

            {{-- Live Preview --}}
            <div style="margin-top: 24px;">
                <label style="font-weight: 600; color: #333; margin-bottom: 10px; display: block;">
                    <i class="fas fa-eye" style="color: #BF9456; margin-left: 4px;"></i>
                    معاينة مباشرة
                </label>
                <div class="offer-banner-preview" id="livePreview" style="background: #1a1a2e;">
                    <div class="banner-content">
                        <div class="banner-badge">
                            <i class="fas fa-star"></i>
                            عرض خاص
                        </div>
                        <h3 class="banner-title" id="previewTitle">عرض الافتتاح</h3>
                        <p class="banner-desc" id="previewDesc">خصم 25% على جميع الباقات هذا الأسبوع</p>
                        <a href="#" class="banner-cta">اكتشف العرض ←</a>
                    </div>
                    <div class="discount-circle">
                        <span class="discount-value" id="previewDiscountValue">25</span>
                        <span class="discount-label" id="previewDiscountLabel">% خصم</span>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn-cancel" onclick="hideForm()">إلغاء</button>
                <button type="submit" class="btn-save">
                    <i class="fas fa-save" style="margin-left: 4px;"></i>
                    حفظ العرض
                </button>
            </div>
        </form>
    </div>

    {{-- Offers Grid --}}
    @if($offers->count() > 0)
        <div class="offers-grid">
            @foreach($offers as $offer)
                @php
                    $titleAr = is_array($offer->title) ? ($offer->title['ar'] ?? '') : ($offer->title ?? '');
                    $descAr = is_array($offer->description) ? ($offer->description['ar'] ?? '') : ($offer->description ?? '');
                    $titleEn = is_array($offer->title) ? ($offer->title['en'] ?? '') : '';
                    $descEn = is_array($offer->description) ? ($offer->description['en'] ?? '') : '';
                    $isExpired = $offer->end_date && \Carbon\Carbon::parse($offer->end_date)->isPast();
                    $isActive = $offer->status && !$isExpired;
                @endphp
                <div class="offer-card" id="offer-card-{{ $offer->id }}">
                    {{-- Banner Preview --}}
                    <div class="offer-banner-preview" style="background: {{ $offer->color ?? '#1a1a2e' }};">
                        @if($offer->image)
                            <img src="{{ asset($offer->image) }}" class="banner-bg-image" alt="">
                        @endif
                        @if($offer->overlay)
                            <div class="banner-overlay"></div>
                        @endif
                        <div class="banner-content">
                            <div class="banner-badge">
                                <i class="fas fa-star"></i>
                                عرض خاص
                            </div>
                            <h3 class="banner-title">{{ $titleAr }}</h3>
                            <p class="banner-desc">{{ $descAr }}</p>
                            @if($offer->link)
                                <a href="{{ $offer->link }}" class="banner-cta" target="_blank">اكتشف العرض ←</a>
                            @endif
                        </div>
                        <div class="discount-circle">
                            <span class="discount-value">{{ intval($offer->discount_value) }}</span>
                            <span class="discount-label">{{ $offer->discount_type == 'percent' ? '% خصم' : 'ر.س' }}</span>
                        </div>
                    </div>

                    {{-- Card Details --}}
                    <div class="offer-card-details">
                        <div class="offer-meta">
                            <div class="offer-meta-item">
                                <i class="fas fa-calendar-alt"></i>
                                {{ $offer->start_date ? \Carbon\Carbon::parse($offer->start_date)->format('Y/m/d') : '-' }}
                                -
                                {{ $offer->end_date ? \Carbon\Carbon::parse($offer->end_date)->format('Y/m/d') : '-' }}
                            </div>
                            <div class="offer-meta-item">
                                @if($isExpired)
                                    <span class="status-badge expired">منتهي</span>
                                @elseif($offer->status)
                                    <span class="status-badge active">فعّال</span>
                                @else
                                    <span class="status-badge inactive">غير فعّال</span>
                                @endif
                            </div>
                        </div>
                        <div class="offer-actions">
                            <button class="btn-edit-offer" title="تعديل" onclick="editOffer({{ $offer->id }}, {{ json_encode([
                                'title_ar' => $titleAr,
                                'title_en' => $titleEn,
                                'desc_ar' => $descAr,
                                'desc_en' => $descEn,
                                'discount_type' => $offer->discount_type,
                                'discount_value' => $offer->discount_value,
                                'start_date' => $offer->start_date,
                                'end_date' => $offer->end_date,
                                'color' => $offer->color,
                                'link' => $offer->link,
                                'overlay' => $offer->overlay,
                                'image' => $offer->image,
                            ]) }})">
                                <i class="fas fa-pen"></i>
                            </button>
                            <button class="btn-toggle-status {{ $offer->status ? '' : 'inactive' }}"
                                title="{{ $offer->status ? 'إلغاء التفعيل' : 'تفعيل' }}"
                                onclick="toggleStatus({{ $offer->id }}, {{ $offer->status ? 0 : 1 }})">
                                <i class="fas {{ $offer->status ? 'fa-eye' : 'fa-eye-slash' }}"></i>
                            </button>
                            <button class="btn-delete-offer" title="حذف" onclick="deleteOffer({{ $offer->id }})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="empty-state">
            <i class="fas fa-tags"></i>
            <h4>لا توجد عروض حالياً</h4>
            <p>اضغط على "إضافة عرض جديد" لإنشاء أول بنر عرض</p>
        </div>
    @endif
@endsection

@push('after-scripts')
<script>
    const storeUrl = "{{ route('offer-banners.store') }}";

    // Toggle Form visibility
    function toggleForm() {
        const form = document.getElementById('offerForm');
        if (form.classList.contains('show')) {
            hideForm();
        } else {
            resetForm();
            form.classList.add('show');
            form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    function hideForm() {
        document.getElementById('offerForm').classList.remove('show');
        resetForm();
    }

    function resetForm() {
        const form = document.getElementById('offerFormElement');
        form.reset();
        form.action = storeUrl;
        document.getElementById('formMethod').value = 'POST';
        document.getElementById('formTitle').innerHTML = '<i class="fas fa-plus-circle" style="margin-left: 6px; color: #BF9456;"></i> إضافة عرض جديد';
        document.getElementById('imagePreview').style.display = 'none';
        document.getElementById('color_picker').value = '#1a1a2e';
        document.getElementById('color').value = '#1a1a2e';
        updatePreview();
    }

    // Edit Offer
    function editOffer(id, data) {
        const form = document.getElementById('offerFormElement');
        form.action = `/app/offer-banners/${id}`;
        document.getElementById('formMethod').value = 'PUT';
        document.getElementById('formTitle').innerHTML = '<i class="fas fa-edit" style="margin-left: 6px; color: #BF9456;"></i> تعديل العرض';

        document.getElementById('title_ar').value = data.title_ar || '';
        document.getElementById('title_en').value = data.title_en || '';
        document.getElementById('desc_ar').value = data.desc_ar || '';
        document.getElementById('desc_en').value = data.desc_en || '';
        document.getElementById('discount_type').value = data.discount_type || 'percent';
        document.getElementById('discount_value').value = data.discount_value || '';
        document.getElementById('start_date').value = data.start_date || '';
        document.getElementById('end_date').value = data.end_date || '';
        document.getElementById('color').value = data.color || '#1a1a2e';
        document.getElementById('color_picker').value = data.color || '#1a1a2e';
        document.getElementById('link').value = data.link || '';
        document.getElementById('overlay').checked = data.overlay == 1;

        if (data.image) {
            const preview = document.getElementById('imagePreview');
            preview.src = '/' + data.image;
            preview.style.display = 'block';
        }

        updatePreview();

        const formCard = document.getElementById('offerForm');
        formCard.classList.add('show');
        formCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // Toggle Status
    function toggleStatus(id, newStatus) {
        fetch(`/app/offer-banners/${id}/status`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ status: newStatus })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        })
        .catch(err => console.error(err));
    }

    // Delete Offer
    function deleteOffer(id) {
        if (!confirm('هل أنت متأكد من حذف هذا العرض؟')) return;

        fetch(`/app/offer-banners/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const card = document.getElementById(`offer-card-${id}`);
                card.style.transition = 'all 0.3s ease';
                card.style.opacity = '0';
                card.style.transform = 'scale(0.9)';
                setTimeout(() => {
                    card.remove();
                    // Check if grid is empty
                    if (document.querySelectorAll('.offer-card').length === 0) {
                        location.reload();
                    }
                }, 300);
            }
        })
        .catch(err => console.error(err));
    }

    // Image Preview
    function previewImage(input) {
        const preview = document.getElementById('imagePreview');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Live Preview Updates
    function updatePreview() {
        const titleAr = document.getElementById('title_ar').value || 'عنوان العرض';
        const descAr = document.getElementById('desc_ar').value || 'وصف العرض يظهر هنا';
        const discountValue = document.getElementById('discount_value').value || '0';
        const discountType = document.getElementById('discount_type').value;
        const color = document.getElementById('color').value || '#1a1a2e';

        document.getElementById('previewTitle').textContent = titleAr;
        document.getElementById('previewDesc').textContent = descAr;
        document.getElementById('previewDiscountValue').textContent = discountValue;
        document.getElementById('previewDiscountLabel').textContent = discountType === 'percent' ? '% خصم' : 'ر.س';
        document.getElementById('livePreview').style.background = color;
    }

    // Attach live preview events
    document.addEventListener('DOMContentLoaded', function() {
        ['title_ar', 'desc_ar', 'discount_value', 'discount_type', 'color'].forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener('input', updatePreview);
                el.addEventListener('change', updatePreview);
            }
        });

        // Auto-dismiss alerts after 5 seconds
        document.querySelectorAll('.alert-custom').forEach(alert => {
            setTimeout(() => {
                alert.style.transition = 'all 0.3s ease';
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(() => alert.remove(), 300);
            }, 5000);
        });
    });
</script>
@endpush
