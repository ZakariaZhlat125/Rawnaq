# طريقة العمل في الفريق

الهدف: أن يعمل كل شخص على مهمته ويدمجها دون أن ينتظر أحداً.

## 1. الفروع

```
main  ← محمي: لا push مباشر، الدمج عبر PR فقط
 ├── feat/wallet-topup-offers
 ├── fix/odoo-id-migration
 └── chore/ci-cache
```

- فرع لكل مهمة، يخرج من `main` ويرجع إليه. **لا فروع بأسماء الأشخاص.**
- الاسم: `feat/…` أو `fix/…` أو `chore/…` أو `refactor/…` بأحرف إنجليزية صغيرة وشرطات.
- عمر الفرع من يوم إلى 3 أيام. الميزة الكبيرة تُقسم إلى PRs صغيرة، والجزء غير المكتمل يُخفى خلف إعداد (feature flag).
- حدّث فرعك من `main` يومياً: `git pull --rebase origin main`.
- يُحذف الفرع بعد الدمج.

## 2. رسائل الـ commit

بصيغة [Conventional Commits](https://www.conventionalcommits.org/):

```
feat(wallet): add top-up offers
fix(booking): keep arrival time on repeated scan
chore(ci): cache composer packages
```

ممنوع: `new commit` و`update` و`fix` بلا تفاصيل.

## 3. الـ Pull Request

1. افتح PR مبكراً كـ Draft إذا أردت رأياً.
2. املأ القالب.
3. يحتاج **مراجعة واحدة** على الأقل ونجاح الـ CI.
4. الدمج بـ **Squash merge**، فيصير كل PR commit واحداً واضحاً في `main`.

## 4. أين أضع الكود؟

- **ميزة جديدة في الباك:** داخل `backend/Modules/<Feature>/` بمساراتها (`Routes/api.php`) وجداولها واختباراتها. لا تُضف إلى `backend/routes/web.php` أو `api.php` المركزيين. هذان الملفان أكثر مكان تحدث فيه التعارضات.
- **الكود القديم في `backend/app/`:** يُنقل إلى الـ Module المناسب عندما تعدّل عليه، دون نقل جماعي.
- **الواجهة:** `apps/web/src/` حسب النوع: `components/<feature>/` و`composables/` و`services/<feature>Api.js`.

## 5. قواعد الـ Migrations

- **لا تعدّل migration بعد دمجه.** أضف migration جديداً يصحّح.
- الاسم بالتاريخ والوقت الحقيقي: `php artisan make:migration` يكفي. لا تكتب الأرقام يدوياً مثل `000003`، لأن شخصين قد يختاران نفس الرقم.

## 6. العمل المتوازي بين الباك والواجهة

1. قبل البدء بقسم جديد، اتفقوا على شكل الـ endpoints (المسار، الطلب، الرد) واكتبوه في `docs/specs/<section>.md`.
2. الباك يبني الـ API، والواجهة تبدأ في نفس اليوم على بيانات وهمية (mock).
3. عند جاهزية الـ API تُزال الـ mocks.

## 7. ما لا يُرفع إلى git أبداً

`.env` و`node_modules/` و`vendor/` و`dist/` و`bootstrap/cache/*.php` وملفات zip والنسخ الاحتياطية. الـ `.gitignore` في الجذر يمنعها، فلا تستخدم `git add -f`.
