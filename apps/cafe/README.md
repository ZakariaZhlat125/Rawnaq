# Sami Cafe — Vue 3 + TypeScript + Pinia

معمارية إنتاج احترافية تستخدم TypeScript وPinia وRepository Pattern مع الحفاظ على توافق بيانات النسخة الأصلية.

## التشغيل
```bash
npm install
npm run dev
```

## البناء
```bash
npm run build
npm run preview
npm run type-check
npm run lint
npm run test
```

## الهيكل
- `views/`: صفحات QR والقائمة وتتبع الطلب.
- `components/`: مكونات المنتجات والسلة والعلامة التجارية.
- `stores/`: Pinia وحفظ السلة وقواعد الطلبات.
- `services/`: Local وHTTP repositories قابلة للتبديل من متغيرات البيئة.
- `constants/`: بيانات القائمة وحالات الطلب.
- `composables/`: السلوكيات المشتركة مثل Toast.

انسخ `.env.example` إلى `.env`. عند تجهيز Backend اجعل `VITE_USE_MOCK_API=false`. المسارات المطلوبة: `GET /menu` و`POST /orders` و`GET /orders/:id`.

يستخدم الطلب `clientToken` لمنع التكرار، ويجب فرض unique constraint عليه في الـBackend أيضًا.
