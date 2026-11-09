<script setup>
/**
 * SkeletonLoader — مكون تحميل هيكلي يعرض placeholder أثناء تحميل البيانات
 * يدعم أشكال مختلفة: مستطيل، دائرة، نص
 */
defineProps({
  /** عرض العنصر – يقبل أي وحدة CSS */
  width: { type: String, default: '100%' },
  /** ارتفاع العنصر – يقبل أي وحدة CSS */
  height: { type: String, default: '20px' },
  /** شكل العنصر: rect | circle | text */
  variant: { type: String, default: 'rect' },
  /** نصف القطر للزوايا */
  borderRadius: { type: String, default: null },
})
</script>

<template>
  <div
    class="skeleton"
    :class="[`skeleton--${variant}`]"
    :style="{
      width,
      height,
      borderRadius: borderRadius || (variant === 'circle' ? '50%' : variant === 'text' ? '6px' : '14px'),
    }"
    aria-hidden="true"
  />
</template>

<style>
/* ============ Skeleton Loading Animations ============ */
@keyframes skeleton-shimmer {
  0% { background-position: -200% 0; }
  100% { background-position: 200% 0; }
}

@keyframes skeleton-fade-in {
  from { opacity: 0; transform: translateY(8px); }
  to { opacity: 1; transform: translateY(0); }
}

.skeleton {
  display: block;
  background: linear-gradient(
    90deg,
    #e8dfd4 0%,
    #f5ede2 25%,
    #faf6f0 50%,
    #f5ede2 75%,
    #e8dfd4 100%
  );
  background-size: 200% 100%;
  animation: skeleton-shimmer 1.8s ease-in-out infinite;
}

.skeleton--circle {
  flex-shrink: 0;
}

.skeleton--text {
  border-radius: 6px;
}

/* ============ Skeleton Section Containers ============ */

/* ---- تحميل هيكلي: عنوان السكشن ---- */
.skeleton-section-title {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 25px;
  margin-bottom: 17px;
  animation: skeleton-fade-in 0.5s ease both;
}

