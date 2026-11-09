# النشر (Deploy)

> **الوضع الحالي (2026-11-06):** النشر يتم على **VPS (Hostinger / CloudPanel) فقط**، تلقائياً مع كل push إلى `main` عبر [deploy-vps.yml](../.github/workflows/deploy-vps.yml)؛ يُبنى الباك (composer) والفرونت (`apps/web`) ويُنشران في كل مرة، مع فحص الصحة وتراجع تلقائي عند الفشل. نشر **cPanel موقوف**، وملفاته محفوظة دون حذف في [deploy/cpanel/](../deploy/cpanel/README.md). الشرح أدناه يخص cPanel.

كل دمج في `main` يُنشر تلقائياً على **staging**، وإذا نجح يُنشر على **production**. لا رفع يدوي للملفات.

```
دمج في main
   │
   ├─► Build الباك (composer بدون dev) ── مرة واحدة، نفس النسخة للبيئتين
   │
   ▼
STAGING ── رفع ─► migrate ─► تبديل current ─► فحص الصحة ──✗──► تراجع تلقائي، ويتوقف كل شيء
   │ ✓
   ▼
PRODUCTION ── رفع ─► نسخة احتياطية لقاعدة البيانات ─► migrate ─► تبديل current ─► فحص الصحة ──✗──► تراجع تلقائي
```

- يُنشر فقط الجزء الذي تغيّر: `backend/` أو `apps/web/`.
- الكافيه غير مشمول حتى تُعرف استضافته.
- الملفات: [deploy.yml](../deploy/cpanel/workflows/deploy.yml) و[deploy-env.yml](../deploy/cpanel/workflows/deploy-env.yml) و[rollback.yml](../deploy/cpanel/workflows/rollback.yml)، والسكربت الذي يعمل على السيرفر [deploy/remote.sh](../deploy/remote.sh).

---

## 1. الترتيب على السيرفر

لكل بيئة مجلد مستقل:

```
/home/CPANEL_USER/sami/
├── staging/
│   ├── backend/
│   │   ├── upload/                ← هدف rsync (يُحدَّث بالفروقات فقط)
│   │   ├── releases/<id>/         ← نسخة لكل نشر، آخر 5 فقط
│   │   ├── shared/
│   │   │   ├── .env               ← الإعدادات الحقيقية، تُنشأ يدوياً مرة واحدة
│   │   │   ├── storage/           ← الـ logs والجلسات وملفات storage
│   │   │   ├── public/            ← uploads و profile_images و media (رفع المستخدمين)
│   │   │   └── backups/           ← نسخ قاعدة البيانات قبل migrate (production)
│   │   └── current → releases/<id>
│   └── web/
│       ├── upload/  releases/<id>/
│       └── current → releases/<id>
└── production/                     ← نفس الترتيب
```

`current` رابط (symlink) يتحوّل إلى النسخة الجديدة دفعة واحدة، فلا يرى الزائر موقعاً نصف محدَّث. والتراجع يعني إرجاع الرابط إلى النسخة السابقة.

| النطاق | Document Root في cPanel |
|---|---|
| `staging.sami-care.sa` | `sami/staging/web/current` |
| `staging-dashboard.sami-care.sa` | `sami/staging/backend/current/public` |
| `dashboard.sami-care.sa` | `sami/production/backend/current/public` |
| `sami-care.sa` | `sami/production/web/current` (انظر القسم 7) |

---

## 2. تجهيز SSH (مرة واحدة)

1. **cPanel ← SSH Access**: تأكد أن SSH مفعّل. منفذ Namecheap غالباً `21098`.
2. على جهازك، أنشئ مفتاحاً خاصاً بالنشر فقط:
   ```bash
   ssh-keygen -t ed25519 -C "github-deploy" -f sami-deploy -N ""
   ```
   ينتج ملفين: `sami-deploy` (خاص) و`sami-deploy.pub` (عام).
