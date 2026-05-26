<script setup>
import { ref, onMounted } from 'vue'
import { adminService } from '@/services/adminService'
import { useToast } from 'vue-toastification'
import BaseSpinner from '@/components/base/BaseSpinner.vue'
import BaseBadge from '@/components/base/BaseBadge.vue'
import BasePagination from '@/components/base/BasePagination.vue'
import BaseInput from '@/components/base/BaseInput.vue'
import BaseSelect from '@/components/base/BaseSelect.vue'

const toast = useToast()
const users  = ref([])
const meta   = ref({})
const loading = ref(false)
const search = ref('')
const roleFilter = ref('')
const page   = ref(1)

const roleOptions = [
  { value: '', label: 'Wszystkie role' },
  { value: 'user',  label: 'Użytkownik' },
  { value: 'owner', label: 'Właściciel' },
  { value: 'admin', label: 'Admin' },
]

async function fetch() {
  loading.value = true
  try {
    const params = { page: page.value }
    if (search.value)     params.search = search.value
    if (roleFilter.value) params.role   = roleFilter.value
    const { data } = await adminService.users(params)
    users.value = data.data
    meta.value  = data.meta
  } finally {
    loading.value = false
  }
}

async function toggleStatus(user) {
  try {
    await adminService.userStatus(user.id, !user.is_active)
    user.is_active = !user.is_active
    toast.success(user.is_active ? 'Konto aktywowane.' : 'Konto zablokowane.')
  } catch (e) {
    toast.error(e.response?.data?.message || 'Błąd operacji.')
  }
}

async function changeRole(user, role) {
  try {
    await adminService.userRole(user.id, role)
    user.role = role
    toast.success('Rola zaktualizowana.')
  } catch (e) {
    toast.error(e.response?.data?.message || 'Błąd operacji.')
  }
}

function formatDate(d) {
  if (!d) return ''
  return new Date(d).toLocaleDateString('pl-PL')
}

onMounted(fetch)
</script>

<template>
  <div>
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Zarządzanie użytkownikami</h1>

    <div class="flex gap-3 mb-4">
      <BaseInput v-model="search" placeholder="Szukaj po nazwie lub e-mailu..." class="flex-1 max-w-xs" @keyup.enter="fetch" />
      <BaseSelect v-model="roleFilter" :options="roleOptions" class="w-44" @update:modelValue="fetch" />
    </div>

    <div v-if="loading" class="flex justify-center py-12"><BaseSpinner /></div>

    <div v-else class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
          <tr>
            <th class="px-4 py-3 text-left">Użytkownik</th>
            <th class="px-4 py-3 text-left">Rola</th>
            <th class="px-4 py-3 text-left">Status</th>
            <th class="px-4 py-3 text-left">Zarejestrowany</th>
            <th class="px-4 py-3 text-right">Akcje</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="u in users" :key="u.id" class="hover:bg-gray-50">
            <td class="px-4 py-3">
              <div>
                <p class="font-medium text-gray-900">{{ u.name }}</p>
                <p class="text-gray-500">{{ u.email }}</p>
              </div>
            </td>
            <td class="px-4 py-3">
              <select
                v-if="u.role !== 'admin'"
                :value="u.role"
                @change="changeRole(u, $event.target.value)"
                class="text-xs border border-gray-200 rounded px-2 py-1 focus:outline-none focus:ring-1 focus:ring-primary-500"
              >
                <option value="user">Użytkownik</option>
                <option value="owner">Właściciel</option>
              </select>
              <BaseBadge v-else variant="blue">Admin</BaseBadge>
            </td>
            <td class="px-4 py-3">
              <BaseBadge :variant="u.is_active ? 'green' : 'red'">
                {{ u.is_active ? 'Aktywny' : 'Zablokowany' }}
              </BaseBadge>
            </td>
            <td class="px-4 py-3 text-gray-500">{{ formatDate(u.created_at) }}</td>
            <td class="px-4 py-3 text-right">
              <button
                v-if="u.role !== 'admin'"
                @click="toggleStatus(u)"
                :class="['text-xs px-3 py-1.5 rounded-lg border font-medium transition-colors',
                  u.is_active ? 'border-red-200 text-red-600 hover:bg-red-50' : 'border-green-200 text-green-600 hover:bg-green-50']"
              >{{ u.is_active ? 'Zablokuj' : 'Aktywuj' }}</button>
            </td>
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
  </div>
</template>
