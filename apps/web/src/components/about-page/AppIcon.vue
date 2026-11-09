<script setup>
defineProps({ name: { type: String, required: true }, size: { type: Number, default: 20 } })

const icons = {
  phone: '<path d="M22 16.9v3a2 2 0 01-2.2 2 19.8 19.8 0 01-8.6-3.1 19.5 19.5 0 01-6-6A19.8 19.8 0 012.1 4.2 2 2 0 014.1 2h3a2 2 0 012 1.7l.6 3a2 2 0 01-.5 1.9L7.9 9.9a16 16 0 006 6l1.3-1.3a2 2 0 011.9-.5l3 .6a2 2 0 011.7 2z"/>',
  whatsapp: '<path d="M12 2a10 10 0 00-8.6 15.1L2 22l5-1.3A10 10 0 1012 2z"/>',
  map: '<path d="M21 10c0 7-9 12-9 12S3 17 3 10a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/>',
  calendar: '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18M9 16l2 2 4-4"/>',
  mail: '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="M22 7l-10 6L2 7"/>',
  clock: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/>',
  share: '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="M8.6 13.5l6.8 4M15.4 6.5l-6.8 4"/>',
  userPlus: '<path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/>',
  gift: '<path d="M20 12v10H4V12M2 7h20v5H2zM12 22V7M12 7s-2-5-5-5-3 5 0 5h5zM12 7s2-5 5-5 3 5 0 5h-5z"/>',
  star: '<path d="M12 2l2.2 4.9 5.3.6-4 3.7 1.1 5.3L12 13.9 7.4 16.5l1.1-5.3-4-3.7 5.3-.6z"/>',
  bag: '<path d="M6 7h12l1.5 14h-15zM8 7a4 4 0 018 0"/>',
  scissors: '<circle cx="6" cy="6" r="2.6"/><circle cx="6" cy="18" r="2.6"/><path d="M20 4L8.2 15.8M14.6 14.6L20 20M8.2 8.2L11.8 11.8"/>',
  home: '<path d="M3 9l9-6 9 6v11a1 1 0 01-1 1H4a1 1 0 01-1-1z"/><path d="M9 21v-6h6v6"/>',
  spa: '<path d="M4 14h16M6 18h12M8 10c0-3 2-5 4-5s4 2 4 5"/>',
  bath: '<path d="M4 12h16l-2 7a2 2 0 01-2 2H8a2 2 0 01-2-2zM8 8c0-2 1.5-2 1.5-4M12 8c0-2 1.5-2 1.5-4M16 8c0-2 1.5-2 1.5-4"/>'
}
</script>

<template>
  <svg :width="size" :height="size" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" v-html="icons[name]" />
</template>
