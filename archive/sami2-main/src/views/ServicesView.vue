<script setup>
import { categoryIconPath } from '@/utils/giftIcons'

/* أيقونات الأقسام مفهرسة بـ hair/skin/... بينما خدمات الرئيسية مرقّمة 1..5 */
const CAT_BY_SERVICE = { 1: 'bath', 2: 'pedi', 3: 'hair', 4: 'skin', 5: 'mass' }
const iconOf = id => categoryIconPath(CAT_BY_SERVICE[id])
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import AppFooter from '@/components/layout/AppFooter.vue'
import { services } from '@/data/home'
import { useRouter } from 'vue-router'
import LocationNotice from '@/components/common/LocationNotice.vue'
import { useServiceLocation } from '@/composables/useServiceLocation'
import { serviceDetails } from '@/data/serviceDetails'

// نبني بطاقات الخدمات من بيانات التفاصيل: أقل سعر وأقصر/أطول مدة
const cards = computed(() =>
  services.map(s => {
    const d = serviceDetails[s.id]
    if (!d) return { ...s, tagline: '', from: null, durMin: null, durMax: null, count: 0 }
    const prices = d.variants.map(v => v.price)
    const durs = d.variants.map(v => v.dur)
    return {
      id: s.id,
      name: d.name,
      image: d.heroImage || s.image,
      tagline: d.tagline,
      from: Math.min(...prices),
      durMin: Math.min(...durs),
      durMax: Math.max(...durs),
      count: d.variants.length,
    }
  })
)

const router = useRouter()
const { requireLocation } = useServiceLocation()
const go = path => requireLocation(() => router.push(path))

const perks = [
  { ic: '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>', t: 'أعلى معايير النظافة', s: 'تعقيم كامل للأدوات قبل وبعد كل جلسة.' },
  { ic: '<path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>', t: 'مختصون محترفون', s: 'فريق مدرّب على أعلى مستوى في كل خدمة.' },
  { ic: '<path d="M11 20A7 7 0 019.8 6.1C15.5 5 20 9.5 20 15a7 7 0 01-9 5z"/><path d="M2 21c0-9 4-13 13-13"/>', t: 'منتجات طبيعية فاخرة', s: 'مكونات مختارة بعناية وآمنة على البشرة.' },
  { ic: '<path d="M6 3h12v4H6z"/><path d="M6 7v10a2 2 0 002 2h8a2 2 0 002-2V7"/>', t: 'أجواء راقية وهادئة', s: 'خصوصية تامة ومساحة مصممة للاسترخاء.' },
]
</script>

<template>
  <div class="home-page services-page" dir="rtl">
    <main>
      <!-- الهيرو -->
      <section class="sv-hero">
        <div class="container sv-hero__in">
          <p class="sv-eyebrow">عناية رجالية متكاملة</p>
          <h1>خدماتنا</h1>
          <p class="sv-lead">
            مجموعة خدمات مصممة لتمنحك تجربة عناية فاخرة من أول لحظة —
            اختر الخدمة التي تناسبك واطّلع على تفاصيلها وأنواعها وأسعارها قبل الحجز.
          </p>
        </div>
      </section>

      <!-- شبكة الخدمات -->
      <section class="home-section container">
        <div class="section-title"><i></i><h2>اختر الخدمة المناسبة لك</h2><i></i></div>
        <div class="sv-loc"><LocationNotice /></div>
        <div class="sv-grid">
          <article v-for="c in cards" :key="c.id" class="sv-card">
            <div class="sv-card__img">
              <img :src="c.image" :alt="c.name" loading="lazy" />
              <span v-if="c.count" class="sv-card__badge">{{ c.count }} أنواع</span>
            </div>
            <span class="sv-card__ic" aria-hidden="true">
              <svg viewBox="0 0 24 24" v-html="iconOf(c.id)"></svg>
            </span>
            <div class="sv-card__body">
              <h3>{{ c.name }}</h3>
              <p class="sv-card__desc">{{ c.tagline }}</p>
              <div class="sv-card__meta">
                <span v-if="c.durMin">🕐 {{ c.durMin }}–{{ c.durMax }} دقيقة</span>
                <span v-if="c.from" class="sv-card__price">يبدأ من <b>{{ c.from }} ريال</b></span>
              </div>
              <div class="sv-card__actions">
                <a href="#" class="sv-btn sv-btn--main" @click.prevent="go(`/services/${c.id}`)">التفاصيل ←</a>
              </div>
            </div>
          </article>
        </div>
      </section>

      <!-- لماذا تختارنا -->
      <section class="home-section container">
        <div class="section-title"><i></i><h2>لماذا تختار عناية سامي؟</h2><i></i></div>
        <div class="sv-perks">
          <div v-for="(p, i) in perks" :key="i" class="sv-perk">
            <span class="sv-perk__ic" v-html="`<svg width='22' height='22' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='1.6'>${p.ic}</svg>`"></span>
            <b>{{ p.t }}</b>
            <small>{{ p.s }}</small>
          </div>
        </div>
      </section>

      <!-- بطاقة ختامية -->
      <section class="sv-cta container">
        <h2>جاهز لتجربة عناية مختلفة؟</h2>
        <p>احجز موعدك الآن واختر الخدمة والوقت الذي يناسبك</p>
        <RouterLink to="/booking" class="button button--gold">احجز الآن ←</RouterLink>
      </section>
    </main>
    <AppFooter />
  </div>
