@extends('backend.layouts.app')

@php
    $templateFields = [
        [
            'name' => 'taqnyat_welcome_message',
            'label' => __('taqnyat.welcome_message'),
            'hint' => __('taqnyat.welcome_message_hint'),
            'placeholder' => __('taqnyat.welcome_message_placeholder'),
            'value' => setting('taqnyat_welcome_message', 'مرحبًا [[name]]، أهلاً بك في [[app_name]].'),
        ],
        [
            'name' => 'taqnyat_booking_created',
            'label' => __('taqnyat.booking_created_message'),
            'hint' => __('taqnyat.booking_created_message_hint'),
            'placeholder' => __('taqnyat.booking_created_message_placeholder'),
            'value' => setting('taqnyat_booking_created', 'تم إنشاء حجزك بنجاح. رقم الحجز [[booking_id]] بتاريخ [[booking_date]] الساعة [[booking_time]].'),
        ],
        [
            'name' => 'taqnyat_payment_service_booking',
            'label' => __('taqnyat.service_payment_message'),
            'hint' => __('taqnyat.service_payment_message_hint'),
            'placeholder' => __('taqnyat.service_payment_message_placeholder'),
            'value' => setting('taqnyat_payment_service_booking', 'تم تأكيد دفع حجز الخدمات بنجاح. رقم الفاتورة [[invoice_code]]. الخدمات: [[service_names]]. التاريخ: [[booking_dates]]. الوقت: [[booking_times]]. الفرع: [[branch_names]]. الإجمالي: [[total_amount]] ريال.'),
        ],
        [
            'name' => 'taqnyat_payment_package_booking',
            'label' => __('taqnyat.package_payment_message'),
            'hint' => __('taqnyat.package_payment_message_hint'),
            'placeholder' => __('taqnyat.package_payment_message_placeholder'),
            'value' => setting('taqnyat_payment_package_booking', 'تم تأكيد دفع حجز الباقة بنجاح. رقم الفاتورة [[invoice_code]]. الباقات: [[package_names]]. التاريخ: [[booking_dates]]. الوقت: [[booking_times]]. الفرع: [[branch_names]]. الإجمالي: [[total_amount]] ريال.'),
        ],
        [
            'name' => 'taqnyat_payment_product_order',
            'label' => __('taqnyat.product_payment_message'),
            'hint' => __('taqnyat.product_payment_message_hint'),
            'placeholder' => __('taqnyat.product_payment_message_placeholder'),
            'value' => setting('taqnyat_payment_product_order', 'تم تأكيد طلب المنتجات بنجاح. أرقام الطلبات: [[order_codes]]. المنتجات: [[product_names]]. إجمالي الدفع: [[total_amount]] ريال.'),
        ],
        [
            'name' => 'taqnyat_payment_gift_card',
            'label' => __('taqnyat.gift_payment_message'),
            'hint' => __('taqnyat.gift_payment_message_hint'),
            'placeholder' => __('taqnyat.gift_payment_message_placeholder'),
            'value' => setting('taqnyat_payment_gift_card', 'تم تأكيد شراء بطاقة الهدية بنجاح. رقم الفاتورة [[invoice_code]]. المستلمون: [[recipient_names]]. الخدمات المطلوبة: [[gift_services]]. إجمالي الدفع: [[total_amount]] ريال.'),
        ],
        [
            'name' => 'taqnyat_recipient',
            'label' => __('taqnyat.gift_recipient_message'),
            'hint' => __('taqnyat.gift_recipient_message_hint'),
            'placeholder' => __('taqnyat.gift_recipient_message_placeholder'),
            'value' => setting('taqnyat_recipient', 'لقد وصلتك بطاقة هدية من [[app_name]]. رقم الهدية [[gift_ref]]. الخدمات: [[gift_services]]. القيمة: [[gift_total]] ريال. رابط الهدية: [[gift_url]]'),
        ],
        [
            'name' => 'taqnyat_booking_cancelled',
            'label' => __('taqnyat.booking_cancelled_message'),
            'hint' => __('taqnyat.booking_cancelled_message_hint'),
            'placeholder' => __('taqnyat.booking_cancelled_message_placeholder'),
            'value' => setting('taqnyat_booking_cancelled', 'تم إلغاء حجزك رقم [[booking_id]] بنجاح.'),
        ],
        [
            'name' => 'taqnyat_booking_reminder',
            'label' => __('taqnyat.booking_reminder_message'),
            'hint' => __('taqnyat.booking_reminder_message_hint'),
            'placeholder' => __('taqnyat.booking_reminder_message_placeholder'),
            'value' => setting('taqnyat_booking_reminder', 'تذكير بموعدك غدًا الساعة [[booking_time]] لخدمة [[service_name]].'),
        ],
        [
            'name' => 'taqnyat_password_reset',
            'label' => __('taqnyat.password_reset_message'),
            'hint' => __('taqnyat.password_reset_message_hint'),
            'placeholder' => __('taqnyat.password_reset_message_placeholder'),
            'value' => setting('taqnyat_password_reset', 'رمز إعادة تعيين كلمة المرور هو [[reset_code]].'),
        ],
    ];
