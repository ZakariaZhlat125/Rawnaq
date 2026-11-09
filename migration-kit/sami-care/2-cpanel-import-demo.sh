#!/usr/bin/env bash
# =============================================================================
#  Sami Care — الخطوة 2: نسخة عرض تسويقية على cPanel (تُنفَّذ بمستخدم الاستضافة)
#
#  تسحب ملفات التصدير من Hostinger (1-hostinger-export.sh) وتركّب نسخة مستقلة
#  بنفس ترتيب النشر (releases/shared/current) حتى يعمل النشر التلقائي لاحقاً.
#
#  نسخة عرض: لا ترسل أي شيء للعملاء — لا SMS ولا واتساب ولا إشعارات جوال ولا
#  إيميل ولا webhook، وبوابات الدفع معطّلة، ولا Cron.
#
#  لا تلمس sami-care.sa ولا ~/sami ولا قاعدة city2tec_sami.
#  يمكن إعادة تشغيلها لتحديث البيانات من Hostinger.
#
#  الاستخدام:   bash ~/2-cpanel-import-demo.sh
# =============================================================================
set -euo pipefail

# ============================ الإعدادات ============================
WEB_DOMAIN="rawnaq.city2tec.com"          # موقع العملاء
API_DOMAIN="rawnaq-api.city2tec.com"      # لوحة التحكم والـ API
DB_SHORT="rawnaq"                         # القاعدة: <حساب cPanel>_rawnaq
HOSTINGER_IP="187.6.167.53"
REMOTE_DIR="/root/migrate-sami"
PHP_VERSION="84"                          # نفس Hostinger (vendor مبني على 8.4)
# ===================================================================

CPUSER="$(whoami)"
DB_NAME="${CPUSER}_${DB_SHORT}"
DB_USER="${CPUSER}_${DB_SHORT}"
DEPLOY_PATH="$HOME/$DB_SHORT"
B="$DEPLOY_PATH/backend"
W="$DEPLOY_PATH/web"
WORK="$HOME/migrate-$DB_SHORT"
PHP_BIN="/opt/cpanel/ea-php${PHP_VERSION}/root/usr/bin/php"
PHP="$PHP_BIN -d session.gc_divisor=100"
TS="$(date +%Y%m%d%H%M%S)"

step() { echo; echo "================ $* ================"; }
die()  { echo "❌ $*" >&2; exit 1; }

[ -x "$PHP_BIN" ] || die "PHP $PHP_VERSION غير موجود في $PHP_BIN"

step "0) التحقق من الدومينات"
docroot() { uapi DomainInfo single_domain_data domain="$1" | awk '/documentroot:/ && !r {r=$2} END {print r}'; }
WEB_ROOT="$(docroot "$WEB_DOMAIN")"
API_ROOT="$(docroot "$API_DOMAIN")"
echo "$WEB_DOMAIN -> ${WEB_ROOT:-غير موجود}"
echo "$API_DOMAIN -> ${API_ROOT:-غير موجود}"
[ "$WEB_ROOT" = "$W/current" ] || die "Document Root لـ $WEB_DOMAIN يجب أن يكون $W/current"
[ "$API_ROOT" = "$B/current/public" ] || die "Document Root لـ $API_DOMAIN يجب أن يكون $B/current/public"

mkdir -p "$WORK"
chmod 700 "$WORK"

step "1) سحب ملفات التصدير من Hostinger (سيطلب كلمة سر root)"
rsync -a --partial --info=progress2 "root@${HOSTINGER_IP}:${REMOTE_DIR}/" "$WORK/"
(cd "$WORK" && sha256sum -c SHA256SUMS) || die "الملفات وصلت تالفة — أعد التشغيل."
# shellcheck disable=SC1091
. "$WORK/info.txt"
echo "نسخة الباك: $BACKEND_RELEASE | نسخة الواجهة: $WEB_RELEASE | صُدِّرت: $EXPORTED_AT"

step "2) فك الكود والملفات المشتركة"
mkdir -p "$DEPLOY_PATH"
if [ -d "$B/releases/$BACKEND_RELEASE" ] && [ -d "$W/releases/$WEB_RELEASE" ]; then
  echo "النسخ موجودة مسبقاً — لن يُعاد فك الكود."
else
  tar -xzf "$WORK/code.tar.gz" -C "$DEPLOY_PATH"
fi
if [ -d "$B/shared" ]; then
  mv "$B/shared" "$WORK/shared.before-$TS"
  echo "المجلد المشترك السابق نُقل إلى $WORK/shared.before-$TS"
fi
tar -xzf "$WORK/shared.tar.gz" -C "$DEPLOY_PATH"
mkdir -p "$B"/shared/storage/{app/public,framework/{cache/data,sessions,views,testing},logs} \
         "$B"/shared/backups "$B"/shared/public/{uploads,profile_images,media}