3. **cPanel ← SSH Access ← Manage SSH Keys ← Import Key**: الصق محتوى `sami-deploy.pub`، ثم اضغط **Authorize**.
4. جرّب الاتصال:
   ```bash
   ssh -p 21098 -i sami-deploy CPANEL_USER@SERVER_HOST "php -v; which php mysqldump"
   ```
   سجّل مسار PHP. إذا كانت النسخة الافتراضية أقدم من 8.1، فالمسار يكون عادة `/opt/cpanel/ea-php82/root/usr/bin/php`.
5. خذ بصمة السيرفر لتثبيتها في GitHub:
   ```bash
   ssh-keyscan -p 21098 SERVER_HOST
   ```

> لا ترسل المفتاح الخاص لأحد، ولا تضعه في الريبو. مكانه أسرار GitHub فقط، ثم احذفه من جهازك أو احفظه في مدير كلمات مرور.

---

## 3. تجهيز staging على cPanel (مرة واحدة)

1. **قاعدة بيانات**: من cPanel ← MySQL Databases، أنشئ قاعدة ومستخدماً لـ staging، منفصلين عن production.
2. **المجلدات**: عبر SSH:
   ```bash
   mkdir -p ~/sami/staging
   ```
   أول نشر سينشئ باقي الترتيب، ويمكن إنشاؤه مسبقاً بـ `DEPLOY_PATH=~/sami/staging bash remote.sh init`.
3. **ملف `.env`**: أنشئ `~/sami/staging/backend/shared/.env` من `backend/.env.example`، وأهم القيم:
   ```dotenv
   APP_ENV=staging
   APP_DEBUG=false
   APP_KEY=            # php artisan key:generate --show (محلياً)
   APP_URL=https://staging-dashboard.sami-care.sa
   FRONTEND_URL=https://staging.sami-care.sa
   DB_DATABASE=...     # قاعدة staging
   DB_USERNAME=...
   DB_PASSWORD=...
   ```
   على staging: مفاتيح بوابات الدفع **التجريبية** فقط، ورسائل SMS وواتساب معطّلة أو موجّهة لأرقام الفريق.
4. **الـ Subdomains**: من cPanel ← Domains، أنشئ `staging` و`staging-dashboard` بالـ Document Root المذكور في الجدول. cPanel قد ينشئ مجلد `current` فارغاً، وهذا طبيعي لأن أول نشر يستبدله تلقائياً.
5. **Cron**: لا تفعّل `schedule:run` على staging إلا إذا كانت الرسائل معطّلة في `.env` الخاص بها.

---

## 4. إعداد GitHub (مرة واحدة)

**Settings ← Environments**: أنشئ بيئتين: `staging` و`production`.

### الأسرار (Secrets): على مستوى الريبو لأن السيرفر واحد

| السر | القيمة |
|---|---|
| `SSH_PRIVATE_KEY` | محتوى الملف `sami-deploy` كاملاً |
| `SSH_KNOWN_HOSTS` | ناتج `ssh-keyscan` من الخطوة 2.5 |

### المتغيرات (Variables)

| المتغير | المستوى | staging | production |
|---|---|---|---|
| `SSH_HOST` | الريبو | `server.example.com` | (نفسه) |
| `SSH_PORT` | الريبو | `21098` | (نفسه) |
| `SSH_USER` | الريبو | `CPANEL_USER` | (نفسه) |
| `PHP_BIN` | الريبو | `/usr/local/bin/php` | (نفسه) |
| `DEPLOY_PATH` | البيئة | `/home/CPANEL_USER/sami/staging` | `/home/CPANEL_USER/sami/production` |
| `API_URL` | البيئة | `https://staging-dashboard.sami-care.sa` | `https://dashboard.sami-care.sa` |
| `WEB_URL` | البيئة | `https://staging.sami-care.sa` | `https://sami-care.sa` |
| `VITE_API_BASE_URL` | البيئة | `https://staging-dashboard.sami-care.sa/api` | `https://dashboard.sami-care.sa/api` |

