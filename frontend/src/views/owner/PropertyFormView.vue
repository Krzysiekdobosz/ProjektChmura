<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { propertyService } from '@/services/propertyService'
import { useApiErrors } from '@/composables/useApiErrors'
import { useToast } from 'vue-toastification'
import BaseInput from '@/components/base/BaseInput.vue'
import BaseSelect from '@/components/base/BaseSelect.vue'
import BaseTextarea from '@/components/base/BaseTextarea.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseAlert from '@/components/base/BaseAlert.vue'
import BaseSpinner from '@/components/base/BaseSpinner.vue'

const route  = useRoute()
const router = useRouter()
const toast  = useToast()
const { message, fieldError, setErrors, clearErrors } = useApiErrors()

const isEdit    = computed(() => !!route.params.id)
const loading   = ref(false)
const saving    = ref(false)
const uploading = ref(false)
const images    = ref([])
const imgInput  = ref(null)

const form = reactive({
  property_type: 'apartment',
  title: '',
  short_description: '',
  description: '',
  voivodeship: '',
  city: '',
  district: '',
  street: '',
  postal_code: '',
  area: '',
  rooms_count: '',
  bathrooms_count: '',
  floor: '',
  total_floors: '',
  furnishing: '',
  year_built: '',
  parking: false,
  elevator: false,
  balcony: false,
  room_area: '',
  apartment_area: '',
  roommates_count: '',
  total_rooms_count: '',
  price: '',
  admin_fee: '',
  deposit: '',
  additional_costs: '',
  rental_period: '',
  available_from: '',
  min_rental_months: '',
  pets_allowed: false,
  smoking_allowed: false,
  students_allowed: true,
  only_women: false,
  only_men: false,
  rules: '',
  notes: '',
  status: 'draft',
})

const isRoom = computed(() => form.property_type === 'room')
let propertyId = ref(null)

onMounted(async () => {
  if (isEdit.value) {
    loading.value = true
    try {
      const { data } = await propertyService.ownerGet(route.params.id)
      const p = data.data
      propertyId.value = p.id
      Object.keys(form).forEach(k => { if (p[k] !== undefined && p[k] !== null) form[k] = p[k] })
      if (p.property_type) form.property_type = p.property_type
      if (p.furnishing)    form.furnishing    = p.furnishing
      if (p.rental_period) form.rental_period = p.rental_period
      if (p.status)        form.status        = p.status
      images.value = p.images || []
    } finally {
      loading.value = false
    }
  }
})

async function save(publish = false) {
  clearErrors()
  saving.value = true
  try {
    const payload = { ...form }

    let id = propertyId.value
    if (isEdit.value) {
      await propertyService.ownerUpdate(id, payload)
      toast.success('Oferta zaktualizowana.')
    } else {
      const { data } = await propertyService.ownerCreate(payload)
      id = data.data.id
      propertyId.value = id
      toast.success('Oferta utworzona.')
      router.replace(`/wlasciciel/oferty/${id}/edytuj`)
    }

    if (publish) {
      await propertyService.ownerPublish(id)
      toast.success('Oferta wysłana do weryfikacji.')
      router.push('/wlasciciel/oferty')
    }
  } catch (e) {
    setErrors(e)
  } finally {
    saving.value = false
  }
}

async function uploadImages(e) {
  const files = Array.from(e.target.files)
  if (!files.length) return
  if (!propertyId.value) { toast.warning('Najpierw zapisz ofertę.'); return }

  uploading.value = true
  const fd = new FormData()
  files.forEach(f => fd.append('images[]', f))

  try {
    const { data } = await propertyService.uploadImages(propertyId.value, fd)
    images.value.push(...data.data.images)
    toast.success('Zdjęcia wgrane.')
  } catch {
    toast.error('Błąd wgrywania zdjęć.')
  } finally {
    uploading.value = false
    if (imgInput.value) imgInput.value.value = ''
  }
}

async function setMain(imageId) {
  try {
    await propertyService.setMainImage(propertyId.value, imageId)
    images.value = images.value.map(img => ({ ...img, is_main: img.id === imageId }))
  } catch {
    toast.error('Nie udało się ustawić zdjęcia głównego.')
  }
}

async function deleteImage(imageId) {
  if (!confirm('Usunąć zdjęcie?')) return
  try {
    await propertyService.deleteImage(propertyId.value, imageId)
    images.value = images.value.filter(img => img.id !== imageId)
    toast.success('Zdjęcie usunięte.')
  } catch {
    toast.error('Nie udało się usunąć zdjęcia.')
  }
}

