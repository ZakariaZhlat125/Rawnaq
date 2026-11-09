<script setup>
/*
  شريط صغير لسامي كافي في الصفحة الرئيسية — لمحة عن المكان
  من غير ما ياخد مساحة قسم كامل.
*/
</script>

<template>
  <section data-reveal class="home-section container cafe-strip" aria-label="سامي كافي">
    <div class="cafe-strip__img">
      <img src="/images/cafe-interior-clean.webp" alt="سامي كافي داخل الفرع" loading="lazy" />
    </div>
    <div class="cafe-strip__copy">
      <span class="cafe-strip__eyebrow">☕ سامي كافي</span>
      <h3>لأن التجربة <span>لا تنتهي عند الخدمة</span></h3>
      <p>استرخِ مع قهوة مختصة قبل جلستك أو بعدها — ركن هادئ داخل الفرع مفتوح لعملائنا.</p>
      <ul class="cafe-strip__chips">
        <li>قهوة مختصة</li>
        <li>ركن هادئ</li>
        <li>واي فاي مجاني</li>
      </ul>
    </div>
  </section>
</template>

<style scoped>
.cafe-strip{display:grid;grid-template-columns:1.15fr 1fr;align-items:stretch;gap:0;
  overflow:hidden;border-radius:20px;border:1px solid var(--border);
  background:linear-gradient(160deg,#14100b,#080706);color:#fff;
  box-shadow:0 20px 40px -30px rgba(0,0,0,.7)}

.cafe-strip__img{position:relative;min-height:190px}
.cafe-strip__img img{width:100%;height:100%;object-fit:cover;display:block;
  filter:saturate(.97) contrast(1.02)}
.cafe-strip__img::after{content:"";position:absolute;inset:0;
  background:linear-gradient(270deg,rgba(8,7,6,.92) 0%,rgba(8,7,6,.35) 45%,transparent 78%)}

.cafe-strip__copy{padding:24px 26px;display:flex;flex-direction:column;justify-content:center}
.cafe-strip__eyebrow{font-size:11px;font-weight:700;letter-spacing:.4px;color:var(--gold);margin-bottom:8px}
.cafe-strip__copy h3{margin:0 0 8px;font-size:21px;line-height:1.5;color:var(--gold-light,#f2d28e)}
.cafe-strip__copy h3 span{display:block;color:#fff;font-size:16px}
.cafe-strip__copy p{margin:0 0 14px;font-size:11.5px;line-height:1.9;color:rgba(255,255,255,.74)}

.cafe-strip__chips{display:flex;flex-wrap:wrap;gap:7px;list-style:none;margin:0;padding:0}
.cafe-strip__chips li{padding:5px 12px;border-radius:999px;font-size:10.5px;
  border:1px solid rgba(233,207,142,.32);color:var(--gold-light,#f2d28e)}

@media(max-width:760px){
  .cafe-strip{grid-template-columns:1fr}
  .cafe-strip__img{height:150px;min-height:0}
  .cafe-strip__img::after{background:linear-gradient(180deg,transparent 30%,rgba(8,7,6,.9) 100%)}
  .cafe-strip__copy{padding:18px 18px 22px}
}
</style>
