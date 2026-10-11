<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-[2147483000] flex items-center justify-center bg-slate-950/70 p-4" @click.self="close" @keydown.esc.stop.prevent="close">
      <form ref="panel" role="dialog" aria-modal="true" aria-labelledby="workflow-status-title" tabindex="-1" class="max-h-[90dvh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-6 shadow-xl dark:bg-slate-900 dark:text-white" @submit.prevent="save" @keydown.tab="trapFocus">
        <div class="flex items-center justify-between gap-3"><h2 id="workflow-status-title" class="text-xl font-bold">{{ $t('Update Status') }}</h2><button type="button" :disabled="busy" :aria-label="$t('Tutup modal')" @click="close">×</button></div>
        <p class="mt-4 font-mono text-xs text-cyan-600">{{ item.number }}</p><p class="mt-1 font-semibold">{{ item.title }}</p>
        <p class="mt-3 text-sm text-slate-500">{{ $t('Status saat ini:') }} {{ item.status_label }}</p>
        <label class="mt-5 block text-sm font-semibold" for="workflow-status-select">{{ $t('Status tujuan') }}</label>
        <select id="workflow-status-select" v-model="status" required :disabled="busy" class="mt-2 w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800">
          <option value="" disabled>{{ $t('Pilih transisi yang tersedia') }}</option><option v-for="option in item.status_options" :key="option.value" :value="option.value">{{ option.label }}</option>
        </select>
        <p v-if="error" role="alert" class="mt-3 text-sm text-rose-600">{{ error }}</p>
        <div class="mt-6 flex justify-end gap-3"><button type="button" :disabled="busy" class="rounded-lg border px-4 py-2" @click="close">{{ $t('Batal') }}</button><button :disabled="busy || !status" class="rounded-lg bg-cyan-600 px-4 py-2 font-semibold text-white disabled:opacity-50">{{ busy ? $t('Memproses...') : $t('Simpan Perubahan') }}</button></div>
      </form>
    </div>
  </Teleport>
</template>

<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import axios from 'axios'
import { useI18n } from '../i18n'
const props = defineProps({ item: { type: Object, required: true } })
const emit = defineEmits(['close', 'saved'])
const { t } = useI18n()
const status = ref(''), busy = ref(false), error = ref(''), panel = ref(null)
let previousFocus, previousOverflow
const close = () => { if (!busy.value) emit('close') }
const trapFocus = event => {
  const items = [...panel.value.querySelectorAll('button:not(:disabled), select:not(:disabled)')]
  const first = items[0], last = items.at(-1)
  if (event.shiftKey && (document.activeElement === first || document.activeElement === panel.value)) { event.preventDefault(); last?.focus() }
  else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus() }
}
const save = async () => {
  if (busy.value || !status.value) return
  busy.value = true; error.value = ''
  try {
    const { data } = await axios.patch(props.item.status_update_url, { status: status.value }, { headers: { Accept: 'application/json' } })
    emit('saved', data.message || t('Status workflow berhasil diperbarui.'))
  } catch (e) {
    error.value = e.response?.data?.errors?.status?.[0] || e.response?.data?.message || t('Status gagal diperbarui. Periksa transisi dan izin Anda.')
  } finally { busy.value = false }
}
onMounted(async () => { previousFocus = document.activeElement; previousOverflow = document.body.style.overflow; document.body.style.overflow = 'hidden'; await nextTick(); panel.value?.focus() })
onBeforeUnmount(() => { document.body.style.overflow = previousOverflow; previousFocus?.focus() })
</script>
