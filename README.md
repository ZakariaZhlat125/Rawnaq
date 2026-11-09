# Sami Care

ريبو واحد يضم الباك إند وواجهات العملاء.

```
backend/        Laravel — الـ API ولوحة الأدمن (Blade). كل ميزة داخل Modules/<Feature>/
apps/web/       موقع العملاء (Vue 3 + Vite)
apps/cafe/      تطبيق الكافيه (Vue 3 + TypeScript + Vite)
archive/        مشاريع متوقفة، للرجوع إليها فقط
docs/           مواصفات الأقسام وقرارات الفريق
.github/        CI، قالب الـ PR، ومالكو المسارات
```

الواجهات تتصل بالباك عبر `VITE_API_BASE_URL` فقط، فكل جزء يُبنى ويُنشر وحده.

## التشغيل محلياً

المتطلبات: PHP 8.3، Composer، Node 22، MySQL.

### الباك إند

```bash
cd backend
cp .env.example .env          # عدّل إعدادات قاعدة البيانات
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve             # http://127.0.0.1:8000
```

إذا ظهر الخطأ `Please provide a valid cache path`، أنشئ المجلدات:

```bash
mkdir -p storage/framework/{cache/data,sessions,views} storage/logs bootstrap/cache
```

### موقع العملاء

```bash
cd apps/web
cp .env.example .env          # VITE_API_BASE_URL=http://127.0.0.1:8000/api
npm ci
npm run dev
```

### الكافيه

```bash
cd apps/cafe
npm ci
npm run dev
```

## الاختبارات

| الجزء | الأمر |
|---|---|
| backend | `php artisan test` |
| apps/web | `node --test tests/<file>.test.cjs` |
| apps/cafe | `npm test` و `npm run lint` |

الـ CI يشغّل الجزء الذي تغيّر فقط في كل PR (`.github/workflows/`).

## النشر

كل push إلى `main` يُنشر تلقائياً على سيرفر cPanel (`rawnaq.city2tec.com` و`rawnaq-api.city2tec.com`)، مع فحص صحة وتراجع تلقائي. الإعداد والتراجع اليدوي في [docs/deploy.md](docs/deploy.md).

## طريقة العمل

اقرأ [CONTRIBUTING.md](CONTRIBUTING.md) قبل أول PR.