/* ---- تحميل هيكلي: قسم الخدمات ---- */
.skeleton-services {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 16px;
  animation: skeleton-fade-in 0.5s ease both;
  animation-delay: 0.1s;
}
.skeleton-service-card {
  height: 174px;
  border-radius: var(--radius, 14px);
  overflow: hidden;
  position: relative;
}
.skeleton-service-card .skeleton-overlay {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 14px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

/* ---- تحميل هيكلي: بطاقات العروض ---- */
.skeleton-promos {
  display: flex;
  flex-direction: column;
  gap: 16px;
  animation: skeleton-fade-in 0.5s ease both;
  animation-delay: 0.15s;
}
.skeleton-promo-card {
  display: flex;
  align-items: center;
  gap: 26px;
  border-radius: 20px;
  padding: 28px 36px;
  background: linear-gradient(100deg, #1a1712, #201c16 60%, #1a1712);
  border: 1px solid #3a3020;
}
.skeleton-promo-card .skeleton {
  background: linear-gradient(
    90deg,
    #2a2520 0%,
    #3a3228 25%,
    #443a2e 50%,
    #3a3228 75%,
    #2a2520 100%
  );
  background-size: 200% 100%;
  animation: skeleton-shimmer 1.8s ease-in-out infinite;
}
.skeleton-promo-text {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

/* ---- تحميل هيكلي: البانر ---- */
.skeleton-gift-banner {
  height: 205px;
  border-radius: 12px;
  margin-bottom: 30px;
  animation: skeleton-fade-in 0.5s ease both;
  animation-delay: 0.2s;
  background: linear-gradient(
    90deg,
    #1f1c18 0%,
    #2a2520 25%,
    #332e28 50%,
    #2a2520 75%,
    #1f1c18 100%
  );
  background-size: 200% 100%;
  animation: skeleton-shimmer 1.8s ease-in-out infinite, skeleton-fade-in 0.5s ease both;
}

/* ---- تحميل هيكلي: عجلة الحظ ---- */
.skeleton-wheel {
  display: flex;
  align-items: center;
  gap: 36px;
  border-radius: 20px;
  padding: 30px 40px;
  background: linear-gradient(120deg, #0c0a08, #171310 55%, #0c0a08);
  animation: skeleton-fade-in 0.5s ease both;
  animation-delay: 0.25s;
}
.skeleton-wheel .skeleton {
  background: linear-gradient(
    90deg,
    #2a2520 0%,
    #3a3228 25%,
    #443a2e 50%,
    #3a3228 75%,
    #2a2520 100%
  );
  background-size: 200% 100%;
  animation: skeleton-shimmer 1.8s ease-in-out infinite;
}
.skeleton-wheel-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
}

/* ---- تحميل هيكلي: الباقات والمنتجات ---- */
.skeleton-catalog {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 45px;
  padding-bottom: 30px;
  animation: skeleton-fade-in 0.5s ease both;
  animation-delay: 0.3s;
}
.skeleton-catalog-section {
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.skeleton-package-grid,
.skeleton-product-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 11px;
}
.skeleton-package-card {
  border-radius: 14px;
  background: linear-gradient(180deg, #fbf8f3, #f0e8dd);
  border: 1px solid #dfcfba;
  padding: 12px 10px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  min-height: 210px;
  justify-content: center;
}
.skeleton-product-card {
  border: 1px solid #e3d7c8;
  border-radius: 10px;
  background: #fff;
  padding: 10px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  min-height: 230px;
}

/* ---- تحميل هيكلي: الفروع ---- */
.skeleton-branches {
  display: grid;
  grid-template-columns: 1.5fr repeat(3, 1fr);
  gap: 18px;
  animation: skeleton-fade-in 0.5s ease both;
  animation-delay: 0.35s;
}
.skeleton-branch-offer {
  border-radius: 14px;
  padding: 35px 28px;
  background: linear-gradient(90deg, #f3eadf, #eee5d8);
  border: 1px solid #dfd0bd;
  display: flex;
  flex-direction: column;
  gap: 12px;
  justify-content: center;
}
.skeleton-branch-card {
  border: 1px solid #dfd0bd;
  border-radius: 11px;
  background: #f3ece3;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding-bottom: 12px;
}
.skeleton-branch-card-body {
  padding: 0 12px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
}

/* ---- تحميل هيكلي: آراء العملاء ---- */
.skeleton-testimonials {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
  animation: skeleton-fade-in 0.5s ease both;
  animation-delay: 0.4s;
}
.skeleton-testimonial-card {
  border: 1px solid var(--border, #e4d7c7);
  border-radius: var(--radius, 14px);
  padding: 20px;
  background: #fff;
  box-shadow: 0 14px 35px #22180d12;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
}

/* ---- تحميل هيكلي: CTA النهائي ---- */
.skeleton-final-cta {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  padding: 50px 20px;
  animation: skeleton-fade-in 0.5s ease both;
  animation-delay: 0.45s;
}

/* ============ Responsive ============ */
@media (max-width: 950px) {
  .skeleton-services { grid-template-columns: repeat(2, 1fr); }
  .skeleton-catalog { grid-template-columns: 1fr; }
  .skeleton-branches { grid-template-columns: repeat(2, 1fr); }
  .skeleton-testimonials { grid-template-columns: 1fr; }
  .skeleton-wheel { flex-direction: column; }
}
@media (max-width: 560px) {
  .skeleton-services { grid-template-columns: 1fr; }
  .skeleton-service-card { height: 300px; }
  .skeleton-branches { grid-template-columns: 1fr; }
  .skeleton-package-grid,
  .skeleton-product-grid { grid-template-columns: 1fr; }
  .skeleton-promo-card { flex-direction: column; }
  .skeleton-gift-banner { height: auto; min-height: 275px; }
}

/* ============ Transition: Skeleton → Content ============ */
.skeleton-fade-enter-active {
  transition: opacity 0.4s ease, transform 0.4s ease;
}
.skeleton-fade-leave-active {
  transition: opacity 0.3s ease, transform 0.3s ease;
}
.skeleton-fade-enter-from {
  opacity: 0;
  transform: translateY(12px);
}
.skeleton-fade-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}
</style>
