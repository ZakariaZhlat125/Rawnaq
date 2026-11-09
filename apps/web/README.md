# عناية سامي — موقع الحجز والمتجر

موقع تسويقي وحجز لعلامة **عناية سامي**، مبني على **Vue 3 + Vite**، عربي بالكامل (RTL).

---

## التشغيل

```bash
npm install
npm run dev      # خادم التطوير — http://localhost:5173
npm run build    # بناء الإنتاج إلى dist/
npm run preview  # معاينة نسخة الإنتاج
```

> لا يوجد مشغّل اختبارات أو linter مُعدّ — لا تفترض وجود `npm test` أو `npm run lint`.

---

## البنية

```
src/
├── main.js                 نقطة الدخول — يُحمّل الأنماط العامة والراوتر
├── App.vue                 الغلاف الأعلى + التحويل لتطبيق الجوال على الشاشات الصغيرة
├── router/index.js         تعريف كل المسارات
│
├── views/                  صفحة (view) لكل مسار
├── components/
│   ├── layout/             الهيدر والفوتر
│   ├── common/             مكوّنات معاد استخدامها
│   ├── home/               أقسام الصفحة الرئيسية
│   └── legacy/             غلاف عرض الصفحات القديمة
│
├── composables/            حالة مشتركة (مكان تنفيذ الخدمة)
├── data/                   البيانات الثابتة — نقاط ربط الباك اند
├── legacy/                 صفحات HTML القديمة — مصدر وحيد للحقيقة
└── assets/styles/          الأنماط العامة

public/
├── mobile/                 تطبيق الجوال (ملف مستقل بالكامل)
├── images/
└── logo.png

docs/                       التوثيق الفني
```

---

## المسارات

| المسار | الصفحة | النمط |
|---|---|---|
| `/` | الرئيسية | Vue |
| `/services` | قائمة الخدمات | Vue |
| `/services/:id` | تفاصيل خدمة | Vue |
| `/booking` | الحجز | Legacy |
| `/store` | المتجر | Legacy |
| `/gifts` | الإهداء | Legacy |
| `/packages-gifts` | الباقات | Legacy |
| `/branches` | الفروع | Legacy |
| `/contact` | تواصل معنا | Legacy |
| `/gift-recipient` | استلام الهدية | Legacy |

كل الصفحات محمّلة عند الطلب (lazy loading). المسارات القديمة بامتداد `.html` مدعومة عبر `alias`.

---

## نمطا العرض — اقرأ هذا قبل تعديل أي صفحة

### 1. مكوّنات Vue كاملة
`HomeView` · `ServicesView` · `ServiceDetailView`

مبنية من مكوّنات حقيقية وبياناتها في `src/data/`.
**هذا هو النمط المستهدف لأي صفحة جديدة.**

### 2. صفحات HTML قديمة عبر `LegacyPage`
باقي الصفحات. كل صفحة ملف مستقل في `src/legacy/` يُستورد كنص خام:

```js
import source from '@/legacy/booking.html?raw'
```

`LegacyPage` يقوم بـ:
1. تحليل الـ HTML بـ `DOMParser`
2. حقن كتل `<style>` في `<head>` وإزالتها عند مغادرة الصفحة
3. تنفيذ السكربتات بعد التركيب، مع تنظيف مستمعاتها تلقائيًا
4. اعتراض روابط `<a>` الداخلية وتوجيهها عبر `vue-router`

> ⚠️ استخدام `eval` هنا **مقصود** — هو ما يسمح بتشغيل صفحات معقّدة (3000+ سطر)
> دون إعادة كتابتها. لا تُبسّط هذه الآلية قبل فهم دورها.

---

## تطبيق الجوال — منفصل تمامًا

`public/mobile/index.html` — ملف واحد (~3200 سطر) فيه HTML و CSS و JS،
**بدون خطوة بناء وليس جزءًا من Vue.**

`App.vue` يعرضه داخل `<iframe>` عندما يكون عرض الشاشة ≤ 640px.
التزامن عبر `postMessage`:

```js
window.parent.postMessage({ type: 'sami:navigate', path }, '*')
```

> **أي تعديل يخص واجهة الجوال يكون في `public/mobile/` وليس في `src/`.**

---

## الحالة المشتركة: مكان تنفيذ الخدمة

| | |
|---|---|
| المفتاح | `localStorage['samiSiteBranch']` |
| القيم | `bg` البغدادية الغربية · `qr` البوادي · `hm` الخدمة المنزلية |
| في Vue | `src/composables/useServiceLocation.js` |
| في الصفحات القديمة | دوال `locGet` / `locSet` داخل كل ملف |

