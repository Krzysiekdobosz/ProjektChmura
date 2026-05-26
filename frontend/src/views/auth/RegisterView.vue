<script setup>
import { ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useApiErrors } from '@/composables/useApiErrors'
import { useToast } from 'vue-toastification'
import { authService } from '@/services/authService'
import BaseInput from '@/components/base/BaseInput.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseAlert from '@/components/base/BaseAlert.vue'
import BaseSelect from '@/components/base/BaseSelect.vue'

const auth = useAuthStore()
const router = useRouter()
const toast = useToast()
const { message, fieldError, setErrors, clearErrors } = useApiErrors()

const form = ref({
  name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
  role: 'user',
})
const loading = ref(false)

const roleOptions = [
  { value: 'user',  label: 'Szukam mieszkania / pokoju' },
  { value: 'owner', label: 'Wynajmuję mieszkania / pokoje' },
]

async function handleSubmit() {
  clearErrors()
  loading.value = true
  try {
    const { data } = await authService.register(form.value)
    auth.setToken(data.data.token)
    auth.user = data.data.user
    toast.success('Konto zostało utworzone!')
    if (auth.isOwner) return router.push('/wlasciciel/dashboard')
    router.push('/panel/dashboard')
  } catch (e) {
    setErrors(e)
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4">
    <div class="w-full max-w-md">
      <div class="text-center mb-8">
        <RouterLink to="/" class="text-2xl font-bold text-primary-600">NajmujMieszkanie</RouterLink>
        <h1 class="mt-4 text-2xl font-bold text-gray-900">Utwórz konto</h1>
        <p class="mt-1 text-sm text-gray-500">
          Masz już konto?
          <RouterLink to="/auth/logowanie" class="text-primary-600 hover:underline font-medium">Zaloguj się</RouterLink>
        </p>
      </div>

      <div class="card p-8">
        <BaseAlert v-if="message" type="error" :message="message" class="mb-4" />

        <form @submit.prevent="handleSubmit" class="space-y-5">
          <BaseInput
            v-model="form.name"
            label="Imię i nazwisko"
            placeholder="Jan Kowalski"
            :error="fieldError('name')"
            required
          />
          <BaseInput
            v-model="form.email"
            label="Adres e-mail"
            type="email"
            placeholder="jan@example.com"
            :error="fieldError('email')"
            required
          />
          <BaseInput
            v-model="form.phone"
            label="Telefon"
            type="tel"
            placeholder="+48 600 000 000"
            :error="fieldError('phone')"
          />
          <BaseSelect
            v-model="form.role"
            label="Kim jesteś?"
            :options="roleOptions"
            :error="fieldError('role')"
          />
          <BaseInput
            v-model="form.password"
            label="Hasło"
            type="password"
            placeholder="Minimum 8 znaków"
            :error="fieldError('password')"
            required
          />
          <BaseInput
            v-model="form.password_confirmation"
            label="Powtórz hasło"
            type="password"
            placeholder="••••••••"
            required
          />

          <p class="text-xs text-gray-500">
            Rejestrując się akceptujesz
            <RouterLink to="/regulamin" class="text-primary-600 hover:underline">Regulamin</RouterLink>
            i
            <RouterLink to="/polityka-prywatnosci" class="text-primary-600 hover:underline">Politykę prywatności</RouterLink>.
          </p>

          <BaseButton type="submit" class="w-full" :loading="loading">
            Zarejestruj się
          </BaseButton>
        </form>
      </div>
    </div>
  </div>
</template>
