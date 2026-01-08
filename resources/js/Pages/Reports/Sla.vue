<template>
  <div class="mx-auto max-w-7xl space-y-8 px-4 py-6 lg:px-6">
    <header class="space-y-4">
      <div>
        <h1 class="text-3xl font-bold text-slate-900 dark:text-slate-100">Service Level Agreement Reports</h1>
        <p class="text-sm text-slate-500 dark:text-slate-300">Pantau performa SLA ticket, task, dan project secara menyeluruh.</p>
      </div>
    </header>

    <section class="flex flex-wrap items-center justify-between gap-3">
      <div class="flex flex-wrap gap-2">
        <button
          v-for="option in typeOptions"
          :key="option.value"
          type="button"
          class="rounded-full px-4 py-2 text-sm font-semibold transition"
          :class="form.type === option.value
            ? 'bg-blue-600 text-white shadow'
            : 'bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700'"
          @click="changeType(option.value)"
        >
          {{ option.label }}
        </button>
      </div>

      <div class="flex flex-wrap gap-2">
        <a
          :href="downloadUrl('csv')"
          class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
        >
          <span class="material-icons text-base">download</span>
          Download CSV
        </a>
        <a
          :href="downloadUrl('pdf')"
          class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-blue-600 transition hover:bg-blue-50 dark:border-slate-700 dark:bg-slate-900 dark:text-blue-300 dark:hover:bg-slate-800"
        >
          <span class="material-icons text-base">picture_as_pdf</span>
          Download PDF
        </a>
      </div>
    </section>

    <form class="grid gap-3 rounded-3xl border border-slate-200 bg-white p-6 text-sm shadow-sm dark:border-slate-700 dark:bg-slate-900/80" @submit.prevent="applyFilters">
      <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <div>
          <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">Dari</label>
          <DatePickerFlatpickr v-model="form.from" class="mt-1" :config="calendarConfig" />
        </div>
        <div>
          <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">Sampai</label>
          <DatePickerFlatpickr v-model="form.to" class="mt-1" :config="calendarConfig" />
        </div>
        <div>
          <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">Status SLA</label>
          <div class="mt-1">
            <FancySelect v-model="form.sla_status" :options="statusSelectOptions" accent="subtle" />
          </div>
        </div>
        <div>
          <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">Pencarian</label>
          <input
            v-model="form.q"
            type="text"
            placeholder="Cari judul / nomor"
            class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-blue-500 focus:outline-none dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
          />
        </div>
        <div>
          <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">Per halaman</label>
          <div class="mt-1">
            <FancySelect v-model="form.per_page" :options="perPageChoices" accent="subtle" />
          </div>
        </div>
      </div>

      <div class="mt-4 flex flex-wrap items-center justify-end gap-3">
        <button type="button" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-100 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800" @click="resetFilters">
          Reset
        </button>
        <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900">
          <span class="material-icons text-base">filter_alt</span>
          Terapkan
        </button>
      </div>
    </form>

    <section class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
      <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">Total Item</p>
        <p class="mt-1 text-3xl font-bold text-slate-900 dark:text-slate-100">{{ stats.total ?? 0 }}</p>
      </div>
      <div class="rounded-3xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm dark:border-emerald-400/50 dark:bg-emerald-500/15">
        <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-100">SLA Tercapai</p>
        <p class="mt-1 text-3xl font-bold text-emerald-900 dark:text-emerald-50">{{ stats.met ?? 0 }}</p>
      </div>
      <div class="rounded-3xl border border-amber-200 bg-amber-50 p-5 shadow-sm dark:border-amber-700 dark:bg-amber-900/40">
        <p class="text-xs font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-200">Dalam Proses</p>
        <p class="mt-1 text-3xl font-bold text-amber-900 dark:text-amber-100">{{ stats.pending ?? 0 }}</p>
      </div>
      <div class="rounded-3xl border border-rose-200 bg-rose-50 p-5 shadow-sm dark:border-rose-400/50 dark:bg-rose-500/15">
        <p class="text-xs font-semibold uppercase tracking-wide text-rose-700 dark:text-rose-100">Lewat SLA</p>
        <p class="mt-1 text-3xl font-bold text-rose-900 dark:text-rose-50">{{ stats.breached ?? 0 }}</p>
      </div>
    </section>

    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
      <header class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-5 py-3 dark:border-slate-700 dark:bg-slate-800">
        <h2 class="text-sm font-semibold text-slate-700 dark:text-slate-200">Daftar SLA</h2>
        <span v-if="loadingRecords" class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-300">
          <span class="material-icons animate-spin text-sm">progress_activity</span>
          Memuat data...
        </span>
      </header>

      <template v-if="form.type === 'ticket_work'">
        <div v-if="!loadingRecords && !workEntries.length" class="px-5 py-10 text-center text-sm text-slate-500 dark:text-slate-300">
          Tidak ada data SLA untuk filter saat ini.
        </div>
        <div v-else class="space-y-5 px-5 py-5">
          <article
            v-for="entry in workEntries"
            :key="entry.id"
            class="rounded-2xl border border-slate-200 bg-slate-50/70 p-5 shadow-sm transition dark:border-slate-700 dark:bg-slate-900/40"
          >
            <div class="flex flex-wrap items-start justify-between gap-3">
              <div class="min-w-[240px] space-y-2">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">Ticket</p>
                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">
                  <span>{{ entry.ticket.number ?? '—' }}</span>
                  <span v-if="entry.ticket.title" class="text-slate-500 dark:text-slate-300"> · {{ entry.ticket.title }}</span>
                </h3>
                <div class="mt-2 flex flex-wrap gap-2 text-xs text-slate-600 dark:text-slate-300">
                  <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 dark:bg-slate-800">Status: {{ entry.ticket.status ?? '—' }}</span>
                  <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 dark:bg-slate-800">Assignee: {{ entry.ticket.assignee ?? '—' }}</span>
                  <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 dark:bg-slate-800">Deadline: {{ displayDate(entry.ticket.deadline) }}</span>
                </div>
              </div>
              <div class="flex flex-col items-end gap-2 text-right">
                <StatusPill variant="sla" size="sm" :status="entry.ticket.sla?.status" :label="entry.ticket.sla?.label || '-'" />
                <span class="text-xs text-slate-500 dark:text-slate-300">{{ entry.ticket.sla?.delta_human ?? '—' }}</span>
                <div class="flex gap-2 text-xs">
                  <a
                    v-if="entry.detail_pdf_url || entry.ticket.links?.pdf"
                    :href="entry.detail_pdf_url || entry.ticket.links?.pdf"
                    target="_blank"
                    rel="noopener"
                    class="action-btn action-btn--pdf"
                  >PDF</a>
                  <Link v-if="entry.ticket.links?.view" :href="entry.ticket.links.view" class="action-btn action-btn--view">View</Link>
                  <Link v-if="entry.ticket.links?.edit" :href="entry.ticket.links.edit" class="action-btn action-btn--edit">Edit</Link>
                </div>
              </div>
            </div>

            <details v-if="entry.tasks.length" class="mt-4 rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900/60">
              <summary class="cursor-pointer text-sm font-semibold text-slate-700 dark:text-slate-100">
                Task terkait ({{ entry.tasks.length }})
              </summary>
              <div class="mt-3 overflow-x-auto">
                <table class="min-w-full text-sm">
                  <thead>
                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">
                      <th class="px-2 py-1">Task</th>
                      <th class="px-2 py-1">Assignee</th>
                      <th class="px-2 py-1">Status</th>
                      <th class="px-2 py-1">Deadline</th>
                      <th class="px-2 py-1">SLA</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr
                      v-for="task in entry.tasks"
                      :key="task.id ?? task.number"
                      class="border-t border-slate-200 text-slate-700 dark:border-slate-700 dark:text-slate-200"
                    >
                      <td class="px-2 py-1 font-semibold">{{ task.number ?? '—' }}</td>
                      <td class="px-2 py-1">{{ task.assignee ?? '—' }}</td>
                      <td class="px-2 py-1">{{ task.status ?? '—' }}</td>
                      <td class="px-2 py-1">{{ displayDate(task.deadline) }}</td>
                      <td class="px-2 py-1">
                        <StatusPill variant="sla" size="sm" :status="task.sla?.status" :label="task.sla?.label || '-'" />
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </details>

            <details v-if="entry.project" class="mt-4 rounded-xl border border-indigo-200 bg-indigo-50 p-4 dark:border-indigo-700 dark:bg-indigo-900/30">
              <summary class="cursor-pointer text-sm font-semibold text-indigo-700 dark:text-indigo-200">Project terkait</summary>
              <dl class="mt-3 grid gap-3 text-sm text-slate-700 dark:text-slate-200 md:grid-cols-2">
                <div>
                  <dt class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Project</dt>
                  <dd class="font-semibold">{{ entry.project.number ?? '—' }} · {{ entry.project.title ?? '—' }}</dd>
                </div>
                <div>
                  <dt class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Status</dt>
                  <dd>{{ entry.project.status ?? '—' }}</dd>
                </div>
                <div>
                  <dt class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Deadline</dt>
                  <dd>{{ displayDate(entry.project.deadline) }}</dd>
                </div>
                <div>
                  <dt class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">SLA</dt>
                  <dd>
                    {{ entry.project.sla?.label ?? '—' }}
                    <span class="block text-xs text-slate-500 dark:text-slate-300">{{ entry.project.sla?.delta_human ?? '—' }}</span>
                  </dd>
                </div>
              </dl>
            </details>
          </article>
        </div>
      </template>

      <template v-else>
        <div v-if="!loadingRecords && !rows.length" class="px-5 py-10 text-center text-sm text-slate-500 dark:text-slate-300">
          Tidak ada data SLA untuk filter saat ini.
        </div>

        <div v-else class="overflow-x-auto">
          <table class="sla-table min-w-full text-sm">
            <thead class="bg-slate-200/90 text-xs font-semibold uppercase tracking-wide text-slate-700 dark:bg-slate-800/95 dark:text-slate-100 border-b border-slate-300 dark:border-slate-700">
              <tr>
                <th v-for="column in columns" :key="column.key" class="px-4 py-3 text-left">{{ column.label }}</th>
                <th class="px-4 py-3 text-center">Aksi</th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-slate-900">
              <tr v-for="row in rows" :key="row.id" class="border-b border-slate-200/80 transition hover:bg-slate-50 dark:border-slate-700/50 dark:hover:bg-slate-800/60">
                <td v-for="column in columns" :key="column.key" class="px-4 py-3 align-top">
                  <component :is="row.render?.[column.key] ?? 'span'" v-bind="row.renderProps?.[column.key]">{{ row.values[column.key] }}</component>
                </td>
                <td class="px-4 py-3 text-center">
                  <div class="flex justify-center gap-2 text-xs">
                    <a
                      v-if="row.links.pdf"
                      :href="row.links.pdf"
                      target="_blank"
                      rel="noopener"
                      class="action-btn action-btn--pdf"
                    >PDF</a>
                    <Link v-if="row.links.view" :href="row.links.view" class="action-btn action-btn--view">View</Link>
                    <Link v-if="row.links.edit" :href="row.links.edit" class="action-btn action-btn--edit">Edit</Link>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>

      <footer v-if="records?.links?.length > 3" class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-200 bg-white px-5 py-3 dark:border-slate-700 dark:bg-slate-900">
        <button
          v-for="link in records.links"
          :key="link.label"
          type="button"
          class="rounded-lg border px-3 py-1.5 text-sm transition"
          :class="link.active
            ? 'border-blue-500 bg-blue-500 text-white shadow-sm'
            : link.url
              ? 'border-slate-300 bg-white text-slate-600 hover:bg-slate-100 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800'
              : 'border-slate-200 bg-slate-100 text-slate-400 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-500'"
          :disabled="!link.url"
          @click="changePage(link)"
          v-html="link.label"
        />
      </footer>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import DatePickerFlatpickr from '@/Components/DatePickerFlatpickr.vue';
