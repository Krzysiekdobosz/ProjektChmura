<script setup>
import { ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useApiErrors } from '@/composables/useApiErrors'
import { authService } from '@/services/authService'
import BaseInput from '@/components/base/BaseInput.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseAlert from '@/components/base/BaseAlert.vue'

const { message, fieldError, setErrors, clearErrors } = useApiErrors()
const email = ref('')
const loading = ref(false)
const sent = ref(false)
const successMsg = ref('')

async function handleSubmit() {
  clearErrors()
  loading.value = true
  try {
    const { data } = await authService.forgotPassword({ email: email.value })
    sent.value = true
    successMsg.value = data.message
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
        <h1 class="mt-4 text-2xl font-bold text-gray-900">Resetowanie hasła</h1>
        <p class="mt-1 text-sm text-gray-500">
          <RouterLink to="/auth/logowanie" class="text-primary-600 hover:underline">Wróć do logowania</RouterLink>
        </p>
      </div>

      <div class="card p-8">
        <div v-if="sent" class="text-center py-4">
          <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-6 h-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
          </div>
          <p class="text-gray-700">{{ successMsg }}</p>
          <RouterLink to="/auth/logowanie" class="mt-4 btn btn-secondary btn-sm inline-flex">
            Powrót do logowania
          </RouterLink>
        </div>

        <template v-else>
          <BaseAlert v-if="message" type="error" :message="message" class="mb-4" />
          <p class="text-sm text-gray-600 mb-5">
            Podaj adres e-mail powiązany z Twoim kontem. Wyślemy link do resetowania hasła.
          </p>

          <form @submit.prevent="handleSubmit" class="space-y-5">
            <BaseInput
              v-model="email"
              label="Adres e-mail"
              type="email"
              placeholder="jan@example.com"
              :error="fieldError('email')"
              required
            />
            <BaseButton type="submit" class="w-full" :loading="loading">
              Wyślij link resetujący
            </BaseButton>
          </form>
        </template>
      </div>
    </div>
  </div>
</template>
