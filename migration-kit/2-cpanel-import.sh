#!/usr/bin/env bash
# =============================================================================
#  الخطوة 2 — تُنفَّذ على cPanel (بمستخدم الاستضافة، وليس root)
#  تنشئ قاعدة البيانات، تسحب الملفات من Hostinger، تستورد، وتجهّز Laravel.
#
#  1) عدّل القيم في قسم «الإعدادات» بالأسفل.
#  2) نفّذ:   bash 2-cpanel-import.sh
#
#  سيطلب كلمة سر root لـ Hostinger مرتين (لسحب الملفين).
# =============================================================================
set -euo pipefail

# ============================ الإعدادات — عدّلها ============================
# اسم الدومين/الدومين الفرعي على cPanel (يجب أن يكون منشأً مسبقاً من Domains)
DOMAIN="newproject.city2tec.com"

# اسم القاعدة بدون البادئة (cPanel يضيف بادئة الحساب تلقائياً). قصير، حروف وأرقام فقط.
DB_SHORT="newproj"

# من ناتج سكربت Hostinger
HOSTINGER_IP="187.6.167.53"
REMOTE_DIR="/root/migrate-CHANGE_ME"

# نسخة PHP (يجب أن تطابق Hostinger أو أحدث)
PHP_VERSION="84"

# نسخة عرض؟ true = تعطيل الواتساب والإيميل والإشعارات حتى لا تصل رسائل لعملاء حقيقيين
DEMO_MODE="true"
# ============================================================================

CPUSER="$(whoami)"
PREFIX="${CPUSER}_"
DB_NAME="${PREFIX}${DB_SHORT}"
DB_USER="${PREFIX}${DB_SHORT}"
TARGET="$HOME/$DOMAIN"
PHP="/opt/cpanel/ea-php${PHP_VERSION}/root/usr/bin/php -d session.gc_divisor=100"
WORK="$HOME/migrate-$DB_SHORT"

[ -x "/opt/cpanel/ea-php${PHP_VERSION}/root/usr/bin/php" ] || { echo "❌ PHP $PHP_VERSION غير موجود. المتوفر:"; ls /opt/cpanel | grep ea-php; exit 1; }
[ -d "$TARGET" ] || { echo "❌ المجلد $TARGET غير موجود. أنشئ الدومين أولاً من cPanel ← Domains."; exit 1; }
mkdir -p "$WORK"

step() { echo; echo "================ $* ================"; }

step "1) إنشاء قاعدة البيانات والمستخدم"
DB_PASS="$(tr -dc 'A-Za-z0-9' </dev/urandom | head -c 24)"
uapi Mysql create_database name="$DB_NAME" | grep -E 'status: 1' >/dev/null || echo "⚠️  لم تُنشأ القاعدة (قد تكون موجودة مسبقاً) — سنكمل."
if ! uapi Mysql create_user name="$DB_USER" password="$DB_PASS" | grep -E 'status: 1' >/dev/null; then
  echo "⚠️  المستخدم موجود مسبقاً — سنغيّر كلمة سره."
  uapi Mysql set_password user="$DB_USER" password="$DB_PASS" >/dev/null
fi
uapi Mysql set_privileges_on_database user="$DB_USER" database="$DB_NAME" privileges='ALL PRIVILEGES' >/dev/null
echo "$DB_PASS" > "$WORK/db_password.txt"; chmod 600 "$WORK/db_password.txt"
echo "✅ القاعدة: $DB_NAME | المستخدم: $DB_USER | كلمة السر محفوظة في $WORK/db_password.txt"

step "2) سحب الملفات من Hostinger (سيطلب كلمة سر root)"
SRC="root@${HOSTINGER_IP}:${REMOTE_DIR}"
scp "$SRC/db.sql.gz" "$SRC/files.tar.gz" "$SRC/old_path.txt" "$WORK/"
ls -lh "$WORK"

