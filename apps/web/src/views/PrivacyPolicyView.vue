<script setup>
/*
  صفحة سياسة الخصوصية — محتوى مبدئي (Placeholder) لحين توفير نص رسمي معتمد
  من الإدارة. راجع نفس الملاحظة في TermsView.vue.
*/
import { ref } from 'vue'
import { usePageStyles } from '@/composables/usePageStyles'
import { useInternalLinks } from '@/composables/useInternalLinks'
import AppFooter from '@/components/layout/AppFooter.vue'
import pageCss from '@/assets/styles/pages/legal.css?raw'

const root = ref(null)
usePageStyles(pageCss, 'privacy')
useInternalLinks(root)

const SECTIONS = [
  ['البيانات التي نجمعها', [
    'الاسم ورقم الجوال المستخدمين لإنشاء الحساب وتسجيل الدخول.',
    'بيانات الحجوزات والطلبات (الخدمات، الباقات، المنتجات، الهدايا).',
    'العنوان عند طلب توصيل منتجات أو خدمة منزلية.',
  ]],
  ['كيف نستخدم بياناتك', 'نستخدم بياناتك لتنفيذ حجوزاتك وطلباتك، والتواصل معك بخصوصها، وتحسين تجربتك داخل التطبيق والموقع.'],
  ['مشاركة البيانات', 'لا تتم مشاركة بياناتك الشخصية مع أي طرف ثالث إلا في الحدود اللازمة لإتمام الخدمة (مثل بوابات الدفع وشركات التوصيل).'],
  ['حماية البيانات', 'نتخذ إجراءات تقنية وتنظيمية معقولة لحماية بياناتك من الوصول أو الاستخدام غير المصرح به.'],
  ['حقوقك', 'يمكنك طلب الاطلاع على بياناتك أو تحديثها أو حذف حسابك من خلال صفحة "حسابي" أو بالتواصل مع فريق الدعم.'],
  ['التعديلات', 'قد يتم تحديث سياسة الخصوصية من وقت لآخر، وسيتم نشر أي تعديل على هذه الصفحة.'],
]
</script>

<template>
  <div ref="root">
    <div class="shell legal-page">
      <div class="wrap">
        <div class="page-head">
          <h1>سياسة الخصوصية</h1>
          <p>كيف نجمع بياناتك ونستخدمها ونحميها داخل منصة رونق</p>
        </div>
        <div class="legal-content">
          <section v-for="(s, i) in SECTIONS" :key="i">
            <h2><i></i> {{ s[0] }}</h2>
            <p v-if="typeof s[1] === 'string'">{{ s[1] }}</p>
            <ul v-else><li v-for="(li, j) in s[1]" :key="j">{{ li }}</li></ul>
          </section>
          <p class="updated">آخر تحديث: 2026 — هذا نص مبدئي قابل للتحديث من إدارة رونق.</p>
        </div>
      </div>
    </div>
    
  </div>
</template>