**عند إضافة فرع جديد يجب تحديث الموضعين معًا.**

---

## للمبرمج الخلفي

المشروع **واجهة أمامية فقط** — كل البيانات مكتوبة في الكود ولا يوجد خادم.

| المستند | المحتوى |
|---|---|
| [`docs/DATA-MODEL.md`](docs/DATA-MODEL.md) | كل الكيانات وحقولها ومواضعها |
| [`docs/API-INTEGRATION.md`](docs/API-INTEGRATION.md) | نقاط النهاية المطلوبة ومكان ربط كل منها |
| [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) | القرارات الفنية والدَّين التقني |

---

## اصطلاحات

- الكود القديم مكتوب بأسلوب مضغوط — **حافظ على أسلوب الملف الذي تعدّله**
- لا مكتبة UI ولا إطار CSS — كل الأنماط يدوية
- كل النصوص عربية مع `dir="rtl"`
- الصور في `public/images/` — استخدم مسارات تبدأ بـ `/images/`

## تطبيق الموبايل PWA

تدعم نسخة الإنتاج التثبيت كتطبيق مستقل من نفس نطاق الواجهة. يبدأ التطبيق من `/?source=pwa` ويحافظ على عرض الموبايل عند تدوير الهاتف. في Android يظهر زر التثبيت عندما يتيح المتصفح ذلك. في iPhone توجد إرشادات الإضافة إلى الشاشة الرئيسية من قائمة المشاركة في Safari.

- شغّل `npm run build` وانشر **كل محتويات `dist/`** في جذر نطاق الواجهة، بما فيها `.htaccess` و`sw.js` و`pwa.js` و`manifest.webmanifest` ومجلد `pwa/`.
- يلزم HTTPS في الإنتاج. يمكن الاختبار محليًا على `localhost` أو `127.0.0.1` باستخدام `npm run preview`. فتح عنوان IP عبر HTTP من الهاتف لا يفعّل Service Worker.
- التسجيل معطّل في `npm run dev` لتجنب بقاء كاش أثناء التطوير. استعمل البناء والمعاينة لاختبار PWA.
- على Apache يضبط `.htaccess` نوع ملف manifest وإعادة التحقق من ملفات PWA. على Nginx أو CDN قدّم manifest بنوع `application/manifest+json` و`sw.js` كـ JavaScript، مع `Cache-Control: no-cache, must-revalidate` لملفات `sw.js` و`pwa.js` و`manifest.webmanifest`. لا تُعد كتابة طلبات هذه الملفات إلى `index.html`.
- دون اتصال، تظهر صفحة عربية/إنجليزية بها زر إعادة المحاولة. الخدمات الحالية والحجز والدفع تحتاج اتصالًا. لا يُخزّن Service Worker صفحات الحساب أو نتائج API أو عمليات الدفع، ولا يعيد إرسال عمليات الحجز تلقائيًا.
- يُولّد البناء إصدار الكاش من محتوى ملفات PWA. ينتظر العامل الجديد إغلاق النوافذ القديمة؛ لا توجد إعادة تحميل تلقائية قد تقطع الحجز أو الدفع. التنظيف يقتصر على كاش `sami-pwa-*`.
- ملفات المصدر: `public/manifest.webmanifest` و`public/pwa.js` و`public/sw.js` و`public/pwa/`. أيقونات التطبيق مشتقة من شعار `public/logo.png`، مع نسخة maskable منفصلة.

اختبارات سياسة التخزين وملفات التثبيت:

```bash
node --test tests/pwa.test.cjs
```

اختبارات Chromium تبدأ خادمًا محليًا مؤقتًا من `dist/` وتفحص قابلية التثبيت، انقطاع الشبكة، زر التثبيت، وإرشادات iPhone. تحتاج Playwright ومتصفح Chromium؛ يمكن تعيين `PLAYWRIGHT_MODULE` لمسار حزمة Playwright المثبتة خارج المشروع. تُتخطى اختبارات المتصفح عند غياب الحزمة.

```bash
npm run build
node --test tests/pwa.test.cjs tests/pwa.browser.test.cjs
```

مرجع متطلبات التثبيت: [MDN — Making PWAs installable](https://developer.mozilla.org/en-US/docs/Web/Progressive_web_apps/Guides/Making_PWAs_installable).