متغيرات البيئة تتقدّم على متغيرات الريبو إذا تكرر الاسم.

**مهم: حتى ينتهي نقل production (القسم 7)**، أضف في بيئة `production` قاعدة **Required reviewers**. بهذا يتوقف الـ pipeline بعد staging ولا يلمس الموقع الحقيقي. وبعد النقل احذف القاعدة ليصبح النشر تلقائياً بالكامل.

---

## 5. ماذا يحدث عند كل نشر؟

| الخطوة | التفاصيل | عند الفشل |
|---|---|---|
| Build الباك | `composer install --no-dev` على GitHub، ثم أرشيف واحد للبيئتين | يتوقف كل شيء، ولا شيء على السيرفر تغيّر |
| رفع | rsync يرفع الفروقات إلى `upload/` | يتوقف، والموقع الحالي لم يتغير |
| نسخة جديدة | نسخ `upload/` إلى `releases/<id>/` بروابط صلبة (فوري ولا يستهلك مساحة)، وربط `.env` و`storage` المشتركين | تُحذف النسخة الفاشلة |
| نسخة احتياطية | production فقط: `mysqldump` إلى `shared/backups/db-<id>.sql.gz`، وتُحفظ آخر 10 نسخ | يتوقف قبل migrate |
| migrate | `php artisan migrate --force` | تُحذف النسخة، و`current` لم يتغير. لكن قد يكون جزء من الـ migration طُبِّق، وعندها تُستعاد النسخة الاحتياطية |
| تبديل | `current` يشير إلى النسخة الجديدة | — |
| فحص الصحة | الباك: `/api/health` يرد بنفس رقم النسخة وقاعدة البيانات متصلة. الواجهة: `/release.txt` يطابق والصفحة الرئيسية ترد | **تراجع تلقائي** إلى النسخة السابقة، والـ pipeline يفشل ولا يكمل إلى production |

**الكاش:** يشغّل النشر `config:cache` و`event:cache` و`route:cache` لكل إصدار، لذلك يجب ألا يُستدعى `env()` خارج `config/` (استخدم `config('sami.*')`). بعد أي تعديل على `shared/.env` شغّل `php artisan config:cache` من `current/`. `route:cache` يفشل لو فيه اسم مسار مكرر، فيتوقف النشر قبل تفعيل الإصدار (وفحص CI يكشف ده مسبقاً). لا نستخدم `cache:clear` حتى لا تضيع رموز OTP المؤقتة.

**إعدادات الإنتاج في `shared/.env`:** `APP_ENV=production`، `APP_DEBUG=false`، `TELESCOPE_ENABLED=false`، و`QUEUE_CONNECTION=database` مع worker دائم:
`* * * * * cd <DEPLOY_PATH>/backend/current && php artisan queue:work --stop-when-empty --max-time=55 >> /dev/null 2>&1` (أو supervisor).

---

## 6. التراجع (Rollback)

**من GitHub:** Actions ← **Rollback** ← Run workflow، ثم اختر البيئة والتطبيق. يرجع إلى النسخة السابقة ويحذف النسخة المتروكة. للتقدم مرة أخرى، أعد النشر من Actions ← **Deploy** ← Run workflow.

**من السيرفر مباشرة:**
```bash
DEPLOY_PATH=~/sami/production bash ~/sami/production/.deploy/remote.sh status
DEPLOY_PATH=~/sami/production PHP_BIN=/usr/local/bin/php bash ~/sami/production/.deploy/remote.sh rollback backend
```

**التراجع لا يعكس الـ migrations.** إذا أفسد migration البيانات، فاستعد النسخة الاحتياطية:
```bash
gunzip < ~/sami/production/backend/shared/backups/db-<id>.sql.gz | mysql -u DB_USER -p DB_NAME
```

---

