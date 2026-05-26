<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, RouterLink, useRouter } from 'vue-router'
import { propertyService } from '@/services/propertyService'
import { favoriteService } from '@/services/favoriteService'
import { conversationService } from '@/services/conversationService'
import { viewingService } from '@/services/viewingService'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'vue-toastification'
import BaseSpinner from '@/components/base/BaseSpinner.vue'
import BaseModal from '@/components/base/BaseModal.vue'
import BaseInput from '@/components/base/BaseInput.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseTextarea from '@/components/base/BaseTextarea.vue'
import PublicLayout from '@/layouts/PublicLayout.vue'

const route  = useRoute()
const router = useRouter()
const auth   = useAuthStore()
const toast  = useToast()

const property   = ref(null)
const loading    = ref(true)
const activeImg  = ref(0)

const showContactModal  = ref(false)
const showViewingModal  = ref(false)
const contactMsg  = ref('')
const viewingDate = ref('')
const viewingTime = ref('10:00')
const viewingNote = ref('')
const submitting  = ref(false)

onMounted(async () => {
  try {
    const { data } = await propertyService.get(route.params.slug)
    property.value = data.data
  } catch {
    router.push('/404')
  } finally {
    loading.value = false
  }
})

async function toggleFavorite() {
  if (!auth.isLoggedIn) { toast.warning('Zaloguj się, aby dodać do ulubionych.'); return }
  const p = property.value
  try {
    if (p.is_favorited) {
      await favoriteService.remove(p.id)
      p.is_favorited = false
      toast.success('Usunięto z ulubionych.')
    } else {
      await favoriteService.add(p.id)
      p.is_favorited = true
      toast.success('Dodano do ulubionych.')
    }
  } catch { toast.error('Błąd operacji.') }
}

async function sendMessage() {
  if (!auth.isLoggedIn) { toast.warning('Zaloguj się, aby napisać wiadomość.'); router.push('/auth/logowanie'); return }
  if (!contactMsg.value.trim()) return
  submitting.value = true
  try {
    await conversationService.start({ property_id: property.value.id, message: contactMsg.value })
    toast.success('Wiadomość wysłana!')
    showContactModal.value = false
    contactMsg.value = ''
    router.push('/panel/wiadomosci')
  } catch (e) {
    toast.error(e.response?.data?.message || 'Nie udało się wysłać wiadomości.')
  } finally {
    submitting.value = false
  }
}

async function bookViewing() {
  if (!auth.isLoggedIn) { toast.warning('Zaloguj się, aby umówić oglądanie.'); router.push('/auth/logowanie'); return }
  if (!viewingDate.value) { toast.error('Wybierz datę.'); return }
  submitting.value = true
  try {
    const proposed_at = `${viewingDate.value}T${viewingTime.value}:00`
    await viewingService.book({ property_id: property.value.id, proposed_at, note: viewingNote.value })
    toast.success('Prośba o oglądanie wysłana!')
    showViewingModal.value = false
    viewingDate.value = ''
    viewingNote.value = ''
  } catch (e) {
    toast.error(e.response?.data?.message || 'Nie udało się umówić oglądania.')
  } finally {
    submitting.value = false
  }
}

const minDate = computed(() => new Date().toISOString().split('T')[0])

function fmt(n) {
  if (!n) return '—'
  return new Intl.NumberFormat('pl-PL').format(n) + ' zł'
}
</script>

