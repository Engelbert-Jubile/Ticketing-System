<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-[2147483000] flex items-center justify-center bg-slate-950/70 p-4" @click.self="close" @keydown.esc.stop.prevent="close">
      <form ref="panel" role="dialog" aria-modal="true" aria-labelledby="workflow-status-title" tabindex="-1" class="max-h-[90dvh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-6 shadow-xl dark:bg-slate-900 dark:text-white" @submit.prevent="save" @keydown.tab="trapFocus">
        <div class="flex items-center justify-between gap-3"><h2 id="workflow-status-title" class="text-xl font-bold">{{ rejecting ? $t('Tolak') : $t('Update Status') }}</h2><button type="button" :disabled="busy" :aria-label="$t('Tutup modal')" @click="close">×</button></div>
        <p class="mt-4 font-mono text-xs text-cyan-600">{{ item.number }}</p><p class="mt-1 font-semibold">{{ item.title }}</p>
        <p class="mt-3 text-sm text-slate-500">{{ $t('Status saat ini:') }} {{ item.status_label }}</p>
        <label v-if="rejecting" class="mt-5 block text-sm font-semibold">
          {{ $t('Alasan penolakan') }}
          <textarea v-model="reason" required maxlength="4000" rows="4" :disabled="busy"
            class="mt-2 block w-full rounded-lg border-slate-300 bg-transparent dark:border-slate-600" />
        </label>
        <template v-else>
          <label class="mt-5 block text-sm font-semibold" for="workflow-status-select">{{ $t('Status tujuan') }}</label>
          <WorkflowSelect id="workflow-status-select" v-model="status" :options="item.status_options"
            :aria-label="$t('Status tujuan')" :placeholder="$t('Pilih transisi yang tersedia')"
            required :disabled="busy" class="mt-2" />
          <TicketConfirmationFields v-if="needsEvidence" v-model:note="note" v-model:evidence="evidence"
            class="mt-4" :disabled="busy" />
        </template>
        <p v-if="error" role="alert" class="mt-3 text-sm text-rose-600">{{ error }}</p>
        <div class="mt-6 flex justify-end gap-3"><button type="button" :disabled="busy" class="rounded-lg border px-4 py-2" @click="close">{{ $t('Batal') }}</button><SaveButton :disabled="busy || !canSave" :processing="busy">{{ rejecting ? $t('Tolak') : $t('Simpan Perubahan') }}</SaveButton></div>
      </form>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import axios from 'axios'
import { useI18n } from '../i18n'
import WorkflowSelect from './WorkflowSelect.vue'
import TicketConfirmationFields from './TicketConfirmationFields.vue'
const props = defineProps({ item: { type: Object, required: true }, rejecting: Boolean })
const emit = defineEmits(['close', 'saved'])
const { t } = useI18n()
const status = ref(''), busy = ref(false), error = ref(''), panel = ref(null)
const reason = ref('')
const note = ref('')
const evidence = ref([])
const needsEvidence = computed(() => !props.rejecting && props.item.type === 'ticket' && status.value === 'confirmation')
const canSave = computed(() => props.rejecting
  ? Boolean(reason.value.trim())
  : Boolean(status.value && (!needsEvidence.value || (note.value.trim() && evidence.value.length))))
let previousFocus, previousOverflow
const close = () => { if (!busy.value) emit('close') }
const trapFocus = event => {
  const items = [...panel.value.querySelectorAll('button:not(:disabled), select:not(:disabled), textarea:not(:disabled), input:not(:disabled)')]
  const first = items[0], last = items.at(-1)
  if (event.shiftKey && (document.activeElement === first || document.activeElement === panel.value)) { event.preventDefault(); last?.focus() }
  else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus() }
}
const save = async () => {
  if (busy.value || !canSave.value) return
  busy.value = true; error.value = ''
  try {
    const payload = props.rejecting ? { rejection_reason: reason.value.trim() } : { status: status.value }
    if (needsEvidence.value) {
      payload.confirmation_note = note.value
      payload.evidence = evidence.value
    }
    const { data } = await axios.patch(props.item.status_update_url, payload, { headers: { Accept: 'application/json' } })
    emit('saved', data.message || t('Status workflow berhasil diperbarui.'))
  } catch (e) {
    error.value = Object.values(e.response?.data?.errors ?? {}).flat().join(' ') || e.response?.data?.message || t('Status gagal diperbarui. Periksa transisi dan izin Anda.')
  } finally { busy.value = false }
}
onMounted(async () => { previousFocus = document.activeElement; previousOverflow = document.body.style.overflow; document.body.style.overflow = 'hidden'; await nextTick(); panel.value?.focus() })
onBeforeUnmount(() => { document.body.style.overflow = previousOverflow; previousFocus?.focus() })
</script>