import StatusPill from '@/Components/StatusPill.vue';
import FancySelect from '@/Components/FancySelect.vue';

const props = defineProps({
  type: { type: String, default: 'ticket' },
  filters: { type: Object, default: () => ({}) },
  stats: { type: Object, default: () => ({}) },
  availableTypes: { type: Array, default: () => [] },
  statusOptions: { type: Array, default: () => [] },
  downloadParams: { type: Object, default: () => ({}) },
  records: { type: Object, default: null },
});

const resolveStatusValue = status => {
  if (status && typeof status === 'object') {
    return status.value ?? status.raw ?? status.label ?? status.display ?? '';
  }
  return status ?? '';
};

const resolveStatusLabel = (status, fallback = '') => {
  if (status && typeof status === 'object') {
    return status.label ?? status.display ?? status.value ?? status.raw ?? fallback ?? '';
  }
  return fallback ?? status ?? '';
};

const formatStatusLabel = (value, fallback = '-') => {
  const label = displayText(value);
  return label === 'ƒ?"' ? fallback : label;
};

const slaPill = status => {
  return {
    status: status || '',
    variant: 'sla',
    size: 'sm',
  };
};

const statusPill = status => {
  return {
    status: status || '',
    size: 'sm',
  };
};

const calendarConfig = { dateFormat: 'd/m/Y', allowInput: true };
const perPageOptions = [15, 25, 50, 100];
const perPageChoices = computed(() => perPageOptions.map(per => ({ label: String(per), value: per })));
const statusSelectOptions = computed(() => props.statusOptions ?? []);

