<script setup>
import { ref, onMounted } from 'vue'
import { adminService } from '@/services/adminService'
import { useToast } from 'vue-toastification'
import BaseSpinner from '@/components/base/BaseSpinner.vue'
import BaseBadge from '@/components/base/BaseBadge.vue'
import BasePagination from '@/components/base/BasePagination.vue'
import BaseSelect from '@/components/base/BaseSelect.vue'
import BaseModal from '@/components/base/BaseModal.vue'
import BaseTextarea from '@/components/base/BaseTextarea.vue'
import BaseButton from '@/components/base/BaseButton.vue'

const toast = useToast()
const messages  = ref([])
const meta      = ref({})
const loading   = ref(false)
const page      = ref(1)
const statusFilter = ref('')
const selected  = ref(null)
const adminNote = ref('')
const newStatus = ref('')
const submitting = ref(false)

const statusOptions = [
  { value: '',        label: 'Wszystkie' },
  { value: 'new',     label: 'Nowe' },
  { value: 'in_progress', label: 'W trakcie' },
  { value: 'closed',  label: 'Zamknięte' },
]

const statusBadge = { new: 'yellow', in_progress: 'blue', closed: 'gray' }
const statusLabel = { new: 'Nowe', in_progress: 'W trakcie', closed: 'Zamknięte' }

async function fetch() {
  loading.value = true
  try {
    const params = { page: page.value }
    if (statusFilter.value) params.status = statusFilter.value
    const { data } = await adminService.contacts(params)
    messages.value = data.data
    meta.value = data.meta
  } finally {
    loading.value = false
  }
}

async function openDetail(msg) {
  const { data } = await adminService.contactShow(msg.id)
  selected.value = data.data
  adminNote.value = data.data.admin_note || ''
  newStatus.value = data.data.status || 'new'
}

async function saveStatus() {
  submitting.value = true
  try {
    await adminService.contactStatus(selected.value.id, newStatus.value, adminNote.value)
    toast.success('Status zaktualizowany.')
    selected.value = null
    fetch()
  } catch {
    toast.error('Błąd operacji.')
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
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Zgłoszenia kontaktowe</h1>

    <div class="flex gap-3 mb-4">
      <BaseSelect v-model="statusFilter" :options="statusOptions" class="w-44" @update:modelValue="() => { page = 1; fetch() }" />
    </div>

    <div v-if="loading" class="flex justify-center py-12"><BaseSpinner /></div>

    <div v-else class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
          <tr>
            <th class="px-4 py-3 text-left">Nadawca</th>
            <th class="px-4 py-3 text-left">Temat</th>
            <th class="px-4 py-3 text-left">Status</th>
            <th class="px-4 py-3 text-left">Data</th>
            <th class="px-4 py-3 text-right">Akcje</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="m in messages" :key="m.id" class="hover:bg-gray-50">
            <td class="px-4 py-3">
              <p class="font-medium text-gray-900">{{ m.name }}</p>
              <p class="text-gray-500">{{ m.email }}</p>
            </td>
            <td class="px-4 py-3 text-gray-700">{{ m.subject }}</td>
            <td class="px-4 py-3">
              <BaseBadge :variant="statusBadge[m.status] || 'gray'">
                {{ statusLabel[m.status] || m.status }}
              </BaseBadge>
            </td>
            <td class="px-4 py-3 text-gray-500">{{ formatDate(m.created_at) }}</td>
            <td class="px-4 py-3 text-right">
              <button @click="openDetail(m)" class="text-xs px-3 py-1.5 rounded-lg border border-primary-200 text-primary-600 hover:bg-primary-50 font-medium transition-colors">
                Szczegóły
              </button>
            </td>
          </tr>
          <tr v-if="!messages.length">
            <td colspan="5" class="px-4 py-12 text-center text-gray-400">Brak zgłoszeń.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <BasePagination
      v-if="meta.last_page > 1"
      class="mt-4"
      :currentPage="meta.current_page"
      :lastPage="meta.last_page"
      :total="meta.total"
      @change="p => { page.value = p; fetch() }"
    />

    <!-- Modal szczegółów -->
    <BaseModal v-if="selected" :show="!!selected" title="Szczegóły zgłoszenia" @close="selected = null">
      <div class="space-y-4">
        <div>
          <p class="text-xs text-gray-500 mb-1">Nadawca</p>
          <p class="font-medium text-gray-900">{{ selected.name }} &lt;{{ selected.email }}&gt;</p>
        </div>
        <div>
          <p class="text-xs text-gray-500 mb-1">Temat</p>
          <p class="font-medium text-gray-900">{{ selected.subject }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-500 mb-1">Treść</p>
          <p class="text-gray-700 text-sm whitespace-pre-wrap">{{ selected.body }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-500 mb-1">Data</p>
          <p class="text-gray-700 text-sm">{{ formatDate(selected.created_at) }}</p>
        </div>
        <div>
          <label class="form-label">Status</label>
          <select v-model="newStatus" class="form-input mt-1">
            <option value="new">Nowe</option>
            <option value="in_progress">W trakcie</option>
            <option value="closed">Zamknięte</option>
          </select>
        </div>
        <BaseTextarea v-model="adminNote" label="Notatka administratora" rows="3" placeholder="Opcjonalna notatka..." />
      </div>
      <template #footer>
        <BaseButton variant="secondary" @click="selected = null">Anuluj</BaseButton>
        <BaseButton :loading="submitting" @click="saveStatus">Zapisz</BaseButton>
      </template>
    </BaseModal>
  </div>
</template>
