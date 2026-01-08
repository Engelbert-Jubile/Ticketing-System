<template>
  <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 lg:px-6">
    <header class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">Tickets</h1>
        <p class="text-sm text-slate-500 dark:text-slate-300">Kelola dan telusuri daftar ticket terbaru.</p>
      </div>
      <div class="flex flex-wrap gap-2">
        <a :href="downloadUrl" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-blue-600 transition hover:bg-blue-50 dark:border-slate-600 dark:bg-slate-800 dark:text-blue-300 dark:hover:bg-slate-700">
          <span class="material-icons text-[18px]">picture_as_pdf</span>
          Download PDF
        </a>
        <Link :href="createUrl" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-blue-700">
          <span class="material-icons text-[18px]">add</span>
          Buat Ticket
        </Link>
      </div>
    </header>

    <section class="space-y-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
      <form class="flex flex-col gap-3 sm:flex-row sm:items-center" @submit.prevent="submit">
        <input
          v-model="form.q"
          type="search"
          placeholder="Cari ticket"
          class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-blue-500 focus:outline-none dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
        />
        <select
          v-model="form.status"
          class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-blue-500 focus:outline-none dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 sm:w-48"
        >
          <option value="">Semua status</option>
          <option v-for="option in statusOptions" :key="option.value" :value="option.value">
            {{ option.label }}
          </option>
        </select>
        <div class="flex gap-2">
          <button type="submit" class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-blue-700">
            Terapkan
          </button>
          <button type="button" class="inline-flex items-center rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-600 hover:bg-slate-100 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800" @click="reset">
            Reset
          </button>
        </div>
      </form>

      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
          <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800 dark:text-slate-300">
            <tr>
              <th class="px-4 py-3">Ticket</th>
              <th class="px-4 py-3">Status</th>
              <th class="px-4 py-3">Prioritas</th>
              <th class="px-4 py-3">Jenis</th>
              <th class="px-4 py-3">Jatuh Tempo</th>
              <th class="px-4 py-3">Diperbarui</th>
              <th class="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-700 dark:bg-slate-900/70">
            <tr v-for="ticket in tickets.data" :key="ticket.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
              <td class="px-4 py-4 align-top">
                <div class="font-semibold text-slate-800 dark:text-slate-100">
                  <Link :href="ticket.links.show" class="text-blue-600 hover:underline dark:text-blue-400">{{ ticket.title }}</Link>
                </div>
                <div class="text-xs text-slate-500">
                  {{ ticket.ticket_no || '—' }}
                  <span v-if="ticket.requester" class="ml-2">· {{ ticket.requester.name }}</span>
                </div>
              </td>
              <td class="px-4 py-4 align-top">
              <StatusPill :status="ticket.status" :label="ticket.status_label" size="sm" />
                <div v-if="ticket.status_id_label" class="text-xs text-slate-400">{{ ticket.status_id_label }}</div>
              </td>
              <td class="px-4 py-4 align-top capitalize text-slate-600 dark:text-slate-200">{{ ticket.priority ?? '—' }}</td>
              <td class="px-4 py-4 align-top capitalize text-slate-600 dark:text-slate-200">{{ ticket.type ?? '—' }}</td>
              <td class="px-4 py-4 align-top text-slate-600 dark:text-slate-200">{{ formatDate(ticket.due_at) }}</td>
              <td class="px-4 py-4 align-top text-slate-600 dark:text-slate-200">{{ formatDate(ticket.updated_at, true) }}</td>
              <td class="px-4 py-4 align-top">
                <div class="flex items-center justify-end gap-3">
                  <Link :href="ticket.links.edit" class="text-sm text-blue-600 hover:underline dark:text-blue-300">Edit</Link>
                  <button
                    v-if="ticket.links.delete"
                    type="button"
                    class="inline-flex items-center gap-1 text-sm font-semibold text-red-600 hover:text-red-700 disabled:opacity-60 dark:text-red-300 dark:hover:text-red-200"
                    :disabled="deletingId === ticket.id"
                    @click.stop="destroyTicket(ticket)"
                  >
                    <span class="material-icons text-base" aria-hidden="true">{{ deletingId === ticket.id ? 'hourglass_top' : 'delete' }}</span>
                    Hapus
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!tickets.data.length">
              <td colspan="7" class="px-4 py-6 text-center text-slate-500">Tidak ada ticket yang cocok.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <nav v-if="tickets.links?.length" class="flex flex-wrap items-center justify-end gap-2">
        <button
          v-for="link in tickets.links"
          :key="link.label"
          type="button"
          :class="[
            'rounded-lg border px-3 py-1.5 text-sm transition',
            link.active
              ? 'border-blue-500 bg-blue-500 text-white'
              : link.url
                ? 'border-slate-300 bg-white text-slate-600 hover:bg-slate-100 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200'
                : 'border-slate-200 bg-slate-100 text-slate-400 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-500',
          ]"
          :disabled="!link.url"
          @click="go(link.url)"
          v-html="link.label"
        />
      </nav>
    </section>
  </div>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import StatusPill from '@/Components/StatusPill.vue';

const props = defineProps({
  tickets: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
  statusOptions: { type: Array, default: () => [] },
});

const form = reactive({
  q: props.filters.q ?? '',
  status: props.filters.status ?? '',
});

const deletingId = ref(null);

const downloadParams = computed(() => {
  const params = {};
  if (props.filters.q) params.q = props.filters.q;
  if (props.filters.status) params.status = props.filters.status;
  return params;
});

const localePrefix = () => {
  if (typeof window === 'undefined') return '';
  const match = window.location.pathname.match(/^\/(en|id)\b/);
  return match ? `/${match[1]}` : '';
};

const indexUrl = `${localePrefix()}/dashboard/tickets`;
const createUrl = `${localePrefix()}/dashboard/tickets/create`;
const downloadUrl = computed(() => buildDownloadUrl(`${localePrefix()}/dashboard/tickets/report/download`, downloadParams.value));

function applyFilter(params) {
  router.get(indexUrl, params, {
    preserveState: true,
    replace: true,
  });
}

function submit() {
  applyFilter({
    q: form.q || undefined,
    status: form.status || undefined,
  });
}

function reset() {
  form.q = '';
  form.status = '';
  applyFilter({});
}

function go(url) {
  if (!url) return;
  router.visit(url, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
}

function buildDownloadUrl(base, params) {
  const payload = Object.fromEntries(
    Object.entries(params || {}).filter(([, value]) => value !== undefined && value !== null && value !== '')
  );
  const query = new URLSearchParams(payload).toString();
  return query ? `${base}?${query}` : base;
}

function destroyTicket(ticket) {
  if (!ticket?.links?.delete) return;
  if (!window.confirm(`Yakin ingin menghapus ticket "${ticket.title}"?`)) return;

  deletingId.value = ticket.id;
  router.delete(ticket.links.delete, {
    preserveScroll: true,
    onFinish: () => {
      deletingId.value = null;
    },
  });
}

function formatDate(value, withTime = false) {
  if (!value) return '—';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    return '—';
  }
  const options = withTime
    ? { dateStyle: 'medium', timeStyle: 'short' }
    : { dateStyle: 'medium' };
  return new Intl.DateTimeFormat('id-ID', options).format(date);
}
</script>
