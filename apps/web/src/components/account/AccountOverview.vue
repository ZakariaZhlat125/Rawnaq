<script setup>
defineProps({ profile: { type: Object, default: null } })
defineEmits(['go'])

function rs(n) {
  return Number(n || 0).toLocaleString('ar-EG-u-nu-latn')
}
</script>

<template>
  <div class="overview">
    <div v-if="profile?.user?.referral_code" class="stat-card" style="margin-bottom:20px"><h3>كود الإحالة الخاص بك</h3><p>شارك الكود مع أصدقائك واكسب نقاط ولاء عند تسجيلهم.</p><input :value="profile.user.referral_code" readonly dir="ltr" aria-label="كود الإحالة الخاص بك" @click="$event.target.select()" style="text-align:center;font-size:20px;padding:10px;border:1px solid #e9e0d3;border-radius:8px;max-width:100%" /></div>
    <div class="stat-grid">
      <button type="button" class="stat-card stat-link" @click="$emit('go', 'wallet')">
        <b>{{ rs(profile?.balances?.wallet) }} ر.س</b>
        <small>رصيد المحفظة</small>
      </button>
      <div class="stat-card">
        <b>{{ rs(profile?.balances?.loyalty_points) }}</b>
        <small>نقطة ولاء</small>
      </div>
      <div class="stat-card">
        <b>{{ profile?.stats?.pending_bookings_count ?? 0 }}</b>
        <small>حجز قادم</small>
      </div>
      <div class="stat-card">
        <b>{{ profile?.stats?.gift_cards_count ?? 0 }}</b>
        <small>بطاقة هدايا</small>
      </div>
      <div class="stat-card">
        <b>{{ profile?.stats?.packages_count ?? 0 }}</b>
        <small>باقة مشتراة</small>
      </div>
      <div class="stat-card">
        <b>{{ profile?.stats?.orders_count ?? 0 }}</b>
        <small>طلب منتجات</small>
      </div>
    </div>

    <div class="quick-links">
      <button @click="$emit('go', 'wallet')">
        <span>محفظتي</span><em>‹</em>
      </button>
      <button @click="$emit('go', 'membership')">
        <span>عضويتي (Rawnaq)</span><em>‹</em>
      </button>
      <button @click="$emit('go', 'bookings')">
        <span>حجوزاتي</span><em>‹</em>
      </button>
      <button @click="$emit('go', 'packages')">
        <span>باقاتي</span><em>‹</em>
      </button>
      <button @click="$emit('go', 'products')">
        <span>منتجاتي</span><em>‹</em>
      </button>
      <button @click="$emit('go', 'gifts')">
        <span>بطاقات الهدايا</span><em>‹</em>
      </button>
      <button @click="$emit('go', 'settings')">
        <span>إعدادات الحساب</span><em>‹</em>
      </button>
    </div>
  </div>
</template>

<style scoped>
.stat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 24px; }
.stat-card {
  background: #fff; border: 1px solid #e9e0d3; border-radius: 14px; padding: 16px;
  text-align: center;
}
.stat-link { font: inherit; cursor: pointer; transition: .2s; }
.stat-link:hover { border-color: #1C9384; background: #fff8e9; }
.stat-card b { display: block; font-size: 20px; color: #7c571d; margin-bottom: 4px; }
.stat-card small { font-size: 11px; color: #8b8379; }
.quick-links { display: grid; gap: 8px; }
.quick-links button {
  display: flex; justify-content: space-between; align-items: center;
  padding: 14px 18px; border-radius: 14px; border: 1px solid #e9e0d3; background: #fff;
  font-size: 14px; color: #241f1b; cursor: pointer; transition: .2s;
}
.quick-links button:hover { border-color: #1C9384; background: #fff8e9; }
.quick-links em { font-style: normal; color: #c3bcb2; transform: scaleX(-1); }

@media (max-width: 640px) {
  .stat-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>
