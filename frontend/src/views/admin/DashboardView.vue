<script setup>
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { adminService } from '@/services/adminService'
import BaseSpinner from '@/components/base/BaseSpinner.vue'

const loading = ref(true)
const stats = ref({ users: 0, properties: 0, pending: 0, contacts: 0 })

onMounted(async () => {
  try {
    const [usersRes, propsRes, contactsRes] = await Promise.all([
      adminService.users({ per_page: 1 }),
      adminService.properties({ per_page: 1 }),
      adminService.contacts({ status: 'new', per_page: 1 }),
    ])
    stats.value.users      = usersRes.data.meta?.total || 0
    stats.value.properties = propsRes.data.meta?.total || 0
    const pendingRes = await adminService.properties({ status: 'pending', per_page: 1 })
    stats.value.pending  = pendingRes.data.meta?.total || 0
    stats.value.contacts = contactsRes.data.meta?.total || 0
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div>
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Panel administratora</h1>

    <div v-if="loading" class="flex justify-center py-12"><BaseSpinner /></div>

    <template v-else>
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <RouterLink to="/admin/uzytkownicy" class="card p-4 text-center hover:shadow-md transition-shadow">
          <p class="text-3xl font-bold text-primary-600">{{ stats.users }}</p>
          <p class="text-sm text-gray-500 mt-1">Użytkowników</p>
        </RouterLink>
        <RouterLink to="/admin/oferty" class="card p-4 text-center hover:shadow-md transition-shadow">
          <p class="text-3xl font-bold text-blue-600">{{ stats.properties }}</p>
          <p class="text-sm text-gray-500 mt-1">Ofert</p>
        </RouterLink>
        <RouterLink to="/admin/oferty" class="card p-4 text-center hover:shadow-md transition-shadow">
          <p class="text-3xl font-bold text-yellow-600">{{ stats.pending }}</p>
          <p class="text-sm text-gray-500 mt-1">Do weryfikacji</p>
        </RouterLink>
        <RouterLink to="/admin/zgloszenia" class="card p-4 text-center hover:shadow-md transition-shadow">
          <p class="text-3xl font-bold text-red-600">{{ stats.contacts }}</p>
          <p class="text-sm text-gray-500 mt-1">Nowych zgłoszeń</p>
        </RouterLink>
      </div>

      <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <RouterLink to="/admin/uzytkownicy" class="card p-4 hover:shadow-md transition-shadow text-center">
          <svg class="w-8 h-8 mx-auto text-primary-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
          </svg>
          <p class="text-sm font-medium text-gray-700">Użytkownicy</p>
        </RouterLink>
        <RouterLink to="/admin/oferty" class="card p-4 hover:shadow-md transition-shadow text-center">
          <svg class="w-8 h-8 mx-auto text-blue-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
          </svg>
          <p class="text-sm font-medium text-gray-700">Oferty</p>
        </RouterLink>
        <RouterLink to="/admin/blog" class="card p-4 hover:shadow-md transition-shadow text-center">
          <svg class="w-8 h-8 mx-auto text-green-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
          </svg>
          <p class="text-sm font-medium text-gray-700">Blog</p>
        </RouterLink>
        <RouterLink to="/admin/logi" class="card p-4 hover:shadow-md transition-shadow text-center">
          <svg class="w-8 h-8 mx-auto text-gray-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
          </svg>
          <p class="text-sm font-medium text-gray-700">Logi aktywności</p>
        </RouterLink>
      </div>
    </template>
  </div>
</template>
