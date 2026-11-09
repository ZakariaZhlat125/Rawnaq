<script setup>
import { homeService, loadHomeService } from '@/composables/useHomeService'
loadHomeService().catch(() => {})
import { useSiteContact } from '@/composables/useSiteContact'
const { whatsappUrl } = useSiteContact()
import { localizeRecord } from '@/utils/i18nField'
import { computed, ref, onMounted } from 'vue'
import { fetchBranches } from '@/services/homeApi'
import { useLanguage } from '@/composables/useLanguage'

const branches = ref([])
const { state: lang } = useLanguage()

const displayBranches = computed(() => {
  const list = branches.value.filter(b => homeService.enabled || !(b.home || b.id === 'hm'))
  if (!homeService.enabled) return list
  return list.some(branch => branch.home || branch.id === 'hm')
    ? list
    : [...list, { id: 'hm', home: true, name: 'خدمات منزلية', description: 'حلاقة شعر ولحية وماسكات طبيعية',description_en: 'Haircut, beard, and natural masks', image: '/images/generated/branches/home-service-hq.png' }]
})

function phoneDigits(phone) {
  const digits = String(phone || '').replace(/\D/g, '')
  if (digits.startsWith('00966')) return digits.slice(2)
  if (digits.startsWith('966')) return digits
  if (digits.startsWith('0')) return `966${digits.slice(1)}`
  return digits
}

const telHref = phone => `tel:+${phoneDigits(phone)}`
const footerContactNumber = computed(() => displayBranches.value.find(branch => branch.contact_number)?.contact_number || '0569472722')

onMounted(async () => {
  branches.value = await fetchBranches().catch(() => [])
})

function branchName(branch) {
  return localizeRecord(branch, 'name', lang.lang)
}
function branchDescription(branch) {
   return lang.lang === 'en' ? branch.description_en || branch.description : branch.description
}
</script>

<template>
  <footer id="contact" class="site-footer sami-unified-footer">
    <div class="wrap">
      <div class="f-grid">
        <div class="f-brand">
          <RouterLink class="logo" to="/"><span class="mark"><img src="/logo.png" alt="رونق" /></span><span class="name"><b>رونق</b><span>RAWNAQ</span></span></RouterLink>
          <p>مركز متخصص في العناية الرجالية المتكاملة بجدة، حيث تلتقي الفخامة بالاحترافية في كل تفصيلة.</p>
          <div class="socials">
            <a v-if="whatsappUrl" :href="whatsappUrl" target="_blank" rel="noopener noreferrer" aria-label="واتساب">WA</a>
            <a href="https://x.com/samicare_sa" aria-label="X">X</a>
            <a href="https://www.instagram.com/samicare.sa/" aria-label="انستقرام">◎</a>
            <a href="https://www.facebook.com/samicare.sa" aria-label="فيسبوك">f</a>
          </div>
        </div>
        <div>
          <h4>روابط مهمة</h4>
          <ul class="f-links"><li><RouterLink to="/">الرئيسية</RouterLink></li><li><RouterLink to="/booking">حجز موعد</RouterLink></li><li><RouterLink to="/#services">خدماتنا</RouterLink></li><li><RouterLink to="/packages-gifts">الباقات</RouterLink></li><li><RouterLink to="/gifts">الهدايا</RouterLink></li></ul>
        </div>
        <div>
          <h4>استكشف</h4>
          <ul class="f-links"><li><RouterLink to="/store">المتجر</RouterLink></li><li><RouterLink to="/branches">فروعنا</RouterLink></li><li><RouterLink to="/contact">تواصل معنا</RouterLink></li><li><RouterLink to="/page-about">صفحة تعريفية</RouterLink></li><li><RouterLink to="/terms">الشروط والأحكام</RouterLink></li><li><RouterLink to="/privacy-policy">سياسة الخصوصية</RouterLink></li></ul>
        </div>
        <div>
          <h4>عناوين الفروع</h4>
           <div class="f-branch">
            <b>الادارة</b>
           
            <a :href="telHref('0569472722')">0569472722</a>
          </div>
          <div v-for="branch in displayBranches" :key="branch.id" class="f-branch">
            <b>{{ branchName(branch) }}</b>
            <small><!-- {{ localizeRecord(branch, 'address', lang.lang) || localizeRecord(branch, 'address_line_1', lang.lang) }} --></small>
            <small>{{ branchDescription(branch) }}</small>
            
            <a v-if="branch.contact_number" :href="telHref(branch.contact_number)">{{ branch.contact_number }}</a>
          </div>
         
        </div>
      </div>
      <div class="f-bottom">
        <small>© {{ new Date().getFullYear() }} رونق — جميع الحقوق محفوظة</small>
        <div class="pay" aria-label="بوابات الدفع"><span title="Visa">VISA</span><span title="Mastercard">Mastercard</span><span title="مدى">mada</span><span title="Tabby">tabby</span><span title="Apple Pay">Pay</span></div>
      </div>
    </div>
  </footer>
