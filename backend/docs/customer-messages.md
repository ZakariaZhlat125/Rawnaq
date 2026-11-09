# رسائل العملاء والإشعارات

- إضافة عرض من الداشبورد تسجل إشعاراً عربياً في صندوق إشعارات العملاء النشطين، باستخدام عنوان العرض ووصفه بالعربية. تعديل العرض لا يعيد إرسال الإشعار.
- إضافة باقة جديدة من الداشبورد تسجل إشعاراً عربياً للعملاء النشطين وغير المحظورين باستخدام اسم الباقة ووصفها بالعربية. لا يعاد الإشعار عند تعديل الباقة، ويحفظ مع الباقة في نفس transaction.
- إعدادات «محتوى الرئيسية» تحتوي على صورة قسم المقهى، وتظهر في نسختي الكمبيوتر والموبايل. الصور حتى 2 ميجابايت بصيغ JPG/PNG/WebP.
- نموذج إعدادات حساب العميل يحتوي على تاريخ ميلاد اختياري. المطابقة اليومية تعتمد على اليوم والشهر فقط؛ 29 فبراير يطابق 29 فبراير فقط.
- صفحة «رسائل العملاء والمناسبات» متاحة لدور `admin` على `/app/customer-messages`. تتيح تعديل نص تهنئة الميلاد، وتعطيلها، وإرسال رسالة مناسبة فوراً لكل العملاء النشطين أو مجموعة محددة عند الضغط على «إرسال الآن». لا يوجد حقل لموعد الإرسال، ولا تعتمد المناسبات على cron أو queue worker. تظهر نتيجة الإرسال في الصفحة والسجل.
- المتغيران `{name}` و`{occasion}` يستبدلان باسم العميل والمناسبة. كل مناسبة ترسل مرة واحدة؛ لإنشاء مناسبة سنوية أخرى يجب إنشاء رسالة جديدة.
- التهنئة اليومية مفعلة افتراضياً الساعة 08:00 وفق `config('app.timezone')` (الافتراضي UTC). الأمر `customers:send-messages` يجهّز ويرسل رسائل ميلاد اليوم فقط، ويحافظ على منع تكرارها عند تشغيله مرة أخرى. لا يحتاج الخيار القديم `--birthdays`.
- المناسبات القديمة التي بقيت معلقة لا تُرسل عبر الـcommand بعد هذا التعديل. لإرسالها أنشئ رسالة مناسبة من الداشبورد واضغط «إرسال الآن». حقل `send_at` في السجل يسجل وقت طلب الإرسال فقط.

## التفعيل على السيرفر

من مجلد تطبيق Laravel (`sami-care`):

```sh
composer install
php artisan migrate --force
php artisan optimize:clear
```

فعّل الـLaravel scheduler مرة كل دقيقة في cron، مع استبدال المسارات بمسارات السيرفر الفعلية:

```cron
* * * * * /usr/bin/php /absolute/path/sami-care/artisan schedule:run >> /absolute/path/sami-care/storage/logs/scheduler.log 2>&1
```

في Windows يمكن إعداد Task Scheduler لتشغيل `php artisan schedule:run` كل دقيقة من مجلد التطبيق.

## إعداد Namecheap cPanel

استضافة Namecheap المشتركة تسمح بتشغيل cron كل **5 دقائق أو أكثر**، ولا تسمح بأقل من 5 دقائق. لذلك استخدم cron واحداً لتشغيل مجدول Laravel كل 5 دقائق، وهو يشغّل أمر رسائل الميلاد الساعة 08:00. رسائل المناسبات ترسل فوراً من الداشبورد ولا يرسلها المجدول.

1. افتح **cPanel → Advanced → Cron Jobs**.
2. اختر **Once Per Five Minutes**، أو أدخل `*/5` في Minute و`*` في Hour / Day / Month / Weekday.
3. في **Command** أدخل الأمر التالي **دون** حقول `*/5 * * * *` لأنها موجودة في خانات التوقيت:

```sh
/usr/local/bin/php /home/CPANEL_USER/public_html/sami-care/artisan schedule:run >> /home/CPANEL_USER/public_html/sami-care/storage/logs/scheduler.log 2>&1
```