BR="$B/releases/$BACKEND_RELEASE"
WR="$W/releases/$WEB_RELEASE"
[ -f "$BR/artisan" ] && [ -f "$WR/index.html" ] || die "الكود غير مكتمل بعد الفك."

step "3) ربط الواجهة بـ API النسخة التسويقية بدل الموقع الحقيقي"
# عنوان الـ API مكتوب داخل ملفات الواجهة المبنية — نستبدله حتى لا تتصل بـ dashboard.sami-care.sa
for dir in "$WR" "$BR/public/cafe-app"; do
  [ -d "$dir" ] || continue
  { grep -rlF --include='*.js' --include='*.html' --include='*.php' --include='*.json' --include='*.webmanifest' \
      'https://dashboard.sami-care.sa' "$dir" 2>/dev/null || true; } \
    | while read -r f; do sed -i "s#https://dashboard\.sami-care\.sa#https://$API_DOMAIN#g" "$f"; echo "  عُدِّل: ${f#"$DEPLOY_PATH"/}"; done
done
# مفتاح Firebase الإداري داخل public يمكن لأي زائر تحميله — لا نحتاجه في نسخة العرض
rm -f "$BR"/public/*firebase-adminsdk*.json

step "4) تفعيل النسخة (current) وتجهيز upload للنشر التلقائي"
activate() {  # activate <app-dir> <release-id>
  local app="$1" id="$2"
  if [ -d "$app/current" ] && [ ! -L "$app/current" ]; then
    mv "$app/current" "$WORK/$(basename "$app")-current.cpanel-$TS"   # المجلد الفارغ الذي أنشأه cPanel
  fi
  ln -sfn "releases/$id" "$app/current.next"
  mv -Tf "$app/current.next" "$app/current"
  [ -d "$app/upload" ] || cp -al "$app/releases/$id" "$app/upload"
  echo "$(basename "$app"): current -> releases/$id"
}
activate "$B" "$BACKEND_RELEASE"
activate "$W" "$WEB_RELEASE"

step "5) إنشاء قاعدة البيانات $DB_NAME"
# ملاحظة: لا نستعمل "… | head" أو "… | grep -q" هنا؛ مع pipefail يُنهي SIGPIPE السكربت بصمت.
DB_PASS="$(openssl rand -base64 48 | tr -dc 'A-Za-z0-9' | cut -c1-24)"
[ "${#DB_PASS}" -ge 20 ] || die "تعذّر توليد كلمة سر للقاعدة"
uapi_ok() { local out; out="$(uapi "$@" 2>&1)" || true; [[ "$out" == *"status: 1"* ]] || { echo "$out" | grep -E 'errors|^\s+- ' >&2; return 1; }; }
uapi_ok Mysql create_database name="$DB_NAME" || echo "القاعدة موجودة مسبقاً — ستُستبدل جداولها."
if ! uapi_ok Mysql create_user name="$DB_USER" password="$DB_PASS"; then
  echo "المستخدم موجود مسبقاً — ستُغيَّر كلمة سره."
  uapi_ok Mysql set_password user="$DB_USER" password="$DB_PASS" || die "تعذّر ضبط كلمة سر $DB_USER"
fi
uapi_ok Mysql set_privileges_on_database user="$DB_USER" database="$DB_NAME" privileges='ALL PRIVILEGES' \
  || die "تعذّر منح الصلاحيات على $DB_NAME"
MYSQL_PWD="$DB_PASS" mysql -u "$DB_USER" "$DB_NAME" -e 'SELECT 1' >/dev/null || die "لا يمكن الاتصال بالقاعدة بالمستخدم الجديد"
echo "✅ القاعدة: $DB_NAME | المستخدم: $DB_USER"
echo "$DB_PASS" > "$WORK/db_password.txt"
chmod 600 "$WORK/db_password.txt"

step "6) استيراد القاعدة (MySQL 8.4 → MariaDB 10.6)"
gunzip < "$WORK/db.sql.gz" \
  | sed -E -e 's/DEFINER=`[^`]+`@`[^`]+`//g' \
           -e 's/utf8mb4_0900_bin/utf8mb4_bin/g' \
           -e 's/utf8mb4_0900_[a-z_]+/utf8mb4_unicode_ci/g' \
  | MYSQL_PWD="$DB_PASS" mysql --default-character-set=utf8mb4 -u "$DB_USER" "$DB_NAME"
sqlq() { MYSQL_PWD="$DB_PASS" mysql -N -u "$DB_USER" "$DB_NAME" -e "$1"; }
echo "✅ عدد الجداول: $(sqlq "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB_NAME';") (في Hostinger: 173)"

step "7) تعطيل كل ما يرسل للعملاء (داخل القاعدة)"
sqlq "UPDATE settings SET val='0' WHERE name IN ('is_taqnyat_sms','is_custom_webhook_notification');"
sqlq "UPDATE settings SET val=''  WHERE name='taqnyat_api_key';"
sqlq "UPDATE settings SET val='demo-disabled' WHERE type='firebase_notification' AND name='firebase_project_id';"
sqlq "UPDATE payment_gateways SET is_active=0;"
echo "SMS والـ webhook وإشعارات الجوال: معطّلة | بوابات الدفع المفعّلة: $(sqlq 'SELECT COUNT(*) FROM payment_gateways WHERE is_active=1;')"

step "8) تعديل .env"
ENV="$B/shared/.env"
[ -f "$WORK/.env.hostinger" ] || cp "$ENV" "$WORK/.env.hostinger"
set_env() {  # set_env KEY VALUE — يعدّل السطر أو يضيفه
  if grep -qE "^$1=" "$ENV"; then sed -i "s|^$1=.*|$1=$2|" "$ENV"; else echo "$1=$2" >> "$ENV"; fi
}
set_env APP_URL "https://$API_DOMAIN"
set_env FRONTEND_URL "https://$WEB_DOMAIN"
set_env APP_DEBUG "false"
set_env DB_HOST "localhost"
set_env DB_PORT "3306"
set_env DB_DATABASE "$DB_NAME"
set_env DB_USERNAME "$DB_USER"
set_env DB_PASSWORD "$DB_PASS"
set_env MAIL_MAILER "log"
set_env BOOKING_WHATSAPP_ENABLED "false"
set_env TAQNYAT_WHATSAPP_API_KEY ""
set_env TAQNYAT_API_KEY ""
set_env WEBPUSH_ENABLED "false"
set_env REALTIME_NOTIFICATIONS_ENABLED "false"
set_env BROADCAST_DRIVER "log"
set_env QUEUE_CONNECTION "sync"
grep -qE '^SESSION_DOMAIN=' "$ENV" && set_env SESSION_DOMAIN ""
grep -qE '^SANCTUM_STATEFUL_DOMAINS=' "$ENV" && set_env SANCTUM_STATEFUL_DOMAINS "$WEB_DOMAIN,$API_DOMAIN"
grep -qE '^WAITING_DEVICE_BASE_URL=' "$ENV" && set_env WAITING_DEVICE_BASE_URL ""
grep -qE '^SLACK_NOTIFICATION_WEBHOOK=' "$ENV" && set_env SLACK_NOTIFICATION_WEBHOOK ""
sed -i "s|$OLD_DEPLOY_PATH|$DEPLOY_PATH|g" "$ENV"
chmod 600 "$ENV"
grep -E '^(APP_ENV|APP_URL|FRONTEND_URL|APP_DEBUG|DB_DATABASE|DB_USERNAME|MAIL_MAILER|BOOKING_WHATSAPP_ENABLED|WEBPUSH_ENABLED|REALTIME_NOTIFICATIONS_ENABLED)=' "$ENV"

step "9) الصلاحيات"
for dir in "$BR" "$WR"; do
  find "$dir" -type d -exec chmod 755 {} +
  find "$dir" -type f -exec chmod 644 {} +
done
find "$B/shared/storage" -type d -exec chmod 775 {} +
find "$B/shared/storage" -type f -exec chmod 664 {} +
find "$B/shared/public" -type d -exec chmod 755 {} +
find "$B/shared/public" -type f -exec chmod 644 {} +

step "10) تجهيز Laravel"
cd "$B/current"
# كاش Hostinger (config/routes/events) فيه مسارات /home/sami/... ، و artisan يحمّله قبل
# أي أمر فيفشل — لذلك يُحذف يدوياً أولاً.
rm -f bootstrap/cache/*.php
$PHP artisan optimize:clear     # يمسح كاش الإعدادات المخزّن (settings) وبقية الكاش
$PHP artisan package:discover --ansi
$PHP artisan config:cache
$PHP artisan event:cache
$PHP artisan route:cache
$PHP artisan about --only=environment 2>&1 | sed -n 1,12p

step "11) فحص سريع"
for url in "https://$API_DOMAIN/api/health" "https://$WEB_DOMAIN/"; do
  printf '%s -> %s\n' "$url" "$(curl -sk -o /dev/null -w '%{http_code}' --max-time 15 "$url")"
done
curl -sk --max-time 15 "https://$API_DOMAIN/api/health"; echo
printf '.env محمي؟ %s (يجب 403 أو 404)\n' "$(curl -sk -o /dev/null -w '%{http_code}' --max-time 15 "https://$API_DOMAIN/.env")"

echo
echo "=================================================================="
echo "✅ انتهى. نسخة العرض في $DEPLOY_PATH"
echo "   الموقع : https://$WEB_DOMAIN"
echo "   اللوحة : https://$API_DOMAIN"
echo "   القاعدة: $DB_NAME (كلمة السر في $WORK/db_password.txt)"
echo "   لا يوجد Cron — لا تضف schedule:run لهذه النسخة."
echo "=================================================================="
