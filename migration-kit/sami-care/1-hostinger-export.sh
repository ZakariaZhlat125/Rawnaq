#!/usr/bin/env bash
# =============================================================================
#  Sami Care — الخطوة 1: تُنفَّذ على Hostinger كـ root
#
#  تصدّر النسخة الحالية من المشروع بترتيب النشر (releases/shared/current):
#    db.sql.gz       قاعدة البيانات
#    code.tar.gz     كود النسخة الحالية: backend/releases/<id> (مع vendor) و web/releases/<id>
#    shared.tar.gz   backend/shared: .env و storage والصور المرفوعة (بدون backups)
#    info.txt        أرقام النسخ واسم القاعدة (يقرؤها سكربت cPanel)
#
#  الاستخدام:   bash /root/1-hostinger-export.sh [/home/sami/production]
#  لا يغيّر أي شيء في الموقع الحالي — يقرأ وينسخ فقط.
#  يمكن إعادة تشغيله يوم التحويل لأخذ آخر نسخة من القاعدة والملفات.
# =============================================================================
set -euo pipefail

DEPLOY_PATH="${1:-/home/sami/production}"
OUT="/root/migrate-sami"
B="$DEPLOY_PATH/backend"
W="$DEPLOY_PATH/web"

step() { echo; echo "================ $* ================"; }
die()  { echo "❌ $*" >&2; exit 1; }

[ -f "$B/shared/.env" ] || die "لا يوجد $B/shared/.env — تأكد من المسار."
BID="$(basename "$(readlink "$B/current")")"
WID="$(basename "$(readlink "$W/current")")"
[ -d "$B/releases/$BID" ] || die "نسخة الباك الحالية غير موجودة: $B/releases/$BID"
[ -d "$W/releases/$WID" ] || die "نسخة الواجهة الحالية غير موجودة: $W/releases/$WID"

env_get() {
  grep -E "^$1=" "$B/shared/.env" | tail -1 | cut -d= -f2- \
    | sed -e 's/\r$//' -e 's/^"//' -e 's/"$//' -e "s/^'//" -e "s/'$//"
}
DB_NAME="$(env_get DB_DATABASE)"

rm -rf "$OUT"
mkdir -p "$OUT"
chmod 700 "$OUT"

# GNU tar يرجع 1 إذا تغيّر ملف أثناء القراءة (مثل laravel.log) — هذا مقبول.
tar_ok() { tar "$@" || [ $? -eq 1 ]; }

step "معلومات"
echo "المسار        : $DEPLOY_PATH"
echo "نسخة الباك    : $BID"
echo "نسخة الواجهة  : $WID"
echo "القاعدة       : $DB_NAME"
echo "PHP           : $(php -v | head -1)"
echo "Laravel       : $(cd "$B/current" && php artisan --version 2>/dev/null || echo '?')"
echo "--- مهام مجدولة تخص sami (root و /etc/cron.d):"
{ crontab -l 2>/dev/null | grep -i sami; grep -ris sami /etc/cron.d /var/spool/cron 2>/dev/null; } || echo "  لا يوجد — المهام المجدولة لا تعمل حالياً على Hostinger"

step "1) تصدير قاعدة البيانات"
# نصدّر بنفس مستخدم القاعدة الذي يستعمله الموقع (من .env)، لأن القاعدة قد لا تكون
# مسجّلة في CloudPanel فلا يعرفها clpctl. و clpctl احتياطي فقط.
# --set-gtid-purged=OFF: بدونه قد يفشل الاستيراد على cPanel (يحتاج صلاحية SUPER).
dump_with_env() {
  MYSQL_PWD="$(env_get DB_PASSWORD)" mysqldump \
    --single-transaction --quick --routines --triggers --no-tablespaces \
    --set-gtid-purged=OFF --default-character-set=utf8mb4 \
    -h "$(env_get DB_HOST)" -P "$(env_get DB_PORT)" -u "$(env_get DB_USERNAME)" "$DB_NAME" \
    | gzip > "$OUT/db.sql.gz"
}
if dump_with_env; then
  echo "تم التصدير بمستخدم الموقع ($(env_get DB_USERNAME))."
elif command -v clpctl >/dev/null 2>&1 && clpctl db:export --databaseName="$DB_NAME" --file="$OUT/db.sql.gz"; then
  echo "تم التصدير عبر clpctl."
else
  die "فشل تصدير القاعدة بالطريقتين — أرسل لي رسالة الخطأ أعلاه."
fi
DB_SIZE=$(stat -c %s "$OUT/db.sql.gz")
[ "$DB_SIZE" -ge 2000 ] || die "ملف القاعدة صغير جداً ($DB_SIZE بايت) — فشل التصدير."
echo "عدد الجداول في الملف: $(gunzip < "$OUT/db.sql.gz" | grep -c '^CREATE TABLE')"

step "2) ضغط الكود (النسخة الحالية فقط، مع vendor)"
tar_ok -czf "$OUT/code.tar.gz" -C "$DEPLOY_PATH" "backend/releases/$BID" "web/releases/$WID"

step "3) ضغط الملفات المشتركة (.env و storage والصور)"
tar_ok -czf "$OUT/shared.tar.gz" -C "$DEPLOY_PATH" \
  --exclude='backend/shared/backups' \
  --exclude='backend/shared/storage/framework/views/*' \
  backend/shared

cat > "$OUT/info.txt" <<EOF
BACKEND_RELEASE=$BID
WEB_RELEASE=$WID
DB_NAME=$DB_NAME
OLD_DEPLOY_PATH=$DEPLOY_PATH
EXPORTED_AT=$(date -u +%Y-%m-%dT%H:%M:%SZ)
EOF
(cd "$OUT" && sha256sum db.sql.gz code.tar.gz shared.tar.gz info.txt > SHA256SUMS)

step "النتيجة"
ls -lh "$OUT"
echo
echo "✅ تم. احفظ هذه القيم لسكربت cPanel:"
echo "   HOSTINGER_IP=$(curl -4 -s ifconfig.me)"
echo "   REMOTE_DIR=$OUT"