const form = useForm({
  type: props.type,
  from: props.filters.from || '',
  to: props.filters.to || '',
  sla_status: props.filters.sla_status || '',
  q: props.filters.q || '',
  per_page: Number(props.filters.per_page || 25),
});

watch(
  () => props.filters,
  value => {
    form.from = value?.from || '';
    form.to = value?.to || '';
    form.sla_status = value?.sla_status || '';
    form.q = value?.q || '';
    form.per_page = Number(value?.per_page || 25);
  }
);

watch(
  () => props.type,
  value => {
    form.type = value;
  }
);

const typeOptions = computed(() => props.availableTypes ?? []);
const stats = computed(() => props.stats ?? {});

const records = ref(props.records || null);
const loadingRecords = ref(false);

watch(
  () => props.records,
  value => {
    if (value !== undefined) {
      records.value = value;
      loadingRecords.value = false;
    }
  }
);

const columns = computed(() => {
  switch (form.type) {
    case 'task':
      return [
        { key: 'number', label: 'Task' },
        { key: 'title', label: 'Judul' },
        { key: 'status', label: 'Status' },
        { key: 'assignee', label: 'Assignee' },
        { key: 'ticket_no', label: 'Ticket' },
        { key: 'project_no', label: 'Project' },
        { key: 'deadline', label: 'Deadline' },
        { key: 'completed_at', label: 'Selesai' },
        { key: 'sla_label', label: 'SLA' },
        { key: 'sla_delta', label: 'Delta' },
      ];
    case 'project':
      return [
        { key: 'number', label: 'Project' },
        { key: 'title', label: 'Judul' },
        { key: 'status', label: 'Status' },
        { key: 'owner', label: 'Owner' },
        { key: 'ticket_no', label: 'Ticket' },
        { key: 'deadline', label: 'Deadline' },
        { key: 'completed_at', label: 'Selesai' },
        { key: 'sla_label', label: 'SLA' },
        { key: 'sla_delta', label: 'Delta' },
      ];
    case 'ticket_work':
      return [
        { key: 'ticket', label: 'Ticket' },
        { key: 'status', label: 'Status' },
        { key: 'assignee', label: 'Assignee' },
        { key: 'deadline', label: 'Deadline' },
        { key: 'sla_label', label: 'SLA Ticket' },
        { key: 'tasks_summary', label: 'Ringkasan Task' },
        { key: 'project_sla', label: 'SLA Project' },
      ];
    default:
      return [
        { key: 'number', label: 'Ticket' },
        { key: 'title', label: 'Judul' },
        { key: 'status', label: 'Status' },
        { key: 'priority', label: 'Prioritas' },
        { key: 'assignee', label: 'Assignee' },
        { key: 'deadline', label: 'Deadline' },
        { key: 'completed_at', label: 'Selesai' },
        { key: 'sla_label', label: 'SLA' },
        { key: 'sla_delta', label: 'Delta' },
      ];
  }
});

