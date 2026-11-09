<script setup>
import { ref, onMounted } from 'vue'
import { authFetch } from '@/services/apiClient'
import BookingQr from '@/components/common/BookingQr.vue'
import PageSkeleton from '@/components/common/PageSkeleton.vue'

const membership = ref(null)
const loading = ref(true)
const error = ref('')

async function fetchMembership() {
  loading.value = true
  try {
    const res = await authFetch('/membership/my-card')
    if (res.status) {
      membership.value = res.data
    } else {
      error.value = 'تعذر تحميل بيانات العضوية'
    }
  } catch (e) {
    error.value = 'حدث خطأ أثناء تحميل بيانات العضوية'
  } finally {
    loading.value = false
  }
}

async function regenerateQr() {
  if (!confirm('هل أنت متأكد من تجديد رمز QR؟ لن تتمكن من استخدام الرمز القديم.')) return
  
  try {
    const res = await authFetch('/membership/regenerate-qr', { method: 'POST' })
    if (res.status) {
      membership.value.qr_token = res.qr_token
      alert('تم تجديد رمز QR بنجاح')
    }
  } catch (e) {
    alert('حدث خطأ أثناء التجديد')
  }
}

onMounted(() => {
  fetchMembership()
})
</script>

<template>
  <div class="membership-card-view">
    <PageSkeleton v-if="loading" />
    <div v-else-if="error" class="alert alert-danger">{{ error }}</div>
    <div v-else-if="membership" class="membership-content">
      
      <!-- Card Display -->
      <div class="digital-card" :class="'tier-' + membership.tier_level">
        <div class="card-header">
          <h3>Rawnaq</h3>
          <span class="tier-badge">{{ membership.tier_ar }}</span>
        </div>
        <div class="card-body">
          <div class="member-name">{{ membership.member_name }}</div>
          <div class="points">{{ membership.points_balance }} نقطة</div>
        </div>
        <div class="card-footer">
          <div class="discount">خصم {{ membership.discount }}%</div>
        </div>
      </div>

      <!-- QR Code Section -->
      <div class="qr-section">
        <p class="text-muted mb-2">امسح هذا الرمز في الفرع للحصول على المزايا</p>
        <div class="qr-box">
          <BookingQr :token="membership.qr_token" />
        </div>
        <button class="btn btn-outline-secondary mt-3" @click="regenerateQr">تجديد الرمز</button>
      </div>

      <!-- Progress Section -->
      <div class="progress-section" v-if="membership.next_tier">
        <h4>الترقية القادمة: {{ membership.next_tier.name_ar }}</h4>
        <p>متبقي {{ membership.points_to_next_tier }} نقطة للترقية</p>
        <div class="progress">
          <div class="progress-bar" role="progressbar" :style="{ width: membership.progress_percentage + '%' }"></div>
        </div>
      </div>

      <!-- Benefits List -->
      <div class="benefits-section mt-4">
        <h4>مزاياك الحالية</h4>
        <ul class="benefits-list">
          <li v-for="(benefit, idx) in membership.benefits.ar" :key="idx">
            <i class="fas fa-check-circle text-success"></i> {{ benefit }}
          </li>
        </ul>
      </div>

    </div>
  </div>
</template>

<style scoped>
.membership-content {
  max-width: 500px;
  margin: 0 auto;
  text-align: center;
}

.digital-card {
  border-radius: 20px;
  padding: 25px;
  color: white;
  margin-bottom: 30px;
  box-shadow: 0 10px 25px rgba(0,0,0,0.15);
  position: relative;
  overflow: hidden;
  text-align: right;
  background: linear-gradient(135deg, #2b2b2b, #444);
}

.digital-card.tier-1 { background: linear-gradient(135deg, #795548, #A1887F); }
.digital-card.tier-2 { background: linear-gradient(135deg, #9E9E9E, #E0E0E0); color: #333; }
.digital-card.tier-3 { background: linear-gradient(135deg, #FFB300, #FFE082); color: #333; }
.digital-card.tier-4 { background: linear-gradient(135deg, #212121, #607D8B); }

.card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
.card-header h3 { margin: 0; font-family: sans-serif; font-weight: bold; }
.tier-badge { background: rgba(255,255,255,0.2); padding: 5px 15px; border-radius: 20px; font-weight: bold; }

.member-name { font-size: 1.5rem; font-weight: bold; margin-bottom: 5px; }
.points { font-size: 1.2rem; opacity: 0.9; }
.card-footer { margin-top: 30px; font-size: 1.1rem; font-weight: bold; text-align: left; }

.qr-section { margin: 30px 0; background: #fff; padding: 20px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
.qr-box { background: #fff; padding: 10px; display: inline-block; border-radius: 10px; border: 1px solid #eee; }

.progress-section { text-align: right; margin-bottom: 30px; }
.progress { height: 10px; border-radius: 5px; background: #eee; margin-top: 10px; }
.progress-bar { background: #bf9456; border-radius: 5px; }

.benefits-section { text-align: right; }
.benefits-list { list-style: none; padding: 0; }
.benefits-list li { margin-bottom: 10px; font-size: 1.1rem; }
.benefits-list i { margin-left: 10px; }
</style>
