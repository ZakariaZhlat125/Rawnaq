<script setup>
import Skeleton from '@/components/common/SkeletonLoader.vue'

defineProps({
  variant: { type: String, default: 'list' },
})
</script>

<template>
  <div class="page-skeleton" :class="`page-skeleton--${variant}`" role="status" aria-label="جاري تحميل المحتوى">
    <template v-if="variant === 'detail'">
      <Skeleton width="100%" height="260px" border-radius="8px" />
      <div class="page-skeleton__detail-copy">
        <Skeleton width="34%" height="24px" variant="text" />
        <Skeleton width="82%" height="14px" variant="text" />
        <Skeleton width="74%" height="14px" variant="text" />
        <Skeleton width="58%" height="14px" variant="text" />
      </div>
    </template>

    <template v-else-if="variant === 'account'">
      <div class="page-skeleton__account-head">
        <Skeleton width="150px" height="24px" variant="text" />
        <Skeleton width="110px" height="13px" variant="text" />
      </div>
      <div class="page-skeleton__account-grid">
        <Skeleton v-for="item in 6" :key="item" height="112px" border-radius="8px" />
      </div>
    </template>

    <template v-else>
      <div class="page-skeleton__heading">
        <Skeleton width="180px" height="26px" variant="text" />
        <Skeleton width="110px" height="13px" variant="text" />
      </div>
      <div class="page-skeleton__grid">
        <div v-for="item in 6" :key="item" class="page-skeleton__card">
          <Skeleton height="150px" border-radius="8px" />
          <Skeleton width="72%" height="16px" variant="text" />
          <Skeleton width="92%" height="12px" variant="text" />
          <Skeleton width="46%" height="12px" variant="text" />
        </div>
      </div>
    </template>
  </div>
</template>

<style scoped>
.page-skeleton { width: 100%; }
.page-skeleton__heading,
.page-skeleton__account-head { display: flex; flex-direction: column; gap: 10px; margin-bottom: 22px; }
.page-skeleton__grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 18px; }
.page-skeleton__card { display: flex; flex-direction: column; gap: 10px; padding: 10px; background: #fff; border: 1px solid var(--border, #e9e0d3); border-radius: 8px; }
.page-skeleton__detail-copy { display: flex; flex-direction: column; gap: 12px; max-width: 820px; margin: 22px auto 0; }
.page-skeleton__account-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
.page-skeleton--account { padding-top: 4px; }
@media (max-width: 700px) {
  .page-skeleton__grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
}
@media (max-width: 440px) {
  .page-skeleton__grid,
  .page-skeleton__account-grid { grid-template-columns: 1fr; }
}
</style>