استبدل `CPANEL_USER` باسم مستخدم cPanel، واستبدل مسار التطبيق بالمسار الفعلي الذي يحتوي على ملف `artisan` من File Manager. قد يكون `/home/CPANEL_USER/sami-care/artisan` إذا كان Laravel خارج `public_html`. مسار `storage/logs/scheduler.log` يجب أن يتبع التطبيق نفسه وأن يكون قابلاً للكتابة.

4. تحقق من إصدار PHP المستخدم في Terminal:

```sh
/usr/local/bin/php -v
```

يجب أن يتوافق مع متطلبات المشروع في `composer.lock`. إذا اختلف عن إصدار الموقع، اطلب مسار PHP CLI الصحيح لإصدار موقعك من دعم Namecheap واستخدمه بدلاً من `/usr/local/bin/php`.

5. من مجلد Laravel على السيرفر، طبق migrations إذا لم تكن مطبقة، ثم حدّث إعدادات البيئة:

```sh
/usr/local/bin/php artisan migrate --force
/usr/local/bin/php artisan optimize:clear
```

في `.env` اضبط `APP_ENV=production`. لا تستخدم `CACHE_DRIVER=array` في الإنتاج لأن أقفال منع تداخل تشغيل المجدول تحتاج cache دائم؛ `CACHE_DRIVER=file` مناسب لسيرفر واحد. الإعداد الافتراضي الحالي للتوقيت `APP_TIMEZONE=UTC`. إذا أردت رسائل الميلاد 8 صباحاً بتوقيت السعودية، ضع `APP_TIMEZONE=Asia/Riyadh`؛ أو `APP_TIMEZONE=Africa/Cairo` لتوقيت القاهرة. هذا يغير توقيت التطبيق كله.

6. فعّل رسائل الميلاد ونصها من **رسائل العملاء والمناسبات**، وتأكد من إعداد مفتاح ومرسل بوابة Taqnyat وتفعيلها.
7. راجع الجدولة دون إرسال رسائل فعلية باستخدام:

```sh
/usr/local/bin/php artisan schedule:list
```

تابع `storage/logs/scheduler.log` و`storage/logs/laravel.log` وسجل الإرسال في الداشبورد. احتفظ بـcron الذي يشغل `schedule:run` كما هو؛ تشغيل رسائل الميلاد يومياً يحدده `Kernel.php`. إذا كنت تستخدم cron مباشراً للأمر القديم، استبدل `customers:send-messages --birthdays` بـ`customers:send-messages` واجعله يومياً في التوقيت المطلوب. لا يلزم cron للمناسبات ولا queue worker لهذه الرسائل.

المراجع: [طريقة إعداد cron في Namecheap](https://www.namecheap.com/support/knowledgebase/article.aspx/9453/29/how-to-run-scripts-via-cron-jobs/)، [حدود cron في الاستضافة المشتركة](https://www.namecheap.com/support/knowledgebase/article.aspx/1212/22/do-you-allow-cron-jobs/).

بوابة Taqnyat تحتاج إعدادات `is_taqnyat_sms` و`taqnyat_api_key` والمرسل كما في الآلية الحالية. تستخدم الرسائل `sendWhatsApp` الحالية بما يشمل التحويل إلى SMS عند فشل الواتساب. لا تحتاج هذه الميزة إلى queue worker؛ المناسبات ترسل مباشرة داخل طلب الداشبورد، وأعياد الميلاد ترسل عبر المجدول.

حالات الإرسال تظهر في سجل المناسبات. `simulated` تعني محاكاة في البيئة المحلية دون إرسال فعلي. الفشل لا يعاد تلقائياً لتجنب الرسائل المكررة؛ يمكن إنشاء مناسبة جديدة للمستلمين المطلوبين. حالة `processing` التي تبقى بعد توقف العملية تحتاج مراجعة سجل السيرفر ومزود الرسائل قبل إعادة الإرسال، لأن قبول المزود للرسالة لا يمكن حسمه من قاعدة البيانات وحدها.

## التحقق

```sh
php artisan test --filter=CustomerMessagesTest
php artisan test --filter=CustomerNotificationsTest
php artisan test --filter=NewPackageCustomerNotificationTest
php tests/Unit/HomePageContentSmoke.php
```

ومن مجلد `apps/web`: `npm run build`.
