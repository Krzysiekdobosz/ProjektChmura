<script setup>
import { ref, onMounted } from 'vue'
import { adminService } from '@/services/adminService'
import BaseSpinner from '@/components/base/BaseSpinner.vue'
import BasePagination from '@/components/base/BasePagination.vue'
import BaseInput from '@/components/base/BaseInput.vue'

const logs    = ref([])
const meta    = ref({})
const loading = ref(false)
const page    = ref(1)
const search  = ref('')

async function fetch() {
  loading.value = true
  try {
    const params = { page: page.value }
    if (search.value) params.action = search.value
    const { data } = await adminService.activityLogs(params)
    logs.value = data.data
    meta.value = data.meta
  } finally {
    loading.value = false
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
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Logi aktywności</h1>

    <div class="flex gap-3 mb-4">
      <BaseInput v-model="search" placeholder="Szukaj akcji..." class="max-w-xs" @keyup.enter="() => { page = 1; fetch() }" />
    </div>

    <div v-if="loading" class="flex justify-center py-12"><BaseSpinner /></div>

    <div v-else class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
          <tr>
            <th class="px-4 py-3 text-left">Akcja</th>
            <th class="px-4 py-3 text-left">Opis</th>
            <th class="px-4 py-3 text-left">Użytkownik</th>
            <th class="px-4 py-3 text-left">IP</th>
            <th class="px-4 py-3 text-left">Data</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="l in logs" :key="l.id" class="hover:bg-gray-50">
            <td class="px-4 py-3">
              <code class="text-xs bg-gray-100 px-1.5 py-0.5 rounded text-gray-700">{{ l.action }}</code>
            </td>
            <td class="px-4 py-3 text-gray-700 max-w-xs truncate">{{ l.description }}</td>
            <td class="px-4 py-3 text-gray-600">{{ l.user?.name || '—' }}</td>
            <td class="px-4 py-3 text-gray-500 font-mono text-xs">{{ l.ip_address || '—' }}</td>
            <td class="px-4 py-3 text-gray-500">{{ formatDate(l.created_at) }}</td>
          </tr>
          <tr v-if="!logs.length">
            <td colspan="5" class="px-4 py-12 text-center text-gray-400">Brak logów.</td>
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
