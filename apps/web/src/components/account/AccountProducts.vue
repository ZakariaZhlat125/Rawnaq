<script setup>
defineProps({ profile: { type: Object, default: null } })

function rs(n) {
  return Number(n || 0).toLocaleString('ar-EG-u-nu-latn')
}
</script>

<template>
  <div class="products">
    <div v-if="!profile?.purchased_products?.length" class="empty">
      <b>لا توجد منتجات مشتراة بعد</b>
      <p>المنتجات اللي تشتريها من المتجر هتظهر هنا</p>
      <router-link class="btn-org" to="/store">تسوّق الآن</router-link>
    </div>
    <div v-else class="prod-list">
      <div v-for="(p, i) in profile.purchased_products" :key="`${p.order_id}-${p.product_id}-${i}`" class="prod-card">
        <img v-if="p.image" :src="p.image" :alt="p.name" />
        <div class="prod-info">
          <b>{{ p.name }}</b>
          <small>الكمية: {{ p.qty }} — {{ p.purchase_date }}</small>
          <span class="price">{{ rs(p.total_price) }} ر.س</span>
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
.prod-list { display: grid; gap: 12px; }
.prod-card { display: flex; gap: 14px; background: #fff; border: 1px solid #e9e0d3; border-radius: 14px; padding: 12px; align-items: center; }
.prod-card img { width: 64px; height: 64px; border-radius: 10px; object-fit: cover; flex: none; background: #f4efe6; }
.prod-info { display: flex; flex-direction: column; gap: 3px; }
.prod-info b { font-size: 14px; }
.prod-info small { color: #8b8379; font-size: 11.5px; }
.prod-info .price { color: #7c571d; font-weight: 700; font-size: 13px; }
</style>
