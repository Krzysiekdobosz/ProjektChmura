<script setup>
import { ref, onMounted } from 'vue'
import { conversationService } from '@/services/conversationService'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'vue-toastification'
import BaseSpinner from '@/components/base/BaseSpinner.vue'
import BaseTextarea from '@/components/base/BaseTextarea.vue'
import BaseButton from '@/components/base/BaseButton.vue'

const auth = useAuthStore()
const toast = useToast()
const conversations = ref([])
const activeConv = ref(null)
const messages   = ref([])
const newMsg     = ref('')
const loadingList = ref(false)
const loadingConv = ref(false)
const sending     = ref(false)

async function fetchConversations() {
  loadingList.value = true
  try {
    const { data } = await conversationService.list()
    conversations.value = data.data
  } finally {
    loadingList.value = false
  }
}

async function openConversation(conv) {
  activeConv.value = conv
  loadingConv.value = true
  try {
    const { data } = await conversationService.show(conv.id)
    messages.value = data.data.messages
    conv.unread_count = 0
  } finally {
    loadingConv.value = false
  }
}

async function sendMessage() {
  if (!newMsg.value.trim()) return
  sending.value = true
  try {
    const { data } = await conversationService.send(activeConv.value.id, newMsg.value)
    messages.value.push(data.data)
    newMsg.value = ''
  } catch {
    toast.error('Błąd wysyłania wiadomości.')
  } finally {
    sending.value = false
  }
}

function formatTime(d) {
  if (!d) return ''
  return new Date(d).toLocaleString('pl-PL', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' })
}

onMounted(fetchConversations)
</script>

<template>
  <div>
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Wiadomości</h1>

    <div class="flex h-[600px] border border-gray-200 rounded-xl overflow-hidden bg-white">
      <!-- Lista konwersacji -->
      <div class="w-64 border-r border-gray-200 flex flex-col shrink-0">
        <div v-if="loadingList" class="flex justify-center p-4"><BaseSpinner size="sm" /></div>
        <div v-else-if="!conversations.length" class="p-4 text-sm text-gray-400 text-center">
          Brak konwersacji
        </div>
        <div v-else class="overflow-y-auto flex-1">
          <button
            v-for="c in conversations" :key="c.id"
            @click="openConversation(c)"
            :class="['w-full text-left p-3 border-b border-gray-100 hover:bg-gray-50 transition-colors',
              activeConv?.id === c.id ? 'bg-primary-50' : '']"
          >
            <div class="flex items-center justify-between mb-1">
              <span class="text-sm font-medium text-gray-900 truncate">{{ c.other_user?.name }}</span>
              <span v-if="c.unread_count" class="badge badge-blue text-xs ml-1 shrink-0">{{ c.unread_count }}</span>
            </div>
            <p class="text-xs text-gray-500 truncate">{{ c.property?.title }}</p>
            <p v-if="c.last_message" class="text-xs text-gray-400 truncate mt-0.5">{{ c.last_message.body }}</p>
          </button>
        </div>
      </div>

      <!-- Okno wiadomości -->
      <div class="flex-1 flex flex-col min-w-0">
        <div v-if="!activeConv" class="flex-1 flex items-center justify-center text-gray-400">
          <p>Wybierz konwersację</p>
        </div>

        <template v-else>
          <div class="p-3 border-b border-gray-200 bg-gray-50">
            <p class="font-medium text-sm text-gray-900">{{ activeConv.other_user?.name }}</p>
            <p class="text-xs text-gray-500 truncate">{{ activeConv.property?.title }}</p>
          </div>

          <div v-if="loadingConv" class="flex-1 flex items-center justify-center"><BaseSpinner /></div>
          <div v-else class="flex-1 overflow-y-auto p-4 space-y-3">
            <div v-for="m in messages" :key="m.id"
              :class="['flex', m.is_mine ? 'justify-end' : 'justify-start']">
              <div :class="['max-w-xs rounded-xl px-3 py-2 text-sm',
                m.is_mine ? 'bg-primary-600 text-white rounded-br-none' : 'bg-gray-100 text-gray-800 rounded-bl-none']">
                <p>{{ m.body }}</p>
                <p class="text-xs opacity-60 mt-0.5 text-right">{{ formatTime(m.created_at) }}</p>
              </div>
            </div>
          </div>

          <div class="p-3 border-t border-gray-200 flex gap-2">
            <BaseTextarea v-model="newMsg" :rows="2" placeholder="Napisz wiadomość..." class="flex-1" @keydown.ctrl.enter="sendMessage" />
            <BaseButton :loading="sending" @click="sendMessage" class="self-end shrink-0">Wyślij</BaseButton>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>
