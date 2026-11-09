<script setup>
import SectionTitle from '@/components/common/SectionTitle.vue'
import { testimonials } from '@/data/home'
</script>

<template>
  <section data-reveal class="home-section container reviews" aria-label="آراء العملاء">
    <SectionTitle title="آراء عملائنا" />
    <p class="reviews__sub">نفخر بثقة عملائنا ونسعى دائمًا لتقديم تجربة استثنائية</p>

    <!-- شريط التقييم الموحّد — نفس ترتيب نسخة الجوال -->
    <div class="reviews__stats">
      <div class="rstat">
        <span class="rstat__g">G</span>
        <span class="rstat__label">Google Reviews</span>
        <small>تم التحقق منها ✓</small>
      </div>
      <div class="rstat">
        <strong>4.9</strong>
        <span class="rstat__stars">★★★★★</span>
        <small>من 5</small>
      </div>
      <div class="rstat">
        <span class="rstat__ic">
          <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
            <path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zM22 21v-2a4 4 0 00-3-3.9M16 3.1a4 4 0 010 7.8"/>
          </svg>
        </span>
        <strong>+2,400</strong>
        <small>تقييم</small>
      </div>
    </div>

    <div class="reviews__grid">
      <article v-for="t in testimonials" :key="t.id" class="rcard">
        <div class="rcard__photo">
          <img src="/images/reviews/spa-room-exact.webp" alt="غرفة عناية سامي" loading="lazy" />
        </div>
        <div class="rcard__copy">
          <div class="rcard__rating">
            <span class="rcard__stars" aria-hidden="true">{{ '★'.repeat(t.rating) }}</span>
            <b>{{ t.rating.toFixed(1) }}</b>
          </div>
          <div class="rcard__who">
            <b>{{ t.name }} <span class="rcard__verified" title="عميل موثّق">✓</span></b>
            <small>
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 10c0 7-9 12-9 12S3 17 3 10a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/>
              </svg>
              {{ t.place }}
            </small>
          </div>
          <span class="rcard__quote" aria-hidden="true">”</span>
          <p>{{ t.text }}</p>
          <div class="rcard__meta">
            <span>
              <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>
              </svg>
              {{ t.when }}
            </span>
            <span class="rcard__google">Google Reviews <b>G</b></span>
          </div>
        </div>
      </article>
    </div>
  </section>
</template>

<style scoped>
.reviews__sub{text-align:center;font-size:12.5px;color:var(--muted);margin:-4px 0 22px;line-height:1.9}

/* ===== شريط الإحصاءات ===== */
.reviews__stats{display:flex;justify-content:center;align-items:stretch;gap:0;margin:0 auto 26px;
  max-width:640px;border:1px solid var(--border);border-radius:16px;background:#fff;
  box-shadow:0 14px 30px -24px rgba(80,60,20,.5);overflow:hidden}
.rstat{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:3px;
  padding:16px 12px;text-align:center;border-inline-start:1px solid var(--border)}
.rstat:first-child{border-inline-start:0}
.rstat strong{font-family:var(--font-d,inherit);font-size:22px;color:var(--ink,#17130d);line-height:1}
.rstat small{font-size:10.5px;color:var(--muted)}
.rstat__g{width:30px;height:30px;border-radius:50%;display:grid;place-items:center;
  background:#fff;border:1px solid var(--border);color:#4285F4;font-weight:800;font-size:15px}
.rstat__label{font-size:11.5px;font-weight:700;color:var(--ink,#17130d)}
.rstat__stars{color:var(--gold);font-size:13px;letter-spacing:1.5px}
.rstat__ic{color:var(--gold);display:grid;place-items:center}

/* ===== الكروت: صورة على اليمين ونص على اليسار ===== */
.reviews__grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}
.rcard{display:grid;grid-template-columns:44% 56%;overflow:hidden;background:#fff;
  border:1px solid var(--border);border-radius:18px;box-shadow:0 16px 32px -26px rgba(80,60,20,.55);
  transition:transform .3s cubic-bezier(.22,1,.36,1),box-shadow .3s ease}
.rcard:hover{transform:translateY(-5px);box-shadow:0 24px 42px -24px rgba(80,60,20,.6)}
.rcard__photo{position:relative;overflow:hidden;background:#efe8df}
.rcard__photo img{width:100%;height:100%;object-fit:cover;display:block;transition:transform .6s}
.rcard:hover .rcard__photo img{transform:scale(1.06)}

.rcard__copy{position:relative;padding:18px 16px 14px;display:flex;flex-direction:column}
.rcard__rating{display:flex;align-items:center;gap:7px;margin-bottom:9px}
.rcard__stars{color:var(--gold);font-size:13px;letter-spacing:1.5px}
.rcard__rating b{font-size:11px;color:var(--muted)}

.rcard__who b{display:block;font-size:13px;color:var(--ink,#17130d)}
.rcard__verified{display:inline-grid;place-items:center;width:14px;height:14px;border-radius:50%;
  background:var(--gold);color:#fff;font-size:9px;vertical-align:middle}
.rcard__who small{display:flex;align-items:center;gap:5px;font-size:10.5px;color:var(--muted);margin-top:3px}

.rcard__quote{font-family:Georgia,serif;font-size:38px;line-height:0;color:rgba(201,139,49,.3);
  display:block;margin:14px 0 6px;height:14px}
.rcard__copy p{font-size:11.5px;line-height:1.95;color:var(--muted);margin:0 0 12px;flex:1}

.rcard__meta{display:flex;align-items:center;justify-content:space-between;gap:8px;
  padding-top:10px;border-top:1px solid var(--border);font-size:10px;color:var(--muted)}
.rcard__meta span{display:inline-flex;align-items:center;gap:4px}
.rcard__google b{color:#4285F4;font-weight:800;font-size:11px}

@media(max-width:1000px){.reviews__grid{grid-template-columns:1fr}.rcard{grid-template-columns:38% 62%}}
@media(max-width:560px){
  .reviews__stats{flex-wrap:wrap}.rstat{flex:1 0 33%}
  .rcard{grid-template-columns:1fr}.rcard__photo{height:150px}
}
</style>