const rows = computed(() => {
  const data = records.value?.data ?? [];
  return data.map(item => buildRow(item));
});

const workEntries = computed(() => {
  if (form.type !== 'ticket_work') {
    return [];
  }

  const data = records.value?.data ?? [];
  return data.map((item, index) => {
    const ticket = item.ticket ?? {};
    const tasks = Array.isArray(item.tasks?.items) ? item.tasks.items : [];
    const project = item.project ?? null;

    return {
      id: ticket.id ?? ticket.number ?? `ticket-${index}`,
      ticket,
      tasks,
      project,
      detail_pdf_url: item.detail_pdf_url ?? ticket.detail_pdf_url ?? null,
    };
  });
});

const currentParams = computed(() => ({
  type: form.type,
  from: form.from || undefined,
  to: form.to || undefined,
  sla_status: form.sla_status || undefined,
  q: form.q || undefined,
  per_page: form.per_page,
}));

function localePrefix() {
  if (typeof window === 'undefined') return '';
  const match = window.location.pathname.match(/^\/(en|id)\b/);
  return match ? `/${match[1]}` : '';
}

function safeRoute(name, params = {}, absolute = false, fallback = '/') {
  if (typeof route === 'function') {
    try {
      return route(name, params, absolute);
    } catch (error) {
    }
  }
  return fallback;
}

