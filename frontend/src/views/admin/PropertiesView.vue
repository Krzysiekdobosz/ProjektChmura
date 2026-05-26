<script setup>
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { adminService } from '@/services/adminService'
import { useToast } from 'vue-toastification'
import BaseSpinner from '@/components/base/BaseSpinner.vue'
import BaseBadge from '@/components/base/BaseBadge.vue'
import BasePagination from '@/components/base/BasePagination.vue'
import BaseInput from '@/components/base/BaseInput.vue'
import BaseSelect from '@/components/base/BaseSelect.vue'
import BaseModal from '@/components/base/BaseModal.vue'
import BaseTextarea from '@/components/base/BaseTextarea.vue'
import BaseButton from '@/components/base/BaseButton.vue'

const toast = useToast()
const properties = ref([])
const meta       = ref({})
const loading    = ref(false)
const search     = ref('')
const statusFilter = ref('pending')
const page       = ref(1)
const selected   = ref(null)
const rejectReason = ref('')
const submitting = ref(false)

const statusOptions = [
  { value: '', label: 'Wszystkie' },
  { value: 'draft',     label: 'Szkic' },
  { value: 'pending',   label: 'Do weryfikacji' },
  { value: 'published', label: 'Opublikowane' },
  { value: 'rejected',  label: 'Odrzucone' },
  { value: 'archived',  label: 'Zarchiwizowane' },
]

const statusBadge = {
  draft:     'gray',
  pending:   'yellow',
  published: 'green',
  rejected:  'red',
  archived:  'gray',
}

async function fetch() {
  loading.value = true
  try {
    const params = { page: page.value }
    if (search.value)       params.search = search.value
    if (statusFilter.value) params.status = statusFilter.value
    const { data } = await adminService.properties(params)
    properties.value = data.data
    meta.value       = data.meta
  } finally {
    loading.value = false
  }
}

async function updateStatus(property, status, reason = '') {
  submitting.value = true
  try {
    await adminService.propertyStatus(property.id, status, reason)
    property.status       = status
    property.status_label = statusOptions.find(o => o.value === status)?.label || status
    selected.value = null
    toast.success('Status zaktualizowany.')
  } catch {
    toast.error('Błąd operacji.')
  } finally {
    submitting.value = false
  }
}

onMounted(fetch)
</script>

<template>
  <div>
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Moderacja ofert</h1>

    <div class="flex gap-3 mb-4">
      <BaseInput v-model="search" placeholder="Szukaj po tytule lub mieście..." class="flex-1 max-w-xs" @keyup.enter="fetch" />
      <BaseSelect v-model="statusFilter" :options="statusOptions" class="w-48" @update:modelValue="fetch" />
    </div>

    <div v-if="loading" class="flex justify-center py-12"><BaseSpinner /></div>

    <div v-else class="space-y-3">
      <div v-for="p in properties" :key="p.id" class="card p-4 flex items-center gap-4">
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2 mb-1">
            <BaseBadge :variant="statusBadge[p.status]">{{ p.status_label }}</BaseBadge>
            <span class="text-xs text-gray-400">{{ p.property_type_label }}</span>
          </div>
          <p class="font-medium text-gray-900 truncate">{{ p.title }}</p>
          <p class="text-sm text-gray-500">{{ p.city }} · {{ p.price }} zł/mies.</p>
          <p class="text-xs text-gray-400">Właściciel: {{ p.owner?.name }}</p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
          <RouterLink :to="`/oferty/${p.slug}`" target="_blank" class="btn btn-secondary btn-sm">Podgląd</RouterLink>
          <button v-if="p.status === 'pending'" @click="updateStatus(p, 'published')" class="btn btn-sm bg-green-600 text-white hover:bg-green-700">Akceptuj</button>
          <button v-if="p.status === 'pending'" @click="selected = p; rejectReason = ''" class="btn btn-danger btn-sm">Odrzuć</button>
          <button v-if="p.status === 'published'" @click="updateStatus(p, 'archived')" class="btn btn-secondary btn-sm">Archiwizuj</button>
        </div>
      </div>

      <div v-if="!loading && !properties.length" class="text-center py-12 text-gray-400">
        Brak ofert w tej kategorii.
      </div>
    </div>

    <BasePagination
      v-if="meta.last_page > 1"
      class="mt-4"
      :currentPage="meta.current_page"
      :lastPage="meta.last_page"
      :total="meta.total"
      @change="p => { page = p; fetch() }"
    />

    <BaseModal :show="!!selected" title="Odrzuć ofertę" @close="selected = null">
      <div class="space-y-3">
        <p class="text-sm text-gray-600">Podaj powód odrzucenia (opcjonalnie):</p>
        <BaseTextarea v-model="rejectReason" :rows="3" placeholder="Powód odrzucenia..." />
      </div>
      <template #footer>
        <BaseButton variant="secondary" @click="selected = null">Anuluj</BaseButton>
        <BaseButton variant="danger" :loading="submitting" @click="updateStatus(selected, 'rejected', rejectReason)">Odrzuć</BaseButton>
      </template>
    </BaseModal>
  </div>
</template>
