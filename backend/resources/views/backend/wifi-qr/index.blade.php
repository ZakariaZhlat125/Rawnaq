 @extends('backend.layouts.app')

@section('title', 'QR الواي فاي')

@section('content')
<div dir="rtl">
    <h4 class="mb-4">إنشاء QR للواي فاي</h4>

    <div class="row">
        <div class="col-12 col-lg-6">
            <div class="card">
                <div class="card-body">
                    <form id="wifi-qr-form">
                        <div class="mb-3">
                            <label for="wifi-title" class="form-label">
                                عنوان يظهر فوق الصورة
                            </label>

                            <input
                                id="wifi-title"
                                class="form-control"
                                value="واي فاي العملاء"
                                maxlength="100"
                                required
                            >
                        </div>

                        <div class="mb-3">
                            <label for="wifi-ssid" class="form-label">
                                اسم الشبكة — مطابق للاسم الموجود في الراوتر
                            </label>

                            <input
                                id="wifi-ssid"
                                class="form-control"
                                dir="auto"
                                autocomplete="off"
                                required
                            >
                        </div>

                        <div class="mb-3">
                            <label for="wifi-security" class="form-label">
                                نوع حماية الشبكة
                            </label>

                            <select id="wifi-security" class="form-select">
                                <option value="WPA">WPA / WPA2 Personal</option>
                                <option value="WEP">WEP</option>
                                <option value="nopass">بدون كلمة مرور</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="wifi-password" class="form-label">
                                كلمة المرور
                            </label>

                            <input
                                id="wifi-password"
                                type="password"
                                class="form-control"
                                dir="auto"
                                autocomplete="new-password"
                                required
                            >
                        </div>

                        <div class="form-check mb-3">
                            <input
                                id="wifi-hidden"
                                type="checkbox"
                                class="form-check-input"
                            >

                            <label for="wifi-hidden" class="form-check-label">
                                الشبكة مخفية
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            إنشاء QR
                        </button>

                        <p
                            id="wifi-error"
                            class="text-danger mt-3 mb-0"
                            role="alert"
                            hidden
                        ></p>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div id="wifi-result" class="card" hidden>
                <div class="card-body text-center">
                    <h5 id="wifi-result-title"></h5>

                    <img
                        id="wifi-image"
                        class="my-3"
                        alt="QR الاتصال بشبكة الواي فاي"
                        style="width: 280px; max-width: 100%; height: auto;"
                    >

                    <p id="wifi-network-name" dir="auto"></p>

                    <p class="text-muted">
                        امسح الكود بكاميرا الموبايل ثم اختار الاتصال بالشبكة.
                    </p>

                    <button
                        id="wifi-download"
                        type="button"
                        class="btn btn-success"
                    >
                        تحميل الصورة PNG
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('after-scripts')
<script src="{{ asset('js/qrcode.min.js') }}"></script>

<script>
(function () {
    const form = document.getElementById('wifi-qr-form');
    const title = document.getElementById('wifi-title');
    const ssid = document.getElementById('wifi-ssid');
    const security = document.getElementById('wifi-security');
    const password = document.getElementById('wifi-password');
    const hidden = document.getElementById('wifi-hidden');
    const result = document.getElementById('wifi-result');
    const error = document.getElementById('wifi-error');

    let imageUrl = null;

    // حماية الرموز الخاصة داخل صيغة WIFI.
    function escapeWifi(value) {
        return value.replace(/[\\;,:"]/g, function (character) {
            return '\\' + character;
        });
    }

    function resetResult() {
        result.hidden = true;
        imageUrl = null;
        error.hidden = true;
    }

    function updatePasswordField() {
        const openNetwork = security.value === 'nopass';

        password.disabled = openNetwork;
        password.required = !openNetwork;
    }

    form.addEventListener('input', resetResult);
    form.addEventListener('change', resetResult);
    security.addEventListener('change', updatePasswordField);
    updatePasswordField();

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        resetResult();

        try {
            if (typeof QRCode === 'undefined') {
                throw new Error('ملف مكتبة QR غير موجود أو لم يتم تحميله.');
            }

            // اسم الشبكة يُقاس بالبايت، وقد يحتوي على حروف عربية.
            if (new TextEncoder().encode(ssid.value).length > 32) {
                throw new Error('اسم شبكة الواي فاي يجب ألا يتجاوز 32 بايت.');
            }

            // لا نستخدم trim حتى نحافظ على المسافات الأصلية في البيانات.
            let payload =
                'WIFI:T:' + security.value +
                ';S:' + escapeWifi(ssid.value) + ';';

            if (security.value !== 'nopass') {
                payload += 'P:' + escapeWifi(password.value) + ';';
            }

            payload += 'H:' + (hidden.checked ? 'true' : 'false') + ';;';

            const source = document.createElement('div');

            new QRCode(source, {
                text: payload,
                width: 512,
                height: 512,
                colorDark: '#000000',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.M
            });

            const qrCanvas = source.querySelector('canvas');

            if (!qrCanvas) {
                throw new Error('المتصفح لا يدعم توليد الصورة.');
            }

            // الهامش الأبيض جزء من الصورة المحمّلة.
            const canvas = document.createElement('canvas');
            canvas.width = 640;
            canvas.height = 640;

            const context = canvas.getContext('2d');

            if (!context) {
                throw new Error('تعذر إنشاء الصورة.');
            }

            context.fillStyle = '#ffffff';
            context.fillRect(0, 0, canvas.width, canvas.height);
            context.drawImage(qrCanvas, 64, 64);

            imageUrl = canvas.toDataURL('image/png');

            document.getElementById('wifi-result-title').textContent =
                title.value;

            document.getElementById('wifi-network-name').textContent =
                ssid.value;

            document.getElementById('wifi-image').src = imageUrl;

            result.hidden = false;
        } catch (exception) {
            error.textContent = exception.message || 'تعذر إنشاء QR.';
            error.hidden = false;
        }
    });

    document.getElementById('wifi-download')
        .addEventListener('click', function () {
            if (!imageUrl) return;

            const link = document.createElement('a');
            link.href = imageUrl;
            link.download = 'wifi-qr.png';

            document.body.appendChild(link);
            link.click();
            link.remove();
        });
})();
</script>
@endpush