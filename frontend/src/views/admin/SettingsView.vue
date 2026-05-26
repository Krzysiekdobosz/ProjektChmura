<script setup>
import { ref, reactive, onMounted } from 'vue'
import { adminService } from '@/services/adminService'
import { useToast } from 'vue-toastification'
import BaseSpinner from '@/components/base/BaseSpinner.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseInput from '@/components/base/BaseInput.vue'

const toast   = useToast()
const loading = ref(true)
const saving  = ref(false)
const settings = reactive({})

const labels = {
  site_name:        'Nazwa serwisu',
  site_email:       'Adres e-mail serwisu',
  site_phone:       'Telefon kontaktowy',
  site_address:     'Adres',
  properties_per_page: 'Ofert na stronę',
  blog_per_page:    'Wpisów na stronę',
  maintenance_mode: 'Tryb konserwacji (0/1)',
}

onMounted(async () => {
  try {
    const { data } = await adminService.settings()
    Object.assign(settings, data.data || {})
  } finally {
    loading.value = false
  }
})

async function save() {
  saving.value = true
  try {
    await adminService.settingsUpdate({ ...settings })
    toast.success('Ustawienia zapisane.')
  } catch {
    toast.error('Błąd zapisu ustawień.')
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div>
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Ustawienia serwisu</h1>

    <div v-if="loading" class="flex justify-center py-12"><BaseSpinner /></div>

    <div v-else class="card p-6 max-w-xl space-y-4">
      <template v-for="(val, key) in settings" :key="key">
        <BaseInput
          :label="labels[key] || key"
          :modelValue="val"
          @update:modelValue="v => settings[key] = v"
        />
      </template>
      <div v-if="!Object.keys(settings).length" class="text-gray-400 text-sm">Brak ustawień do wyświetlenia.</div>

      <div class="pt-2">
        <BaseButton :loading="saving" @click="save">Zapisz ustawienia</BaseButton>
      </div>
    </div>
  </div>
</template>
