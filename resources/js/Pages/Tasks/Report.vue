<template>
  <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 lg:px-6">
    <header class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div class="space-y-1">
        <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">Task Report</h1>
        <p class="text-sm text-slate-500 dark:text-slate-300">Analisis task berdasarkan sumber tiket maupun mandiri dengan tampilan yang ringkas dan konsisten.</p>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <a :href="downloadUrl" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-blue-600 transition hover:bg-blue-50 dark:border-slate-600 dark:bg-slate-900 dark:text-blue-300 dark:hover:bg-slate-800">
          <span class="material-icons text-base">picture_as_pdf</span>
          Download PDF
        </a>
        <Link :href="route('tasks.create')" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-blue-700">
          <span class="material-icons text-base">add_task</span>
          Task Baru
        </Link>
      </div>
    </header>

    <transition name="fade">
      <div v-if="flashSuccess" class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 dark:border-emerald-500/40 dark:bg-emerald-900/40 dark:text-emerald-100">
        {{ flashSuccess }}
      </div>
    </transition>

    <form class="grid gap-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900" @submit.prevent="applyFilters">
      <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <div>
          <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">Kata Kunci</label>
          <div class="relative mt-1">
            <span class="material-icons pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">search</span>
            <input
              v-model="form.q"
              type="search"
              placeholder="Judul, deskripsi, atau nomor task"
              class="w-full rounded-lg border border-slate-300 bg-white py-2 pl-10 pr-3 text-sm text-slate-700 focus:border-blue-500 focus:outline-none dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
            />
          </div>
        </div>
        <div>
          <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">Status</label>
          <div class="mt-1 w-full">
            <FancySelect v-model="form.status" :options="statusSelectOptions" accent="subtle" />
          </div>
        </div>
        <div>
          <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">Dari</label>
          <div class="mt-1">
            <DatePickerFlatpickr v-model="form.from" :config="calendarConfig" placeholder="dd/mm/yyyy" />
          </div>
        </div>
        <div>
          <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">Sampai</label>
          <div class="mt-1">
            <DatePickerFlatpickr v-model="form.to" :config="calendarConfig" placeholder="dd/mm/yyyy" />
          </div>
        </div>
      </div>
      <div class="flex flex-wrap justify-end gap-2">
        <button
          type="button"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-600 hover:bg-slate-100 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800"
          :disabled="submitting"
          @click="resetFilters"
        >
          Reset
        </button>
        <button
          type="submit"
          class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-blue-700 disabled:opacity-70"
          :disabled="submitting"
        >
          <span class="material-icons text-base">{{ submitting ? 'hourglass_top' : 'filter_alt' }}</span>
          Terapkan
        </button>
      </div>
    </form>

    <section class="space-y-4">
      <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
        <button
          type="button"
          class="flex w-full items-center justify-between gap-3 bg-blue-100 px-6 py-4 text-left text-blue-900 transition dark:bg-blue-900/30 dark:text-blue-100"
          :aria-expanded="accordion.ticket"
          @click="toggleSection('ticket')"
        >
          <div>
            <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Task dari Ticket</h2>
            <p class="text-sm text-slate-500 dark:text-slate-300">{{ ticketSummary.total }} task · {{ ticketSummary.in_progress }} in progress · {{ ticketSummary.done }} selesai</p>
          </div>
          <span class="material-icons text-blue-700 transition-transform duration-200 dark:text-blue-300" :class="{ 'rotate-180': accordion.ticket }" aria-hidden="true">expand_more</span>
        </button>
        <transition name="accordion">
          <div v-show="accordion.ticket" class="border-t border-slate-200 bg-blue-50/70 py-5 dark:border-slate-700 dark:bg-blue-500/10 report-accordion__content">
            <div class="space-y-5 px-6">
              <div class="grid gap-3 sm:grid-cols-3">
                <div class="rounded-2xl border border-blue-100 bg-blue-50 px-4 py-3 dark:border-blue-500/40 dark:bg-blue-500/10">
                  <p class="text-xs font-semibold uppercase tracking-wide text-blue-600 dark:text-blue-200">Total</p>
                  <p class="mt-1 text-2xl font-semibold text-blue-800 dark:text-blue-100">{{ ticketSummary.total }}</p>
                </div>
                <div class="rounded-2xl border border-amber-100 bg-amber-50 px-4 py-3 dark:border-amber-500/40 dark:bg-amber-500/10">
                  <p class="text-xs font-semibold uppercase tracking-wide text-amber-600 dark:text-amber-200">In Progress</p>
                  <p class="mt-1 text-2xl font-semibold text-amber-700 dark:text-amber-100">{{ ticketSummary.in_progress }}</p>
                </div>
                <div class="rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 dark:border-emerald-500/40 dark:bg-emerald-500/10">
                  <p class="text-xs font-semibold uppercase tracking-wide text-emerald-600 dark:text-emerald-200">Completed</p>
                  <p class="mt-1 text-2xl font-semibold text-emerald-700 dark:text-emerald-100">{{ ticketSummary.done }}</p>
                </div>
              </div>

              <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white px-0 pb-4 pt-3 mt-4 dark:border-slate-700 dark:bg-slate-900">
                <div class="overflow-x-auto">
                  <table class="report-table w-full min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800 dark:text-slate-300">
                      <tr>
                        <th class="px-4 py-3 text-left">#</th>
                        <th class="px-4 py-3 text-left">Ticket</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-left">Ringkasan Task</th>
                        <th class="px-4 py-3 text-left">Timeline</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-700 dark:bg-slate-900">
                      <tr v-if="loadingTicket">
                        <td colspan="6" class="px-4 py-6 text-center text-slate-500 dark:text-slate-300">Memuat task dari ticket...</td>
                      </tr>
                      <tr v-else-if="!ticketData">
                        <td colspan="6" class="px-4 py-6 text-center text-slate-500 dark:text-slate-300">Klik bagian ini untuk memuat data.</td>
                      </tr>
                      <tr v-else-if="!ticketData.data.length">
                        <td colspan="6" class="px-4 py-6 text-center text-slate-500 dark:text-slate-300">Tidak ada task dari ticket.</td>
                      </tr>
                      <template v-else>
                        <template v-for="(group, index) in ticketData.data" :key="group.ticket?.id ?? `ticket-${index}`">
                          <tr class="transition hover:bg-slate-50 dark:hover:bg-slate-800/60">
                            <td class="px-4 py-3 align-top">{{ ticketRowNumber(index) }}</td>
                            <td class="px-4 py-3 align-top">
                              <div class="font-semibold text-slate-900 dark:text-slate-100">{{ group.ticket?.ticket_no || '—' }}</div>
                              <p class="text-sm text-slate-600 dark:text-slate-300">{{ group.ticket?.title || 'Ticket Tanpa Judul' }}</p>
                              <p v-if="group.summary?.total" class="mt-1 text-[11px] font-semibold uppercase tracking-wide text-blue-700 dark:text-blue-200">
                                {{ group.summary.total }} task di tiket ini
                              </p>
                            </td>
                            <td class="px-4 py-3 align-top">
                              <StatusPill :status="group.ticket?.status" :label="group.ticket?.status_label || '—'" size="sm" />
                            </td>
                            <td class="px-4 py-3">
                              <div class="flex flex-wrap gap-2">
                                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-100">
                                  Total {{ group.summary?.total ?? 0 }}
                                </span>
                                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-200">
                                  In Progress {{ group.summary?.in_progress ?? 0 }}
                                </span>
                                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-200">
                                  Selesai {{ group.summary?.done ?? 0 }}
                                </span>
                              </div>
                            </td>
                            <td class="px-4 py-3">
                              <div class="space-y-1 text-xs text-slate-500 dark:text-slate-300">
                                <p>Mulai: <span class="font-semibold text-slate-800 dark:text-slate-100">{{ group.ticket?.timeline?.start || '—' }}</span></p>
                                <p>Jatuh Tempo: <span class="font-semibold text-slate-800 dark:text-slate-100">{{ group.ticket?.timeline?.due || '—' }}</span></p>
                                <p>Terakhir: <span class="font-semibold text-slate-800 dark:text-slate-100">{{ group.ticket?.timeline?.end || '—' }}</span></p>
                              </div>
                            </td>
                            <td class="px-4 py-3">
                              <div class="flex items-center justify-end gap-2">
                                <Link
                                  v-if="group.ticket?.links?.show"
                                  :href="group.ticket.links.show"
                                  class="inline-flex items-center gap-1 rounded-full border border-blue-200 px-3 py-1 text-xs font-semibold text-blue-700 hover:bg-blue-50 dark:border-blue-500/40 dark:text-blue-200"
                                >
                                  <span class="material-icons text-xs" aria-hidden="true">open_in_new</span>
                                  Lihat Ticket
                                </Link>
                                <Link
                                  v-if="group.links?.detail"
                                  :href="group.links.detail"
                                  class="inline-flex items-center gap-1 rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-100 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800"
                                >
                                  <span class="material-icons text-sm" aria-hidden="true">description</span>
                                  Detail
                                </Link>
                              </div>
                            </td>
                          </tr>
                        </template>
                      </template>
                    </tbody>
                  </table>
                </div>

                <nav
                  v-if="ticketData?.links?.length > 3"
                  class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900"
                >
                  <button
                    v-for="link in ticketData.links"
                    :key="`ticket-link-${link.label}`"
                    type="button"
                    class="rounded-lg border px-3 py-1.5 text-sm transition"
                    :class="
                      link.active
                        ? 'border-blue-500 bg-blue-500 text-white shadow-sm'
                        : link.url
                          ? 'border-slate-300 bg-white text-slate-600 hover:bg-slate-100 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800'
                          : 'border-slate-200 bg-slate-100 text-slate-400 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-500'
                    "
                    :disabled="!link.url"
                    @click="changeTicketPage(link)"
                    v-html="link.label"
                  />
                </nav>
              </div>
            </div>
          </div>
        </transition>
      </div>

      <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
        <button
          type="button"
          class="flex w-full items-center justify-between gap-3 bg-purple-100 px-6 py-4 text-left text-purple-900 transition dark:bg-purple-900/30 dark:text-purple-100"
          :aria-expanded="accordion.standalone"
          @click="toggleSection('standalone')"
        >
          <div>
            <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Task Mandiri</h2>
            <p class="text-sm text-slate-500 dark:text-slate-300">{{ standaloneSummary.total }} task · {{ standaloneSummary.in_progress }} in progress · {{ standaloneSummary.done }} selesai</p>
          </div>
          <span class="material-icons text-purple-700 transition-transform duration-200 dark:text-purple-300" :class="{ 'rotate-180': accordion.standalone }" aria-hidden="true">expand_more</span>
        </button>
        <transition name="accordion">
          <div v-show="accordion.standalone" class="border-t border-slate-200 bg-purple-50/70 py-5 dark:border-slate-700 dark:bg-purple-500/10 report-accordion__content">
            <div class="space-y-5 px-6">
              <div class="grid gap-3 sm:grid-cols-3">
                <div class="rounded-2xl border border-purple-100 bg-purple-50 px-4 py-3 dark:border-purple-500/40 dark:bg-purple-500/10">
                  <p class="text-xs font-semibold uppercase tracking-wide text-purple-600 dark:text-purple-200">Total</p>
                  <p class="mt-1 text-2xl font-semibold text-purple-700 dark:text-purple-100">{{ standaloneSummary.total }}</p>
                </div>
                <div class="rounded-2xl border border-amber-100 bg-amber-50 px-4 py-3 dark:border-amber-500/40 dark:bg-amber-500/10">
                  <p class="text-xs font-semibold uppercase tracking-wide text-amber-600 dark:text-amber-200">In Progress</p>
                  <p class="mt-1 text-2xl font-semibold text-amber-700 dark:text-amber-100">{{ standaloneSummary.in_progress }}</p>
                </div>
                <div class="rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 dark:border-emerald-500/40 dark:bg-emerald-500/10">
                  <p class="text-xs font-semibold uppercase tracking-wide text-emerald-600 dark:text-emerald-200">Completed</p>
                  <p class="mt-1 text-2xl font-semibold text-emerald-700 dark:text-emerald-100">{{ standaloneSummary.done }}</p>
                </div>
              </div>

              <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white px-0 pb-4 pt-3 mt-4 dark:border-slate-700 dark:bg-slate-900">
                <div class="overflow-x-auto">
                  <table class="report-table w-full min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800 dark:text-slate-300">
                      <tr>
                        <th class="px-4 py-3 text-left">#</th>
                        <th class="px-4 py-3 text-left">Task</th>
                        <th class="px-4 py-3 text-left">Judul</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-left">Prioritas</th>
                        <th class="px-4 py-3 text-left">Due</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-700 dark:bg-slate-900">
                      <tr v-if="loadingStandalone">
                        <td colspan="7" class="px-4 py-6 text-center text-slate-500 dark:text-slate-300">Memuat task mandiri...</td>
                      </tr>
                      <tr v-else-if="!standaloneData">
                        <td colspan="7" class="px-4 py-6 text-center text-slate-500 dark:text-slate-300">Klik bagian ini untuk memuat data.</td>
                      </tr>
                      <tr v-else-if="!standaloneData.data.length">
                        <td colspan="7" class="px-4 py-6 text-center text-slate-500 dark:text-slate-300">Tidak ada task mandiri.</td>
                      </tr>
                      <template v-else>
                        <template v-for="(row, index) in standaloneData.data" :key="`standalone-task-${row.id}`">
                          <tr class="transition hover:bg-slate-50 dark:hover:bg-slate-800/60">
                            <td class="px-4 py-3">{{ standaloneRowNumber(index) }}</td>
                            <td class="px-4 py-3">
                              <div class="font-semibold text-slate-900 dark:text-slate-100">{{ row.task_no || '—' }}</div>
                              <div class="text-xs text-slate-500 dark:text-slate-300">Task Mandiri</div>
                          </td>
                          <td class="px-4 py-3 font-semibold text-slate-900 dark:text-slate-100">{{ row.title }}</td>
                          <td class="px-4 py-3">
                            <StatusPill
                              size="sm"
                              :status="row.display_status?.value || row.status"
                              :label="row.display_status?.label || row.status_label"
                            />
                          </td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-200">{{ row.priority_label }}</td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-200">{{ row.due_display }}</td>
                            <td class="px-4 py-3">
                              <div class="flex items-center justify-end gap-3">
                                <Link v-if="row.links?.show" :href="row.links.show" class="text-sm text-blue-600 hover:underline dark:text-blue-400">Detail</Link>
                                <Link v-if="row.links?.edit" :href="row.links.edit" class="text-sm text-blue-600 hover:underline dark:text-blue-400">Edit</Link>
                                <button
                                  v-if="row.links?.delete"
                                  type="button"
                                  class="inline-flex items-center gap-1 text-sm font-semibold text-red-600 hover:text-red-700 disabled:opacity-60 dark:text-red-300 dark:hover:text-red-200"
                                  :disabled="isDeleting(row.id)"
                                  @click.stop="destroyTask(row)"
                                >
                                  <span class="material-icons text-base" aria-hidden="true">{{ isDeleting(row.id) ? 'hourglass_top' : 'delete' }}</span>
                                  Hapus
                                </button>
                              </div>
                            </td>
                          </tr>
                        </template>
                      </template>
                    </tbody>
                  </table>
                </div>

                <nav
                  v-if="standaloneData?.links?.length > 3"
                  class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900"
                >
                  <button
                    v-for="link in standaloneData.links"
                    :key="`standalone-link-${link.label}`"
                    type="button"
                    class="rounded-lg border px-3 py-1.5 text-sm transition"
                    :class="
                      link.active
                        ? 'border-purple-500 bg-purple-500 text-white shadow-sm'
                        : link.url
                          ? 'border-slate-300 bg-white text-slate-600 hover:bg-slate-100 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800'
                          : 'border-slate-200 bg-slate-100 text-slate-400 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-500'
                    "
                    :disabled="!link.url"
                    @click="changeStandalonePage(link)"
                    v-html="link.label"
                  />
                </nav>
              </div>
            </div>
          </div>
        </transition>
      </div>
    </section>
  </div>
