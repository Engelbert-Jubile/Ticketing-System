<template>
  <fieldset class="space-y-3 rounded-xl border border-indigo-200 p-4 dark:border-indigo-800">
    <legend class="px-2 text-sm font-semibold">{{ $t('Bukti konfirmasi') }}</legend>
    <label class="block text-sm font-medium">
      {{ $t('Keterangan') }}
      <textarea :value="note" :disabled="disabled" required maxlength="4000" rows="3"
        class="mt-1 block w-full rounded-lg border-slate-300 bg-transparent dark:border-slate-600"
        @input="$emit('update:note', $event.target.value)" />
    </label>
    <p v-if="errors.confirmation_note" role="alert" class="text-sm text-rose-600">{{ errors.confirmation_note }}</p>
    <FileUploaderPond multiple :disabled="disabled"
      :allowed-extensions="['pdf', 'png', 'jpg', 'jpeg', 'doc', 'docx', 'xls', 'xlsx', 'zip', 'rar', 'txt', 'csv']"
      @uploaded="uploaded" @removed="removed" @error="uploadError = true" />
    <p v-if="errors.evidence || uploadError" role="alert" class="text-sm text-rose-600">
      {{ errors.evidence || $t('Lampiran bukti gagal disimpan.') }}
    </p>
  </fieldset>
</template>

<script setup>
import { ref } from 'vue'
import FileUploaderPond from './FileUploaderPond.vue'

const props = defineProps({
  note: { type: String, default: '' },
  evidence: { type: Array, default: () => [] },
  disabled: Boolean,
  errors: { type: Object, default: () => ({}) },
})
const emit = defineEmits(['update:note', 'update:evidence'])
const uploadError = ref(false)

function uploaded({ id }) {
  if (id) {
    uploadError.value = false
    emit('update:evidence', [...new Set([...props.evidence, id])])
  }
}

function removed(id) {
  emit('update:evidence', props.evidence.filter(file => file !== id))
}
</script>
