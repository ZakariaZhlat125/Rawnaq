<script setup>
import { onMounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import PageSkeleton from '@/components/common/PageSkeleton.vue'
import { fetchBlogs } from '@/services/blogApi'
import { assetPath } from '@/utils/assetPath'
import { useLanguage } from '@/composables/useLanguage'
const { state: language } = useLanguage()
import { localizeRecord } from '@/utils/i18nField'
const field = (item, key) => localizeRecord(item, key, language.lang)
let blogRequest = 0
import '@/assets/styles/home.css'

const blogs = ref([])
const pagination = ref(null)
const page = ref(1)
const loading = ref(true)
const error = ref('')

const fallbackImage = assetPath('/logo.png')

function formatDate(value) {
  if (!value) return ''

  return new Intl.DateTimeFormat(language.lang === 'en' ? 'en-GB' : 'ar-SA', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  }).format(new Date(value))
}

async function loadBlogs(nextPage = 1) {
  const request = ++blogRequest
  loading.value = true
  error.value = ''

  try {
    const result = await fetchBlogs(nextPage)
    if (request !== blogRequest) return
    blogs.value = result.blogs
    pagination.value = result.pagination
    page.value = result.pagination?.current_page || nextPage
  } catch (err) {
    if (request !== blogRequest) return
    error.value = 'تعذر تحميل المدونات الآن. حاول مرة أخرى لاحقا.'
  } finally {
    if (request === blogRequest) loading.value = false
  }
}

onMounted(() => loadBlogs())
watch(() => language.lang, () => loadBlogs(page.value))
</script>

<template>
  <div class="home-page blog-page" dir="rtl">
    <main>
      <section class="blog-hero">
        <div class="blog-hero__bg"><img src="/images/generated/packages/complete-care-hq.png" alt="" /></div>
        <svg class="blog-hero__ghost" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width=".8" aria-hidden="true"><path d="M12 2l9 5v10l-9 5-9-5V7z"/><path d="M12 2v20M3 7l9 5 9-5M3 17l9-5 9 5"/></svg>
        <div class="container blog-hero__in">
          <p class="blog-eyebrow">نصائح وأخبار رونق</p>
          <h1>المدونة</h1>
          <p>
            مقالات قصيرة تساعدك تختار خدمة العناية المناسبة وتحافظ على مظهرك
            اليومي بأفضل طريقة.
          </p>
        </div>
      </section>

      <section class="container blog-list" aria-live="polite">
        <PageSkeleton v-if="loading" variant="list" />
        <div v-else-if="error" class="blog-state blog-state--error">{{ error }}</div>
        <div v-else-if="!blogs.length" class="blog-state">لا توجد مدونات منشورة حاليا.</div>

        <template v-else>
          <div class="blog-grid">
            <article v-for="blog in blogs" :key="blog.id" class="blog-card">
              <RouterLink class="blog-card__image" :to="`/blog/${blog.slug}`">
                <img :src="blog.image_url || fallbackImage" :alt="field(blog, 'title')" loading="lazy" />
              </RouterLink>

              <div class="blog-card__body">
                <time v-if="blog.published_at" :datetime="blog.published_at">
                  {{ formatDate(blog.published_at) }}
                </time>
                <h2>
                  <RouterLink :to="`/blog/${blog.slug}`">{{ field(blog, 'title') }}</RouterLink>
                </h2>
                <p>{{ field(blog, 'excerpt') }}</p>
                <RouterLink class="blog-read" :to="`/blog/${blog.slug}`">اقرأ المزيد</RouterLink>
              </div>
            </article>
          </div>

          <div v-if="pagination && pagination.last_page > 1" class="blog-pager">
            <button :disabled="page <= 1 || loading" @click="loadBlogs(page - 1)">السابق</button>
            <span>{{ page }} / {{ pagination.last_page }}</span>
            <button :disabled="page >= pagination.last_page || loading" @click="loadBlogs(page + 1)">التالي</button>
          </div>
        </template>
      </section>
    </main>

    
  </div>
</template>

<style scoped>
.blog-page{background:#f8f4ee}
.blog-hero{position:relative;width:min(1280px,94%);min-height:300px;margin:24px auto 0;border-radius:22px;overflow:hidden;display:flex;align-items:center;background:#0d0b07;color:#fff}
.blog-hero__bg{position:absolute;inset:0}.blog-hero__bg img{width:100%;height:100%;object-fit:cover;object-position:75% center;filter:brightness(.85)}.blog-hero__bg::after{content:"";position:absolute;inset:0;background:linear-gradient(90deg,rgba(7,18,20,.92) 0%,rgba(7,18,20,.72) 42%,rgba(7,18,20,.1) 100%)}
.blog-hero__ghost{position:absolute;left:5%;top:50%;transform:translateY(-50%);width:220px;opacity:.14;z-index:1;color:var(--gold)}
.blog-hero__in{position:relative;z-index:2;width:100%;max-width:none;padding:52px;text-align:right}
.blog-eyebrow{margin:0 0 8px;color:var(--gold);font-size:12px;font-weight:700}
.blog-hero h1{margin:0 0 12px;font-size:42px;line-height:1.35}
.blog-hero p:last-child{max-width:44ch;margin:0;color:#d7cabc;font-size:13px;line-height:2}
.blog-list{padding-block:38px 52px}
.blog-state{min-height:220px;display:grid;place-items:center;text-align:center;color:var(--muted);background:#fff;border:1px solid var(--border);border-radius:8px}
.blog-state--error{color:#9b2d20}
.blog-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}
.blog-card{display:flex;flex-direction:column;overflow:hidden;background:#fff;border:1px solid var(--border);border-radius:8px;box-shadow:0 14px 30px #22180d10}
.blog-card__image{display:block;aspect-ratio:16/10;background:#eee4d8;overflow:hidden}
.blog-card__image img{display:block;width:100%;height:100%;object-fit:cover;transition:transform .35s ease}
.blog-card:hover .blog-card__image img{transform:scale(1.04)}
.blog-card__body{display:flex;flex-direction:column;flex:1;padding:18px}
.blog-card time{color:var(--gold);font-size:11px;font-weight:700;margin-bottom:7px}
.blog-card h2{margin:0 0 9px;font-size:18px;line-height:1.55;color:#1e1914}
.blog-card h2 a:hover{color:var(--gold)}
.blog-card p{margin:0 0 16px;color:var(--muted);font-size:12px;line-height:1.9;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden}
.blog-read{margin-top:auto;align-self:flex-start;color:#20160c;background:linear-gradient(90deg,#b77420,#f0cf89,#be7920);border-radius:22px;padding:8px 18px;font-size:12px;font-weight:700}
.blog-pager{display:flex;align-items:center;justify-content:center;gap:14px;margin-top:28px}
.blog-pager button{border:1px solid var(--gold);background:#fff;color:var(--gold);border-radius:22px;padding:8px 18px;cursor:pointer}
.blog-pager button:disabled{opacity:.45;cursor:not-allowed}
.blog-pager span{color:var(--muted);font-size:12px}
@media(max-width:900px){.blog-grid{grid-template-columns:repeat(2,1fr)}.blog-hero h1{font-size:34px}.blog-hero__in{padding:32px 24px}}
@media(max-width:560px){.blog-grid{grid-template-columns:1fr}.blog-hero{padding:42px 0 36px}.blog-list{padding-block:26px 38px}}
</style>