const voivodeships = ['dolnośląskie','kujawsko-pomorskie','lubelskie','lubuskie','łódzkie','małopolskie','mazowieckie','opolskie','podkarpackie','podlaskie','pomorskie','śląskie','świętokrzyskie','warmińsko-mazurskie','wielkopolskie','zachodniopomorskie']
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-gray-900">{{ isEdit ? 'Edycja oferty' : 'Nowa oferta' }}</h1>
      <RouterLink to="/wlasciciel/oferty" class="btn btn-secondary btn-sm">← Wróć</RouterLink>
    </div>

    <div v-if="loading" class="flex justify-center py-20"><BaseSpinner /></div>

    <form v-else @submit.prevent="save()" class="space-y-8 max-w-3xl">
      <BaseAlert v-if="message" type="error" :message="message" />

      <!-- 1. Typ oferty -->
      <div class="card p-6">
        <h2 class="font-semibold text-gray-900 mb-4">Typ oferty</h2>
        <div class="flex gap-3">
          <button type="button"
            v-for="t in [{ value: 'apartment', label: 'Mieszkanie' }, { value: 'room', label: 'Pokój' }]" :key="t.value"
            @click="form.property_type = t.value"
            :class="['flex-1 py-3 rounded-xl border-2 font-medium transition-all',
              form.property_type === t.value ? 'border-primary-500 bg-primary-50 text-primary-700' : 'border-gray-200 text-gray-600 hover:border-gray-300']"
          >{{ t.label }}</button>
        </div>
      </div>

      <!-- 2. Dane podstawowe -->
      <div class="card p-6 space-y-4">
        <h2 class="font-semibold text-gray-900">Dane podstawowe</h2>
        <BaseInput v-model="form.title" label="Tytuł oferty" placeholder="np. Słoneczne mieszkanie w centrum" :error="fieldError('title')" required />
        <BaseInput v-model="form.short_description" label="Krótki opis (do karty oferty)" placeholder="max 500 znaków" :error="fieldError('short_description')" />
        <BaseTextarea v-model="form.description" label="Pełny opis" :rows="5" placeholder="Opisz mieszkanie szczegółowo..." :error="fieldError('description')" />
      </div>

      <!-- 3. Lokalizacja -->
      <div class="card p-6 space-y-4">
        <h2 class="font-semibold text-gray-900">Lokalizacja</h2>
        <div class="grid grid-cols-2 gap-4">
          <BaseSelect v-model="form.voivodeship" label="Województwo"
            :options="voivodeships.map(v => ({ value: v, label: v.charAt(0).toUpperCase() + v.slice(1) }))"
            :error="fieldError('voivodeship')" required />
          <BaseInput v-model="form.city" label="Miasto" :error="fieldError('city')" required />
        </div>
        <div class="grid grid-cols-2 gap-4">
          <BaseInput v-model="form.district" label="Dzielnica" :error="fieldError('district')" />
          <BaseInput v-model="form.postal_code" label="Kod pocztowy" placeholder="00-000" :error="fieldError('postal_code')" />
        </div>
        <BaseInput v-model="form.street" label="Ulica i numer" :error="fieldError('street')" />
      </div>

      <!-- 4. Parametry -->
      <div class="card p-6 space-y-4">
        <h2 class="font-semibold text-gray-900">Parametry nieruchomości</h2>

        <div v-if="!isRoom" class="grid grid-cols-2 gap-4">
          <BaseInput v-model="form.area" label="Metraż (m²)" type="number" :error="fieldError('area')" required />
          <BaseInput v-model="form.rooms_count" label="Liczba pokoi" type="number" :error="fieldError('rooms_count')" required />
        </div>

        <div v-if="isRoom" class="grid grid-cols-2 gap-4">
          <BaseInput v-model="form.room_area" label="Metraż pokoju (m²)" type="number" :error="fieldError('room_area')" required />
          <BaseInput v-model="form.apartment_area" label="Metraż mieszkania (m²)" type="number" :error="fieldError('apartment_area')" required />
        </div>

        <div class="grid grid-cols-3 gap-4">
          <BaseInput v-model="form.bathrooms_count" label="Łazienki" type="number" :error="fieldError('bathrooms_count')" />
          <BaseInput v-model="form.floor" label="Piętro" type="number" :error="fieldError('floor')" />
          <BaseInput v-model="form.total_floors" label="Pięter w budynku" type="number" :error="fieldError('total_floors')" />
        </div>

        <div class="grid grid-cols-2 gap-4">
          <BaseSelect v-model="form.furnishing" label="Umeblowanie"
            :options="[{ value: 'furnished', label: 'Umeblowane' }, { value: 'partially_furnished', label: 'Częściowo' }, { value: 'unfurnished', label: 'Nieumeblowane' }]"
            placeholder="Wybierz" :error="fieldError('furnishing')" />
          <BaseInput v-model="form.year_built" label="Rok budowy" type="number" :error="fieldError('year_built')" />
        </div>

        <div class="flex gap-6">
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" v-model="form.parking"  class="w-4 h-4 rounded text-primary-600" />
            <span class="text-sm">Parking</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" v-model="form.elevator" class="w-4 h-4 rounded text-primary-600" />
            <span class="text-sm">Winda</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" v-model="form.balcony"  class="w-4 h-4 rounded text-primary-600" />
            <span class="text-sm">Balkon</span>
          </label>
        </div>
      </div>

      <!-- 5. Cena -->
      <div class="card p-6 space-y-4">
        <h2 class="font-semibold text-gray-900">Cena i opłaty</h2>
        <div class="grid grid-cols-2 gap-4">
          <BaseInput v-model="form.price"     label="Czynsz (zł/mies.)" type="number" :error="fieldError('price')" required />
          <BaseInput v-model="form.admin_fee" label="Czynsz administracyjny (zł)" type="number" :error="fieldError('admin_fee')" />
        </div>
        <div class="grid grid-cols-2 gap-4">
          <BaseInput v-model="form.deposit"    label="Kaucja (zł)" type="number" :error="fieldError('deposit')" />
        </div>
        <BaseTextarea v-model="form.additional_costs" label="Opłaty dodatkowe" :rows="2" placeholder="Np. prąd według licznika, internet 50 zł" />
      </div>

      <!-- 6. Zasady -->
      <div class="card p-6 space-y-4">
        <h2 class="font-semibold text-gray-900">Zasady i dostępność</h2>
        <div class="grid grid-cols-2 gap-4">
          <BaseSelect v-model="form.rental_period" label="Preferowany okres najmu"
            :options="[
              { value: 'minimum_1_month', label: 'Min. 1 miesiąc' },
              { value: '3_months', label: 'Min. 3 miesiące' },
              { value: '6_months', label: 'Min. 6 miesięcy' },
              { value: '12_months', label: 'Min. 12 miesięcy' },
              { value: 'indefinite', label: 'Czas nieokreślony' },
            ]" placeholder="Dowolny" />
          <BaseInput v-model="form.available_from" label="Dostępne od" type="date" :error="fieldError('available_from')" />
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" v-model="form.pets_allowed"     class="w-4 h-4 rounded text-primary-600" />
            <span class="text-sm">Zwierzęta</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" v-model="form.smoking_allowed"  class="w-4 h-4 rounded text-primary-600" />
            <span class="text-sm">Palenie</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" v-model="form.students_allowed" class="w-4 h-4 rounded text-primary-600" />
            <span class="text-sm">Studenci</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" v-model="form.only_women" class="w-4 h-4 rounded text-primary-600" />
            <span class="text-sm">Tylko kobiety</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" v-model="form.only_men"   class="w-4 h-4 rounded text-primary-600" />
            <span class="text-sm">Tylko mężczyźni</span>
          </label>
        </div>

        <BaseTextarea v-model="form.rules" label="Zasady najmu" :rows="3" placeholder="Dodatkowe zasady i wymagania..." />
      </div>

      <!-- 7. Zdjęcia -->
      <div class="card p-6">
        <h2 class="font-semibold text-gray-900 mb-4">Zdjęcia</h2>

        <div v-if="!propertyId" class="text-sm text-gray-500 mb-3">
          Zapisz ofertę, aby wgrać zdjęcia.
        </div>

        <div v-if="images.length" class="grid grid-cols-3 sm:grid-cols-4 gap-3 mb-4">
          <div v-for="img in images" :key="img.id" class="relative group aspect-square rounded-lg overflow-hidden bg-gray-100">
            <img :src="img.url" class="w-full h-full object-cover" />
            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/40 transition-colors flex items-center justify-center gap-2 opacity-0 group-hover:opacity-100">
              <button type="button" @click="setMain(img.id)"
                :class="['p-1.5 rounded-lg text-xs', img.is_main ? 'bg-yellow-400 text-black' : 'bg-white text-gray-700']"
                :title="img.is_main ? 'Zdjęcie główne' : 'Ustaw jako główne'"
              >★</button>
              <button type="button" @click="deleteImage(img.id)" class="p-1.5 rounded-lg bg-red-500 text-white text-xs">✕</button>
            </div>
            <span v-if="img.is_main" class="absolute bottom-1 left-1 text-xs bg-yellow-400 text-black px-1.5 py-0.5 rounded font-medium">Główne</span>
          </div>
        </div>

        <div v-if="propertyId">
          <input ref="imgInput" type="file" multiple accept="image/jpeg,image/png,image/webp"
            class="hidden" @change="uploadImages" />
          <BaseButton type="button" variant="secondary" :loading="uploading" @click="imgInput?.click()">
            {{ uploading ? 'Wgrywanie...' : '+ Dodaj zdjęcia' }}
          </BaseButton>
          <p class="text-xs text-gray-400 mt-1">JPG, PNG, WebP · Max 5 MB każde</p>
        </div>
      </div>

      <!-- Przyciski -->
      <div class="flex items-center gap-3 pb-8">
        <BaseButton type="submit" variant="secondary" :loading="saving">
          Zapisz szkic
        </BaseButton>
        <BaseButton type="button" :loading="saving" @click="save(true)">
          Wyślij do weryfikacji
        </BaseButton>
      </div>
    </form>
  </div>
</template>
