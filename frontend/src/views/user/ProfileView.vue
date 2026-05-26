<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useApiErrors } from '@/composables/useApiErrors'
import { useToast } from 'vue-toastification'
import { authService } from '@/services/authService'
import BaseInput from '@/components/base/BaseInput.vue'
import BaseSelect from '@/components/base/BaseSelect.vue'
import BaseTextarea from '@/components/base/BaseTextarea.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseAlert from '@/components/base/BaseAlert.vue'

const auth = useAuthStore()
const toast = useToast()
const { message, fieldError, setErrors, clearErrors } = useApiErrors()
const saving = ref(false)

const form = reactive({
  name: '',
  email: '',
  phone: '',
  bio: '',
  city: '',
  voivodeship: '',
  date_of_birth: '',
  password: '',
  password_confirmation: '',
})

onMounted(() => {
  const u = auth.user
  if (u) {
    form.name  = u.name  || ''
    form.email = u.email || ''
    form.phone = u.phone || ''
    if (u.profile) {
      form.bio          = u.profile.bio          || ''
      form.city         = u.profile.city         || ''
      form.voivodeship  = u.profile.voivodeship  || ''
      form.date_of_birth = u.profile.date_of_birth || ''
    }
  }
})

async function save() {
  clearErrors()
  saving.value = true
  try {
    const { data } = await authService.updateProfile(form)
    auth.user = data.data
    toast.success('Profil zaktualizowany.')
    form.password = ''
    form.password_confirmation = ''
  } catch (e) {
    setErrors(e)
  } finally {
    saving.value = false
  }
}

const voivodeships = ['dolnośląskie','kujawsko-pomorskie','lubelskie','lubuskie','łódzkie','małopolskie','mazowieckie','opolskie','podkarpackie','podlaskie','pomorskie','śląskie','świętokrzyskie','warmińsko-mazurskie','wielkopolskie','zachodniopomorskie']
</script>

<template>
  <div class="max-w-xl">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Mój profil</h1>

    <BaseAlert v-if="message" type="error" :message="message" class="mb-4" />

    <form @submit.prevent="save" class="space-y-6">
      <div class="card p-6 space-y-4">
        <h2 class="font-semibold text-gray-900">Dane osobowe</h2>
        <BaseInput v-model="form.name"  label="Imię i nazwisko" :error="fieldError('name')" required />
        <BaseInput v-model="form.email" label="Adres e-mail" type="email" :error="fieldError('email')" required />
        <BaseInput v-model="form.phone" label="Telefon" type="tel" :error="fieldError('phone')" />
        <BaseInput v-model="form.date_of_birth" label="Data urodzenia" type="date" :error="fieldError('date_of_birth')" />
      </div>

      <div class="card p-6 space-y-4">
        <h2 class="font-semibold text-gray-900">O mnie</h2>
        <BaseTextarea v-model="form.bio" label="Krótki opis" :rows="3" placeholder="Napisz kilka słów o sobie..." />
        <div class="grid grid-cols-2 gap-4">
          <BaseSelect v-model="form.voivodeship" label="Województwo"
            :options="voivodeships.map(v => ({ value: v, label: v.charAt(0).toUpperCase() + v.slice(1) }))"
            placeholder="Wybierz" />
          <BaseInput v-model="form.city" label="Miasto" />
        </div>
      </div>

      <div class="card p-6 space-y-4">
        <h2 class="font-semibold text-gray-900">Zmiana hasła</h2>
        <p class="text-sm text-gray-500">Zostaw puste, jeśli nie chcesz zmieniać hasła.</p>
        <BaseInput v-model="form.password" label="Nowe hasło" type="password" placeholder="Minimum 8 znaków" :error="fieldError('password')" />
        <BaseInput v-model="form.password_confirmation" label="Powtórz nowe hasło" type="password" />
      </div>

      <BaseButton type="submit" :loading="saving">Zapisz zmiany</BaseButton>
    </form>
  </div>
</template>