step "3) فك الملفات"
if [ -n "$(ls -A "$TARGET" 2>/dev/null)" ]; then
  BK="$HOME/old_docroot_${DB_SHORT}_$(date +%Y%m%d%H%M%S)"
  mkdir -p "$BK"; mv "$TARGET"/* "$TARGET"/.[!.]* "$BK"/ 2>/dev/null || true
  echo "المحتوى السابق للمجلد نُقل إلى $BK"
fi
tar -xzf "$WORK/files.tar.gz" -C "$TARGET"
[ -f "$TARGET/artisan" ] || { echo "❌ لم يُعثر على artisan بعد الفك."; exit 1; }

step "4) استيراد قاعدة البيانات"
gunzip < "$WORK/db.sql.gz" \
  | sed -E -e 's/DEFINER=`[^`]+`@`[^`]+`//g' -e 's/utf8mb4_0900_ai_ci/utf8mb4_unicode_ci/g' \
  | MYSQL_PWD="$DB_PASS" mysql -u "$DB_USER" "$DB_NAME"
TABLES=$(MYSQL_PWD="$DB_PASS" mysql -N -u "$DB_USER" "$DB_NAME" -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB_NAME';")
echo "✅ عدد الجداول: $TABLES"

step "5) تعديل .env"
cd "$TARGET"
cp .env .env.hostinger.bak
set_env() {  # set_env KEY VALUE — يعدّل السطر أو يضيفه
  if grep -qE "^$1=" .env; then sed -i "s|^$1=.*|$1=$2|" .env; else echo "$1=$2" >> .env; fi
}
set_env APP_URL "https://$DOMAIN"
set_env APP_DEBUG "false"
set_env DB_HOST "localhost"
set_env DB_PORT "3306"
set_env DB_DATABASE "$DB_NAME"
set_env DB_USERNAME "$DB_USER"
set_env DB_PASSWORD "$DB_PASS"
OLD_PATH="$(cat "$WORK/old_path.txt")"
sed -i "s|$OLD_PATH|$TARGET|g" .env   # مسارات Hostinger داخل .env (مثل FIREBASE_CREDENTIALS)
if [ "$DEMO_MODE" = "true" ]; then
  set_env MAIL_MAILER "log"
  grep -qE '^WHATSAPP_ENABLED=' .env && set_env WHATSAPP_ENABLED "false"
  grep -qE '^WHATSAPP_DRY_RUN=' .env && set_env WHATSAPP_DRY_RUN "true"
  grep -qE '^FIREBASE_ENABLED=' .env && set_env FIREBASE_ENABLED "false"
  echo "وضع العرض: الإيميل → log، الواتساب والإشعارات معطّلة."
fi
grep -E '^(APP_URL|APP_DEBUG|DB_HOST|DB_DATABASE|DB_USERNAME|MAIL_MAILER|WHATSAPP_ENABLED|FIREBASE_ENABLED)=' .env

step "6) تثبيت المكتبات (composer) — قد يأخذ عدة دقائق"
$PHP "$(command -v composer)" install --optimize-autoloader --no-interaction 2>&1 | tail -5

step "7) الصلاحيات"
chmod -R 775 storage bootstrap/cache
# مهم: الملفات القادمة من Hostinger صلاحياتها 660، وخادم الويب لا يقرؤها فيظهر 404
find public -type d -exec chmod 755 {} +
find public -type f -exec chmod 644 {} +
[ -d storage/app/public ] && { find storage/app/public -type d -exec chmod 755 {} +; find storage/app/public -type f -exec chmod 644 {} +; }

step "8) تجهيز Laravel"
rm -f public/storage
$PHP artisan storage:link
$PHP artisan optimize:clear
$PHP artisan config:cache
$PHP artisan route:cache
$PHP artisan view:cache
$PHP artisan about --only=environment 2>&1 | head -12

echo
echo "=================================================================="
echo "✅ انتهى السكربت. بقيت خطوات من واجهة cPanel (انظر README):"
echo "  1. Domains ← $DOMAIN ← Manage ← Document Root = $DOMAIN/public"
echo "  2. MultiPHP Manager ← $DOMAIN ← PHP $PHP_VERSION"
echo "  3. SSL/TLS Status ← Run AutoSSL"
echo "  4. (اختياري) Cron:  * * * * *  cd $TARGET && /opt/cpanel/ea-php${PHP_VERSION}/root/usr/bin/php artisan schedule:run >> /dev/null 2>&1"
echo
echo "كلمة سر القاعدة: $DB_PASS   (محفوظة أيضاً في $WORK/db_password.txt)"
echo "بعد التأكد أن الموقع يعمل احذف ملفات النقل:  rm -rf $WORK"
echo "=================================================================="