<template>
  <PublicLayout>
  <div v-if="loading" class="flex justify-center py-32"><BaseSpinner /></div>

  <div v-else-if="property" class="max-w-5xl mx-auto px-4 py-8 sm:px-6">
    <!-- Breadcrumb -->
    <nav class="text-sm text-gray-500 mb-4 flex items-center gap-2">
      <RouterLink to="/" class="hover:text-primary-600">Strona główna</RouterLink>
      <span>›</span>
      <RouterLink to="/oferty" class="hover:text-primary-600">Oferty</RouterLink>
      <span>›</span>
      <span class="text-gray-800 font-medium line-clamp-1">{{ property.title }}</span>
    </nav>

    <!-- Galeria zdjęć -->
    <div class="mb-6">
      <div class="aspect-video rounded-xl overflow-hidden bg-gray-100 mb-2">
        <img v-if="property.images?.length"
          :src="property.images[activeImg]?.url"
          :alt="property.title"
          class="w-full h-full object-cover"
        />
        <div v-else class="w-full h-full flex items-center justify-center text-gray-300">
          <svg class="w-20 h-20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
          </svg>
        </div>
      </div>
      <div v-if="property.images?.length > 1" class="flex gap-2 overflow-x-auto pb-1">
        <button v-for="(img, i) in property.images" :key="img.id"
          @click="activeImg = i"
          :class="['w-20 h-16 rounded-lg overflow-hidden shrink-0 ring-2 transition-all', i === activeImg ? 'ring-primary-500' : 'ring-transparent hover:ring-gray-300']"
        >
          <img :src="img.url" class="w-full h-full object-cover" />
        </button>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Główna treść -->
      <div class="lg:col-span-2 space-y-6">
        <!-- Nagłówek -->
        <div>
          <div class="flex items-start justify-between gap-4">
            <div>
              <span :class="['badge mb-2', property.property_type === 'apartment' ? 'badge-blue' : 'badge-green']">
                {{ property.property_type_label }}
              </span>
              <h1 class="text-2xl font-bold text-gray-900">{{ property.title }}</h1>
              <p class="text-gray-500 mt-1">
                {{ property.voivodeship && property.voivodeship.charAt(0).toUpperCase() + property.voivodeship.slice(1) }} · {{ property.city }}<span v-if="property.district">, {{ property.district }}</span><span v-if="property.street">, {{ property.street }}</span>
              </p>
            </div>
            <button @click="toggleFavorite"
              :class="['shrink-0 p-2 rounded-full border-2 transition-colors',
                property.is_favorited ? 'border-red-500 text-red-500' : 'border-gray-200 text-gray-400 hover:border-red-300 hover:text-red-400']"
            >
              <svg class="w-6 h-6" :fill="property.is_favorited ? 'currentColor' : 'none'" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
              </svg>
            </button>
          </div>
        </div>

        <!-- Cena -->
        <div class="card p-4 bg-primary-50 border-primary-100">
          <div class="flex flex-wrap gap-6">
            <div>
              <p class="text-sm text-gray-500">Cena najmu</p>
              <p class="text-2xl font-bold text-primary-600">{{ fmt(property.price) }}<span class="text-base font-normal">/mies.</span></p>
            </div>
            <div v-if="property.admin_fee">
              <p class="text-sm text-gray-500">Czynsz administracyjny</p>
              <p class="font-semibold text-gray-800">{{ fmt(property.admin_fee) }}</p>
            </div>
            <div v-if="property.deposit">
              <p class="text-sm text-gray-500">Kaucja</p>
              <p class="font-semibold text-gray-800">{{ fmt(property.deposit) }}</p>
            </div>
          </div>
        </div>

        <!-- Parametry -->
        <div class="card p-4">
          <h2 class="font-semibold text-gray-900 mb-4">Parametry nieruchomości</h2>
          <dl class="grid grid-cols-2 sm:grid-cols-3 gap-4">
            <div v-if="property.area">
              <dt class="text-xs text-gray-500">Metraż</dt>
              <dd class="font-medium">{{ property.area }} m²</dd>
            </div>
            <div v-if="property.rooms_count">
              <dt class="text-xs text-gray-500">Pokoje</dt>
              <dd class="font-medium">{{ property.rooms_count }}</dd>
            </div>
            <div v-if="property.bathrooms_count">
              <dt class="text-xs text-gray-500">Łazienki</dt>
              <dd class="font-medium">{{ property.bathrooms_count }}</dd>
            </div>
            <div v-if="property.floor !== null">
              <dt class="text-xs text-gray-500">Piętro</dt>
              <dd class="font-medium">{{ property.floor === 0 ? 'Parter' : property.floor }}</dd>
            </div>
            <div v-if="property.furnishing_label">
              <dt class="text-xs text-gray-500">Umeblowanie</dt>
              <dd class="font-medium">{{ property.furnishing_label }}</dd>
            </div>
            <div v-if="property.year_built">
              <dt class="text-xs text-gray-500">Rok budowy</dt>
              <dd class="font-medium">{{ property.year_built }}</dd>
            </div>
          </dl>

          <div v-if="property.parking || property.elevator || property.balcony" class="mt-4 flex flex-wrap gap-2">
            <span v-if="property.parking"  class="badge badge-green">Parking</span>
            <span v-if="property.elevator" class="badge badge-green">Winda</span>
            <span v-if="property.balcony"  class="badge badge-green">Balkon</span>
          </div>
        </div>

        <!-- Opis -->
        <div v-if="property.description" class="card p-4">
          <h2 class="font-semibold text-gray-900 mb-3">Opis</h2>
          <div class="text-gray-600 text-sm leading-relaxed whitespace-pre-line">{{ property.description }}</div>
        </div>

        <!-- Zasady najmu -->
        <div class="card p-4">
          <h2 class="font-semibold text-gray-900 mb-3">Zasady najmu</h2>
          <div class="flex flex-wrap gap-2 mb-3">
            <span :class="['badge', property.pets_allowed     ? 'badge-green' : 'badge-red']">{{ property.pets_allowed ? '✓' : '✗' }} Zwierzęta</span>
            <span :class="['badge', property.smoking_allowed  ? 'badge-green' : 'badge-red']">{{ property.smoking_allowed ? '✓' : '✗' }} Palenie</span>
            <span :class="['badge', property.students_allowed ? 'badge-green' : 'badge-gray']">{{ property.students_allowed ? '✓' : '✗' }} Studenci</span>
          </div>
          <div v-if="property.rental_period_label" class="text-sm text-gray-600 mb-1">
            Okres najmu: <strong>{{ property.rental_period_label }}</strong>
          </div>
          <div v-if="property.available_from" class="text-sm text-gray-600">
            Dostępne od: <strong>{{ new Date(property.available_from).toLocaleDateString('pl-PL') }}</strong>
          </div>
          <div v-if="property.rules" class="mt-3 text-sm text-gray-600 whitespace-pre-line">{{ property.rules }}</div>
        </div>
      </div>

      <!-- Sidebar - kontakt -->
      <div class="space-y-4">
        <div class="card p-4 sticky top-20">
          <div v-if="property.owner" class="mb-4">
            <p class="text-sm font-semibold text-gray-900 mb-1">Właściciel</p>
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center text-primary-700 font-bold">
                {{ property.owner.name?.charAt(0) }}
              </div>
              <div>
                <p class="font-medium text-sm">{{ property.owner.name }}</p>
                <p v-if="property.owner.phone" class="text-xs text-gray-500">{{ property.owner.phone }}</p>
              </div>
            </div>
          </div>

          <div class="space-y-2">
            <button @click="showContactModal = true" class="btn btn-primary w-full">
              Napisz do właściciela
            </button>
            <button @click="showViewingModal = true" class="btn btn-secondary w-full">
              Umów oglądanie
            </button>
          </div>

          <div class="mt-4 pt-4 border-t border-gray-100 text-xs text-gray-400 space-y-1">
            <p>Obejrzano: {{ property.views_count }} razy</p>
            <p>Dodano do ulubionych: {{ property.favorites_count }} razy</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal kontakt -->
  <BaseModal :show="showContactModal" title="Napisz do właściciela" @close="showContactModal = false">
    <BaseTextarea v-model="contactMsg" label="Wiadomość" :rows="4" placeholder="Dzień dobry, jestem zainteresowany/a ofertą..." />
    <template #footer>
      <BaseButton variant="secondary" @click="showContactModal = false">Anuluj</BaseButton>
      <BaseButton :loading="submitting" @click="sendMessage">Wyślij</BaseButton>
    </template>
  </BaseModal>

  <!-- Modal oglądanie -->
  <BaseModal :show="showViewingModal" title="Umów oglądanie" @close="showViewingModal = false">
    <div class="space-y-4">
      <BaseInput v-model="viewingDate" label="Data" type="date" :min="minDate" required />
      <BaseInput v-model="viewingTime" label="Godzina" type="time" required />
      <BaseTextarea v-model="viewingNote" label="Uwagi (opcjonalne)" :rows="3" placeholder="Np. zadzwoń przed przyjazdem" />
    </div>
    <template #footer>
      <BaseButton variant="secondary" @click="showViewingModal = false">Anuluj</BaseButton>
      <BaseButton :loading="submitting" @click="bookViewing">Wyślij prośbę</BaseButton>
    </template>
  </BaseModal>
  </PublicLayout>
</template>