</template>

<style scoped>
.sami-unified-footer{background:radial-gradient(circle at 18% 0,rgba(95,211,192,.16),transparent 30%),linear-gradient(180deg,#0b0906,#050403);color:#f5efe4;padding:58px 24px 20px;border-top:1px solid rgba(198,161,91,.28)}
.sami-unified-footer .wrap{width:min(1280px,94%);margin-inline:auto}
.f-grid{max-width:1200px;margin:0 auto;display:grid;grid-template-columns:1.25fr .85fr 1fr 1.1fr;gap:28px;align-items:start}
.f-brand{min-width:0}.f-brand .logo{display:flex;align-items:center;gap:11px}.f-brand .mark{width:56px;height:56px;display:grid;place-items:center;border:1.5px solid #1F9E8F;border-radius:14px;overflow:hidden}.f-brand .mark img{width:100%;height:100%;object-fit:contain}.f-brand .name b{font-size:17px;color:#D6EFEA;display:block;line-height:1.15}.f-brand .name span{font-size:10px;color:#9a9080;letter-spacing:.08em}.f-brand p{max-width:330px;color:#cfc5b3;line-height:1.9;font-size:14px;margin-top:18px}
.socials{display:flex;gap:10px;margin-top:18px}.socials a{width:38px;height:38px;border-radius:50%;display:grid;place-items:center;border:1px solid rgba(198,161,91,.34);color:#7EDDCD;background:rgba(255,255,255,.03)}
.sami-unified-footer h4{font-family:inherit;color:#A6EBDF;margin:0 0 12px;font-size:22px}.f-links{display:grid;gap:8px;list-style:none;padding:0;margin:0}.f-links a{color:#cfc5b3;line-height:1.9;font-size:14px}.f-links a:hover{color:#7EDDCD}
.f-branch{display:grid;gap:3px;margin-bottom:12px}.f-branch b{color:#fff}.f-branch small{color:#b8ad9d;line-height:1.8}.f-branch a{color:#7EDDCD;font-size:13px;text-align: start;direction:ltr;}
.f-bottom{max-width:1200px;margin:34px auto 0;padding-top:18px;border-top:1px solid rgba(255,255,255,.1);display:grid;grid-template-columns:auto minmax(320px,1fr);align-items:center;gap:18px;color:#b8ad9d;font-size:13px}.pay{justify-self:end;display:grid;grid-template-columns:repeat(5,minmax(72px,1fr));gap:10px;width:min(100%,520px)}.pay span{min-height:37px;border-radius:14px;display:grid;place-items:center;border:1px solid rgba(95,211,192,.3);background:linear-gradient(160deg,rgba(255,255,255,.1),rgba(255,255,255,.03));color:#A6EBDF;font-size:0;font-weight:900;position:relative;overflow:hidden}.pay span::before{font-size:13px;line-height:1}.pay span:nth-child(1)::before{content:'VISA';font-style:italic;font-size:15px;color:#fff}.pay span:nth-child(2)::before{content:'MC';font-size:14px;color:#111;background:linear-gradient(90deg,#eb001b 0 50%,#f79e1b 50%);width:38px;height:24px;border-radius:999px;display:grid;place-items:center}.pay span:nth-child(3)::before{content:'مدى';font-size:16px;color:#9fe7c1}.pay span:nth-child(4)::before{content:'tabby';font-size:14px;color:#b8f7d0}.pay span:nth-child(5)::before{content:'Pay';font-size:15px;color:#fff}
@media(max-width:900px){.f-grid{grid-template-columns:1fr 1fr}.f-brand{grid-column:1/-1}.f-bottom{grid-template-columns:1fr}.pay{justify-self:stretch;width:100%}}
@media(max-width:640px){.sami-unified-footer{padding:42px 16px calc(22px + env(safe-area-inset-bottom))}.f-grid{grid-template-columns:1fr 1fr;gap:22px 14px}.f-brand,.f-grid>div:last-child{grid-column:1/-1}.f-brand{text-align:center}.f-brand .logo{justify-content:center}.f-brand p{max-width:none;margin-inline:auto;font-size:13px}.socials{justify-content:center}.sami-unified-footer h4{font-size:15px;margin-bottom:10px}.f-links a{font-size:12.5px}.f-branch{background:rgba(255,255,255,.035);border:1px solid rgba(198,161,91,.18);border-radius:14px;padding:12px}.f-bottom{margin-top:24px;text-align:center}.pay{grid-template-columns:repeat(3,1fr);gap:8px}.pay span{min-height:44px;border-radius:12px}.pay span:nth-child(5){grid-column:2/3}}
</style>
