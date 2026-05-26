<script setup>
import { ref, reactive, onMounted } from 'vue'
import { blogService } from '@/services/blogService'
import { useToast } from 'vue-toastification'
import BaseSpinner from '@/components/base/BaseSpinner.vue'
import BaseBadge from '@/components/base/BaseBadge.vue'
import BasePagination from '@/components/base/BasePagination.vue'
import BaseInput from '@/components/base/BaseInput.vue'
import BaseSelect from '@/components/base/BaseSelect.vue'
import BaseTextarea from '@/components/base/BaseTextarea.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseModal from '@/components/base/BaseModal.vue'

const toast = useToast()
const posts   = ref([])
const meta    = ref({})
const loading = ref(false)
const page    = ref(1)
const search  = ref('')
const statusFilter = ref('')
const categories = ref([])

const showModal   = ref(false)
const isEdit      = ref(false)
const submitting  = ref(false)
const editId      = ref(null)

const statusOptions = [
  { value: '',          label: 'Wszystkie' },
  { value: 'draft',     label: 'Szkic' },
  { value: 'published', label: 'Opublikowane' },
]
const statusBadge = { draft: 'gray', published: 'green' }
const statusLabel = { draft: 'Szkic', published: 'Opublikowany' }

const form = reactive({
  title: '', excerpt: '', content: '', category_id: '', status: 'draft',
  meta_title: '', meta_description: '', tags: '',
})

function resetForm() {
  Object.assign(form, { title: '', excerpt: '', content: '', category_id: '', status: 'draft', meta_title: '', meta_description: '', tags: '' })
  editId.value = null
  isEdit.value = false
}

async function fetch() {
  loading.value = true
  try {
    const params = { page: page.value }
    if (search.value)       params.search = search.value
    if (statusFilter.value) params.status = statusFilter.value
    const { data } = await blogService.adminList(params)
    posts.value = data.data
    meta.value  = data.meta
  } finally {
    loading.value = false
  }
}

async function fetchCategories() {
  try {
    const { data } = await blogService.categories()
    categories.value = data.data || []
  } catch {}
}

function openCreate() {
  resetForm()
  showModal.value = true
}

async function openEdit(post) {
  try {
    const { data } = await blogService.adminShow(post.id)
    const p = data.data
    editId.value = p.id
    isEdit.value = true
    form.title = p.title || ''
    form.excerpt = p.excerpt || ''
    form.content = p.content || ''
    form.category_id = p.category?.id || ''
    form.status = p.status || 'draft'
    form.meta_title = p.meta_title || ''
    form.meta_description = p.meta_description || ''
    form.tags = p.tags ? p.tags.map(t => t.name).join(', ') : ''
    showModal.value = true
  } catch {
    toast.error('Nie udało się wczytać wpisu.')
  }
}

async function submit() {
  if (!form.title || !form.content) { toast.warning('Tytuł i treść są wymagane.'); return }
  submitting.value = true
  const payload = {
    ...form,
    category_id: form.category_id || null,
    tags: form.tags ? form.tags.split(',').map(s => s.trim()).filter(Boolean) : [],
  }
  try {
    if (isEdit.value) {
      await blogService.adminUpdate(editId.value, payload)
      toast.success('Wpis zaktualizowany.')
    } else {
      await blogService.adminCreate(payload)
      toast.success('Wpis utworzony.')
    }
    showModal.value = false
    fetch()
  } catch (e) {
    toast.error(e.response?.data?.message || 'Błąd operacji.')
  } finally {
    submitting.value = false
  }
}

async function remove(post) {
  if (!confirm(`Usunąć wpis „${post.title}"?`)) return
  try {
    await blogService.adminDelete(post.id)
    toast.success('Wpis usunięty.')
    fetch()
  } catch {
    toast.error('Błąd usuwania.')
  }
}

function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('pl-PL')
}

