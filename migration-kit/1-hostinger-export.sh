#!/usr/bin/env bash
# =============================================================================
#  الخطوة 1 — تُنفَّذ على Hostinger (كـ root)
#  تصدّر قاعدة البيانات وملفات مشروع Laravel إلى /root/migrate-<اسم المجلد>/
#
#  الاستخدام:
#     bash 1-hostinger-export.sh /home/USER/htdocs/DOMAIN
#
#  لا يغيّر أي شيء في الموقع الحالي — يقرأ وينسخ فقط.
# =============================================================================
set -euo pipefail

PROJECT="${1:-}"
if [ -z "$PROJECT" ] || [ ! -f "$PROJECT/artisan" ]; then
  echo "❌ أعطني مسار المشروع (المجلد الذي فيه artisan)، مثال:"
  echo "   bash $0 /home/doctol-admin/htdocs/admin.doctol.com.sa"
  echo
  echo "مشاريع Laravel الموجودة على هذا الخادم:"
  find /home -maxdepth 5 -name artisan -not -path "*/vendor/*" 2>/dev/null | sed 's|/artisan$||'
  exit 1
fi

cd "$PROJECT"
NAME="$(basename "$PROJECT")"
OUT="/root/migrate-$NAME"
mkdir -p "$OUT"

env_get() {
  grep -E "^$1=" .env | head -1 | cut -d= -f2- \
    | sed -e 's/^"//' -e 's/"$//' -e "s/^'//" -e "s/'$//" -e 's/\r$//'
}

DB_NAME="$(env_get DB_DATABASE)"

echo "================ معلومات المشروع ================"
echo "المسار    : $PROJECT"
echo "PHP       : $(php -v | head -1)"
echo "Laravel   : $(php artisan --version 2>/dev/null || echo '?')"
echo "قاعدة     : $DB_NAME"
echo "QUEUE     : $(env_get QUEUE_CONNECTION)"
echo "الحجم     : $(du -sh . | cut -f1)  (منها vendor: $(du -sh vendor 2>/dev/null | cut -f1))"
echo "--- Cron الخاص بالمشروع:"
grep -rl "$PROJECT" /etc/cron.d/ /var/spool/cron/ 2>/dev/null | while read -r f; do echo "  $f:"; grep "$PROJECT" "$f" | sed 's/^/    /'; done || true
echo "--- مسارات Hostinger المكتوبة داخل .env (ستُصحَّح تلقائياً على cPanel):"
grep -n "$PROJECT" .env | sed 's/=.*$/=.../' || echo "  لا يوجد"
echo

echo "================ 1) تصدير قاعدة البيانات ================"
rm -f "$OUT/db.sql.gz"
if command -v clpctl >/dev/null 2>&1; then
  clpctl db:export --databaseName="$DB_NAME" --file="$OUT/db.sql.gz"
else
  MYSQL_PWD="$(env_get DB_PASSWORD)" mysqldump --single-transaction --routines --triggers --no-tablespaces \
    -h "$(env_get DB_HOST)" -P "$(env_get DB_PORT)" -u "$(env_get DB_USERNAME)" "$DB_NAME" | gzip > "$OUT/db.sql.gz"
fi

echo "================ 2) ضغط الملفات (بدون vendor و node_modules) ================"
tar --exclude='./vendor' --exclude='./node_modules' --exclude='./public/storage' \
    -czf "$OUT/files.tar.gz" -C "$PROJECT" .

# يحفظ المسار القديم ليستعمله سكربت cPanel في تصحيح .env
echo "$PROJECT" > "$OUT/old_path.txt"

echo
echo "================ النتيجة ================"
ls -lh "$OUT"
DB_SIZE=$(stat -c %s "$OUT/db.sql.gz")
if [ "$DB_SIZE" -lt 2000 ]; then
  echo "⚠️  ملف قاعدة البيانات صغير جداً ($DB_SIZE بايت) — غالباً فشل التصدير. لا تكمل."
  exit 1
fi
echo
echo "✅ تم. عنوان IPv4 لهذا الخادم (ضعه في HOSTINGER_IP داخل سكربت cPanel):"
curl -4 -s ifconfig.me; echo
echo "ومجلد الملفات (ضعه في REMOTE_DIR): $OUT"
