<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">

    <title>{{ $code->title }}</title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 20px;
            background: #f5f6fa;
            font-family: Arial, sans-serif;
        }

        main {
            width: 100%;
            max-width: 440px;
            padding: 24px;
            border-radius: 16px;
            background: white;
            text-align: center;
        }

        img {
            display: block;
            width: 100%;
            max-width: 320px;
            height: auto;
            margin: auto;
        }

        p { line-height: 1.8; }

        .download {
            display: inline-block;
            padding: 12px 20px;
            background: #2563eb;
            color: white;
            border-radius: 8px;
            text-decoration: none;
        }

        [hidden] { display: none !important; }
    </style>
</head>
<body>
    <main>
        <h1>{{ $code->title }}</h1>
        <p dir="auto">{{ $ssid }}</p>

        <img
            id="wifi-image"
            alt="QR الاتصال بشبكة الواي فاي"
            hidden
        >

        <p>
            امسح الصورة بكاميرا جهاز آخر للاتصال بالشبكة.
            لو فاتح الصفحة على نفس الموبايل، يمكنك حفظ الصورة
            وقراءتها من المعرض إذا كان جهازك يدعم ذلك.
        </p>

        <a
            id="wifi-download"
            class="download"
            download="wifi-qr.png"
            hidden
        >تحميل صورة QR</a>

        <p id="error" role="alert" hidden></p>
    </main>

    <script src="{{ asset('js/qrcode.min.js') }}"></script>

    <script>
    (function () {
        try {
            const source = document.createElement('div');

            new QRCode(source, {
                text: {{ Illuminate\Support\Js::from($payload) }},
                width: 512,
                height: 512,
                colorDark: '#000000',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.M
            });

            const qrCanvas = source.querySelector('canvas');

            if (!qrCanvas) {
                throw new Error('QR canvas unavailable');
            }

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

            const image = document.getElementById('wifi-image');
            image.src = imageUrl;
            image.hidden = false;

            const download = document.getElementById('wifi-download');
            download.href = imageUrl;
            download.hidden = false;
        } catch (exception) {
            const error = document.getElementById('error');
            error.textContent = 'تعذر تحميل QR. حاول مرة أخرى.';
            error.hidden = false;
        }
    })();
    </script>
</body>
</html> 