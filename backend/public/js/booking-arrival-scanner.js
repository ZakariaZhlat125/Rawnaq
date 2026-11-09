(() => {
    const video = document.getElementById('arrival-camera');
    const message = document.getElementById('arrival-scan-message');
    const start = document.getElementById('start-arrival-scan');
    const stop = document.getElementById('stop-arrival-scan');
    let stream = null;
    let scanning = false;
    let generation = 0;
    function stopCamera() {
        generation++;
        scanning = false;
        stream?.getTracks().forEach(track => track.stop());
        stream = null;
        video.srcObject = null;
        video.hidden = true;
        stop.disabled = true;
        start.disabled = false;
    }
    function openBooking(raw) {
        try {
            const url = new URL(raw);
            const prefix = location.pathname.replace(/\/$/, '');
            if (url.origin !== location.origin || !url.pathname.startsWith(prefix + '/') ||
                !/\/\d+\/scan$/.test(url.pathname) || !url.searchParams.has('signature')) {
                throw new Error();
            }
            stopCamera();
            location.assign(url.href);
        } catch {
            message.textContent = 'هذا الرمز ليس رابط تأكيد وصول لحجز في النظام.';
        }
    }
    start.addEventListener('click', async () => {
        if (!window.BarcodeDetector || !navigator.mediaDevices?.getUserMedia) {
            message.textContent = 'المسح داخل المتصفح غير متاح هنا. استخدم كاميرا الهاتف لفتح الرمز، أو الصق رابطه أدناه.';
            return;
        }
        start.disabled = true;
        const attempt = ++generation;
        message.textContent = '';
        try {
            const detector = new BarcodeDetector({formats: ['qr_code']});
            const camera = await navigator.mediaDevices.getUserMedia({video: {facingMode: 'environment'}, audio: false});
            if (attempt !== generation) { camera.getTracks().forEach(track => track.stop()); return; }
            stream = camera;
            video.srcObject = stream;
            video.hidden = false;
            await video.play();
            if (attempt !== generation) return;
            stop.disabled = false;
            scanning = true;
            while (scanning) {
                const codes = await detector.detect(video);
                if (codes.length) { openBooking(codes[0].rawValue); if (!scanning) break; }
                await new Promise(resolve => setTimeout(resolve, 250));
            }
        } catch {
            stopCamera();
            message.textContent = 'تعذر تشغيل المسح. اسمح باستخدام الكاميرا على اتصال HTTPS، أو استخدم كاميرا الهاتف.';
        }
    });
    stop.addEventListener('click', stopCamera);
    window.addEventListener('pagehide', stopCamera);
    video.closest('details').addEventListener('toggle', event => { if (!event.target.open) stopCamera(); });
    document.getElementById('arrival-scan-link-form').addEventListener('submit', event => {
        event.preventDefault();
        openBooking(document.getElementById('arrival-scan-link').value.trim());
    });
})();
