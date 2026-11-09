<script setup>
/*
  صفحة الشروط والأحكام — محتوى مبدئي (Placeholder) لحين توفير نص رسمي معتمد
  من الإدارة. راجع نفس الملاحظة في PrivacyPolicyView.vue.
*/
import { ref } from 'vue'
import { usePageStyles } from '@/composables/usePageStyles'
import { useInternalLinks } from '@/composables/useInternalLinks'
import AppFooter from '@/components/layout/AppFooter.vue'
import pageCss from '@/assets/styles/pages/legal.css?raw'

const root = ref(null)
usePageStyles(pageCss, 'terms')
useInternalLinks(root)

const SECTIONS = [
  ['قبول الشروط', 'باستخدامك لموقع وتطبيق رونق، فإنك توافق على الالتزام بهذه الشروط والأحكام بالكامل. إذا كنت لا توافق على أي جزء منها، يُرجى التوقف عن استخدام خدماتنا.'],
  ['الحجوزات والمواعيد', [
    'يجب تسجيل الدخول برقم جوال صحيح لإتمام أي حجز خدمة أو باقة.',
    'يمكن إلغاء أو تعديل الموعد قبل وقت كافٍ من الموعد المحدد وفق سياسة الفرع.',
    'التأخر عن الموعد المحدد قد يؤدي إلى تقليص مدة الخدمة أو إعادة الجدولة.',
  ]],
  ['الأسعار والدفع', 'جميع الأسعار المعروضة شاملة لضريبة القيمة المضافة ما لم يُذكر خلاف ذلك. تتم عمليات الدفع عبر وسائل الدفع المتاحة داخل المنصة فقط.'],
  ['بطاقات الهدايا', 'بطاقات الهدايا الرقمية صالحة للاستخدام خلال المدة الموضحة عند الإصدار، ولا يمكن استرجاع قيمتها نقدًا.'],
  ['الاستخدام المقبول', 'يُمنع استخدام المنصة لأي غرض غير قانوني أو بطريقة قد تضر بالخدمة أو بالمستخدمين الآخرين.'],
  ['التعديلات', 'تحتفظ رونق بحقها في تعديل هذه الشروط في أي وقت، وسيتم نشر أي تحديث على هذه الصفحة.'],
]
</script>

<template>
  <div ref="root">
    <div class="shell legal-page">
      <div class="wrap">
        <div class="page-head">
          <h1>الشروط والأحكام</h1>
          <p>يرجى قراءة هذه الشروط بعناية قبل استخدام خدمات رونق</p>
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
