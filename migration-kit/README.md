# نقل مشروع Laravel من Hostinger (CloudPanel) إلى cPanel

ملفان يُنفَّذان بالترتيب، وبعدهما 4 خطوات من واجهة cPanel.

| الملف | أين يُنفَّذ | ماذا يفعل |
|---|---|---|
| `1-hostinger-export.sh` | Hostinger كـ `root` | يصدّر القاعدة ويضغط الملفات (لا يغيّر الموقع الحالي) |
| `2-cpanel-import.sh` | cPanel بمستخدم الاستضافة | ينشئ القاعدة، يسحب الملفات، يستورد، ويجهّز Laravel |

---

## قبل البدء

1. **أنشئ الدومين على cPanel:** Domains ← Create A New Domain (مثلاً `newproject.city2tec.com`).
2. **اعرف مسار المشروع على Hostinger** — المجلد الذي فيه `artisan`، مثل:
   `/home/USER/htdocs/DOMAIN`
   (إذا شغّلت السكربت الأول بدون مسار سيعرض لك كل المشاريع الموجودة.)

---

## الخطوة 1 — على Hostinger

ارفع الملف `1-hostinger-export.sh` إلى `/root/` ثم:

```bash
bash /root/1-hostinger-export.sh /home/USER/htdocs/DOMAIN
```

في آخر الناتج ستجد:
- **عنوان IPv4** للخادم
- **مسار مجلد النقل** مثل `/root/migrate-DOMAIN`

احفظهما للخطوة 2. وراجع قسم **Cron** في الناتج: إذا ظهرت أوامر فالمشروع يعتمد على مهام مجدولة.

---

## الخطوة 2 — على cPanel

ارفع `2-cpanel-import.sh` إلى المجلد الرئيسي (`/home/city2tec/`)، **وافتحه وعدّل قسم الإعدادات** في أعلاه:

```bash
DOMAIN="newproject.city2tec.com"   # الدومين الذي أنشأته
DB_SHORT="newproj"                 # اسم قصير للقاعدة (حروف وأرقام)
HOSTINGER_IP="..."                 # من ناتج الخطوة 1
REMOTE_DIR="/root/migrate-..."     # من ناتج الخطوة 1
PHP_VERSION="84"                   # مثل نسخة Hostinger أو أحدث
DEMO_MODE="true"                   # true لنسخة عرض: يعطّل الواتساب والإيميل والإشعارات
```

ثم:

```bash
bash ~/2-cpanel-import.sh
```

- سيطلب **كلمة سر root لـ Hostinger** (لسحب الملفات).
- `composer install` يأخذ عدة دقائق — انتظر.
- في النهاية يطبع **كلمة سر قاعدة البيانات** — احفظها.

---

## الخطوة 3 — من واجهة cPanel

1. **Domains** ← الدومين ← **Manage** ← Document Root = `اسم-الدومين/public` ← **Update**
2. **MultiPHP Manager** ← الدومين ← نفس نسخة PHP
3. **SSL/TLS Status** ← **Run AutoSSL**
4. **Cron Jobs** (فقط إذا أردت المهام المجدولة — لا تضفه لنسخة عرض):
   ```
   * * * * *  cd /home/city2tec/DOMAIN && /opt/cpanel/ea-php84/root/usr/bin/php artisan schedule:run >> /dev/null 2>&1
   ```

---

## الخطوة 4 — التحقق

```bash
cd ~/DOMAIN
uapi DomainInfo single_domain_data domain=DOMAIN | grep -i documentroot   # يجب أن ينتهي بـ /public
curl -sIL https://DOMAIN | grep -iE '^(HTTP|location)'                     # 200 أو 302 ثم 200
curl -s -o /dev/null -w "env: %{http_code}\n" https://DOMAIN/.env          # يجب 403 أو 404
tail -5 storage/logs/laravel.log
```

ثم احذف ملفات النقل:
- على cPanel: `rm -rf ~/migrate-DB_SHORT`
- على Hostinger: `rm -rf /root/migrate-*`

---

## مشاكل واجهناها في النقل الأول وحلولها

| المشكلة | السبب | الحل |
|---|---|---|
| **404** رغم أن كل شيء صحيح | Document Root لم يُحفظ على `/public` | أعد الخطوة 3.1 وتحقق بأمر `uapi` أعلاه |
| **404** بعد ضبط Document Root | صلاحيات الملفات 660 من Hostinger لا يقرؤها خادم الويب | السكربت يصلحها؛ يدوياً: `find public -type f -exec chmod 644 {} +` و`-type d ... 755` |
| `Access denied` أثناء التصدير | كلمة السر في `.env` فيها رموز | السكربت يستخدم `clpctl db:export` فلا يحتاجها |
| خطأ `DEFINER` أو `Unknown collation` عند الاستيراد | فرق بين MySQL 8 وخادم cPanel | السكربت يعالجها تلقائياً |
| `session.gc_divisor must be greater than 0` في `error_log` | إعداد PHP 8.4 عند الاستضافة | غير مؤثر؛ السكربت يمرّر `-d session.gc_divisor=100`. للموقع: MultiPHP INI Editor ← أضف `session.gc_divisor = 100` |
| `scp` لا يتصل | عنوان IPv6 بدل IPv4 | استخدم العنوان الذي يطبعه السكربت الأول (`curl -4`) |
| الموقع **بطيء جداً** عند فتح عدة صفحات | حدود الاستضافة المشتركة (Entry Processes / CPU / IO) | cPanel ← Metrics ← **Resource Usage**؛ إن ظهرت Faults اطلب رفع الحدود من الاستضافة |

---

## بعد النقل (إن كان المشروع نسخة عرض)

- **الفرونت اند:** غيّر رابط الباك اند في كود الواجهة إلى الدومين الجديد ثم أعد البناء (`npm run build`).
- **`APP_NAME`** في `.env` يظهر في عنوان تبويب المتصفح — عدّله ثم `php artisan config:cache`.
- **النصوص والشعار** المخزّنة في قاعدة البيانات لا تتغيّر بتعديل الكود — تُعدَّل من لوحة التحكم.