## 7. نقل production من الإعداد اليدوي الحالي

يُنفَّذ **مرة واحدة**، في وقت قليل الزيارات، وبعد أن يعمل staging بنجاح:

1. نسخة احتياطية كاملة: قاعدة البيانات، ومجلد الباك الحالي (`public_html/sami-care` حسب التوثيق القديم)، ومجلد الواجهة.
2. `mkdir -p ~/sami/production`، ثم انسخ `.env` الحالي إلى `~/sami/production/backend/shared/.env`.
3. انسخ الملفات المرفوعة من الباك القديم (التطبيق يحفظ صور المستخدمين داخل `public/` مباشرة):
   ```bash
   OLD=~/public_html/sami-care          # مسار الباك القديم الفعلي
   NEW=~/sami/production/backend/shared
   mkdir -p $NEW/public $NEW/storage/app
   cp -a $OLD/storage/app/public   $NEW/storage/app/
   cp -a $OLD/public/uploads       $NEW/public/
   cp -a $OLD/public/profile_images $NEW/public/ 2>/dev/null || true
   cp -a $OLD/public/media         $NEW/public/ 2>/dev/null || true
   ```
4. شغّل Deploy من GitHub ووافق على خطوة production (Required reviewers). فحص الصحة **سيفشل** لأن النطاقات ما زالت تشير إلى الإعداد القديم، والتراجع التلقائي لن يجد نسخة سابقة، فيبقى `current` على النسخة الجديدة. هذا متوقع في هذه الخطوة فقط، والموقع الحالي لم يتأثر.
5. **غيّر Document Root**:
   - `dashboard.sami-care.sa`: إلى `sami/production/backend/current/public`.
   - `sami-care.sa`: إذا كان النطاق الرئيسي للحساب فمجلده `public_html` ثابت، فاجعله رابطاً:
     ```bash
     mv ~/public_html ~/public_html.old && ln -s ~/sami/production/web/current ~/public_html
     ```
     إذا رفضت الاستضافة هذا، فتواصل مع دعم Namecheap لتغيير Document Root.
6. حدّث الـ Cron إلى: `/usr/local/bin/php /home/CPANEL_USER/sami/production/backend/current/artisan schedule:run`.
7. أعد Deploy وتأكد من نجاح فحص الصحة. ثم احذف قاعدة Required reviewers من بيئة production (القسم 4).
8. احتفظ بالمجلدات القديمة أسبوعاً ثم احذفها.

---

## 8. حل المشاكل

| الرسالة | السبب والحل |
|---|---|
| `Permission denied (publickey)` | المفتاح العام غير مفعّل (Authorize) في cPanel، أو `SSH_PRIVATE_KEY` ناقص |
| `Host key verification failed` | `SSH_KNOWN_HOSTS` لا يطابق السيرفر. أعد `ssh-keyscan` |
| `Missing variables in the 'staging' environment` | أضف المتغيرات المذكورة في القسم 4 |
| `shared/.env is missing` | أنشئ الملف (القسم 3.3) |
| `current is a real directory containing files` | مجلد `current` فيه ملفات حقيقية. انقلها يدوياً، فالسكربت لا يحذف ملفات حقيقية أبداً |
| فشل فحص الصحة | راجع `backend/shared/storage/logs/laravel.log` على السيرفر، وتأكد أن Document Root يشير إلى `current/public` |
| `mysqldump not found` | اطلب من الاستضافة مساره، أو أضفه إلى `PATH` |

---

## ملاحظات

- staging وproduction على نفس حساب cPanel، فكود staging يستطيع نظرياً قراءة ملفات production. هذا مقبول حالياً، والأفضل لاحقاً حساب أو سيرفر منفصل.
- لإضافة الكافيه لاحقاً: متغيرات `CAFE_URL` وما يشبهها، وخطوة build ورفع مثل `web` في `deploy-env.yml`، ونوع `cafe` في `remote.sh`.
