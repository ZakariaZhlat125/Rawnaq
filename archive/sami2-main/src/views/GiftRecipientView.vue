<script setup>
/*
  استلام الهدية — صفحة تحويل فقط.
  الصفحة الأصلية كانت redirect ثابت؛ حُوّلت إلى تحويل عند التركيب.
*/
import { onMounted } from 'vue'
onMounted(() => {
  location.replace('/gift-recipient/' + location.search + location.hash)
})
</script>

<template>
  <p style="padding:40px;text-align:center">يتم تحويلك إلى صفحة مستلم الهدية...</p>
</template>
