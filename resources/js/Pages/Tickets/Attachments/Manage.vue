<template>
  <div class="mx-auto max-w-4xl space-y-6 px-4 py-8 lg:px-8">
    <header class="flex flex-col gap-3">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Kelola Lampiran</h1>
        <p class="text-sm text-slate-500 dark:text-slate-300">Unggah lampiran baru atau hapus lampiran yang tidak relevan.</p>
      </div>
    </header>

    <section class="rounded-3xl border border-slate-200 bg-white/80 p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900/70 md:p-7">
      <header class="flex items-center justify-between">
        <div>
          <h2 class="text-lg font-semibold text-slate-800 dark:text-slate-200">Lampiran Saat Ini</h2>
          <p class="text-xs text-slate-400">Ticket: {{ ticket.title }}</p>
        </div>
        <button
          type="button"
          class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-3 py-1.5 text-xs text-slate-600 transition hover:bg-slate-100 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800"
          @click="refresh"
        >
          <span class="material-icons text-sm">refresh</span>
          Segarkan
        </button>
      </header>

      <div v-if="ticket.attachments?.length" class="mt-4 space-y-3">
        <article v-for="item in ticket.attachments" :key="item.id" class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200/70 px-4 py-3 dark:border-slate-700/60">
          <div class="flex flex-col">
            <div class="flex items-center gap-2 text-sm font-medium text-slate-700 dark:text-slate-200">
              <span class="material-icons text-base text-slate-400">attach_file</span>
              {{ item.name }}
              <span v-if="item.size" class="text-xs font-normal text-slate-400">({{ formatSize(item.size) }})</span>
            </div>
            <div class="text-xs text-slate-500 dark:text-slate-400">
              <a :href="item.view_url" target="_blank" class="font-semibold text-blue-600 hover:underline dark:text-blue-400">Lihat</a>
              <span class="mx-1 text-slate-400">•</span>
              <a :href="item.download_url" class="font-semibold text-blue-600 hover:underline dark:text-blue-400">Unduh</a>
            </div>
          </div>
          <button
            type="button"
            class="inline-flex items-center gap-1 rounded-full border border-red-200 px-3 py-1 text-xs font-semibold text-red-500 transition hover:bg-red-50 disabled:opacity-60 dark:border-red-500/40 dark:hover:bg-red-500/10"
            :disabled="deletingId === item.id"
            @click="destroyAttachment(item)"
          >
            <span class="material-icons text-sm" v-if="deletingId === item.id">hourglass_top</span>
            <span v-else class="material-icons text-sm">delete</span>
            Hapus
          </button>
        </article>
      </div>
      <p v-else class="mt-4 rounded-lg bg-slate-100 px-4 py-3 text-sm text-slate-500 dark:bg-slate-800/60 dark:text-slate-300">Belum ada lampiran.</p>
    </section>

    <form class="space-y-5 rounded-3xl border border-slate-200 bg-white/80 p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900/70 md:p-7" @submit.prevent="submit">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-lg font-semibold text-slate-800 dark:text-slate-200">Unggah Lampiran Baru</h2>
          <p class="text-xs text-slate-400">Pilih satu atau beberapa file untuk ditambahkan.</p>
        </div>
        <span v-if="attachmentIds.length" class="text-xs text-blue-500">{{ attachmentIds.length }} file siap diunggah</span>
      </div>
      <FileUploaderPond multiple @uploaded="handleUploaded" @removed="handleRemoved" @error="handleUploadError" />
      <p v-if="form.errors.attachments" class="text-xs text-red-500">{{ form.errors.attachments }}</p>
      <p v-if="uploadError" class="text-xs text-red-500">{{ uploadError }}</p>

      <div class="mt-3 flex flex-wrap items-center justify-end gap-3 pb-3 pt-2">
        <button
          type="button"
          class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-4 py-2 text-sm text-slate-600 transition hover:bg-slate-100 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800"
          @click="resetUploads"
        >
          Reset
        </button>
        <button
          type="submit"
          class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 via-indigo-500 to-purple-500 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/30 transition hover:shadow-xl disabled:cursor-not-allowed disabled:opacity-70"
          :disabled="form.processing || !attachmentIds.length"
        >
          <span class="material-icons text-base" v-if="form.processing">hourglass_top</span>
          <span v-else class="material-icons text-base">upload_file</span>
          Simpan Lampiran
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import FileUploaderPond from '@/Components/FileUploaderPond.vue';

const props = defineProps({
  ticket: { type: Object, required: true },
});

const form = useForm({ attachments: [] });
const attachmentIds = ref([]);
const uploadError = ref('');
const deletingId = ref(null);

function handleUploaded(payload) {
  uploadError.value = '';
  const id = payload?.id ?? null;
  if (!id) return;
  if (!attachmentIds.value.includes(id)) {
    attachmentIds.value.push(id);
  }
  form.attachments = [...attachmentIds.value];
}

function handleRemoved(id) {
  attachmentIds.value = attachmentIds.value.filter(value => value !== id);
  form.attachments = [...attachmentIds.value];
}

function handleUploadError(error) {
  if (typeof error === 'string') {
    uploadError.value = error;
  } else if (error?.response?.data?.message) {
    uploadError.value = error.response.data.message;
  } else {
    uploadError.value = 'Gagal mengunggah file.';
  }
}

function resetUploads() {
  attachmentIds.value = [];
  form.attachments = [];
  uploadError.value = '';
}

function submit() {
  uploadError.value = '';
  form.put(route('tickets.attachments.update', { ticket: props.ticket.id }), {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      resetUploads();
      refresh();
    },
  });
}

function refresh() {
  router.reload({ only: ['ticket'] });
}

function destroyAttachment(item) {
  if (!item?.delete_url) return;
  deletingId.value = item.id;
  router.delete(item.delete_url, {
    preserveScroll: true,
    onFinish: () => {
      deletingId.value = null;
    },
    onSuccess: () => refresh(),
  });
}

const formatSize = size => {
  if (!size) return '';
  const value = Number(size);
  if (Number.isNaN(value)) return '';
  const kb = value / 1024;
  if (kb < 1024) return `${kb.toFixed(0)} KB`;
  const mb = kb / 1024;
  return `${mb.toFixed(1)} MB`;
};
</script>

<style scoped>
.material-icons {
  font-size: inherit;
}
</style>