function applyFilters() {
  const url = safeRoute('dashboard.sla', {}, false, `${localePrefix()}/dashboard/sla`);
  form.get(url, {
    preserveScroll: true,
    replace: true,
    onSuccess: () => {
      loadRecords({ page: 1 });
    },
  });
}

function resetFilters() {
  form.from = '';
  form.to = '';
  form.sla_status = '';
  form.q = '';
  form.per_page = 25;
  applyFilters();
}

function changeType(value) {
  if (value === form.type) return;
  form.type = value;
  applyFilters();
}

function changePage(link) {
  if (!link.url || link.active) return;
  const page = extractPage(link.url);
  loadRecords({ page });
}

function loadRecords(extra = {}) {
  loadingRecords.value = true;
  const params = { ...currentParams.value };
  if (extra.page) {
    params.page = extra.page;
  }

  const url = safeRoute('dashboard.sla', {}, false, `${localePrefix()}/dashboard/sla`);
  router.get(url, params, {
    only: ['records'],
    preserveState: true,
    preserveScroll: true,
    replace: true,
    onFinish: () => {
      loadingRecords.value = false;
    },
  });
}

function extractPage(url) {
  try {
    const query = new URL(url, window.location.origin).searchParams;
    return query.get('page') || undefined;
  } catch (error) {
    return undefined;
  }
}

const displayText = value => {
  if (value === null || value === undefined || value === '') {
    return '—';
  }

  if (typeof value === 'object') {
    if (value.display) {
      return value.display;
    }
    if (value.label) {
      return value.label;
    }
    if (value.value) {
      return value.value;
    }
    if (value.raw) {
      return value.raw;
    }
    return '—';
  }

  return value;
};

const displayDate = value => displayText(value);

function buildRow(item) {
  const baseLinks = item.links ?? { view: null, edit: null };
  const pdfLink = item.detail_pdf_url ?? null;
  const defaultLinks = { ...baseLinks };
  if (pdfLink) {
    defaultLinks.pdf = pdfLink;
  }

  if (form.type === 'task') {
    const statusLabel = formatStatusLabel(item.status);
    return {
      id: item.id,
      values: {
        number: item.number,
        title: item.title,
        status: statusLabel,
        assignee: item.assignee ?? '—',
        ticket_no: item.ticket_no ?? '—',
        project_no: item.project_no ?? '—',
        deadline: displayDate(item.deadline),
        completed_at: displayDate(item.completed_at),
        sla_label: item.sla?.label ?? '—',
        sla_delta: item.sla?.delta_human ?? '—',
      },
      render: {
        sla_label: StatusPill,
      },
      renderProps: {
        sla_label: slaPill(item.sla?.status),
      },
      links: defaultLinks,
    };
  }

  if (form.type === 'project') {
    const statusLabel = formatStatusLabel(item.status);
    return {
      id: item.id,
      values: {
        number: item.number,
        title: item.title,
        status: statusLabel,
        owner: item.owner ?? '—',
        ticket_no: item.ticket_no ?? '—',
        deadline: displayDate(item.deadline),
        completed_at: displayDate(item.completed_at),
        sla_label: item.sla?.label ?? '—',
        sla_delta: item.sla?.delta_human ?? '—',
      },
      render: {
        sla_label: StatusPill,
      },
      renderProps: {
        sla_label: slaPill(item.sla?.status),
      },
      links: defaultLinks,
    };
  }

  if (form.type === 'ticket_work') {
    const ticket = item.ticket ?? {};
    const tasksStats = item.tasks?.stats ?? {};
    const project = item.project ?? null;
    const ticketLinks = ticket.links ?? { view: null, edit: null };
    const statusLabel = formatStatusLabel(ticket.status);
    if (!ticketLinks.pdf && (item.detail_pdf_url || ticket.detail_pdf_url)) {
      ticketLinks.pdf = item.detail_pdf_url || ticket.detail_pdf_url;
    }
    return {
      id: ticket.id ?? ticket.number,
      values: {
        ticket: ticket.number ? `${ticket.number} · ${ticket.title}` : ticket.title ?? '—',
        status: statusLabel,
        assignee: ticket.assignee ?? '—',
        deadline: displayDate(ticket.deadline),
        sla_label: ticket.sla?.label ?? '—',
        tasks_summary: `Met ${tasksStats.met ?? 0} / Total ${tasksStats.total ?? 0}`,
        project_sla: project?.sla?.label ?? '—',
      },
      render: {
        sla_label: StatusPill,
        project_sla: StatusPill,
      },
      renderProps: {
        sla_label: slaPill(ticket.sla?.status),
        project_sla: slaPill(project?.sla?.status),
      },
      links: ticketLinks,
    };
  }

  return {
    id: item.id,
    values: {
      number: item.number,
      title: item.title,
      status: formatStatusLabel(item.status),
      priority: item.priority ?? '—',
      assignee: item.assignee ?? '—',
      deadline: displayDate(item.deadline),
      completed_at: displayDate(item.completed_at),
      sla_label: item.sla?.label ?? '—',
      sla_delta: item.sla?.delta_human ?? '—',
    },
    render: {
      sla_label: StatusPill,
    },
    renderProps: {
      sla_label: slaPill(item.sla?.status),
    },
    links: defaultLinks,
  };
}

