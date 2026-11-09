<script setup>
/*
  شريط صغير لسامي كافي في الصفحة الرئيسية — لمحة عن المكان
  من غير ما ياخد مساحة قسم كامل.
*/
import { RouterLink } from 'vue-router'
const props = defineProps({ image: { type: String, default: '' } })
</script>

<template>
  <section data-reveal class="home-section container cafe-strip" aria-label="رونق كافي">
    <RouterLink to="/cafe" class="cafe-strip__link">
      <div class="cafe-strip__img">
        <img :src="props.image || '/images/cafe-interior-clean.webp'" alt="رونق كافي داخل الفرع" loading="lazy" />
      </div>
      <div class="cafe-strip__copy">
        <span class="cafe-strip__eyebrow">☕ رونق كافي</span>
        <h3>لأن التجربة <span>لا تنتهي عند الخدمة</span></h3>
        <p>استرخِ مع قهوة مختصة قبل جلستك أو بعدها — ركن هادئ داخل الفرع مفتوح لعملائنا.</p>
        <ul class="cafe-strip__chips">
          <li>قهوة مختصة</li>
          <li>ركن هادئ</li>
          <li>واي فاي مجاني</li>
        </ul>
        <span class="cafe-strip__cta">تعرف على المقهى ←</span>
      </div>
    </RouterLink>
  </section>
</template>

<style scoped>
.cafe-strip{margin-bottom:20px;padding:0;position:relative;
  overflow:hidden;border-radius:20px;border:1px solid var(--border);
  background:linear-gradient(160deg,#14100b,#080706);color:#fff;
  box-shadow:0 20px 40px -30px rgba(0,0,0,.7);transition:box-shadow .25s,transform .25s}
.cafe-strip:hover{box-shadow:0 24px 48px -28px rgba(0,0,0,.8);transform:translateY(-2px)}

.cafe-strip__link{display:block;position:relative;min-height:230px;color:inherit;text-decoration:none;cursor:pointer}

.cafe-strip__img{position:absolute;inset:0}
.cafe-strip__img img{width:100%;height:100%;object-fit:cover;display:block;
  filter:saturate(.97) contrast(1.02)}
.cafe-strip__img::after{content:"";position:absolute;inset:0;
  background:linear-gradient(270deg,rgba(8,7,6,.82) 0%,rgba(8,7,6,.45) 45%,rgba(8,7,6,.12) 100%)}

.cafe-strip__copy{position:relative;z-index:1;width:min(100%,560px);min-height:230px;padding:24px 26px;display:flex;flex-direction:column;justify-content:center}
.cafe-strip__eyebrow{font-size:11px;font-weight:700;letter-spacing:.4px;color:var(--gold);margin-bottom:8px}
.cafe-strip__copy h3{margin:0 0 8px;font-size:21px;line-height:1.5;color:var(--gold-light,#f2d28e)}
.cafe-strip__copy h3 span{display:block;color:#fff;font-size:16px}
.cafe-strip__copy p{margin:0 0 14px;font-size:11.5px;line-height:1.9;color:rgba(255,255,255,.74)}

.cafe-strip__chips{display:flex;flex-wrap:wrap;gap:7px;list-style:none;margin:0;padding:0}
.cafe-strip__chips li{padding:5px 12px;border-radius:999px;font-size:10.5px;
  border:1px solid rgba(233,207,142,.32);color:var(--gold-light,#f2d28e)}

.cafe-strip__cta{display:inline-flex;align-items:center;gap:6px;margin-top:16px;
  font-size:12px;font-weight:700;color:var(--gold)}

@media(max-width:760px){
  .cafe-strip__img::after{background:rgba(8,7,6,.62)}
  .cafe-strip__copy{padding:18px 18px 22px}
}
</style>
