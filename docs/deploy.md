# النشر (Deploy)

النشر على **سيرفر cPanel فقط** (نسخة Rawnaq التسويقية)، تلقائياً مع كل push إلى `main` عبر [deploy-cpanel.yml](../.github/workflows/deploy-cpanel.yml). يُبنى الباك (composer) والموقع (`apps/web`) على GitHub ويُرفعان في كل مرة، مع فحص صحة وتراجع تلقائي عند الفشل.

| | |
|---|---|
| موقع العملاء | `https://rawnaq.city2tec.com` |
| لوحة التحكم والـ API | `https://rawnaq-api.city2tec.com` |
| السيرفر | `city2tec@195.250.26.84` (SSH على المنفذ 22) |
| المجلد | `/home/city2tec/rawnaq` |
| PHP | `/opt/cpanel/ea-php84/root/usr/bin/php` |

```
push إلى main
   │
   ▼
Build على GitHub (composer بدون dev + npm build للموقع)
   │
   ▼
رفع (rsync) ─► نسخة احتياطية للقاعدة ─► migrate ─► تبديل current ─► فحص الصحة ──✗──► تراجع تلقائي
```

الكافيه غير مشمول بعد.

---

## 1. الترتيب على السيرفر

```
/home/city2tec/rawnaq/
├── backend/
│   ├── upload/                ← هدف rsync (يُحدَّث بالفروقات فقط)
│   ├── releases/<id>/         ← نسخة لكل نشر، آخر 5 فقط
│   ├── shared/
│   │   ├── .env               ← الإعدادات الحقيقية، لا تُرفع من git أبداً
│   │   ├── storage/           ← الـ logs والجلسات وملفات storage
│   │   ├── public/            ← uploads و profile_images و media (رفع المستخدمين)
│   │   └── backups/           ← نسخ قاعدة البيانات قبل كل migrate (آخر 10)
│   └── current → releases/<id>
└── web/
    ├── upload/  releases/<id>/
    └── current → releases/<id>
```

`current` رابط يتحوّل إلى النسخة الجديدة دفعة واحدة، فلا يرى الزائر موقعاً نصف محدَّث. التراجع يعني إرجاع الرابط إلى النسخة السابقة. السكربت الذي يدير هذا على السيرفر: [deploy/remote.sh](../deploy/remote.sh).

| الدومين | Document Root في cPanel |
|---|---|
| `rawnaq.city2tec.com` | `rawnaq/web/current` |
| `rawnaq-api.city2tec.com` | `rawnaq/backend/current/public` |

النسخة الأولى (الكود والقاعدة والصور) نُقلت من Hostinger بسكربتات [migration-kit/sami-care/](../migration-kit/sami-care/).

---

## 2. إعدادات GitHub

**Settings ← Secrets and variables ← Actions**

| النوع | الاسم | القيمة |
|---|---|---|
| Secret | `CPANEL_SSH_PRIVATE_KEY` | المفتاح الخاص للنشر (مفتاحه العام في `~/.ssh/authorized_keys` على cPanel) |
| Secret | `CPANEL_SSH_KNOWN_HOSTS` | ناتج `ssh-keyscan -p 22 195.250.26.84` |
| Variable | `CPANEL_SSH_HOST` | `195.250.26.84` |
| Variable | `CPANEL_SSH_PORT` | `22` |
| Variable | `CPANEL_SSH_USER` | `city2tec` |
| Variable | `CPANEL_PHP_BIN` | `/opt/cpanel/ea-php84/root/usr/bin/php` |
| Variable | `CPANEL_DEPLOY_PATH` | `/home/city2tec/rawnaq` |
| Variable | `CPANEL_API_URL` | `https://rawnaq-api.city2tec.com` |
| Variable | `CPANEL_WEB_URL` | `https://rawnaq.city2tec.com` |
| Variable | `CPANEL_VITE_API_BASE_URL` | `https://rawnaq-api.city2tec.com/api` |

> لضبط متغير يبدأ بـ `/` من Git Bash على ويندوز استعمل `MSYS_NO_PATHCONV=1 gh variable set ...`، وإلا حوّله Git Bash إلى مسار ويندوز.

---

## 3. ماذا يحدث عند كل نشر؟

