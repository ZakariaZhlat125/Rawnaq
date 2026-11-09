<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import AppHeader from '@/components/layout/AppHeader.vue'
import AppFooter from '@/components/layout/AppFooter.vue'
import PageSkeleton from '@/components/common/PageSkeleton.vue'
import { fetchBlog } from '@/services/blogApi'
import { assetPath } from '@/utils/assetPath'
import { useLanguage } from '@/composables/useLanguage'
const { state: language } = useLanguage()
import { localizeRecord } from '@/utils/i18nField'
const field = (item, key) => localizeRecord(item, key, language.lang)
let blogRequest = 0
import '@/assets/styles/home.css'

const route = useRoute()
const router = useRouter()
const blog = ref(null)
const loading = ref(true)
const error = ref('')
const fallbackImage = assetPath('/logo.png')

const formattedDate = computed(() => {
  if (!blog.value?.published_at) return ''

  return new Intl.DateTimeFormat(language.lang === 'en' ? 'en-GB' : 'ar-SA', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  }).format(new Date(blog.value.published_at))
})

async function loadBlog(slug) {
  const request = ++blogRequest
  loading.value = true
  error.value = ''

  try {
    const result = await fetchBlog(slug)
    if (request === blogRequest) blog.value = result
  } catch (err) {
    if (request !== blogRequest) return
    blog.value = null
    error.value = 'المدونة غير متاحة حاليا.'
  } finally {
    if (request === blogRequest) loading.value = false
  }
}

function goBack() {
  if (window.history.length > 1) router.back()
  else router.push('/blog')
}

onMounted(() => loadBlog(route.params.slug))
watch(() => route.params.slug, slug => loadBlog(slug))
watch(() => language.lang, () => loadBlog(route.params.slug))
</script>

<template>
  <div class="home-page blog-detail-page" dir="rtl">
    <AppHeader />

    <main>
      <section v-if="loading" class="container blog-detail-state">
        <PageSkeleton variant="detail" />
      </section>

      <section v-else-if="error" class="container blog-detail-state blog-detail-state--error">
        <p>{{ error }}</p>
        <RouterLink to="/blog" class="blog-detail__back">العودة للمدونة</RouterLink>
      </section>

      <article v-else-if="blog" class="blog-detail">
        <header class="blog-detail__hero">
          <div class="container">
            <nav class="blog-detail__crumb">
              <RouterLink to="/">الرئيسية</RouterLink>
              <span>/</span>
              <RouterLink to="/blog">المدونة</RouterLink>
            </nav>
            <time v-if="blog.published_at" :datetime="blog.published_at">{{ formattedDate }}</time>
            <h1>{{ field(blog, 'title') }}</h1>
            <p v-if="blog.excerpt">{{ field(blog, 'excerpt') }}</p>
          </div>
        </header>

        <div class="container blog-detail__layout">
          <figure class="blog-detail__image">
            <img :src="blog.image_url || fallbackImage" :alt="field(blog, 'title')" />
          </figure>

          <div class="blog-detail__content" v-html="field(blog, 'content')"></div>

          <button class="blog-detail__back" type="button" @click="goBack">العودة</button>
        </div>
      </article>
    </main>

    
  </div>
</template>

<style scoped>
.blog-detail-page{background:#f8f4ee}
.blog-detail-state{min-height:420px;display:grid;place-items:center;text-align:center;color:var(--muted)}
.blog-detail-state--error{color:#9b2d20}
.blog-detail__hero{background:linear-gradient(110deg,#070808 0%,#16100b 58%,#2f210f 100%);color:#fff;padding:44px 0 50px;text-align:center}
.blog-detail__crumb{display:flex;justify-content:center;align-items:center;gap:8px;margin-bottom:16px;color:#d6c7b4;font-size:12px}
.blog-detail__crumb a:hover{color:var(--gold)}
.blog-detail__hero time{display:block;color:var(--gold);font-size:12px;font-weight:700;margin-bottom:8px}
.blog-detail__hero h1{max-width:820px;margin:0 auto 12px;font-size:40px;line-height:1.45}
.blog-detail__hero p{max-width:720px;margin:0 auto;color:#d7cabc;font-size:13px;line-height:2}
.blog-detail__layout{padding-block:34px 52px}
.blog-detail__image{margin:0 auto 28px;max-width:920px;aspect-ratio:16/8;border-radius:8px;overflow:hidden;background:#eee4d8;border:1px solid var(--border)}
.blog-detail__image img{display:block;width:100%;height:100%;object-fit:cover}
.blog-detail__content{max-width:820px;margin:0 auto;color:#2b2520;font-size:15px;line-height:2.15;background:#fff;border:1px solid var(--border);border-radius:8px;padding:28px}
.blog-detail__content :deep(p){margin:0 0 16px}
.blog-detail__content :deep(img){max-width:100%;height:auto;border-radius:8px}
.blog-detail__content :deep(h2),.blog-detail__content :deep(h3){line-height:1.6;margin:24px 0 10px;color:#1e1914}
.blog-detail__back{display:table;margin:24px auto 0;border:1px solid var(--gold);background:#fff;color:var(--gold);border-radius:22px;padding:9px 22px;font-size:12px;font-weight:700;cursor:pointer}
@media(max-width:700px){.blog-detail__hero h1{font-size:28px}.blog-detail__layout{padding-block:24px 38px}.blog-detail__content{padding:20px;font-size:14px}.blog-detail__image{aspect-ratio:16/10}}
</style>
