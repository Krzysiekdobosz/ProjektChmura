<script setup>
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { viewingService } from '@/services/viewingService'
import { useToast } from 'vue-toastification'
import BaseSpinner from '@/components/base/BaseSpinner.vue'
import BaseBadge from '@/components/base/BaseBadge.vue'
import BaseSelect from '@/components/base/BaseSelect.vue'
import BasePagination from '@/components/base/BasePagination.vue'

const toast = useToast()
const viewings = ref([])
const meta     = ref({})
const loading  = ref(false)
const page     = ref(1)
const statusFilter = ref('')

const statusOptions = [
  { value: '', label: 'Wszystkie' },
  { value: 'pending',   label: 'Oczekujące' },
  { value: 'accepted',  label: 'Zaakceptowane' },
  { value: 'rejected',  label: 'Odrzucone' },
  { value: 'cancelled', label: 'Anulowane' },
]

const statusBadge = {
  pending:   'yellow',
  accepted:  'green',
  rejected:  'red',
  cancelled: 'gray',
  completed: 'blue',
}

async function fetch() {
  loading.value = true
  try {
    const params = { page: page.value }
    if (statusFilter.value) params.status = statusFilter.value
    const { data } = await viewingService.list(params)
    viewings.value = data.data
    meta.value = data.meta
  } finally {
    loading.value = false
  }
}

async function cancelViewing(id) {
  if (!confirm('Czy na pewno chcesz anulować rezerwację?')) return
  try {
    await viewingService.cancel(id)
    toast.success('Rezerwacja anulowana.')
    fetch()
  } catch (e) {
    toast.error(e.response?.data?.message || 'Błąd anulowania.')
  }
}

function formatDate(d) {
  if (!d) return ''
  return new Date(d).toLocaleString('pl-PL', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

onMounted(fetch)
</script>

<template>
  <div>
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Moje rezerwacje oglądania</h1>

    <div class="mb-4">
      <BaseSelect v-model="statusFilter" :options="statusOptions" class="w-48" @update:modelValue="() => { page.value = 1; fetch() }" />
    </div>

    <div v-if="loading" class="flex justify-center py-12"><BaseSpinner /></div>

    <div v-else-if="!viewings.length" class="text-center py-12 text-gray-400">
      <p>Brak rezerwacji.</p>
      <RouterLink to="/oferty" class="mt-3 btn btn-primary btn-sm inline-flex">Przeglądaj oferty</RouterLink>
    </div>

    <div v-else class="space-y-3">
      <div v-for="v in viewings" :key="v.id" class="card p-4 flex items-start gap-4">
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2 mb-1">
            <BaseBadge :variant="statusBadge[v.status]">{{ v.status_label }}</BaseBadge>
          </div>
          <RouterLink :to="`/oferty/${v.property?.slug}`" class="font-medium text-gray-900 hover:text-primary-600">
            {{ v.property?.title }}
          </RouterLink>
          <p class="text-sm text-gray-500">{{ v.property?.city }}</p>
          <p class="text-sm text-gray-600 mt-1">📅 {{ formatDate(v.proposed_at) }}</p>
          <p v-if="v.owner" class="text-sm text-gray-500 mt-1">Właściciel: {{ v.owner?.name }}
            <span v-if="v.owner?.phone"> · {{ v.owner.phone }}</span>
          </p>
          <p v-if="v.owner_note" class="text-sm text-gray-500 mt-1 italic">"{{ v.owner_note }}"</p>
        </div>
        <button
          v-if="['pending','accepted'].includes(v.status)"
          @click="cancelViewing(v.id)"
          class="btn btn-secondary btn-sm shrink-0 text-red-600 border-red-200 hover:bg-red-50"
        >Anuluj</button>
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