| الخطوة | التفاصيل | عند الفشل |
|---|---|---|
| Build | `composer install --no-dev` و`npm run build` على GitHub | يتوقف، ولا شيء على السيرفر تغيّر |
| عنوان الـ API | الملفات المبنية التي فيها `https://dashboard.sami-care.sa` (الكافيه وصفحات الجوال) تُحوَّل إلى `CPANEL_API_URL` حتى لا تتصل النسخة التسويقية بالموقع الحقيقي | — |
| رفع | rsync يرفع الفروقات إلى `upload/` | يتوقف، والموقع الحالي لم يتغير |
| نسخة جديدة | نسخ `upload/` إلى `releases/<id>/` بروابط صلبة، وربط `.env` و`storage` و`public/uploads` المشتركة | تُحذف النسخة الفاشلة |
| نسخة احتياطية | `mysqldump` إلى `shared/backups/db-<id>.sql.gz` | يتوقف قبل migrate |
| migrate | `php artisan migrate --force` | تُحذف النسخة و`current` لم يتغير. إن طُبِّق جزء من الـ migration تُستعاد النسخة الاحتياطية |
| تبديل | `current` يشير إلى النسخة الجديدة، ثم `config:cache` و`event:cache` و`route:cache` | — |
| فحص الصحة | الباك: `/api/health` يرد بنفس رقم النسخة وقاعدة البيانات متصلة. الموقع: `/release.txt` يطابق والصفحة الرئيسية ترد | **تراجع تلقائي** إلى النسخة السابقة، والـ workflow يفشل |

**الكاش:** لأن الإعدادات تُخزَّن بـ `config:cache`، لا يُستدعى `env()` خارج `config/`. بعد أي تعديل على `shared/.env` شغّل من `current/`:

```bash
/opt/cpanel/ea-php84/root/usr/bin/php artisan config:cache
```

**نسخة عرض:** في `shared/.env` الإيميل `log`، والواتساب والإشعارات و الـ realtime معطّلة، وفي القاعدة SMS وإشعارات الجوال وبوابات الدفع معطّلة. لا يوجد Cron لهذه النسخة، ولا تضف `schedule:run`.

---

## 4. التراجع (Rollback)

**من GitHub:** Actions ← **Rollback cPanel** ← Run workflow، واختر `backend` أو `web`. يرجع إلى النسخة السابقة ويحذف النسخة المتروكة. للتقدّم مرة أخرى: Actions ← **Deploy cPanel** ← Run workflow.

**من السيرفر مباشرة:**

```bash
DEPLOY_PATH=~/rawnaq bash ~/rawnaq/.deploy/remote.sh status
DEPLOY_PATH=~/rawnaq PHP_BIN=/opt/cpanel/ea-php84/root/usr/bin/php bash ~/rawnaq/.deploy/remote.sh rollback backend
```

**التراجع لا يعكس الـ migrations.** لاستعادة القاعدة:

```bash
gunzip < ~/rawnaq/backend/shared/backups/db-<id>.sql.gz | mysql -u DB_USER -p DB_NAME
```

---

## 5. حل المشاكل

| الرسالة | السبب والحل |
|---|---|
| `Permission denied (publickey)` | المفتاح العام غير موجود في `~/.ssh/authorized_keys` على cPanel، أو `CPANEL_SSH_PRIVATE_KEY` ناقص |
| `Host key verification failed` | `CPANEL_SSH_KNOWN_HOSTS` لا يطابق السيرفر. أعد `ssh-keyscan` |
| `Missing repository variables` | أضف المتغيرات في القسم 2 |
| `shared/.env is missing` | الملف `~/rawnaq/backend/shared/.env` غير موجود |
| `current is a real directory containing files` | مجلد `current` فيه ملفات حقيقية. انقلها يدوياً، فالسكربت لا يحذف ملفات حقيقية |
| فشل فحص الصحة | راجع `~/rawnaq/backend/shared/storage/logs/laravel.log`، وتأكد أن Document Root يشير إلى `current/public` |
| `There is no existing directory at "/home/sami/..."` | كاش منسوخ من سيرفر آخر: `rm -f bootstrap/cache/*.php` داخل `current/` ثم `config:cache` |
