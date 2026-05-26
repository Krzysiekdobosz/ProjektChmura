<script setup>
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { propertyService } from '@/services/propertyService'
import { useToast } from 'vue-toastification'
import BaseSpinner from '@/components/base/BaseSpinner.vue'
import BaseBadge from '@/components/base/BaseBadge.vue'
import BasePagination from '@/components/base/BasePagination.vue'
import BaseSelect from '@/components/base/BaseSelect.vue'

const toast = useToast()
const properties = ref([])
const meta   = ref({})
const loading = ref(false)
const page   = ref(1)
const statusFilter = ref('')

const statusOptions = [
  { value: '', label: 'Wszystkie statusy' },
  { value: 'draft', label: 'Szkic' },
  { value: 'pending', label: 'Oczekuje' },
  { value: 'published', label: 'Opublikowane' },
  { value: 'rejected', label: 'Odrzucone' },
  { value: 'archived', label: 'Zarchiwizowane' },
]

async function fetch() {
  loading.value = true
  try {
    const params = { page: page.value }
    if (statusFilter.value) params.status = statusFilter.value
    const { data } = await propertyService.ownerList(params)
    properties.value = data.data
    meta.value = data.meta
  } finally {
    loading.value = false
  }
}

async function deleteProperty(id) {
  if (!confirm('Czy na pewno chcesz usunąć tę ofertę?')) return
  await propertyService.ownerDelete(id)
  toast.success('Oferta usunięta.')
  fetch()
}

const statusBadge = {
  draft:     'gray',
  pending:   'yellow',
  published: 'green',
  rejected:  'red',
  archived:  'gray',
}

onMounted(fetch)
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Moje oferty</h1>
      <RouterLink to="/wlasciciel/oferty/nowa" class="btn btn-primary btn-sm">+ Nowa oferta</RouterLink>
    </div>

    <div class="flex items-center gap-3 mb-4">
      <BaseSelect v-model="statusFilter" :options="statusOptions" class="w-48" @update:modelValue="fetch" />
    </div>

    <div v-if="loading" class="flex justify-center py-16"><BaseSpinner /></div>

    <div v-else-if="!properties.length" class="text-center py-16 text-gray-400">
      <p class="text-lg">Nie masz jeszcze żadnych ofert.</p>
      <RouterLink to="/wlasciciel/oferty/nowa" class="mt-3 btn btn-primary btn-sm inline-flex">Dodaj pierwszą ofertę</RouterLink>
    </div>

    <div v-else class="space-y-3">
      <div v-for="p in properties" :key="p.id"
        class="card p-4 flex items-center gap-4 hover:shadow-sm transition-shadow">
        <div class="w-20 h-16 rounded-lg bg-gray-100 overflow-hidden shrink-0">
          <img v-if="p.main_image_url" :src="p.main_image_url" class="w-full h-full object-cover" />
          <div v-else class="w-full h-full flex items-center justify-center text-gray-300">
            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
          </div>
        </div>

        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2 mb-1">
            <BaseBadge :variant="statusBadge[p.status]">{{ p.status_label }}</BaseBadge>
            <span class="text-xs text-gray-400">{{ p.property_type_label }}</span>
          </div>
          <p class="font-medium text-gray-900 truncate">{{ p.title }}</p>
          <p class="text-sm text-gray-500">{{ p.city }}<span v-if="p.district">, {{ p.district }}</span> · {{ p.price }} zł/mies.</p>
        </div>

        <div class="flex items-center gap-2 shrink-0">
          <RouterLink :to="`/wlasciciel/oferty/${p.id}/edytuj`" class="btn btn-secondary btn-sm">Edytuj</RouterLink>
          <button @click="deleteProperty(p.id)" class="btn btn-danger btn-sm">Usuń</button>
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
