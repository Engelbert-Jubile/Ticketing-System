<template>
  <aside v-if="draft.available.value || draft.message.value" class="my-4 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-100">
    <div v-if="draft.available.value" class="flex flex-wrap items-center gap-3">
      <span>Draft sebelumnya tersedia. Penugasan dan lampiran perlu dipilih ulang setelah pemulihan.</span>
      <button type="button" class="rounded-lg bg-blue-600 px-3 py-2 text-white" @click="draft.restore">Pulihkan draft</button>
      <button type="button" class="rounded-lg border border-blue-300 px-3 py-2" @click="draft.clear">Buang draft</button>
    </div>
    <p v-else role="status">{{ draft.message.value }}</p>
  </aside>
</template>
<script setup>
defineProps({ draft: { type: Object, required: true } })
</script>
