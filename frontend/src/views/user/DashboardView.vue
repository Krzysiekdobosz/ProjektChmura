<script setup>
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { favoriteService } from '@/services/favoriteService'
import { viewingService } from '@/services/viewingService'
import { conversationService } from '@/services/conversationService'
import { useAuthStore } from '@/stores/auth'
import BaseSpinner from '@/components/base/BaseSpinner.vue'
import BaseBadge from '@/components/base/BaseBadge.vue'

const auth = useAuthStore()
const loading  = ref(true)
const favorites = ref([])
const viewings  = ref([])
const conversations = ref([])

const statusBadge = {
  pending:   'yellow',
  accepted:  'green',
  rejected:  'red',
  cancelled: 'gray',
  completed: 'blue',
}

onMounted(async () => {
  try {
    const [favRes, viewRes, convRes] = await Promise.all([
      favoriteService.list({ per_page: 3 }),
      viewingService.list({ per_page: 5 }),
      conversationService.list({ per_page: 5 }),
    ])
    favorites.value    = favRes.data.data?.slice(0, 3) || []
    viewings.value     = viewRes.data.data || []
    conversations.value = convRes.data.data || []
  } finally {
    loading.value = false
  }
})

function formatDate(d) {
  if (!d) return ''
  return new Date(d).toLocaleString('pl-PL', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}
</script>

<template>
  <div>
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Witaj, {{ auth.user?.name }}!</h1>
      <p class="text-gray-500 text-sm mt-1">Twój panel wynajmu</p>
    </div>

    <div v-if="loading" class="flex justify-center py-12"><BaseSpinner /></div>

    <template v-else>
      <!-- Szybkie linki -->
      <div class="flex flex-wrap gap-3 mb-8">
        <RouterLink to="/oferty" class="btn btn-primary btn-sm">Przeglądaj oferty</RouterLink>
        <RouterLink to="/panel/ulubione" class="btn btn-secondary btn-sm">Ulubione</RouterLink>
        <RouterLink to="/panel/rezerwacje" class="btn btn-secondary btn-sm">Moje rezerwacje</RouterLink>
        <RouterLink to="/panel/wiadomosci" class="btn btn-secondary btn-sm">Wiadomości</RouterLink>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Rezerwacje -->
        <div class="card p-4">
          <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold text-gray-900">Moje rezerwacje</h2>
            <RouterLink to="/panel/rezerwacje" class="text-xs text-primary-600 hover:underline">Wszystkie →</RouterLink>
          </div>
          <div v-if="!viewings.length" class="text-sm text-gray-400 py-4 text-center">
            Nie masz jeszcze rezerwacji.
            <RouterLink to="/oferty" class="block mt-2 text-primary-600 hover:underline">Przeglądaj oferty</RouterLink>
          </div>
          <div v-else class="space-y-3">
            <div v-for="v in viewings" :key="v.id" class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg">
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate">{{ v.property?.title }}</p>
                <p class="text-xs text-gray-500">{{ formatDate(v.proposed_at) }}</p>
              </div>
              <BaseBadge :variant="statusBadge[v.status]" class="shrink-0 text-xs">{{ v.status_label }}</BaseBadge>
            </div>
          </div>
        </div>

        <!-- Ulubione -->
        <div class="card p-4">
          <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold text-gray-900">Ulubione oferty</h2>
            <RouterLink to="/panel/ulubione" class="text-xs text-primary-600 hover:underline">Wszystkie →</RouterLink>
          </div>
          <div v-if="!favorites.length" class="text-sm text-gray-400 py-4 text-center">
            Nie masz ulubionych ofert.
            <RouterLink to="/oferty" class="block mt-2 text-primary-600 hover:underline">Przeglądaj oferty</RouterLink>
          </div>
          <div v-else class="space-y-2">
            <RouterLink
              v-for="p in favorites" :key="p?.id"
              :to="`/oferty/${p?.slug}`"
              class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-lg block"
            >
              <div class="w-12 h-10 rounded bg-gray-100 overflow-hidden shrink-0">
                <img v-if="p?.main_image_url" :src="p.main_image_url" class="w-full h-full object-cover" />
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate">{{ p?.title }}</p>
                <p class="text-xs text-gray-500">{{ p?.city }} · {{ p?.price }} zł/mies.</p>
              </div>
            </RouterLink>
          </div>
        </div>

        <!-- Wiadomości -->
        <div class="card p-4">
          <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold text-gray-900">Ostatnie wiadomości</h2>
            <RouterLink to="/panel/wiadomosci" class="text-xs text-primary-600 hover:underline">Wszystkie →</RouterLink>
          </div>
          <div v-if="!conversations.length" class="text-sm text-gray-400 py-4 text-center">
            Brak wiadomości
          </div>
          <div v-else class="space-y-2">
            <RouterLink
              v-for="c in conversations" :key="c.id"
              to="/panel/wiadomosci"
              class="flex items-start gap-3 p-2 hover:bg-gray-50 rounded-lg block"
            >
              <div class="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center text-primary-700 font-semibold text-sm shrink-0">
                {{ c.other_user?.name?.charAt(0) }}
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between">
                  <p class="text-sm font-medium text-gray-900">{{ c.other_user?.name }}</p>
                  <span v-if="c.unread_count" class="badge badge-blue text-xs">{{ c.unread_count }}</span>
                </div>
                <p class="text-xs text-gray-500 truncate">{{ c.last_message?.body }}</p>
              </div>
            </RouterLink>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
