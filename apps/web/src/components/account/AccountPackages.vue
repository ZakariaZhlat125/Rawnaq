<script setup>
defineProps({ profile: { type: Object, default: null } })

function rs(n) {
  return Number(n || 0).toLocaleString('ar-EG-u-nu-latn')
}
</script>

<template>
  <div class="packages">
    <div v-if="!profile?.purchased_packages?.length" class="empty">
      <b>لا توجد باقات مشتراة بعد</b>
      <p>الباقات اللي تشتريها هتظهر هنا</p>
      <router-link class="btn-org" to="/packages-gifts">استكشف الباقات</router-link>
    </div>
    <div v-else class="pkg-list">
      <div v-for="p in profile.purchased_packages" :key="p.id" class="pkg-card">
        <img v-if="p.image" :src="p.image" :alt="p.name" />
        <div class="pkg-info">
          <b>{{ p.name }}</b>
          <small>{{ p.purchase_date }}</small>
          <span class="price">{{ rs(p.price) }} ر.س</span>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.empty { text-align: center; padding: 40px 20px; color: #8b8379; }
.empty b { display: block; color: #241f1b; margin-bottom: 6px; }
.empty p { margin-bottom: 18px; }
.btn-org {
  display: inline-block; padding: 12px 28px; border-radius: 999px; text-decoration: none;
  background: linear-gradient(90deg, #b77420, #f0cf89, #be7920); color: #160f07;
  font-size: 14px; font-weight: 700;
}
.pkg-list { display: grid; gap: 12px; }
.pkg-card { display: flex; gap: 14px; background: #fff; border: 1px solid #e9e0d3; border-radius: 14px; padding: 12px; align-items: center; }
.pkg-card img { width: 64px; height: 64px; border-radius: 10px; object-fit: cover; flex: none; background: #f4efe6; }
.pkg-info { display: flex; flex-direction: column; gap: 3px; }
.pkg-info b { font-size: 14px; }
.pkg-info small { color: #8b8379; font-size: 11.5px; }
.pkg-info .price { color: #7c571d; font-weight: 700; font-size: 13px; }
</style>
