<script setup>
import { useRouter } from 'vue-router'
import { useServiceLocation } from '@/composables/useServiceLocation'

const router = useRouter()
const { requireLocation } = useServiceLocation()
const go = path => requireLocation(() => router.push(path))
</script>

<template>
  <section data-reveal class="gift-banner container" aria-label="بطاقات الهدايا">
    <div class="gift-banner__content">
      <h2>هل تبحث عن هدية مميزة؟</h2>
      <p>قدم تجربة عناية فاخرة لمن تحب مع بطاقات الهدايا من عناية سامي</p>
      <a class="button button--gold" href="#" @click.prevent="go('/gifts')">احصل على الهدية <span>←</span></a>
    </div>
  </section>
</template>