@endphp

@section('title')
    {{ __('taqnyat.sms_gateway_settings') }}
@endsection

@push('after-styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <style>
        .sms-shell {
            display: grid;
            gap: 24px;
        }

        .sms-hero {
            border-radius: 24px;
            padding: 28px 30px;
            background:
                radial-gradient(circle at top right, rgba(255, 255, 255, 0.24), transparent 26%),
                linear-gradient(135deg, #171717 0%, #3f3122 45%, #bf9456 100%);
            color: #fff;
            box-shadow: 0 20px 44px rgba(37, 28, 17, 0.22);
        }

        .sms-hero h1 {
            margin: 0 0 10px;
            font-size: 30px;
            font-weight: 800;
        }

        .sms-hero p {
            margin: 0;
            max-width: 760px;
            color: rgba(255, 255, 255, 0.84);
            line-height: 1.8;
        }

        .sms-grid {
            display: grid;
            gap: 24px;
            grid-template-columns: minmax(0, 1.25fr) minmax(280px, 0.75fr);
        }

        .sms-card {
            background: #fff;
            border: 1px solid rgba(191, 148, 86, 0.16);
            border-radius: 22px;
            box-shadow: 0 12px 30px rgba(18, 18, 18, 0.05);
            padding: 24px;
        }

        .sms-card h5 {
            margin: 0 0 10px;
            font-size: 20px;
            font-weight: 800;
            color: #1f1f1f;
        }

        .sms-card p.section-copy {
            margin: 0 0 20px;
            color: #6c6c6c;
            line-height: 1.7;
        }

        .sms-switch {
            display: flex;
            justify-content: space-between;
            gap: 18px;
            padding: 18px 20px;
            border-radius: 18px;
            background: linear-gradient(135deg, #f9f5ed 0%, #fff 100%);
            border: 1px solid rgba(191, 148, 86, 0.14);
            margin-bottom: 18px;
        }

        .sms-switch strong {
            display: block;
            font-size: 16px;
            margin-bottom: 4px;
            color: #1f1f1f;
        }

        .sms-switch span {
            color: #737373;
            font-size: 13px;
            line-height: 1.7;
        }

        .sms-form-grid {
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .sms-field {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .sms-field--full {
            grid-column: 1 / -1;
        }

        .sms-field label {
            font-weight: 700;
            color: #222;
        }

        .sms-field input,
        .sms-field textarea {
            width: 100%;
            border: 1px solid #dfdfdf;
            border-radius: 16px;
            padding: 14px 16px;
            background: #fbfbfb;
            transition: border-color .2s ease, box-shadow .2s ease, background-color .2s ease;
        }

        .sms-field textarea {
            min-height: 130px;
            resize: vertical;
        }

        .sms-field input:focus,
        .sms-field textarea:focus {
            outline: none;
            border-color: #bf9456;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(191, 148, 86, 0.12);
        }

        .sms-field small {
            color: #787878;
            line-height: 1.7;
        }

        .sms-template-grid {
            display: grid;
            gap: 18px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .template-card {
            border: 1px solid rgba(191, 148, 86, 0.14);
            border-radius: 18px;
            padding: 18px;
            background: linear-gradient(180deg, #fff 0%, #fcfaf6 100%);
        }

        .template-card textarea {
            min-height: 150px;
        }

        .sms-vars {
            display: grid;
            gap: 10px;
        }

        .sms-vars__group {
            border-radius: 16px;
            border: 1px dashed rgba(191, 148, 86, 0.35);
            background: #fffaf2;
            padding: 14px 16px;
        }

        .sms-vars__group strong {
            display: block;
            margin-bottom: 8px;
            color: #593d17;
        }

        .sms-vars__group code {
            display: block;
            white-space: pre-wrap;
            direction: ltr;
            color: #6a4f26;
            font-size: 12px;
            line-height: 1.9;
        }

        .sms-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 24px;
        }

        .sms-save-btn {
            border: 0;
            border-radius: 16px;
            background: linear-gradient(135deg, #bf9456 0%, #8a6434 100%);
            color: #fff;
            padding: 14px 26px;
            font-weight: 800;
            box-shadow: 0 12px 24px rgba(191, 148, 86, 0.24);
        }

        .sms-test-form {
            display: grid;
            gap: 16px;
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: end;
        }

        @media (max-width: 1200px) {
            .sms-grid,
            .sms-template-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .sms-form-grid,
            .sms-test-form {
                grid-template-columns: 1fr;
            }

            .sms-hero,
            .sms-card {
                padding: 20px;
            }
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid py-4">
        <div class="sms-shell">
            <section class="sms-hero">
                <h1>{{ __('taqnyat.sms_gateway_settings') }}</h1>
                <p>{{ __('taqnyat.templates_intro') }}</p>
            </section>

            <form method="POST" action="{{ route('store') }}">
                @csrf

                <div class="sms-grid">
                    <section class="sms-card">
                        <h5>{{ __('taqnyat.api_configuration') }}</h5>
                        <p class="section-copy">{{ __('taqnyat.credentials_hint') }}</p>

                        <div class="sms-switch">
                            <div>
                                <strong>{{ __('taqnyat.enable_sms_gateway') }}</strong>
                                <span>{{ __('taqnyat.enable_sms_gateway_hint') }}</span>
                            </div>
                            <div class="form-check form-switch m-0">
                                <input type="hidden" name="is_taqnyat_sms" value="0">
                                <input class="form-check-input" type="checkbox" role="switch" id="is_taqnyat_sms" name="is_taqnyat_sms" value="1" {{ setting('is_taqnyat_sms') ? 'checked' : '' }}>
                            </div>
                        </div>

                        <div class="sms-form-grid">
                            <div class="sms-field">
                                <label for="taqnyat_api_key">{{ __('taqnyat.api_key') }}</label>
                                <input type="text" id="taqnyat_api_key" name="taqnyat_api_key" value="{{ old('taqnyat_api_key', setting('taqnyat_api_key', config('sami.taqnyat_api_key'))) }}" placeholder="{{ __('taqnyat.api_key_placeholder') }}">
                                <small>{{ __('taqnyat.api_key_hint') }}</small>
                            </div>

                            <div class="sms-field">
                                <label for="taqnyat_sender">{{ __('taqnyat.sender_name') }}</label>
                                <input type="text" id="taqnyat_sender" name="taqnyat_sender" value="{{ old('taqnyat_sender', setting('taqnyat_sender', config('sami.taqnyat_sender_name'))) }}" placeholder="{{ __('taqnyat.sender_name_placeholder') }}">
                                <small>{{ __('taqnyat.sender_name_hint') }}</small>
                            </div>
                        </div>
                    </section>

                    <section class="sms-card">
                        <h5>{{ __('taqnyat.available_variables') }}</h5>
                        <p class="section-copy">{{ __('taqnyat.available_variables_hint') }}</p>

                        <div class="sms-vars">
                            <div class="sms-vars__group">
                                <strong>{{ __('taqnyat.general_variables') }}</strong>
                                <code>[[user_name]] [[app_name]] [[invoice_id]] [[invoice_code]] [[total_amount]] [[payment_method]]</code>
                            </div>
                            <div class="sms-vars__group">
                                <strong>{{ __('taqnyat.service_variables') }}</strong>
                                <code>[[booking_ids]] [[booking_dates]] [[booking_times]] [[branch_names]] [[service_names]] [[services_count]]</code>
                            </div>
                            <div class="sms-vars__group">
                                <strong>{{ __('taqnyat.package_variables') }}</strong>
                                <code>[[booking_ids]] [[booking_dates]] [[booking_times]] [[branch_names]] [[package_names]] [[packages_count]]</code>
                            </div>
                            <div class="sms-vars__group">
                                <strong>{{ __('taqnyat.product_variables') }}</strong>
                                <code>[[order_codes]] [[product_names]] [[products_count]]</code>
                            </div>
                            <div class="sms-vars__group">
                                <strong>{{ __('taqnyat.gift_variables') }}</strong>
                                <code>[[gift_ids]] [[recipient_names]] [[recipient_phones]] [[gift_services]] [[gifts_count]] [[gift_ref]] [[gift_total]] [[gift_url]] [[gift_urls]] [[sender_name]]</code>
                            </div>
                        </div>
                    </section>
                </div>

                <section class="sms-card mt-4 d-none" id="messageTemplatesSection">
                    <h5>{{ __('taqnyat.message_templates') }}</h5>
                    <p class="section-copy">{{ __('taqnyat.templates_manage_hint') }}</p>

                    <div class="sms-template-grid">
                        @foreach ($templateFields as $field)
                            <div class="template-card">
                                <div class="sms-field">
                                    <label for="{{ $field['name'] }}">{{ $field['label'] }}</label>
                                    <textarea id="{{ $field['name'] }}" name="{{ $field['name'] }}" placeholder="{{ $field['placeholder'] }}">{{ old($field['name'], $field['value']) }}</textarea>
                                    <small>{{ $field['hint'] }}</small>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="sms-actions">
                        <button type="submit" class="sms-save-btn">
                            <i class="fas fa-save ms-2"></i>{{ __('taqnyat.save_settings') }}
                        </button>
                    </div>
                </section>
            </form>

            <section class="sms-card d-none" id="smsTestSection">
                <h5>{{ __('taqnyat.test_message') }}</h5>
                <p class="section-copy">{{ __('taqnyat.test_message_hint') }}</p>

                <form action="{{ route('send-test') }}" method="POST" class="sms-test-form">
                    @csrf
                    <div class="sms-field">
                        <label for="testPhone">{{ __('taqnyat.phone_number') }}</label>
                        <input type="text" id="testPhone" name="test_phone" value="{{ old('test_phone') }}" placeholder="{{ __('taqnyat.phone_number_placeholder') }}">
                        <small>{{ __('taqnyat.phone_number_hint') }}</small>
                    </div>
                    <button type="submit" class="sms-save-btn">{{ __('taqnyat.send_test_message') }}</button>
                </form>
            </section>
        </div>
    </div>
@endsection

@push('after-scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const checkbox = document.getElementById('is_taqnyat_sms');
            const templatesSection = document.getElementById('messageTemplatesSection');
            const testSection = document.getElementById('smsTestSection');

            function toggleSections() {
                const isEnabled = checkbox.checked;
                templatesSection.classList.toggle('d-none', !isEnabled);
                testSection.classList.toggle('d-none', !isEnabled);
            }

            checkbox.addEventListener('change', toggleSections);
            toggleSections();
        });
    </script>

    <script>
        @if(session('success'))
            toastr.success("{{ session('success') }}");
        @endif

        @if(session('error'))
            toastr.error("{{ session('error') }}");
        @endif

        @if ($errors->any())
            @foreach ($errors->all() as $error)
                toastr.error("{{ $error }}");
            @endforeach
        @endif

        @if(session('warning'))
            toastr.warning("{{ session('warning') }}");
        @endif

        @if(session('info'))
            toastr.info("{{ session('info') }}");
        @endif
    </script>
@endpush
