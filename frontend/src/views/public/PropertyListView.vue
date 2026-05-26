<script setup>
import { ref, reactive, watch, onMounted, computed } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { propertyService } from '@/services/propertyService'
import { favoriteService } from '@/services/favoriteService'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'vue-toastification'
import BasePagination from '@/components/base/BasePagination.vue'
import BaseSelect from '@/components/base/BaseSelect.vue'
import BaseInput from '@/components/base/BaseInput.vue'
import BaseSpinner from '@/components/base/BaseSpinner.vue'
import PublicLayout from '@/layouts/PublicLayout.vue'

const route  = useRoute()
const router = useRouter()
const auth   = useAuthStore()
const toast  = useToast()

const properties = ref([])
const meta       = ref({})
const loading    = ref(false)

const filters = reactive({
  type:        route.query.type        || '',
  city:        route.query.city        || '',
  voivodeship: route.query.voivodeship || '',
  price_min:   route.query.price_min   || '',
  price_max:   route.query.price_max   || '',
  area_min:    route.query.area_min    || '',
  rooms_min:   route.query.rooms_min   || '',
  furnishing:  route.query.furnishing  || '',
  pets:        route.query.pets        || '',
  sort:        route.query.sort        || 'newest',
  page:        parseInt(route.query.page) || 1,
})

const voivodeships = [
  'dolnośląskie','kujawsko-pomorskie','lubelskie','lubuskie','łódzkie','małopolskie',
  'mazowieckie','opolskie','podkarpackie','podlaskie','pomorskie','śląskie',
  'świętokrzyskie','warmińsko-mazurskie','wielkopolskie','zachodniopomorskie',
]

const sortOptions = [
  { value: 'newest',     label: 'Najnowsze' },
  { value: 'price_asc',  label: 'Cena rosnąco' },
  { value: 'price_desc', label: 'Cena malejąco' },
  { value: 'area_desc',  label: 'Metraż malejąco' },
]

const typeOptions = [
  { value: '',         label: 'Wszystkie' },
  { value: 'apartment', label: 'Mieszkania' },
  { value: 'room',     label: 'Pokoje' },
]

async function fetchProperties() {
  loading.value = true
  try {
    const params = {}
    Object.entries(filters).forEach(([k, v]) => { if (v !== '' && v !== null) params[k] = v })
    const { data } = await propertyService.list(params)
    properties.value = data.data
    meta.value = data.meta
    syncUrl()
  } finally {
    loading.value = false
  }
}

function syncUrl() {
  const q = {}
  Object.entries(filters).forEach(([k, v]) => { if (v && v !== 'newest') q[k] = v })
  router.replace({ query: q })
}

function resetFilters() {
  Object.assign(filters, { type:'', city:'', voivodeship:'', price_min:'', price_max:'', area_min:'', rooms_min:'', furnishing:'', pets:'', sort:'newest', page: 1 })
}

function onPageChange(p) {
  filters.page = p
  fetchProperties()
}

async function toggleFavorite(property) {
  if (!auth.isLoggedIn) { toast.warning('Zaloguj się, aby dodać do ulubionych.'); return }
  try {
    if (property.is_favorited) {
      await favoriteService.remove(property.id)
      property.is_favorited = false
      toast.success('Usunięto z ulubionych.')
    } else {
      await favoriteService.add(property.id)
      property.is_favorited = true
      toast.success('Dodano do ulubionych.')
    }
  } catch { toast.error('Błąd operacji.') }
}

watch(
  () => [filters.type, filters.city, filters.voivodeship, filters.price_min, filters.price_max,
         filters.area_min, filters.rooms_min, filters.furnishing, filters.pets, filters.sort],
  () => { filters.page = 1; fetchProperties() }
)

onMounted(fetchProperties)

function priceLabel(p) {
  return new Intl.NumberFormat('pl-PL').format(p) + ' zł/mies.'
}
</script>

