<template>
  <div class="space-y-2">
    <div
      v-for="attachment in normalizedAttachments"
      :key="attachment.key"
      class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs dark:border-slate-700 dark:bg-slate-800/60"
    >
      <div>
        <div class="text-sm font-semibold text-slate-800 dark:text-slate-100">
          {{ attachment.name || 'Lampiran ticket' }}
        </div>
        <div v-if="attachment.sizeLabel" class="text-xs text-slate-400">{{ attachment.sizeLabel }}</div>
      </div>
      <div class="flex items-center gap-2">
        <a
          v-if="attachment.view_url"
          :href="attachment.view_url"
          target="_blank"
          class="inline-flex items-center gap-1 rounded-lg border border-blue-200 px-3 py-1 text-xs font-semibold text-blue-600 transition hover:bg-blue-50 dark:border-blue-400/40 dark:text-blue-200 dark:hover:bg-blue-500/10"
        >
          Lihat
        </a>
        <a
          v-if="attachment.download_url"
          :href="attachment.download_url"
          class="inline-flex items-center gap-1 rounded-lg border border-slate-300 px-3 py-1 text-xs font-semibold text-slate-600 transition hover:bg-slate-100 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800"
        >
          Unduh
        </a>
      </div>
    </div>
    <p v-if="!normalizedAttachments.length" class="text-xs text-slate-400">
      {{ emptyText }}
    </p>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  attachments: { type: Array, default: () => [] },
  emptyText: { type: String, default: 'Lampiran ticket belum dikirim ke halaman ini.' },
});

const normalizedAttachments = computed(() => (props.attachments || []).map((item, index) => ({
  key: item.id ?? `${item.name}-${index}`,
  name: item.name ?? item.original_name ?? 'Lampiran ticket',
  sizeLabel: formatSize(item.size),
  view_url: item.view_url ?? item.viewUrl ?? null,
  download_url: item.download_url ?? item.downloadUrl ?? null,
})));

function formatSize(size) {
  if (!size || Number.isNaN(Number(size))) return '';
  const kb = Number(size) / 1024;
  if (kb < 1024) return `${kb.toFixed(0)} KB`;
  const mb = kb / 1024;
  if (mb < 1024) return `${mb.toFixed(1)} MB`;
  const gb = mb / 1024;
  return `${gb.toFixed(2)} GB`;
}
</script>