</template>

<style scoped>
.sv-loc{text-align:center;margin:-4px 0 22px}
.sv-hero{background:radial-gradient(circle at 22% 50%,#4b351b55,transparent 30%),linear-gradient(110deg,#050606 48%,#17120d);color:#fff;padding:58px 0 54px}
.sv-hero__in{text-align:center}
.sv-eyebrow{color:var(--gold);font-size:12px;letter-spacing:1px;margin:0 0 8px}
.sv-hero h1{font-size:44px;margin:0 0 14px}
.sv-lead{font-size:14px;line-height:2;color:#cfc7bb;max-width:60ch;margin:0 auto}

/* الخدمات كلها في صف واحد */
.sv-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:14px}
@media(max-width:1100px){.sv-grid{grid-template-columns:repeat(3,minmax(0,1fr))}}
@media(max-width:720px){.sv-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:11px}}
.sv-card{border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;background:#fff;box-shadow:var(--shadow);
  display:flex;flex-direction:column;transition:transform .28s,box-shadow .28s}
.sv-card:hover{transform:translateY(-5px);box-shadow:0 26px 46px -24px rgba(80,60,20,.45)}
.sv-card{position:relative}
.sv-card__img{position:relative;aspect-ratio:4/3;overflow:hidden;background:linear-gradient(135deg,#ece4d9,#d5c1a5)}
.sv-card__img img{width:100%;height:100%;object-fit:cover;display:block;transition:transform .6s}
.sv-card:hover .sv-card__img img{transform:scale(1.05)}
.sv-card__badge{position:absolute;top:12px;right:12px;background:rgba(12,10,8,.72);color:var(--gold-light);
  font-size:10px;border-radius:20px;padding:4px 12px;backdrop-filter:blur(3px)}
/* أيقونة الخدمة بين الصورة والنص */
.sv-card__ic{position:relative;z-index:2;display:grid;place-items:center;width:46px;height:46px;margin:-23px auto 0;
  border-radius:50%;background:#fff;border:1.5px solid rgba(201,139,49,.42);color:var(--gold);
  box-shadow:0 8px 18px -10px rgba(80,60,20,.45)}
.sv-card__ic svg{width:23px;height:23px;fill:none;stroke:currentColor;stroke-width:1.6;stroke-linecap:round;stroke-linejoin:round}

.sv-card__body{padding:12px 14px 16px;display:flex;flex-direction:column;flex:1;text-align:center}
.sv-card__body h3{font-size:15px;margin:6px 0 6px}
.sv-card__desc{font-size:11.5px;line-height:1.85;color:var(--muted);margin:0 0 12px;
  display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden}
.sv-card__meta{display:flex;flex-direction:column;align-items:center;gap:4px;font-size:11px;color:var(--muted);
  padding-top:10px;border-top:1px dashed var(--border);margin-top:auto}
.sv-card__price b{color:var(--gold);font-size:13px}
.sv-card__actions{display:grid;grid-template-columns:1fr;gap:8px;margin-top:12px}
.sv-btn{display:inline-flex;align-items:center;justify-content:center;border-radius:22px;padding:9px 10px;font-size:12px;font-weight:600;transition:.22s}
.sv-btn--main{background:linear-gradient(90deg,#b77420,#f0cf89,#be7920);color:#160f07}
.sv-btn--main:hover{filter:brightness(1.05)}
.sv-btn--ghost{border:1px solid var(--gold);color:var(--gold)}
.sv-btn--ghost:hover{background:#fdf6e9}

.sv-perks{display:grid;grid-template-columns:repeat(4,1fr);gap:16px}
.sv-perk{text-align:center;border:1px solid var(--border);border-radius:var(--radius);padding:22px 16px;background:#fff}
.sv-perk__ic{display:inline-grid;place-items:center;width:46px;height:46px;border-radius:50%;background:#f7efe0;color:var(--gold);margin-bottom:10px}
.sv-perk b{display:block;font-size:13px;margin-bottom:5px}
.sv-perk small{font-size:11px;color:var(--muted);line-height:1.7}

.sv-cta{text-align:center;background:var(--black);color:#fff;border-radius:var(--radius);padding:50px 20px;margin:24px auto 44px}
.sv-cta h2{color:var(--gold);font-size:27px;margin:0 0 8px}
.sv-cta p{font-size:13px;color:#cfc7bb;margin:0 0 20px}

@media(max-width:900px){
  .sv-hero h1{font-size:34px}
  .sv-perks{grid-template-columns:repeat(2,1fr)}
}
</style>
