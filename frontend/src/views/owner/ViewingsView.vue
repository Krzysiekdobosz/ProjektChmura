<script setup>
import { ref, onMounted } from 'vue'
import { viewingService } from '@/services/viewingService'
import { useToast } from 'vue-toastification'
import BaseSpinner from '@/components/base/BaseSpinner.vue'
import BaseBadge from '@/components/base/BaseBadge.vue'
import BaseModal from '@/components/base/BaseModal.vue'
import BaseTextarea from '@/components/base/BaseTextarea.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseSelect from '@/components/base/BaseSelect.vue'

const toast = useToast()
const viewings = ref([])
const loading  = ref(false)
const statusFilter = ref('')
const selectedViewing = ref(null)
const ownerNote = ref('')
const submitting = ref(false)

const statusOptions = [
  { value: '', label: 'Wszystkie' },
  { value: 'pending',   label: 'Oczekujące' },
  { value: 'accepted',  label: 'Zaakceptowane' },
  { value: 'rejected',  label: 'Odrzucone' },
  { value: 'cancelled', label: 'Anulowane' },
  { value: 'completed', label: 'Zakończone' },
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
    const params = {}
    if (statusFilter.value) params.status = statusFilter.value
    const { data } = await viewingService.ownerList(params)
    viewings.value = data.data
  } finally {
    loading.value = false
  }
}

function openModal(v) {
  selectedViewing.value = v
  ownerNote.value = v.owner_note || ''
}

async function updateStatus(status) {
  submitting.value = true
  try {
    await viewingService.ownerUpdateStatus(selectedViewing.value.id, status, ownerNote.value)
    toast.success('Status zaktualizowany.')
    selectedViewing.value = null
    fetch()
  } catch {
    toast.error('Błąd aktualizacji.')
  } finally {
    submitting.value = false
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
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Rezerwacje oglądania</h1>

    <div class="mb-4">
      <BaseSelect v-model="statusFilter" :options="statusOptions" class="w-48" @update:modelValue="fetch" />
    </div>

    <div v-if="loading" class="flex justify-center py-12"><BaseSpinner /></div>

    <div v-else-if="!viewings.length" class="text-center py-12 text-gray-400">
      Brak rezerwacji w tej kategorii.
    </div>

    <div v-else class="space-y-3">
      <div v-for="v in viewings" :key="v.id" class="card p-4 flex items-center gap-4">
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2 mb-1">
            <BaseBadge :variant="statusBadge[v.status]">{{ v.status_label }}</BaseBadge>
          </div>
          <p class="font-medium text-gray-900 truncate">{{ v.property?.title }}</p>
          <p class="text-sm text-gray-500">
            <strong>{{ v.user?.name }}</strong>
            <span v-if="v.user?.phone"> · {{ v.user.phone }}</span>
            <span v-if="v.user?.email"> · {{ v.user.email }}</span>
          </p>
          <p class="text-sm text-gray-600 mt-1">📅 {{ formatDate(v.proposed_at) }}</p>
          <p v-if="v.note" class="text-sm text-gray-500 mt-1 italic">"{{ v.note }}"</p>
        </div>
        <button
          v-if="['pending','accepted'].includes(v.status)"
          @click="openModal(v)"
          class="btn btn-secondary btn-sm shrink-0"
        >Zarządzaj</button>
      </div>
    </div>

    <BaseModal :show="!!selectedViewing" title="Zarządzaj rezerwacją" @close="selectedViewing = null">
      <div v-if="selectedViewing" class="space-y-4">
        <div class="bg-gray-50 rounded-lg p-3 text-sm">
          <p><strong>{{ selectedViewing.user?.name }}</strong></p>
          <p class="text-gray-500">{{ formatDate(selectedViewing.proposed_at) }}</p>
          <p v-if="selectedViewing.note" class="italic text-gray-500 mt-1">"{{ selectedViewing.note }}"</p>
        </div>
        <BaseTextarea v-model="ownerNote" label="Uwaga dla najemcy (opcjonalna)" :rows="3" />
      </div>
      <template #footer>
        <BaseButton variant="secondary" @click="selectedViewing = null">Anuluj</BaseButton>
        <BaseButton variant="danger" :loading="submitting" @click="updateStatus('rejected')">Odrzuć</BaseButton>
        <BaseButton :loading="submitting" @click="updateStatus('accepted')">Zaakceptuj</BaseButton>
      </template>
    </BaseModal>
  </div>
</template>
