<script setup>
import { ref } from 'vue'
import { RouterLink, RouterView, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'vue-toastification'

const auth   = useAuthStore()
const router = useRouter()
const toast  = useToast()
const menuOpen = ref(false)

const navLinks = [
  { to: '/oferty', label: 'Oferty' },
  { to: '/blog',   label: 'Blog' },
  { to: '/jak-to-dziala', label: 'Jak to działa' },
  { to: '/kontakt', label: 'Kontakt' },
]

async function handleLogout() {
  await auth.logout()
  toast.success('Wylogowano pomyślnie.')
  router.push('/')
}

function panelLink() {
  if (auth.isAdmin)  return '/admin/dashboard'
  if (auth.isOwner)  return '/wlasciciel/dashboard'
  return '/panel/dashboard'
}
</script>

<template>
  <div class="min-h-screen flex flex-col">
    <!-- Navbar -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-40">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
          <!-- Logo -->
          <RouterLink to="/" class="flex items-center gap-2 font-bold text-xl text-primary-600">
            NajmujMieszkanie
          </RouterLink>

          <!-- Desktop nav -->
          <nav class="hidden md:flex items-center gap-6">
            <RouterLink
              v-for="link in navLinks"
              :key="link.to"
              :to="link.to"
              class="text-sm text-gray-600 hover:text-primary-600 transition-colors"
              active-class="text-primary-600 font-medium"
            >
              {{ link.label }}
            </RouterLink>
          </nav>

          <!-- Auth buttons -->
          <div class="hidden md:flex items-center gap-3">
            <template v-if="auth.isLoggedIn">
              <RouterLink :to="panelLink()" class="btn btn-secondary btn-sm">
                Panel
              </RouterLink>
              <button @click="handleLogout" class="btn btn-secondary btn-sm">
                Wyloguj
              </button>
            </template>
            <template v-else>
              <RouterLink to="/auth/logowanie"    class="btn btn-secondary btn-sm">Zaloguj</RouterLink>
              <RouterLink to="/auth/rejestracja"  class="btn btn-primary btn-sm">Zarejestruj</RouterLink>
            </template>
          </div>

          <!-- Mobile hamburger -->
          <button @click="menuOpen = !menuOpen" class="md:hidden p-2 rounded-lg text-gray-500 hover:bg-gray-100">
            <span class="sr-only">Menu</span>
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path v-if="!menuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
              <path v-else           stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>
      </div>

      <!-- Mobile menu -->
      <div v-if="menuOpen" class="md:hidden border-t border-gray-200 px-4 py-3 space-y-2 bg-white">
        <RouterLink v-for="link in navLinks" :key="link.to" :to="link.to"
          @click="menuOpen = false"
          class="block py-2 text-sm text-gray-700 hover:text-primary-600">
          {{ link.label }}
        </RouterLink>
        <div class="pt-2 border-t border-gray-100 flex gap-2">
          <RouterLink v-if="!auth.isLoggedIn" to="/auth/logowanie"   class="btn btn-secondary btn-sm flex-1">Zaloguj</RouterLink>
          <RouterLink v-if="!auth.isLoggedIn" to="/auth/rejestracja" class="btn btn-primary  btn-sm flex-1">Zarejestruj</RouterLink>
          <RouterLink v-if="auth.isLoggedIn"  :to="panelLink()"      class="btn btn-secondary btn-sm flex-1">Panel</RouterLink>
          <button     v-if="auth.isLoggedIn"  @click="handleLogout"  class="btn btn-secondary btn-sm flex-1">Wyloguj</button>
        </div>
      </div>
    </header>

    <!-- Content -->
    <main class="flex-1">
      <slot />
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 mt-16">
      <div class="max-w-7xl mx-auto px-4 py-10 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
          <div>
            <p class="font-semibold text-gray-900 mb-3">NajmujMieszkanie</p>
            <p class="text-sm text-gray-500">Platforma wynajmu mieszkań i pokoi w Polsce.</p>
          </div>
          <div>
            <p class="font-semibold text-gray-700 mb-3 text-sm">Informacje</p>
            <ul class="space-y-2 text-sm text-gray-500">
              <li><RouterLink to="/o-nas" class="hover:text-primary-600">O nas</RouterLink></li>
              <li><RouterLink to="/jak-to-dziala" class="hover:text-primary-600">Jak to działa</RouterLink></li>
              <li><RouterLink to="/blog" class="hover:text-primary-600">Blog</RouterLink></li>
            </ul>
          </div>
          <div>
            <p class="font-semibold text-gray-700 mb-3 text-sm">Pomoc</p>
            <ul class="space-y-2 text-sm text-gray-500">
              <li><RouterLink to="/faq" class="hover:text-primary-600">FAQ</RouterLink></li>
              <li><RouterLink to="/kontakt" class="hover:text-primary-600">Kontakt</RouterLink></li>
            </ul>
          </div>
          <div>
            <p class="font-semibold text-gray-700 mb-3 text-sm">Prawne</p>
            <ul class="space-y-2 text-sm text-gray-500">
              <li><RouterLink to="/regulamin" class="hover:text-primary-600">Regulamin</RouterLink></li>
              <li><RouterLink to="/polityka-prywatnosci" class="hover:text-primary-600">Polityka prywatności</RouterLink></li>
            </ul>
          </div>
        </div>
        <p class="mt-8 text-center text-xs text-gray-400">
          © {{ new Date().getFullYear() }} NajmujMieszkanie. Wszelkie prawa zastrzeżone.
        </p>
      </div>
    </footer>
  </div>
</template>