</template>

<style scoped>
.accordion-enter-active,
.accordion-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
  transform-origin: top;
}

.accordion-enter-from,
.accordion-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}

.report-accordion__content {
  padding: 0 1.5rem 1.5rem;
  display: grid;
  gap: 1.25rem;
  border-top: 1px solid rgba(15, 23, 42, 0.06);
  background: rgba(255, 255, 255, 0.65);
}

.dark .report-accordion__content {
  background: rgba(15, 23, 42, 0.6);
  border-top-color: rgba(148, 163, 184, 0.18);
}

.report-table tbody tr {
  background: transparent;
  transition: background 0.15s ease;
}

.report-table tbody tr:hover,
.report-table tbody tr:focus,
.report-table tbody tr:focus-within {
  background: rgba(148, 163, 184, 0.12);
}
</style>

<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import DatePickerFlatpickr from '@/Components/DatePickerFlatpickr.vue';
import FancySelect from '@/Components/FancySelect.vue';
import StatusPill from '@/Components/StatusPill.vue';

const props = defineProps({
  filters: { type: Object, default: () => ({}) },
  statusOptions: { type: Array, default: () => [] },
  ticketSummary: { type: Object, default: () => ({ total: 0, in_progress: 0, done: 0 }) },
  standaloneSummary: { type: Object, default: () => ({ total: 0, in_progress: 0, done: 0 }) },
  ticketTasks: { type: [Object, null], default: null },
  standaloneTasks: { type: [Object, null], default: null },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success ?? null);

const form = reactive({
  q: props.filters.q || '',
  status: props.filters.status || '',
  from: props.filters.from || '',
  to: props.filters.to || '',
});

const statusSelectOptions = computed(() => {
  const base = Array.isArray(props.statusOptions) ? props.statusOptions : [];
  return [
    { value: '', label: 'Semua Status' },
    ...base,
  ];
});

watch(
  () => props.filters,
  value => {
    form.q = value?.q || '';
    form.status = value?.status || '';
    form.from = value?.from || '';
    form.to = value?.to || '';
  }
);

const calendarConfig = {
  dateFormat: 'd/m/Y',
  allowInput: true,
};

const accordion = reactive({
  ticket: false,
  standalone: false,
});

const ticketData = ref(props.ticketTasks || null);
const standaloneData = ref(props.standaloneTasks || null);
const loadingTicket = ref(false);
const loadingStandalone = ref(false);
const submitting = ref(false);
const deleting = reactive({ id: null });

const localePrefix = () => {
  if (typeof window === 'undefined') return '';
  const match = window.location.pathname.match(/^\/(en|id)\b/);
  return match ? `/${match[1]}` : '';
};

const downloadUrl = computed(() => buildDownloadUrl(`${localePrefix()}/dashboard/tasks/report/download`, cleanPayload(props.filters || {})));

watch(
  () => props.ticketTasks,
  value => {
    if (value !== undefined) {
      ticketData.value = value;
      loadingTicket.value = false;
    }
  }
);

watch(
  () => props.standaloneTasks,
  value => {
    if (value !== undefined) {
      standaloneData.value = value;
      loadingStandalone.value = false;
    }
  }
);

watch(
  () => accordion.ticket,
  open => {
    if (open && !ticketData.value) {
      loadTicketTasks();
    }
  }
);

watch(
  () => accordion.standalone,
  open => {
    if (open && !standaloneData.value) {
      loadStandaloneTasks();
    }
  }
);

function buildRoute() {
  return `${localePrefix()}/dashboard/tasks/report`;
}

function cleanPayload(payload) {
  const data = {};
  if (payload.q) data.q = payload.q;
  if (payload.status) data.status = payload.status;
  if (payload.from) data.from = payload.from;
  if (payload.to) data.to = payload.to;
  return data;
}

function buildDownloadUrl(base, payload = {}) {
  const searchable = Object.fromEntries(
    Object.entries(payload).filter(([, value]) => value !== undefined && value !== null && value !== '')
  );
  const query = new URLSearchParams(searchable).toString();
  return query ? `${base}?${query}` : base;
}

function toggleSection(scope) {
  accordion[scope] = !accordion[scope];
  if (scope === 'ticket' && !ticketData.value) {
    loadTicketTasks();
  } else if (scope === 'standalone' && !standaloneData.value) {
    loadStandaloneTasks();
  }
}

function applyFilters() {
  if (submitting.value) return;
  submitting.value = true;

  const payload = cleanPayload(form);

  router.get(buildRoute(), payload, {
    preserveScroll: true,
    replace: true,
    onStart: () => {
      ticketData.value = null;
      standaloneData.value = null;
      loadingTicket.value = accordion.ticket;
      loadingStandalone.value = accordion.standalone;
    },
    onFinish: () => {
      submitting.value = false;
    },
  });
}

function resetFilters() {
  if (submitting.value) return;
  form.q = '';
  form.status = '';
  form.from = '';
  form.to = '';
  applyFilters();
}

function ticketRowNumber(index) {
  if (!ticketData.value?.meta) return index + 1;
  return (ticketData.value.meta.current_page - 1) * ticketData.value.meta.per_page + index + 1;
}

function standaloneRowNumber(index) {
  if (!standaloneData.value?.meta) return index + 1;
  return (standaloneData.value.meta.current_page - 1) * standaloneData.value.meta.per_page + index + 1;
}

function extractPage(url) {
  try {
    const searchParams = new URL(url, window.location.origin).searchParams;
    return searchParams.get('page') || searchParams.get('ticket_page') || searchParams.get('standalone_page');
  } catch (error) {
    return null;
  }
}

function changeTicketPage(link) {
  if (!link.url || link.active) return;
  loadingTicket.value = true;
  const payload = cleanPayload(form);
  const page = extractPage(link.url);
  if (page) payload.ticket_page = page;

  router.get(buildRoute(), payload, {
    only: ['ticketTasks', 'ticketSummary'],
    preserveState: true,
    preserveScroll: true,
    replace: true,
    onFinish: () => {
      loadingTicket.value = false;
    },
  });
}

function changeStandalonePage(link) {
  if (!link.url || link.active) return;
  loadingStandalone.value = true;
  const payload = cleanPayload(form);
  const page = extractPage(link.url);
  if (page) payload.standalone_page = page;

  router.get(buildRoute(), payload, {
    only: ['standaloneTasks', 'standaloneSummary'],
    preserveState: true,
    preserveScroll: true,
    replace: true,
    onFinish: () => {
      loadingStandalone.value = false;
    },
  });
}

function loadTicketTasks() {
  if (loadingTicket.value) return;
  loadingTicket.value = true;
  const payload = cleanPayload(form);

  router.get(buildRoute(), payload, {
    only: ['ticketTasks', 'ticketSummary'],
    preserveState: true,
    preserveScroll: true,
    replace: true,
    onFinish: () => {
      loadingTicket.value = false;
    },
  });
}

function loadStandaloneTasks() {
  if (loadingStandalone.value) return;
  loadingStandalone.value = true;
  const payload = cleanPayload(form);

  router.get(buildRoute(), payload, {
    only: ['standaloneTasks', 'standaloneSummary'],
    preserveState: true,
    preserveScroll: true,
    replace: true,
    onFinish: () => {
      loadingStandalone.value = false;
    },
  });
}

function destroyTask(row) {
  if (!row.links?.delete) return;
  if (!window.confirm(`Yakin ingin menghapus task "${row.title}"?`)) return;

  deleting.id = row.id;

  router.delete(row.links.delete, {
    preserveScroll: true,
    onFinish: () => {
      deleting.id = null;
    },
  });
}

function isDeleting(id) {
  return deleting.id === id;
}

</script>
