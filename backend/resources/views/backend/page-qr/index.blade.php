@extends('backend.layouts.app')

@section('title', app()->getLocale()=='ar' ? 'QR صفحات الموقع' : 'website pages qr code')

@section('content')
<div dir="rtl">
    <h4 class="mb-4">{{app()->getLocale()=='ar' ? 'QR صفحات الموقع' : 'website pages qr code'}}</h4>

    <div class="row">
        @foreach ($pages as $page)
            <div class="col-12 col-md-6 col-xl-4 mb-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <h5 class="mb-3">{{ $page['title'] }}</h5>

                        <div
                            class="page-qr mb-3"
                            data-url="{{ $page['url'] }}"
                            data-filename="page-{{ $loop->iteration }}.png"
                        ></div>

                        <a
                            href="{{ $page['url'] }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="d-block mb-3 text-break"
                            dir="ltr"
                        >{{ $page['url'] }}</a>

                        <button
                            type="button"
                            class="btn btn-primary download-qr"
                            disabled
                        >
                            تحميل QR كصورة
                        </button>

                        <p
                            class="qr-error text-danger small mt-2"
                            role="alert"
                            hidden
                        ></p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection

@push('after-styles')
<style>
    .page-qr img {
        display: block;
        width: 100%;
        max-width: 240px;
        height: auto;
        margin: auto;
    }
</style>
@endpush

@push('after-scripts')
<script src="{{ asset('js/qrcode.min.js') }}"></script>
<script>
(function () {
    document.querySelectorAll('.page-qr').forEach(function (container) {
        const card = container.closest('.card-body');
        const button = card.querySelector('.download-qr');
        const error = card.querySelector('.qr-error');

        try {
            if (typeof QRCode === 'undefined') {
                throw new Error('QR library missing');
            }

            // عنصر مؤقت لتوليد الكود.
            const source = document.createElement('div');

            new QRCode(source, {
                text: container.dataset.url,
                width: 512,
                height: 512,
                colorDark: '#000000',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.M
            });

            const qrCanvas = source.querySelector('canvas');

            if (!qrCanvas) {
                throw new Error('Canvas unavailable');
            }

            // إضافة هامش أبيض للصورة المعروضة والمحمّلة.
            const canvas = document.createElement('canvas');
            canvas.width = 640;
            canvas.height = 640;

            const context = canvas.getContext('2d');

            if (!context) {
                throw new Error('Canvas context unavailable');
            }

            context.fillStyle = '#ffffff';
            context.fillRect(0, 0, 640, 640);
            context.drawImage(qrCanvas, 64, 64);

            const imageUrl = canvas.toDataURL('image/png');
            const image = document.createElement('img');

            image.src = imageUrl;
            image.alt = 'QR ' + card.querySelector('h5').textContent;

            container.replaceChildren(image);
            button.disabled = false;

            button.addEventListener('click', function () {
                const link = document.createElement('a');

                link.href = imageUrl;
                link.download = container.dataset.filename;

                document.body.appendChild(link);
                link.click();
                link.remove();
            });
        } catch (exception) {
            error.textContent =
                'تعذّر إنشاء QR. تأكد من تحميل ملف المكتبة وصحة الرابط.';
            error.hidden = false;
        }
    });
})();
</script>
@endpush