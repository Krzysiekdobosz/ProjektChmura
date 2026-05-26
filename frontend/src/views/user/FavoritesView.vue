<script setup>
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { favoriteService } from '@/services/favoriteService'
import { useToast } from 'vue-toastification'
import BaseSpinner from '@/components/base/BaseSpinner.vue'
import BasePagination from '@/components/base/BasePagination.vue'

const toast = useToast()
const properties = ref([])
const meta   = ref({})
const loading = ref(false)
const page   = ref(1)

async function fetch() {
  loading.value = true
  try {
    const { data } = await favoriteService.list({ page: page.value })
    properties.value = data.data
    meta.value = data.meta
  } finally {
    loading.value = false
  }
}

async function remove(propertyId) {
  await favoriteService.remove(propertyId)
  toast.success('Usunięto z ulubionych.')
  properties.value = properties.value.filter(p => p.id !== propertyId)
  meta.value.total = (meta.value.total || 1) - 1
}

function fmt(n) {
  return new Intl.NumberFormat('pl-PL').format(n) + ' zł/mies.'
}

onMounted(fetch)
</script>

<template>
  <div>
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Ulubione oferty</h1>

    <div v-if="loading" class="flex justify-center py-12"><BaseSpinner /></div>

    <div v-else-if="!properties.length" class="text-center py-12 text-gray-400">
      <p>Nie masz jeszcze żadnych ulubionych ofert.</p>
      <RouterLink to="/oferty" class="mt-3 btn btn-primary btn-sm inline-flex">Przeglądaj oferty</RouterLink>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-for="p in properties" :key="p?.id" class="card group hover:shadow-md transition-shadow">
        <RouterLink :to="`/oferty/${p?.slug}`">
          <div class="aspect-video bg-gray-100 overflow-hidden rounded-t-xl">
            <img v-if="p?.main_image_url" :src="p.main_image_url" :alt="p.title"
              class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
          </div>
        </RouterLink>
        <div class="p-4">
          <RouterLink :to="`/oferty/${p?.slug}`">
            <h3 class="font-semibold text-gray-900 hover:text-primary-600 transition-colors line-clamp-1">{{ p?.title }}</h3>
          </RouterLink>
          <p class="text-sm text-gray-500 mt-1">{{ p?.city }}</p>
          <div class="flex items-center justify-between mt-3">
            <p class="font-bold text-primary-600">{{ p?.price ? fmt(p.price) : '—' }}</p>
            <button @click="remove(p.id)" class="text-red-400 hover:text-red-600 text-sm transition-colors">
              ♥ Usuń
            </button>
          </div>
        </div>
      </div>
    </div>

    <BasePagination
      v-if="meta.last_page > 1"
      class="mt-6"
      :currentPage="meta.current_page"
      :lastPage="meta.last_page"
      :total="meta.total"
      @change="p => { page.value = p; fetch() }"
    />
  </div>
</template>
