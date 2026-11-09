# نموذج البيانات

كل الكيانات المستخدمة في الواجهة ومواضعها في الكود.
**جميعها مكتوبة يدويًا (hardcoded) — لا يوجد مصدر خارجي.**

---

## 1. الفروع / أماكن تنفيذ الخدمة

| الحقل | النوع | مثال |
|---|---|---|
| `id` | string | `bg` — القيم الثابتة: `bg` / `qr` / `hm` |
| `name` | string | البغدادية الغربية |
| `addr` | string | شارع حائل، حي البغدادية الغربية — جدة |
| `img` | string | مسار الصورة |
| `home` | boolean | `true` للخدمة المنزلية |
| `rate` | number | 4.8 |
| `hours` | string | 12:00 ص – 10:00 م |

**المواضع (7):**
`src/composables/useServiceLocation.js` · `src/legacy/booking.html` (`BRANCHES`) ·
`gifts.html` (`BRANCHES`) · `packages-gifts.html` (`BRANCHES`) ·
`store.html` (`LOC_LIST`) · `branches.html` · `contact.html` · `public/mobile/index.html`

> ⚠️ مكرّرة في 7 مواضع — أول ما يُربط الباك اند يجب توحيدها.

---

## 2. الخدمات الرئيسية

| `id` | الاسم |
|---|---|
| 1 | حمام مغربي |
| 2 | بديكير |
| 3 | حلاقة |
| 4 | تنظيف البشرة |
| 5 | مساج |

| الحقل | النوع |
|---|---|
| `id` · `name` · `image` | معرّف · اسم · صورة البطاقة |
| `heroImage` · `benefitsImage` | صور صفحة التفاصيل |
| `tagline` | وصف مختصر |
| `features[]` | `{icon, label}` |
| `benefits[]` · `whyUs[]` · `beforeVisit[]` | `{icon, title, text}` |
| `faq[]` | `{q, a}` |

**المواضع:** `src/data/home.js` → `services` · `src/data/serviceDetails.js`

---

## 3. الخدمات الفرعية

`{ id, name, cat, dur, price, desc, img? }`

- `cat` = معرّف الخدمة الرئيسية
- `dur` بالدقائق · `price` بالريال

**المواضع:** `src/legacy/booking.html` → `SERVICES` · `src/data/serviceDetails.js` → `variants[]`

> ⚠️ معرّفات الأقسام نصية في صفحة الحجز (`hair`) ورقمية في `serviceDetails.js` (3).

---

## 4. الباقات

`{ id, name, price, desc, img, services[] }`

> ⚠️ السعر موحّد حاليًا — **المطلوب سعر لكل فرع.**

**المواضع:** `src/data/home.js` → `packages` · `packages-gifts.html` → `PKGS` · `gifts.html` → `PKGS`

---

## 5. المنتجات

`{ id, name, price, img, cat }`

**المواضع:** `src/data/home.js` → `products` · `store.html` → `PRODUCTS` · `public/mobile/index.html`

---

## 6. الحجز

الكيان الذي يُنشئه المستخدم. **لا يُحفظ حاليًا في أي مكان.**

| الحقل | ملاحظات |
|---|---|
| `branchId` | من `samiSiteBranch` |
| `services[]` | معرّفات الخدمات الفرعية |
| `employeeMode` | `auto` / `manual` |
| `employees{}` | `{catId: employeeName}` |
| `date` · `time` | الوقت بالدقائق من منتصف الليل |
| `customer` | `{name, phone, email, notes}` |
| `payment` | طريقة الدفع |
| `total` | المجموع + 15% ضريبة − الخصومات |

**الموضع:** `src/legacy/booking.html` → كائن `S`

---

## 7. الهدية

| الحقل | ملاحظات |
|---|---|
| `type` | `svc` خدمة / `pkg` باقة |
| `branchId` · `items[]` | |
| `design` | تصميم البطاقة |
| `message` | رسالة المُهدي |
| `sendMethod` | `wa` / `email` / `sms` |
| `sendWhen` | `now` / تاريخ |
| `recipient` | `{name, phone}` |

**الموضع:** `src/legacy/gifts.html` → كائن `S` · صلاحية الهدية **6 أشهر**

---

## 8. برنامج الولاء

`{ points, tier, memberId, progress }` — `tier`: `member`/`silver`/`gold`/`platinum`

**الموضع:** `public/mobile/index.html` — **بيانات وهمية بالكامل.**

---

## 9. عجلة الحظ

`{ prizes[]: {label,color}, user: {name,phone} }`

**المواضع:** `src/components/home/LuckyWheelCard.vue` · `public/mobile/index.html` → `WHEEL`

> ⚠️ النتيجة **عشوائية في المتصفح** بدون منطق احتمالات أو تحقق — ثغرة أمنية.

---

## 10. آراء العملاء

`{ id, name, text, rating }` — **الموضع:** `src/data/home.js` → `testimonials`

---

## 11. العروض والإعلانات

`{ id, title, text, badge, cta }`

**المواضع:** `src/data/home.js` → `promos` · `store.html` → `STORE_PROMOS`