function downloadUrl(format) {
  const payload = { ...props.downloadParams, format };
  const filtered = Object.fromEntries(
    Object.entries(payload).filter(([, value]) => value !== undefined && value !== null && value !== '')
  );

  try {
    if (typeof route === 'function') {
      const ziggy = route();
      if (ziggy?.has?.('sla.download')) {
        return route('sla.download', filtered, false);
      }
    }
  } catch (error) {
    // ignore and fall back to manual URL construction
  }

  const query = new URLSearchParams(filtered).toString();
  const prefix = localePrefix();
  return `${prefix}/dashboard/sla/download${query ? `?${query}` : ''}`;
}

onMounted(() => {
  if (!records.value) {
    loadRecords();
  }
});
</script>

<style scoped>
.material-icons {
  font-size: inherit;
}

.action-btn {
  display: inline-flex;
  align-items: center;
  border-radius: 0.75rem;
  padding: 0.35rem 0.85rem;
  font-weight: 600;
  transition: background-color 0.2s ease, color 0.2s ease;
}

.action-btn--pdf {
  border: 1px solid rgba(15, 23, 42, 0.2);
  color: #0f172a;
}

.action-btn--pdf:hover {
  background: rgba(37, 99, 235, 0.08);
  color: #2563eb;
}

.dark .action-btn--pdf {
  border-color: rgba(248, 113, 113, 0.35);
  color: #fecdd3;
}

.dark .action-btn--pdf:hover {
  background: rgba(248, 113, 113, 0.12);
  color: #ffe4e6;
}

.action-btn--view {
  border: 1px solid rgba(37, 99, 235, 0.25);
  color: #2563eb;
}

.action-btn--view:hover {
  background: rgba(37, 99, 235, 0.1);
}

.action-btn--edit {
  border: 1px solid rgba(16, 185, 129, 0.3);
  color: #059669;
}

.action-btn--edit:hover {
  background: rgba(16, 185, 129, 0.1);
}

.dark .action-btn--view {
  border-color: rgba(96, 165, 250, 0.3);
  color: #bfdbfe;
}

.dark .action-btn--edit {
  border-color: rgba(110, 231, 183, 0.3);
  color: #bbf7d0;
}

.sla-table {
  border-collapse: collapse;
}

.light .sla-table thead th {
  color: #0f172a;
}

.light .sla-table td > span:not(.status-pill) {
  color: #0f172a;
}

.dark .sla-table thead th {
  color: #e2e8f0;
}

.dark .sla-table td > span:not(.status-pill) {
  color: #e2e8f0;
}
</style>