<template>
  <PublicLayout>
  <div class="max-w-7xl mx-auto px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-6">
      <h1 class="text-3xl font-bold text-gray-900">Oferty wynajmu</h1>
      <p v-if="meta.total !== undefined" class="text-gray-500 mt-1">Znaleziono {{ meta.total }} ofert</p>
    </div>

    <div class="flex flex-col lg:flex-row gap-6">
      <!-- Filtry sidebar -->
      <aside class="w-full lg:w-64 shrink-0">
        <div class="card p-4 space-y-4 sticky top-20">
          <div class="flex items-center justify-between">
            <h2 class="font-semibold text-gray-900">Filtry</h2>
            <button @click="resetFilters" class="text-xs text-primary-600 hover:underline">Wyczyść</button>
          </div>

          <div>
            <label class="form-label">Typ oferty</label>
            <div class="flex gap-2">
              <button
                v-for="opt in typeOptions" :key="opt.value"
                @click="filters.type = opt.value"
                :class="['flex-1 py-1.5 text-sm rounded-lg border transition-colors',
                  filters.type === opt.value ? 'bg-primary-600 text-white border-primary-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50']"
              >{{ opt.label }}</button>
            </div>
          </div>

          <BaseInput v-model="filters.city" label="Miasto" placeholder="np. Warszawa" />

          <BaseSelect v-model="filters.voivodeship" label="Województwo"
            :options="voivodeships.map(v => ({ value: v, label: v.charAt(0).toUpperCase() + v.slice(1) }))"
            placeholder="Wybierz województwo"
          />

          <div class="grid grid-cols-2 gap-2">
            <BaseInput v-model="filters.price_min" label="Cena od" type="number" placeholder="0" />
            <BaseInput v-model="filters.price_max" label="Cena do" type="number" placeholder="∞" />
          </div>

          <BaseInput v-model="filters.area_min" label="Min. metraż (m²)" type="number" placeholder="0" />

          <BaseSelect v-model="filters.rooms_min" label="Min. pokoi"
            :options="[1,2,3,4,5].map(n => ({ value: String(n), label: n + ' pokoje' }))"
            placeholder="Dowolna liczba"
          />

          <BaseSelect v-model="filters.furnishing" label="Umeblowanie"
            :options="[
              { value: 'furnished', label: 'Umeblowane' },
              { value: 'partially_furnished', label: 'Częściowo' },
              { value: 'unfurnished', label: 'Nieumeblowane' },
            ]"
            placeholder="Dowolne"
          />

          <BaseSelect v-model="filters.pets" label="Zwierzęta"
            :options="[{ value: '1', label: 'Tak' }, { value: '0', label: 'Nie' }]"
            placeholder="Dowolne"
          />
        </div>
      </aside>

      <!-- Lista ofert -->
      <div class="flex-1 min-w-0">
        <div class="flex items-center justify-between mb-4">
          <BaseSelect v-model="filters.sort" :options="sortOptions" class="w-44" />
        </div>

        <div v-if="loading" class="flex justify-center py-20"><BaseSpinner /></div>

        <div v-else-if="!properties.length" class="text-center py-20 text-gray-400">
          <svg class="w-16 h-16 mx-auto mb-4 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
          </svg>
          <p>Nie znaleziono ofert pasujących do wybranych filtrów.</p>
          <button @click="resetFilters" class="mt-3 text-primary-600 hover:underline text-sm">Wyczyść filtry</button>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
          <div v-for="p in properties" :key="p.id" class="card group hover:shadow-md transition-shadow">
            <RouterLink :to="`/oferty/${p.slug}`" class="block">
              <div class="relative aspect-video bg-gray-100 overflow-hidden">
                <img v-if="p.main_image_url" :src="p.main_image_url" :alt="p.title"
                  class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                <div v-else class="w-full h-full flex items-center justify-center text-gray-300">
                  <svg class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                  </svg>
                </div>
                <span :class="['absolute top-2 left-2 badge', p.property_type === 'apartment' ? 'badge-blue' : 'badge-green']">
                  {{ p.property_type === 'apartment' ? 'Mieszkanie' : 'Pokój' }}
                </span>
              </div>
            </RouterLink>

            <div class="p-4">
              <RouterLink :to="`/oferty/${p.slug}`">
                <h3 class="font-semibold text-gray-900 group-hover:text-primary-600 transition-colors line-clamp-1">{{ p.title }}</h3>
              </RouterLink>
              <p class="text-sm text-gray-500 mt-1">{{ p.city }}<span v-if="p.district">, {{ p.district }}</span></p>

              <div class="flex items-center justify-between mt-3">
                <div>
                  <p class="text-lg font-bold text-primary-600">{{ priceLabel(p.price) }}</p>
                  <p class="text-xs text-gray-400">
                    <span v-if="p.area">{{ p.area }} m²</span>
                    <span v-if="p.rooms_count"> · {{ p.rooms_count }} pokoje</span>
                  </p>
                </div>
                <button @click.prevent="toggleFavorite(p)"
                  :class="['p-2 rounded-full transition-colors', p.is_favorited ? 'text-red-500 hover:text-red-600' : 'text-gray-300 hover:text-red-400']">
                  <svg class="w-5 h-5" :fill="p.is_favorited ? 'currentColor' : 'none'" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>

        <BasePagination
          v-if="meta.last_page > 1"
          class="mt-8"
          :currentPage="meta.current_page"
          :lastPage="meta.last_page"
          :total="meta.total"
          @change="onPageChange"
        />
      </div>
    </div>
  </div>
  </PublicLayout>
</template>
