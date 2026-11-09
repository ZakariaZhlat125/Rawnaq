<script setup>
import { useServiceLocation } from '@/composables/useServiceLocation'

const { current, openPicker } = useServiceLocation()
</script>

<template>
  <!--
    ملاحظة صغيرة على سطر واحد — مش سكشن كامل.
    الهدف إنها تعرّف المستخدم بمكان التنفيذ من غير ما تاخد مساحة القسم.
  -->
  <p class="loc-note" :class="{ 'loc-note--empty': !current }">
    <span class="loc-note__ic" aria-hidden="true">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M21 10c0 7-9 12-9 12S3 17 3 10a9 9 0 0118 0z" /><circle cx="12" cy="10" r="3" />
      </svg>
    </span>
    <template v-if="current">مكان التنفيذ: <b>{{ current.name }}</b></template>
    <template v-else>لم تحدد مكان تنفيذ الخدمة بعد</template>
    <button type="button" @click="openPicker">{{ current ? 'تغيير' : 'اختر المكان' }}</button>
  </p>
</template>

<style scoped>
.loc-note {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  margin: 0 0 18px;
  padding: 0;
  background: none;
  border: 0;
  font-size: 12px;
  color: #6f675e;
  line-height: 1.6;
}
.loc-note__ic { display: inline-flex; color: #b98a33; flex: none }
.loc-note b { color: #a8721c; font-weight: 700 }
.loc-note--empty { color: #a5603f }
.loc-note--empty .loc-note__ic { color: #c07048 }

.loc-note button {
  border: 0;
  background: none;
  padding: 0;
  margin-inline-start: 2px;
  color: #a8721c;
  font: inherit;
  font-size: 11.5px;
  font-weight: 700;
  text-decoration: underline;
  text-underline-offset: 3px;
  cursor: pointer;
}
.loc-note button:hover { color: #855911 }

@media (max-width: 560px) {
  .loc-note { font-size: 11.5px; flex-wrap: wrap }
}
</style>
