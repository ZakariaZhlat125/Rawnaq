<script setup>
import SectionTitle from './SectionTitle.vue'
import { stats } from '../data/site'
</script>

<template>
  <section>
    <SectionTitle title="نبذة عن المركز" />
    <div class="card about reveal">
      <p><b>مركز رونق للرجال</b> — وجهتك المتكاملة للعناية الرجالية الفاخرة في جدة. نقدم تجربة تجمع بين الأصالة والاحترافية: من <b>الحلاقة</b> بأيدي نخبة الحلاقين، إلى <b>المساج</b> و<b>الحمام المغربي</b> و<b>تنظيف البشرة</b> و<b>البديكير</b> — في أجواء هادئة صُممت لراحتك، مع خدمة منزلية تصلك أينما كنت.</p>
      <div class="stats"><div v-for="stat in stats" :key="stat.label"><b>{{ stat.value }}</b><small>{{ stat.label }}</small></div></div>
    </div>
  </section>
</template>
