<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import { blogService } from '@/services/blogService'
import PublicLayout from '@/layouts/PublicLayout.vue'
import BaseSpinner from '@/components/base/BaseSpinner.vue'

const route  = useRoute()
const router = useRouter()

const post    = ref(null)
const loading = ref(true)

onMounted(async () => {
  try {
    const { data } = await blogService.show(route.params.slug)
    post.value = data.data
  } catch {
    router.push('/404')
  } finally {
    loading.value = false
  }
})

function formatDate(d) {
  if (!d) return ''
  return new Date(d).toLocaleDateString('pl-PL', { day: 'numeric', month: 'long', year: 'numeric' })
}
</script>

<template>
  <PublicLayout>
  <div class="max-w-3xl mx-auto px-4 py-10 sm:px-6">
    <div v-if="loading" class="flex justify-center py-24"><BaseSpinner /></div>

    <article v-else-if="post">
      <!-- Breadcrumb -->
      <nav class="flex items-center gap-2 text-sm text-gray-500 mb-6">
        <RouterLink to="/" class="hover:text-primary-600">Strona główna</RouterLink>
        <span>/</span>
        <RouterLink to="/blog" class="hover:text-primary-600">Blog</RouterLink>
        <span>/</span>
        <span class="text-gray-900 truncate max-w-xs">{{ post.title }}</span>
      </nav>

      <!-- Kategoria i meta -->
      <div class="flex items-center gap-3 text-sm text-gray-500 mb-4">
        <RouterLink
          v-if="post.category"
          :to="`/blog?category=${post.category.slug}`"
          class="bg-primary-50 text-primary-700 px-2 py-0.5 rounded font-medium hover:bg-primary-100 transition-colors"
        >{{ post.category.name }}</RouterLink>
        <span>{{ formatDate(post.published_at) }}</span>
        <span v-if="post.author">· {{ post.author.name }}</span>
      </div>

      <!-- Tytuł -->
      <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4 leading-tight">
        {{ post.title }}
      </h1>

      <!-- Zajawka -->
      <p v-if="post.excerpt" class="text-lg text-gray-600 mb-8 border-l-4 border-primary-300 pl-4 italic">
        {{ post.excerpt }}
      </p>

      <!-- Treść -->
      <div class="prose prose-gray max-w-none text-gray-800 leading-relaxed whitespace-pre-wrap mb-8">
        {{ post.content }}
      </div>

      <!-- Tagi -->
      <div v-if="post.tags && post.tags.length" class="flex items-center gap-2 flex-wrap pt-4 border-t border-gray-100">
        <span class="text-sm text-gray-500">Tagi:</span>
        <RouterLink
          v-for="tag in post.tags" :key="tag.slug"
          :to="`/blog?tag=${tag.slug}`"
          class="text-sm bg-gray-100 hover:bg-gray-200 text-gray-600 px-2 py-0.5 rounded transition-colors"
        >#{{ tag.name }}</RouterLink>
      </div>

      <!-- Powrót -->
      <div class="mt-10">
        <RouterLink to="/blog" class="text-primary-600 hover:underline text-sm font-medium">
          ← Wróć do bloga
        </RouterLink>
      </div>
    </article>
  </div>
  </PublicLayout>
</template>
