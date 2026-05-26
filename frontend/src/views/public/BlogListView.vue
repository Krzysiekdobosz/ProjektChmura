<script setup>
import { ref, reactive, watch, onMounted, computed } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { blogService } from '@/services/blogService'
import PublicLayout from '@/layouts/PublicLayout.vue'
import BasePagination from '@/components/base/BasePagination.vue'
import BaseSpinner from '@/components/base/BaseSpinner.vue'
import BaseInput from '@/components/base/BaseInput.vue'

const route  = useRoute()
const router = useRouter()

const posts      = ref([])
const meta       = ref({})
const categories = ref([])
const loading    = ref(false)

const filters = reactive({
  search:   route.query.search   || '',
  category: route.query.category || '',
  page:     parseInt(route.query.page) || 1,
})

async function fetchPosts() {
  loading.value = true
  try {
    const params = {}
    if (filters.search)   params.search   = filters.search
    if (filters.category) params.category = filters.category
    params.page = filters.page
    const { data } = await blogService.list(params)
    posts.value = data.data
    meta.value  = data.meta
    router.replace({ query: Object.fromEntries(Object.entries(filters).filter(([, v]) => v)) })
  } finally {
    loading.value = false
  }
}

async function fetchCategories() {
  try {
    const { data } = await blogService.categories()
    categories.value = data.data || []
  } catch {}
}

watch([() => filters.search, () => filters.category], () => { filters.page = 1; fetchPosts() })

function formatDate(d) {
  if (!d) return ''
  return new Date(d).toLocaleDateString('pl-PL', { day: 'numeric', month: 'long', year: 'numeric' })
}

onMounted(() => { fetchPosts(); fetchCategories() })
</script>

<template>
  <PublicLayout>
  <div class="max-w-5xl mx-auto px-4 py-10 sm:px-6">
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900">Blog</h1>
      <p class="text-gray-500 mt-1">Porady, aktualności i informacje o rynku wynajmu</p>
    </div>

    <div class="flex flex-col md:flex-row gap-8">
      <!-- Sidebar filtry -->
      <aside class="w-full md:w-56 shrink-0">
        <div class="card p-4 space-y-4 sticky top-20">
          <BaseInput v-model="filters.search" placeholder="Szukaj..." />

          <div>
            <p class="text-xs font-semibold text-gray-500 uppercase mb-2">Kategorie</p>
            <nav class="space-y-1">
              <button
                @click="filters.category = ''"
                :class="['w-full text-left text-sm px-2 py-1.5 rounded transition-colors',
                  !filters.category ? 'bg-primary-50 text-primary-700 font-medium' : 'text-gray-600 hover:bg-gray-50']"
              >Wszystkie</button>
              <button
                v-for="c in categories" :key="c.id"
                @click="filters.category = c.slug"
                :class="['w-full text-left text-sm px-2 py-1.5 rounded transition-colors',
                  filters.category === c.slug ? 'bg-primary-50 text-primary-700 font-medium' : 'text-gray-600 hover:bg-gray-50']"
              >{{ c.name }}</button>
            </nav>
          </div>
        </div>
      </aside>

      <!-- Lista wpisów -->
      <div class="flex-1 min-w-0">
        <div v-if="loading" class="flex justify-center py-16"><BaseSpinner /></div>

        <div v-else-if="!posts.length" class="text-center py-20 text-gray-400">
          <svg class="w-12 h-12 mx-auto mb-3 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
          </svg>
          <p>Brak wpisów spełniających kryteria.</p>
        </div>

        <div v-else class="space-y-6">
          <article v-for="p in posts" :key="p.id" class="card p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center gap-2 text-xs text-gray-400 mb-3">
              <span v-if="p.category" class="bg-primary-50 text-primary-700 px-2 py-0.5 rounded font-medium">
                {{ p.category.name }}
              </span>
              <span>{{ formatDate(p.published_at) }}</span>
              <span v-if="p.author">· {{ p.author.name }}</span>
            </div>

            <RouterLink :to="`/blog/${p.slug}`" class="group">
              <h2 class="text-xl font-bold text-gray-900 group-hover:text-primary-600 transition-colors mb-2">
                {{ p.title }}
              </h2>
            </RouterLink>

            <p v-if="p.excerpt" class="text-gray-600 text-sm line-clamp-3 mb-4">{{ p.excerpt }}</p>

            <div class="flex items-center justify-between">
              <div class="flex gap-1 flex-wrap">
                <span v-for="tag in (p.tags || []).slice(0, 3)" :key="tag.slug"
                  class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded">#{{ tag.name }}</span>
              </div>
              <RouterLink :to="`/blog/${p.slug}`" class="text-sm text-primary-600 hover:underline font-medium">
                Czytaj dalej →
              </RouterLink>
            </div>
          </article>
        </div>

        <BasePagination
          v-if="meta.last_page > 1"
          class="mt-8"
          :currentPage="meta.current_page"
          :lastPage="meta.last_page"
          :total="meta.total"
          @change="p => { filters.page = p; fetchPosts() }"
        />
      </div>
    </div>
  </div>
  </PublicLayout>
</template>