onMounted(() => { fetch(); fetchCategories() })
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Blog</h1>
      <BaseButton @click="openCreate">+ Nowy wpis</BaseButton>
    </div>

    <div class="flex gap-3 mb-4">
      <BaseInput v-model="search" placeholder="Szukaj po tytule..." class="max-w-xs" @keyup.enter="() => { page = 1; fetch() }" />
      <BaseSelect v-model="statusFilter" :options="statusOptions" class="w-44" @update:modelValue="() => { page = 1; fetch() }" />
    </div>

    <div v-if="loading" class="flex justify-center py-12"><BaseSpinner /></div>

    <div v-else class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
          <tr>
            <th class="px-4 py-3 text-left">Tytuł</th>
            <th class="px-4 py-3 text-left">Kategoria</th>
            <th class="px-4 py-3 text-left">Status</th>
            <th class="px-4 py-3 text-left">Opublikowano</th>
            <th class="px-4 py-3 text-right">Akcje</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="p in posts" :key="p.id" class="hover:bg-gray-50">
            <td class="px-4 py-3">
              <p class="font-medium text-gray-900">{{ p.title }}</p>
              <p v-if="p.excerpt" class="text-gray-400 text-xs truncate max-w-xs">{{ p.excerpt }}</p>
            </td>
            <td class="px-4 py-3 text-gray-600">{{ p.category?.name || '—' }}</td>
            <td class="px-4 py-3">
              <BaseBadge :variant="statusBadge[p.status] || 'gray'">{{ statusLabel[p.status] || p.status }}</BaseBadge>
            </td>
            <td class="px-4 py-3 text-gray-500">{{ formatDate(p.published_at) }}</td>
            <td class="px-4 py-3 text-right flex justify-end gap-2">
              <button @click="openEdit(p)" class="text-xs px-3 py-1.5 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 font-medium transition-colors">
                Edytuj
              </button>
              <button @click="remove(p)" class="text-xs px-3 py-1.5 rounded-lg border border-red-200 text-red-600 hover:bg-red-50 font-medium transition-colors">
                Usuń
              </button>
            </td>
          </tr>
          <tr v-if="!posts.length">
            <td colspan="5" class="px-4 py-12 text-center text-gray-400">Brak wpisów blogowych.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <BasePagination
      v-if="meta.last_page > 1"
      class="mt-4"
      :currentPage="meta.current_page"
      :lastPage="meta.last_page"
      :total="meta.total"
      @change="p => { page.value = p; fetch() }"
    />

    <!-- Modal tworzenia/edycji -->
    <BaseModal
      v-if="showModal"
      :show="showModal"
      :title="isEdit ? 'Edytuj wpis' : 'Nowy wpis'"
      size="lg"
      @close="showModal = false"
    >
      <div class="space-y-4">
        <BaseInput v-model="form.title" label="Tytuł *" placeholder="Tytuł wpisu" />

        <div>
          <label class="form-label">Kategoria</label>
          <select v-model="form.category_id" class="form-input mt-1">
            <option value="">Bez kategorii</option>
            <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
        </div>

        <BaseTextarea v-model="form.excerpt" label="Wstęp (zajawka)" rows="2" placeholder="Krótki opis wpisu..." />

        <BaseTextarea v-model="form.content" label="Treść *" rows="8" placeholder="Pełna treść wpisu..." />

        <BaseInput v-model="form.tags" label="Tagi (oddzielone przecinkami)" placeholder="np. wynajem, mieszkanie, Warszawa" />

        <div>
          <label class="form-label">Status</label>
          <select v-model="form.status" class="form-input mt-1">
            <option value="draft">Szkic</option>
            <option value="published">Opublikowany</option>
          </select>
        </div>

        <hr />
        <p class="text-xs text-gray-500 font-semibold uppercase tracking-wide">SEO (opcjonalnie)</p>
        <BaseInput v-model="form.meta_title" label="Meta title" placeholder="Tytuł dla wyszukiwarek" />
        <BaseTextarea v-model="form.meta_description" label="Meta description" rows="2" placeholder="Opis dla wyszukiwarek" />
      </div>
      <template #footer>
        <BaseButton variant="secondary" @click="showModal = false">Anuluj</BaseButton>
        <BaseButton :loading="submitting" @click="submit">{{ isEdit ? 'Zapisz zmiany' : 'Utwórz wpis' }}</BaseButton>
      </template>
    </BaseModal>
  </div>
</template>
