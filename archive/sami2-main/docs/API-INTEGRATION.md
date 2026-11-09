# دليل ربط الباك اند

الواجهة جاهزة وظيفيًا لكن **بدون خادم** — كل البيانات ثابتة في الكود.

---

## أولويات التنفيذ

| # | المجموعة | الأهمية |
|---|---|---|
| 1 | الفروع والخدمات والأسعار | 🔴 حرجة — كل شيء يعتمد عليها |
| 2 | الحجز | 🔴 حرجة — الوظيفة الأساسية |
| 3 | المصادقة والعملاء | 🟠 عالية |
| 4 | المنتجات والطلبات | 🟠 عالية |
| 5 | الإهداء | 🟡 متوسطة |
| 6 | الولاء وعجلة الحظ | 🟡 متوسطة |
| 7 | المحتوى (عروض · آراء) | 🟢 منخفضة |

---

## 1. الفروع والخدمات

```http
GET /api/branches
GET /api/services
GET /api/services/:id
GET /api/services/:id/variants?branch=bg
```

**نقاط الاستبدال:**

| الملف | ما يُستبدل |
|---|---|
| `src/composables/useServiceLocation.js` | `serviceLocations` |
| `src/data/home.js` | `services` · `packages` · `products` |
| `src/data/serviceDetails.js` | كامل الملف |
| `src/legacy/booking.html` | `BRANCHES` · `CATS` · `SERVICES` |
| `src/legacy/store.html` | `LOC_LIST` · `CATS` · `PRODUCTS` |
| `src/legacy/gifts.html` | `BRANCHES` · `PKGS` · `SERVICES` |
| `src/legacy/packages-gifts.html` | `BRANCHES` · `PKGS` |
| `public/mobile/index.html` | `B_BRANCHES` وما يليها |

### مطلوب: سعر لكل فرع

```json
{ "id": "h1", "name": "حلاقة كلاسيكية", "duration": 30,
  "prices": { "bg": 70, "qr": 65, "hm": 90 } }
```

---

## 2. الحجز

```http
GET   /api/availability?branch=bg&date=2026-08-01&services=h1,h2
POST  /api/bookings
GET   /api/bookings/:id
GET   /api/customers/me/bookings
PATCH /api/bookings/:id/cancel
```

```json
{
  "branchId": "bg",
  "services": ["h1", "h2", "m3"],
  "employeeMode": "auto",
  "employees": { "hair": "أحمد" },
  "date": "2026-08-01",
  "time": 630,
  "customer": { "name": "...", "phone": "05...", "email": "", "notes": "" },
  "payment": { "method": "card", "usePoints": 0, "useWallet": 0, "coupon": null }
}
```

**نقطة الربط:** `src/legacy/booking.html` — خطوة الدفع.
حاليًا تعرض شاشة نجاح وهمية بمرجع عشوائي `#SAMI-2026-XXXXX`.

**قواعد يجب تطبيقها في الخادم:**
- فاصل **15 دقيقة** بين كل خدمة رئيسية
- الضريبة **15%** بعد الخصومات
- خطة الجلسة تُجمَّع حسب الخدمة الرئيسية (5 مراحل كحد أقصى)

---

## 3. المصادقة والعملاء

```http
POST  /api/auth/otp/request   { phone }
POST  /api/auth/otp/verify    { phone, code }
GET   /api/customers/me
PATCH /api/customers/me
```

**الوضع الحالي:** لا يوجد تسجيل دخول. أزرار "تسجيل دخول" و"حسابي" غير مرتبطة بشيء.

---

## 4. المنتجات والطلبات

```http
GET  /api/products?category=hair
POST /api/orders
GET  /api/orders/:id
```

**خيارات الاستلام** (يجب التحكم بها من لوحة الإدارة):

```json
{ "pickup": true, "delivery": false }
```

**الموضع:** `public/mobile/index.html` → `FULLFILLMENT_OPTIONS`
**السلة** محفوظة في الذاكرة فقط وتضيع عند إعادة التحميل.

---

## 5. الإهداء

```http
POST /api/gifts
GET  /api/gifts/:code
POST /api/gifts/:code/redeem
```

**الموضع:** `src/legacy/gifts.html` — خطوة الدفع · صلاحية **6 أشهر**

---

## 6. الولاء وعجلة الحظ

```http
GET  /api/loyalty/me
POST /api/loyalty/redeem    { points, rewardId }
POST /api/wheel/spin        → يعيد الجائزة من الخادم
GET  /api/wheel/eligibility
```

> 🔒 **مهم أمنيًا:** نتيجة العجلة تُحسب حاليًا في المتصفح — أي مستخدم يستطيع التلاعب بها.
> **يجب أن تُحدَّد الجائزة في الخادم**، والواجهة تعرض النتيجة فقط.
> والتحقق من الاستحقاق يكون في الخادم بدل `localStorage`.

**المواضع:** `src/components/home/LuckyWheelCard.vue` → `spin()` · `public/mobile/index.html` → `doSpin()`

---

## 7. المحتوى الإداري

```http
GET /api/promos?placement=home
GET /api/testimonials
```

**مطلوب:** إمكانية إضافة أكثر من بطاقة إعلانات **وتحديد مكانها من لوحة التحكم**.

---

## ملاحظات عامة

### الحالة المشتركة
الفرع المختار في `localStorage['samiSiteBranch']` ويُقرأ من صفحات Vue
والصفحات القديمة وتطبيق الجوال. يجب أن تبقى متزامنة بعد إضافة طبقة API.

### تطبيق الجوال
`public/mobile/index.html` له نسخته الخاصة من كل البيانات —
**أي تغيير في النموذج يجب تطبيقه فيه أيضًا.**

### الصور الخارجية
صور تُحمَّل من `https://sami-care.sa` — يفضّل نقلها لخدمة ملفات المشروع.

### غير موجود حاليًا
- معالجة أخطاء الشبكة وحالات التحميل
- تحقق من المدخلات في الخادم (كل التحقق في المتصفح)
- ترقيم صفحات في المتجر
- بوابة دفع حقيقية (الشاشات تحاكي النجاح بعد تأخير ثابت)
