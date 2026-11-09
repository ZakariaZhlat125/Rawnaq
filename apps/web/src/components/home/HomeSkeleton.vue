<script setup>
/**
 * HomeSkeleton — يعرض هيكل الصفحة الرئيسية كاملًا أثناء تحميل البيانات
 * يحاكي شكل كل قسم (خدمات، عروض، باقات، منتجات، فروع، آراء)
 */
import Skeleton from '@/components/common/SkeletonLoader.vue'
</script>

<template>
  <div class="home-skeleton" aria-label="جاري تحميل المحتوى..." role="status">

    <!-- ═══ قسم "من نحن" ═══ -->
    <section class="about-section container skeleton-about">
      <Skeleton width="100%" height="300px" border-radius="46% 46% 40% 40%" />
      <div class="skeleton-about-copy">
        <Skeleton width="120px" height="32px" variant="text" />
        <Skeleton width="200px" height="18px" variant="text" />
        <Skeleton width="100%" height="14px" variant="text" />
        <Skeleton width="95%" height="14px" variant="text" />
        <Skeleton width="80%" height="14px" variant="text" />
        <Skeleton width="100%" height="14px" variant="text" />
        <Skeleton width="70%" height="14px" variant="text" />
        <div class="skeleton-features">
          <Skeleton v-for="n in 4" :key="n" width="100%" height="60px" border-radius="8px" />
        </div>
      </div>
    </section>

    <!-- ═══ قسم الخدمات ═══ -->
    <section class="home-section container">
      <div class="skeleton-section-title">
        <Skeleton width="42px" height="1px" />
        <Skeleton width="140px" height="28px" variant="text" />
        <Skeleton width="42px" height="1px" />
      </div>
      <div class="skeleton-services">
        <div v-for="n in 5" :key="n" class="skeleton-service-card">
          <Skeleton width="100%" height="100%" border-radius="14px" />
          <div class="skeleton-overlay">
            <Skeleton width="70%" height="16px" variant="text" />
            <Skeleton width="100%" height="28px" border-radius="20px" />
          </div>
        </div>
      </div>
    </section>

    <!-- ═══ قسم العروض ═══ -->
    <section class="home-section container promo-section">
      <div class="skeleton-promos">
        <div v-for="n in 2" :key="n" class="skeleton-promo-card">
          <Skeleton width="86px" height="86px" variant="circle" />
          <div class="skeleton-promo-text">
            <Skeleton width="100px" height="12px" variant="text" />
            <Skeleton width="200px" height="20px" variant="text" />
            <Skeleton width="85%" height="14px" variant="text" />
          </div>
          <Skeleton width="130px" height="42px" border-radius="30px" />
        </div>
      </div>
    </section>

    <!-- ═══ بانر الهدايا ═══ -->
    <section class="container">
      <div class="skeleton-gift-banner" />
    </section>

    <!-- ═══ عجلة الحظ ═══ -->
    <section class="home-section container">
      <div class="skeleton-wheel">
        <Skeleton width="200px" height="200px" variant="circle" />
        <div class="skeleton-wheel-content">
          <Skeleton width="240px" height="28px" variant="text" />
          <Skeleton width="180px" height="14px" variant="text" />
          <Skeleton width="160px" height="46px" border-radius="30px" />
        </div>
      </div>
    </section>

    <!-- ═══ الباقات + المنتجات ═══ -->
    <div class="catalog container">
      <div class="skeleton-catalog-section">
        <div class="skeleton-section-title" style="justify-content:flex-start">
          <Skeleton width="160px" height="24px" variant="text" />
        </div>
        <div class="skeleton-package-grid">
          <div v-for="n in 3" :key="n" class="skeleton-package-card">
            <Skeleton width="100px" height="100px" variant="circle" />
            <Skeleton width="80%" height="14px" variant="text" />
            <Skeleton width="90px" height="30px" border-radius="18px" />
          </div>
        </div>
      </div>
      <div class="skeleton-catalog-section">
        <div class="skeleton-section-title" style="justify-content:flex-start">
          <Skeleton width="140px" height="24px" variant="text" />
        </div>
        <div class="skeleton-product-grid">
          <div v-for="n in 3" :key="n" class="skeleton-product-card">
            <Skeleton width="100%" height="145px" border-radius="8px" />
            <Skeleton width="75%" height="13px" variant="text" />
            <Skeleton width="50%" height="13px" variant="text" />
          </div>
        </div>
      </div>
    </div>

    <!-- ═══ قسم الفروع ═══ -->
    <section class="home-section container">
      <div class="skeleton-section-title">
        <Skeleton width="42px" height="1px" />
        <Skeleton width="90px" height="28px" variant="text" />
        <Skeleton width="42px" height="1px" />
      </div>
      <div class="skeleton-branches">
        <div class="skeleton-branch-offer">
          <Skeleton width="130px" height="36px" variant="text" />
          <Skeleton width="170px" height="20px" variant="text" />
          <Skeleton width="110px" height="38px" border-radius="30px" />
        </div>
        <div v-for="n in 3" :key="n" class="skeleton-branch-card">
          <Skeleton width="100%" height="150px" border-radius="0" />
          <div class="skeleton-branch-card-body">
            <Skeleton width="70%" height="14px" variant="text" />
            <Skeleton width="50%" height="10px" variant="text" />
            <Skeleton width="112px" height="34px" border-radius="30px" />
          </div>
        </div>
      </div>
    </section>

    <!-- ═══ آراء العملاء ═══ -->
    <section class="home-section container">
      <div class="skeleton-section-title">
        <Skeleton width="42px" height="1px" />
        <Skeleton width="130px" height="28px" variant="text" />
        <Skeleton width="42px" height="1px" />
      </div>
      <div class="skeleton-testimonials">
        <div v-for="n in 3" :key="n" class="skeleton-testimonial-card">
          <Skeleton width="80px" height="14px" variant="text" />
          <Skeleton width="100%" height="12px" variant="text" />
          <Skeleton width="90%" height="12px" variant="text" />
          <Skeleton width="75%" height="12px" variant="text" />
          <Skeleton width="60px" height="12px" variant="text" />
        </div>
      </div>
    </section>

    <!-- ═══ CTA النهائي ═══ -->
    <div class="skeleton-final-cta">
      <Skeleton width="260px" height="30px" variant="text" />
      <Skeleton width="200px" height="14px" variant="text" />
      <Skeleton width="160px" height="44px" border-radius="30px" />
    </div>

  </div>
</template>

<style scoped>
.home-skeleton {
  padding-top: 12px;
}

.skeleton-about {
  display: grid;
  grid-template-columns: 0.82fr 1.8fr;
  gap: 55px;
  align-items: center;
  padding-block: 20px 18px;
  animation: skeleton-fade-in 0.4s ease both;
}

.skeleton-about-copy {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.skeleton-features {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 12px;
  margin-top: 14px;
}

@media (max-width: 950px) {
  .skeleton-about {
    grid-template-columns: 1fr;
  }
  .skeleton-features {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 560px) {
  .skeleton-features {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>
