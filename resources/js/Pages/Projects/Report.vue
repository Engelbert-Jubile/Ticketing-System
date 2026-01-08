<template>
  <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 lg:px-6">
    <header class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div class="space-y-1.5">
        <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">Project Report</h1>
        <p class="text-sm text-slate-500 dark:text-slate-300">Ringkasan project berdasarkan status beserta filter yang fleksibel.</p>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <a :href="downloadUrl" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-blue-600 transition hover:bg-blue-50 dark:border-slate-600 dark:bg-slate-900 dark:text-blue-300 dark:hover:bg-slate-800">
          <span class="material-icons text-base">picture_as_pdf</span>
          Download PDF
        </a>
        <Link :href="route('projects.create')" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-blue-700">
          <span class="material-icons text-base">add</span>
          Project Baru
        </Link>
      </div>
    </header>

    <transition name="fade">
      <div
        v-if="flashSuccess"
        class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 dark:border-emerald-500/40 dark:bg-emerald-900/40 dark:text-emerald-100"
      >
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
                placeholder="Judul, deskripsi, atau nomor project"
                class="w-full rounded-lg border border-slate-300 bg-white py-2 pl-10 pr-3 text-sm text-slate-700 focus:border-blue-500 focus:outline-none dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
              />
            </div>
        </div>
        <div>
          <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">Status</label>
          <div ref="statusFilterRef" class="status-select-wrapper mt-1" @keydown.escape.stop.prevent="closeStatusDropdown">
            <button
              type="button"
              class="status-select"
              :class="{ 'is-open': statusDropdownOpen }"
              :aria-expanded="statusDropdownOpen"
              aria-haspopup="listbox"
              @click="toggleStatusDropdown"
            >
              <span class="status-select__value">
                <StatusPill
                  v-if="selectedStatusOption"
                  class="status-select__pill"
                  size="sm"
                  :status="selectedStatusOption.value"
                  :label="selectedStatusOption.label"
                />
                <StatusPill
                  v-else
                  class="status-select__pill"
                  size="sm"
                  status="all"
                  label="Semua Status"
                />
              </span>
              <span class="material-icons status-select__icon">{{ statusDropdownOpen ? 'expand_less' : 'expand_more' }}</span>
            </button>
            <div v-if="statusDropdownOpen" class="status-select__menu" role="listbox">
              <button
                type="button"
                class="status-select__option"
                :class="{ active: form.status === '' }"
                role="option"
                :aria-selected="form.status === ''"
                @click="selectStatus('')"
              >
                <StatusPill class="status-select__pill" size="sm" status="all" label="Semua Status" />
                <span v-if="form.status === ''" class="material-icons" aria-hidden="true">check</span>
              </button>
              <button
                v-for="option in statusOptions"
                :key="option.value"
                type="button"
                class="status-select__option"
                :class="{ active: form.status === option.value }"
                role="option"
                :aria-selected="form.status === option.value"
                @click="selectStatus(option.value)"
              >
                <StatusPill class="status-select__pill" size="sm" :status="option.value" :label="option.label" />
                <span v-if="form.status === option.value" class="material-icons" aria-hidden="true">check</span>
              </button>
            </div>
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
          @click="toggleSection('ticket')"
          :aria-expanded="accordion.ticket"
        >
          <div>
            <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Project Berbasis Ticket</h2>
            <p class="text-sm text-slate-500 dark:text-slate-300">{{ ticketSummary.total }} project · {{ ticketSummary.in_progress }} in progress · {{ ticketSummary.done }} selesai</p>
          </div>
          <span
            class="material-icons text-blue-700 transition-transform duration-200 dark:text-blue-300"
            :class="{ 'rotate-180': accordion.ticket }"
            aria-hidden="true"
          >expand_more</span>
        </button>
        <transition name="accordion">
          <div
            v-show="accordion.ticket"
            class="border-t border-slate-200 bg-blue-50/70 py-5 dark:border-slate-700 dark:bg-blue-500/10 report-accordion__content"
          >
            <div class="space-y-5 px-6">
              <div class="grid gap-3 sm:grid-cols-3">
                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                  <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">Total</p>
                  <p class="mt-1 text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ ticketSummary.total }}</p>
                </div>
                <div class="rounded-2xl border border-blue-100 bg-blue-50 px-4 py-3 dark:border-blue-500/40 dark:bg-blue-500/10">
                  <p class="text-xs font-semibold uppercase tracking-wide text-blue-600 dark:text-blue-200">In Progress</p>
                  <p class="mt-1 text-2xl font-semibold text-blue-700 dark:text-blue-100">{{ ticketSummary.in_progress }}</p>
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
                        <th class="px-4 py-3 text-left">Nama Project</th>
                        <th class="px-4 py-3 text-left">Ticket</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-left">Status ID</th>
                        <th class="px-4 py-3 text-left">Due</th>
                        <th class="px-4 py-3 text-left">Dibuat</th>
                        <th class="px-4 py-3 text-left">Diperbarui</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-700 dark:bg-slate-900">
                      <tr v-if="ticketLoading">
                        <td colspan="9" class="px-4 py-6 text-center text-slate-500 dark:text-slate-300">Memuat project ticket...</td>
                      </tr>
                      <tr v-else-if="!ticketData">
                        <td colspan="9" class="px-4 py-6 text-center text-slate-500 dark:text-slate-300">Klik bagian ini untuk memuat data.</td>
                      </tr>
                      <tr v-else-if="!ticketData.data.length">
                        <td colspan="9" class="px-4 py-6 text-center text-slate-500 dark:text-slate-300">Tidak ada project dari ticket.</td>
                      </tr>
                      <template v-else>
                        <template v-for="(group, index) in ticketData.data" :key="group.primary?.id ?? `ticket-group-${index}`">
                          <tr class="transition hover:bg-slate-50 dark:hover:bg-slate-800/60">
                            <td class="px-4 py-3">{{ ticketRowNumber(index) }}</td>
                            <td class="px-4 py-3">
                              <div class="font-semibold text-slate-900 dark:text-slate-100">{{ group.primary.title }}</div>
                              <div class="text-xs text-slate-500 dark:text-slate-300">No: {{ group.primary.project_no || '—' }}</div>
                              <p v-if="group.children.length" class="mt-1 text-[11px] font-semibold uppercase tracking-wide text-blue-700 dark:text-blue-200">
                                + {{ group.children.length }} project terkait
                              </p>
                            </td>
                            <td class="px-4 py-3">
                              <span class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                                {{ group.ticket ? group.ticket.ticket_no : '—' }}
                              </span>
                            </td>
                            <td class="px-4 py-3">
                              <StatusPill
                                size="sm"
                                :status="group.ticket?.status || group.primary.status_ticket || group.primary.status"
                                :label="group.ticket?.status_label || group.primary.status_ticket_label || group.primary.status_label || group.primary.status || '—'"
                              />
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-slate-500 dark:text-slate-300">{{ group.primary.status_id || '—' }}</td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-200">{{ group.primary.due_display }}</td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-200">{{ group.primary.created_display }}</td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-200">{{ group.primary.updated_display }}</td>
                            <td class="px-4 py-3">
                              <div class="flex items-center justify-end gap-3">
                                <Link :href="group.primary.links.show" class="text-sm text-blue-600 hover:underline dark:text-blue-400">Lihat</Link>
                                <Link v-if="group.primary.links.edit" :href="group.primary.links.edit" class="text-sm text-blue-600 hover:underline dark:text-blue-400">Edit</Link>
                                <Link v-if="group.primary.links.ticket" :href="group.primary.links.ticket" class="text-sm text-blue-600 hover:underline dark:text-blue-400">Ticket</Link>
                                <button
                                  v-if="group.primary.links.delete"
                                  type="button"
                                  class="inline-flex items-center gap-1 text-sm font-semibold text-red-600 hover:text-red-700 disabled:opacity-60 dark:text-red-300 dark:hover:text-red-200"
                                  :disabled="isDeleting('ticket', group.primary.id)"
                                  @click.stop="destroyProject(group.primary, 'ticket')"
                                >
                                  <span class="material-icons text-base" aria-hidden="true">{{ isDeleting('ticket', group.primary.id) ? 'hourglass_top' : 'delete' }}</span>
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

                <nav v-if="ticketData && ticketData.links?.length > 3" class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900">
                  <button
                    v-for="link in ticketData.links"
                    :key="`ticket-link-${link.label}`"
                    type="button"
                    class="rounded-lg border px-3 py-1.5 text-sm transition"
                    :class="link.active
                      ? 'border-blue-500 bg-blue-500 text-white shadow-sm'
                      : link.url
                        ? 'border-slate-300 bg-white text-slate-600 hover:bg-slate-100 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800'
                        : 'border-slate-200 bg-slate-100 text-slate-400 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-500'"
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
          @click="toggleSection('standalone')"
          :aria-expanded="accordion.standalone"
        >
          <div>
            <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Project Mandiri</h2>
            <p class="text-sm text-slate-500 dark:text-slate-300">{{ standaloneSummary.total }} project · {{ standaloneSummary.in_progress }} in progress · {{ standaloneSummary.done }} selesai</p>
          </div>
          <span
            class="material-icons text-purple-700 transition-transform duration-200 dark:text-purple-300"
            :class="{ 'rotate-180': accordion.standalone }"
            aria-hidden="true"
          >expand_more</span>
        </button>
        <transition name="accordion">
          <div
            v-show="accordion.standalone"
            class="border-t border-slate-200 bg-amber-50/70 py-5 dark:border-slate-700 dark:bg-amber-500/10 report-accordion__content"
          >
            <div class="space-y-5 px-6">
              <div class="grid gap-3 sm:grid-cols-3">
                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm dark:border-slate-600 dark:bg-slate-900">
                  <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">Total</p>
                  <p class="mt-1 text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ standaloneSummary.total }}</p>
                </div>
                <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-500/40 dark:bg-amber-500/10">
                  <p class="text-xs font-semibold uppercase tracking-wide text-amber-600 dark:text-amber-200">In Progress</p>
                  <p class="mt-1 text-2xl font-semibold text-amber-700 dark:text-amber-100">{{ standaloneSummary.in_progress }}</p>
                </div>
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 dark:border-emerald-500/40 dark:bg-emerald-500/10">
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
                        <th class="px-4 py-3 text-left">Nama Project</th>
                        <th class="px-4 py-3 text-left">Tipe</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-left">Start</th>
                        <th class="px-4 py-3 text-left">End</th>
                        <th class="px-4 py-3 text-left">Dibuat</th>
                        <th class="px-4 py-3 text-left">Diperbarui</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-700 dark:bg-slate-900">
                      <tr v-if="standaloneLoading">
                        <td colspan="9" class="px-4 py-6 text-center text-slate-500 dark:text-slate-300">Memuat project mandiri...</td>
                      </tr>
                      <tr v-else-if="!standaloneData">
                        <td colspan="9" class="px-4 py-6 text-center text-slate-500 dark:text-slate-300">Klik bagian ini untuk memuat data.</td>
                      </tr>
                      <tr v-else-if="!standaloneData.data.length">
                        <td colspan="9" class="px-4 py-6 text-center text-slate-500 dark:text-slate-300">Tidak ada project mandiri.</td>
                      </tr>
                      <template v-else>
                        <template v-for="(row, index) in standaloneData.data" :key="`standalone-row-${row.id}`">
                          <tr class="transition hover:bg-slate-50 dark:hover:bg-slate-800/60">
                            <td class="px-4 py-3">{{ standaloneRowNumber(index) }}</td>
                            <td class="px-4 py-3">
                              <div class="font-semibold text-slate-900 dark:text-slate-100">{{ row.title }}</div>
                              <div class="text-xs text-slate-500 dark:text-slate-300">No: {{ row.project_no || '—' }}</div>
                            </td>
                            <td class="px-4 py-3">
                              <span class="inline-flex rounded-full border border-slate-300 bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200">{{ row.type_label }}</span>
                            </td>
                            <td class="px-4 py-3">
                              <StatusPill size="sm" :status="row.status" :label="row.status_label" />
                            </td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-200">{{ row.start_display || '—' }}</td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-200">{{ row.due_display }}</td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-200">{{ row.created_display }}</td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-200">{{ row.updated_display }}</td>
                            <td class="px-4 py-3">
                              <div class="flex items-center justify-end gap-3">
                                <Link :href="row.links.show" class="text-sm text-blue-600 hover:underline dark:text-blue-400">Lihat</Link>
                                <Link v-if="row.links.edit" :href="row.links.edit" class="text-sm text-blue-600 hover:underline dark:text-blue-400">Edit</Link>
                              <button
                                v-if="row.links.delete"
                                type="button"
                                class="inline-flex items-center gap-1 text-sm font-semibold text-red-600 hover:text-red-700 disabled:opacity-60 dark:text-red-300 dark:hover:text-red-200"
                                :disabled="isDeleting('standalone', row.id)"
                                @click.stop="destroyProject(row, 'standalone')"
                              >
                                <span class="material-icons text-base" aria-hidden="true">{{ isDeleting('standalone', row.id) ? 'hourglass_top' : 'delete' }}</span>
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

                <nav v-if="standaloneData && standaloneData.links?.length > 3" class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900">
                  <button
                    v-for="link in standaloneData.links"
                    :key="`standalone-link-${link.label}`"
                    type="button"
                    class="rounded-lg border px-3 py-1.5 text-sm transition"
                    :class="link.active
                      ? 'border-blue-500 bg-blue-500 text-white shadow-sm'
                      : link.url
                        ? 'border-slate-300 bg-white text-slate-600 hover:bg-slate-100 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800'
                        : 'border-slate-200 bg-slate-100 text-slate-400 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-500'"
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

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import DatePickerFlatpickr from '@/Components/DatePickerFlatpickr.vue';
import StatusPill from '@/Components/StatusPill.vue';

const props = defineProps({
  filters: { type: Object, default: () => ({}) },
  statusOptions: { type: Array, default: () => [] },
  ticketSummary: { type: Object, default: () => ({ total: 0, in_progress: 0, done: 0 }) },
  standaloneSummary: { type: Object, default: () => ({ total: 0, in_progress: 0, done: 0 }) },
  ticketProjects: { type: [Object, null], default: null },
  standaloneProjects: { type: [Object, null], default: null },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success ?? null);

const deleting = reactive({ scope: null, id: null });

const form = reactive({
  q: props.filters.q || '',
  status: props.filters.status || '',
  from: props.filters.from || '',
  to: props.filters.to || '',
});

const statusFilterRef = ref(null);
const statusDropdownOpen = ref(false);
const statusOptionMap = computed(() => {
  const map = {};
  (props.statusOptions || []).forEach(option => {
    if (option?.value) {
      map[option.value] = option;
    }
  });
  return map;
});

const selectedStatusOption = computed(() => {
  if (!form.status) return null;
  return statusOptionMap.value[form.status] ?? null;
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

const ticketData = ref(props.ticketProjects || null);
const standaloneData = ref(props.standaloneProjects || null);
const ticketLoading = ref(false);
const standaloneLoading = ref(false);
const submitting = ref(false);

const localePrefix = () => {
  if (typeof window === 'undefined') return '';
  const match = window.location.pathname.match(/^\/(en|id)\b/);
  return match ? `/${match[1]}` : '';
};

const downloadUrl = computed(() => buildDownloadUrl(`${localePrefix()}/dashboard/projects/report/download`, cleanPayload(props.filters || {})));

const ticketUnlinkedCount = computed(() => {
  if (!ticketData.value?.data) {
    return 0;
  }

  return ticketData.value.data.filter(group => !group?.ticket).length;
});

watch(
  () => props.ticketProjects,
  value => {
    if (value !== undefined) {
      ticketData.value = value;
      ticketLoading.value = false;
    }
  }
);

watch(
  () => props.standaloneProjects,
  value => {
    if (value !== undefined) {
      standaloneData.value = value;
      standaloneLoading.value = false;
    }
  }
);

onMounted(() => {
  document.addEventListener('click', handleStatusClickOutside);
  if (!ticketData.value) {
    loadTicketProjects();
  }
  if (!standaloneData.value) {
    loadStandaloneProjects();
  }
});

onBeforeUnmount(() => {
  document.removeEventListener('click', handleStatusClickOutside);
});

function reportUrl() {
  return `${localePrefix()}/dashboard/projects/report`;
}

function finishSubmitting() {
  submitting.value = false;
}

function applyFilters() {
  if (submitting.value) return;

  closeStatusDropdown();
  submitting.value = true;
  router.get(reportUrl(), cleanPayload(form), {
    preserveScroll: true,
    replace: true,
    onStart: () => {
      ticketData.value = null;
      standaloneData.value = null;
      ticketLoading.value = true;
      standaloneLoading.value = true;
    },
    onSuccess: finishSubmitting,
    onError: finishSubmitting,
    onCancel: finishSubmitting,
    onFinish: finishSubmitting,
  });
}

function resetFilters() {
  if (submitting.value) return;

  form.q = '';
  form.status = '';
  form.from = '';
  form.to = '';

  closeStatusDropdown();
  submitting.value = true;
  router.get(reportUrl(), {}, {
    preserveScroll: true,
    replace: true,
    onStart: () => {
      ticketData.value = null;
      standaloneData.value = null;
      ticketLoading.value = true;
      standaloneLoading.value = true;
    },
    onSuccess: finishSubmitting,
    onError: finishSubmitting,
    onCancel: finishSubmitting,
    onFinish: finishSubmitting,
  });
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

function toggleStatusDropdown() {
  statusDropdownOpen.value = !statusDropdownOpen.value;
}

function closeStatusDropdown() {
  statusDropdownOpen.value = false;
}

function selectStatus(value) {
  form.status = value;
  closeStatusDropdown();
}

function handleStatusClickOutside(event) {
  if (!statusDropdownOpen.value) return;
  if (!statusFilterRef.value) return;
  if (statusFilterRef.value.contains(event.target)) return;
  closeStatusDropdown();
}

function toggleSection(section) {
  accordion[section] = !accordion[section];
}

function loadTicketProjects(pageUrl) {
  ticketLoading.value = true;
  const payload = cleanPayload(form);
  const page = extractPage(pageUrl);
  if (page) {
    payload.ticket_page = page;
  }

  router.get(reportUrl(), payload, {
    only: ['ticketProjects', 'ticketSummary'],
    preserveState: true,
    preserveScroll: true,
    replace: true,
    onFinish: () => {
      ticketLoading.value = false;
    },
  });
}

function loadStandaloneProjects(pageUrl) {
  standaloneLoading.value = true;
  const payload = cleanPayload(form);
  const page = extractPage(pageUrl);
  if (page) {
    payload.standalone_page = page;
  }

  router.get(reportUrl(), payload, {
    only: ['standaloneProjects', 'standaloneSummary'],
    preserveState: true,
    preserveScroll: true,
    replace: true,
    onFinish: () => {
      standaloneLoading.value = false;
    },
  });
}

function changeTicketPage(link) {
  if (!link.url || link.active) return;
  loadTicketProjects(link.url);
}

function changeStandalonePage(link) {
  if (!link.url || link.active) return;
  loadStandaloneProjects(link.url);
}

function extractPage(url) {
  if (!url) return null;
  try {
    const query = new URL(url, window.location.origin).searchParams;
    return query.get('page') || query.get('ticket_page') || query.get('standalone_page');
  } catch (error) {
    return null;
  }
}

function ticketRowNumber(index) {
  if (!ticketData.value?.meta) return index + 1;
  return (ticketData.value.meta.current_page - 1) * ticketData.value.meta.per_page + index + 1;
}

function standaloneRowNumber(index) {
  if (!standaloneData.value?.meta) return index + 1;
  return (standaloneData.value.meta.current_page - 1) * standaloneData.value.meta.per_page + index + 1;
}

function combinedAttachments(row) {
  const items = [];
  if (Array.isArray(row.attachments)) {
    items.push(...row.attachments);
  }
  if (row.ticket?.attachments?.length) {
    items.push(...row.ticket.attachments);
  }
  return items;
}

function attachmentPreview(row) {
  const items = combinedAttachments(row);
  return {
    items: items.slice(0, 5),
    remaining: items.length > 5 ? items.length - 5 : 0,
  };
}

function creatorName(row) {
  return row.creator?.name || '—';
}

function ticketMeta(row) {
  return [
    { label: 'Status', value: row.status_label || '—' },
    { label: 'Status ID', value: row.status_id || '—' },
    { label: 'Due', value: row.due_display || '—' },
    { label: 'Dibuat', value: row.created_display || '—' },
    { label: 'Diperbarui', value: row.updated_display || '—' },
    { label: 'Dibuat Oleh', value: creatorName(row) },
  ];
}

function standaloneMeta(row) {
  return [
    { label: 'Status', value: row.status_label || '—' },
    { label: 'Tipe', value: row.type_label || '—' },
    { label: 'Start', value: row.start_display || '—' },
    { label: 'End', value: row.due_display || '—' },
    { label: 'Dibuat', value: row.created_display || '—' },
    { label: 'Diperbarui', value: row.updated_display || '—' },
    { label: 'Dibuat Oleh', value: creatorName(row) },
  ];
}

function isDeleting(scope, id) {
  return deleting.scope === scope && deleting.id === id;
}

function destroyProject(row, scope) {
  if (!row?.links?.delete) return;
  if (!window.confirm(`Yakin ingin menghapus project "${row.title}"?`)) return;

  deleting.scope = scope;
  deleting.id = row.id;

  router.delete(row.links.delete, {
    preserveScroll: true,
    onFinish: () => {
      deleting.scope = null;
      deleting.id = null;
    },
  });
}
</script>

<style scoped>
.material-icons {
  line-height: 1;
}

.report-surface {
  min-height: calc(100vh - 6rem);
  padding: 2.5rem 1.25rem 4rem;
  background: linear-gradient(135deg, #eef2ff 0%, #e0f2fe 40%, #fdf2f8 100%);
}

.dark .report-surface {
  background: radial-gradient(circle at top, rgba(56, 189, 248, 0.12), transparent 55%),
    radial-gradient(circle at bottom, rgba(192, 132, 252, 0.08), transparent 60%),
    #020617;
}

.report-shell {
  max-width: 1180px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.report-top {
  display: grid;
  gap: 1rem;
  align-items: start;
}

.report-back {
  display: inline-flex;
  align-items: center;
  gap: 0.55rem;
  width: max-content;
  font-size: 0.9rem;
  font-weight: 600;
  color: #1d4ed8;
  padding: 0.48rem 1.15rem;
  border-radius: 999px;
  background: linear-gradient(135deg, rgba(239, 246, 255, 0.95), rgba(219, 234, 254, 0.95));
  border: 1px solid rgba(59, 130, 246, 0.28);
  box-shadow: 0 14px 28px rgba(96, 165, 250, 0.2);
  transition: transform 0.18s ease, box-shadow 0.18s ease, color 0.18s ease;
}

.report-back .material-icons {
  font-size: 1.15rem;
}

.report-back:hover {
  transform: translateY(-1px);
  color: #1e3a8a;
  box-shadow: 0 18px 32px rgba(37, 99, 235, 0.26);
}

.dark .report-back {
  color: #bfdbfe;
  background: rgba(37, 99, 235, 0.18);
  border-color: rgba(59, 130, 246, 0.32);
  box-shadow: 0 18px 36px rgba(2, 6, 23, 0.6);
}

.report-hero {
  position: relative;
  overflow: hidden;
  padding: 2.4rem 2.2rem;
  border-radius: 22px;
  background: linear-gradient(135deg, #2563eb 0%, #7c3aed 48%, #ec4899 100%);
  color: #fff;
  box-shadow: 0 28px 48px rgba(76, 29, 149, 0.22);
}

.report-hero::after {
  content: '';
  position: absolute;
  inset: 0;
  background: radial-gradient(circle at top right, rgba(255, 255, 255, 0.28), transparent 60%);
  pointer-events: none;
}

.report-hero__badge {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.45rem 1rem;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.18);
  font-size: 0.82rem;
  font-weight: 600;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  margin-bottom: 1.1rem;
}

.report-hero h1 {
  font-size: clamp(1.9rem, 2.4vw + 1rem, 2.6rem);
  font-weight: 700;
}

.report-hero p {
  margin-top: 0.5rem;
  font-size: 1.05rem;
  max-width: 34rem;
  opacity: 0.85;
}

.filter-control {
  position: relative;
  display: flex;
  align-items: stretch;
  min-height: 3rem;
}

.filter-icon {
  position: absolute;
  left: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  width: 2.2rem;
  height: 2.2rem;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: rgba(59, 130, 246, 0.12);
  color: #2563eb;
  font-size: 1.1rem;
}

.filter-control input,
.filter-control select {
  width: 100%;
  border-radius: 18px;
  border: 1px solid rgba(148, 163, 184, 0.26);
  background: rgba(255, 255, 255, 0.95);
  padding: 0.75rem 1.05rem 0.75rem 3.2rem;
  font-size: 0.95rem;
  color: #0f172a;
  box-shadow: 0 8px 18px rgba(15, 23, 42, 0.05);
  transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
}

.filter-control select {
  padding-left: 1.15rem;
}

.status-select-wrapper {
  position: relative;
}

.status-select {
  width: 100%;
  display: inline-flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.8rem;
  border-radius: 12px;
  border: 1px solid rgba(148, 163, 184, 0.6);
  background: rgba(255, 255, 255, 0.96);
  padding: 0.55rem 0.85rem 0.55rem 1rem;
  font-size: 0.9rem;
  font-weight: 500;
  color: #0f172a;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
  box-shadow: 0 8px 16px rgba(15, 23, 42, 0.05);
}

.status-select:hover {
  border-color: rgba(59, 130, 246, 0.7);
}

.status-select.is-open {
  border-color: rgba(59, 130, 246, 0.85);
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
}

.status-select__value {
  display: inline-flex;
  align-items: center;
  gap: 0.55rem;
  flex: 1;
  min-width: 0;
}

.status-select__pill {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  white-space: nowrap;
}

.status-select__icon {
  font-size: 1.1rem;
  color: #475569;
}

.status-select__menu {
  position: absolute;
  top: calc(100% + 0.4rem);
  left: 0;
  right: 0;
  z-index: 30;
  border-radius: 14px;
  border: 1px solid rgba(148, 163, 184, 0.4);
  background: rgba(255, 255, 255, 0.98);
  box-shadow: 0 18px 38px rgba(15, 23, 42, 0.15);
  padding: 0.35rem;
}

.status-select__option {
  width: 100%;
  display: inline-flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.45rem 0.6rem;
  border-radius: 10px;
  border: none;
  background: transparent;
  cursor: pointer;
  color: #0f172a;
  font-size: 0.9rem;
  transition: background 0.15s ease;
}

.status-select__option:hover {
  background: rgba(59, 130, 246, 0.08);
}

.status-select__option.active {
  background: rgba(59, 130, 246, 0.12);
}

.status-select__option .material-icons {
  font-size: 1rem;
  color: #2563eb;
}

.dark .status-select {
  background: rgba(15, 23, 42, 0.75);
  border-color: rgba(100, 116, 139, 0.65);
  color: #e2e8f0;
  box-shadow: 0 8px 18px rgba(2, 6, 23, 0.6);
}

.dark .status-select__icon {
  color: #94a3b8;
}

.dark .status-select__pill--all {
  background: rgba(51, 65, 85, 0.7);
  color: #e2e8f0;
  border-color: rgba(148, 163, 184, 0.4);
}

.dark .status-select__menu {
  background: rgba(15, 23, 42, 0.95);
  border-color: rgba(71, 85, 105, 0.75);
  box-shadow: 0 24px 45px rgba(2, 6, 23, 0.72);
}

.dark .status-select__option {
  color: #e2e8f0;
}

.dark .status-select__option:hover {
  background: rgba(59, 130, 246, 0.18);
}

.dark .status-select__option.active {
  background: rgba(59, 130, 246, 0.25);
}

.filter-control input:focus,
.filter-control select:focus {
  outline: none;
  border-color: rgba(59, 130, 246, 0.6);
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.16);
  background: rgba(255, 255, 255, 1);
}

.filter-control :deep(input.flatpickr-input) {
  width: 100%;
  border-radius: 18px;
  border: 1px solid rgba(148, 163, 184, 0.26);
  background: rgba(255, 255, 255, 0.95);
  padding: 0.75rem 1.05rem 0.75rem 3.2rem;
  font-size: 0.95rem;
  color: #0f172a;
  box-shadow: 0 8px 18px rgba(15, 23, 42, 0.05);
  transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
}

.filter-control :deep(input.flatpickr-input:focus) {
  outline: none;
  border-color: rgba(59, 130, 246, 0.6);
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.16);
  background: rgba(255, 255, 255, 1);
}

.dark .filter-icon {
  background: rgba(96, 165, 250, 0.22);
  color: #dbeafe;
}

.dark .filter-control input,
.dark .filter-control select,
.dark .filter-control :deep(input.flatpickr-input) {
  border-color: rgba(148, 163, 184, 0.28);
  background: rgba(15, 23, 42, 0.72);
  color: #e2e8f0;
  box-shadow: 0 10px 22px rgba(2, 6, 23, 0.55);
}

.dark .filter-control input:focus,
.dark .filter-control select:focus,
.dark .filter-control :deep(input.flatpickr-input:focus) {
  border-color: rgba(148, 197, 255, 0.6);
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.28);
}

.report-primary-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.8rem 1.4rem;
  border-radius: 16px;
  background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 50%, #0f172a 100%);
  color: #fff;
  font-weight: 600;
  border: none;
  box-shadow: 0 20px 36px rgba(37, 99, 235, 0.28);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.report-primary-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 24px 48px rgba(37, 99, 235, 0.3);
}

.report-ghost-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.75rem 1.2rem;
  border-radius: 14px;
  border: 1px solid rgba(15, 23, 42, 0.14);
  background: rgba(255, 255, 255, 0.9);
  font-weight: 600;
  color: #0f172a;
}

.report-ghost-btn:hover {
  border-color: rgba(59, 130, 246, 0.4);
  background: rgba(255, 255, 255, 0.98);
}

.dark .report-ghost-btn {
  border-color: rgba(148, 163, 184, 0.28);
  background: rgba(15, 23, 42, 0.7);
  color: #e2e8f0;
}
.report-filter {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  padding: 1.6rem;
  border-radius: 22px;
  background: rgba(255, 255, 255, 0.9);
  border: 1px solid rgba(148, 163, 184, 0.24);
  box-shadow: 0 18px 36px rgba(15, 23, 42, 0.08);
  backdrop-filter: blur(16px);
}

.dark .report-filter {
  background: rgba(15, 23, 42, 0.72);
  border-color: rgba(37, 99, 235, 0.28);
}

.filter-field {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.filter-field label {
  font-size: 0.78rem;
  font-weight: 600;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: rgba(15, 23, 42, 0.6);
}

.dark .filter-field label {
  color: rgba(226, 232, 240, 0.6);
}

.filter-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.filter-toolbar__actions {
  display: inline-flex;
  align-items: center;
  gap: 0.6rem;
}

.filter-toolbar__spacer {
  flex: 1 1 auto;
}

.filter-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1rem;
}

.filter-icon-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 999px;
  border: 1px solid rgba(148, 163, 184, 0.45);
  background: white;
  color: #475569;
  box-shadow: 0 6px 18px rgba(15, 23, 42, 0.08);
  transition: all 0.2s ease;
  cursor: pointer;
}

.filter-icon-btn:disabled {
  opacity: 0.65;
  cursor: not-allowed;
  transform: none;
}

.filter-icon-btn.primary {
  background: linear-gradient(135deg, #2563eb, #1d4ed8);
  color: #ffffff;
  border-color: transparent;
}

.filter-icon-btn:hover {
  transform: translateY(-1px);
}

.filter-icon-btn:active {
  transform: translateY(0);
}

.filter-icon-btn .material-icons {
  font-size: 1.25rem;
}

.report-summary {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: 0.75rem;
}

.summary-card {
  padding: 0.95rem 1.1rem;
  border-radius: 16px;
  background: rgba(255, 255, 255, 0.92);
  border: 1px solid rgba(148, 163, 184, 0.18);
  box-shadow: 0 12px 22px rgba(15, 23, 42, 0.06);
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
  min-height: 96px;
}

.summary-card span {
  font-size: 0.72rem;
  text-transform: uppercase;
  font-weight: 600;
  letter-spacing: 0.08em;
  color: rgba(15, 23, 42, 0.6);
}

.summary-card strong {
  font-size: 1.6rem;
  font-weight: 700;
  color: #0f172a;
}

.summary-card.accent {
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.9), rgba(147, 197, 253, 0.85));
  border: none;
  color: #f8fafc;
}

.summary-card.success {
  background: linear-gradient(135deg, rgba(34, 197, 94, 0.9), rgba(22, 163, 74, 0.85));
  border: none;
  color: #f0fdf4;
}

.summary-card.mandiri {
  background: linear-gradient(135deg, rgba(255, 184, 108, 0.95), rgba(255, 126, 95, 0.95));
  border: none;
  color: #311b0b;
}

.dark .summary-card {
  background: rgba(15, 23, 42, 0.68);
  border-color: rgba(148, 163, 184, 0.22);
  color: #e2e8f0;
}

.dark .summary-card.mandiri {
  color: #fff7ed;
}

.report-accordion {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.report-accordion__item {
  --accent: #2563eb;
  --accent-soft: rgba(37, 99, 235, 0.2);
  --accent-subtle: rgba(37, 99, 235, 0.08);
  --icon-gradient: linear-gradient(135deg, rgba(37, 99, 235, 0.95), rgba(30, 64, 175, 0.95));
  border-radius: 18px;
  border: 1px solid rgba(15, 23, 42, 0.08);
  background: rgba(255, 255, 255, 0.95);
  box-shadow: 0 18px 36px rgba(15, 23, 42, 0.08);
  overflow: hidden;
  transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
}

.report-accordion__item--standalone {
  --accent: #059669;
  --accent-soft: rgba(16, 185, 129, 0.24);
  --accent-subtle: rgba(16, 185, 129, 0.1);
  --icon-gradient: linear-gradient(135deg, rgba(16, 185, 129, 0.95), rgba(5, 122, 85, 0.95));
}

.report-accordion__item:hover {
  transform: translateY(-2px);
  border-color: var(--accent-soft);
  box-shadow: 0 24px 48px rgba(15, 23, 42, 0.12);
}

.report-accordion__item.is-open {
  border-color: var(--accent);
  box-shadow: 0 28px 56px rgba(15, 23, 42, 0.18);
}

.dark .report-accordion__item {
  background: rgba(15, 23, 42, 0.78);
  border-color: rgba(59, 130, 246, 0.22);
  box-shadow: 0 22px 48px rgba(2, 6, 23, 0.6);
}

.dark .report-accordion__item--standalone {
  border-color: rgba(45, 212, 191, 0.28);
}

.dark .report-accordion__item--ticket {
  --accent-subtle: rgba(37, 99, 235, 0.18);
  --accent-soft: rgba(37, 99, 235, 0.32);
}

.dark .report-accordion__item--standalone {
  --accent-subtle: rgba(16, 185, 129, 0.2);
  --accent-soft: rgba(16, 185, 129, 0.34);
}

.report-accordion__toggle {
  width: 100%;
  border: none;
  background: transparent;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1.2rem;
  padding: 1.6rem 1.8rem;
  cursor: pointer;
}

.report-accordion__heading {
  display: flex;
  align-items: center;
  gap: 1rem;
  text-align: left;
}

.report-accordion__icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 54px;
  height: 54px;
  border-radius: 18px;
  background: var(--icon-gradient);
  color: #fff;
  box-shadow: 0 16px 30px rgba(15, 23, 42, 0.12);
}

.report-accordion__icon .material-icons {
  font-size: 1.6rem;
}

.report-accordion__heading h2 {
  font-size: 1.28rem;
  font-weight: 700;
  color: #0f172a;
}

.report-accordion__heading p {
  margin-top: 0.25rem;
  font-size: 0.9rem;
  color: rgba(15, 23, 42, 0.58);
}

.dark .report-accordion__heading h2 {
  color: #e2e8f0;
}

.dark .report-accordion__heading p {
  color: rgba(148, 163, 184, 0.72);
}

.report-accordion__meta {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.report-accordion__count {
  display: inline-flex;
  align-items: center;
  padding: 0.35rem 0.85rem;
  border-radius: 999px;
  font-size: 0.78rem;
  font-weight: 600;
  background: var(--accent-subtle);
  border: 1px solid var(--accent-soft);
  color: var(--accent);
}

.dark .report-accordion__count {
  color: #f8fafc;
}

.report-accordion__chevron-icon {
  font-size: 1.6rem;
  color: var(--accent);
  transition: transform 0.25s ease;
}

.report-accordion__item.is-open .report-accordion__chevron-icon {
  transform: rotate(180deg);
}

.report-accordion__content {
  padding: 0 1.8rem 1.8rem;
  display: grid;
  gap: 1.3rem;
  border-top: 1px solid rgba(15, 23, 42, 0.06);
  background: rgba(255, 255, 255, 0.6);
}

.dark .report-accordion__content {
  background: rgba(15, 23, 42, 0.6);
  border-color: rgba(148, 163, 184, 0.18);
}

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

.report-table tbody tr {
  background: transparent;
  transition: background 0.15s ease;
}

.report-table tbody tr:hover,
.report-table tbody tr:focus,
.report-table tbody tr:focus-within {
  background: rgba(148, 163, 184, 0.12);
}

.report-metrics {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: 0.9rem;
}

.report-metrics div {
  padding: 0.95rem 1.05rem;
  border-radius: 14px;
  background: var(--accent-subtle);
  border: 1px solid var(--accent-soft);
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  font-weight: 600;
}

.report-metrics span {
  text-transform: uppercase;
  letter-spacing: 0.06em;
  font-size: 0.78rem;
  color: rgba(15, 23, 42, 0.58);
}

.report-metrics strong {
  font-size: 1.25rem;
  color: #0f172a;
}

.dark .report-metrics span {
  color: rgba(226, 232, 240, 0.65);
}

.dark .report-metrics strong {
  color: #e2e8f0;
}

.report-board {
  display: grid;
  gap: 1.1rem;
}

.report-placeholder {
  padding: 2.6rem 1.6rem;
  border-radius: 18px;
  border: 1px dashed var(--accent-soft);
  text-align: center;
  background: rgba(255, 255, 255, 0.95);
  color: var(--accent);
  display: grid;
  justify-items: center;
  gap: 0.75rem;
  font-weight: 600;
}

.report-placeholder .material-icons {
  font-size: 1.6rem;
}

.dark .report-placeholder {
  background: rgba(15, 23, 42, 0.78);
  color: #f0fdf4;
}

.report-projects {
  display: grid;
  gap: 1rem;
}

@media (min-width: 920px) {
  .report-projects {
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  }
}

.report-project {
  position: relative;
  display: flex;
  flex-direction: column;
  gap: 1.1rem;
  padding: 1.35rem 1.4rem;
  border-radius: 20px;
  background: rgba(255, 255, 255, 0.96);
  border: 1px solid var(--accent-soft);
  box-shadow: 0 18px 36px rgba(15, 23, 42, 0.08);
}

.report-project::after {
  content: '';
  position: absolute;
  inset: 0;
  border-radius: 20px;
  border: 1px solid transparent;
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0)) padding-box,
    linear-gradient(135deg, var(--accent-subtle), transparent) border-box;
  opacity: 0;
  transition: opacity 0.25s ease;
  pointer-events: none;
}

.report-project:hover::after {
  opacity: 1;
}

.dark .report-project {
  background: rgba(15, 23, 42, 0.78);
  border-color: rgba(148, 163, 184, 0.3);
}

.report-project__header {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  justify-content: space-between;
}

.report-project__badge {
  flex: 0 0 auto;
  width: 44px;
  height: 44px;
  border-radius: 14px;
  background: var(--accent-subtle);
  border: 1px solid var(--accent-soft);
  color: var(--accent);
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.05rem;
}

.report-project__heading {
  flex: 1;
  min-width: 0;
}

.report-project__heading h3 {
  font-size: 1.02rem;
  font-weight: 700;
  color: #0f172a;
}

.report-project__heading p {
  font-size: 0.82rem;
  color: rgba(15, 23, 42, 0.6);
  margin-top: 0.2rem;
}

.dark .report-project__heading h3 {
  color: #e2e8f0;
}

.dark .report-project__heading p {
  color: rgba(148, 163, 184, 0.75);
}

.report-project__status {
  flex: 0 0 auto;
  display: flex;
  align-items: center;
}

.report-project__meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.6rem;
}

.meta-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.4rem 0.85rem;
  border-radius: 999px;
  background: var(--accent-subtle);
  border: 1px solid var(--accent-soft);
  font-size: 0.8rem;
  color: #0f172a;
}

.meta-label {
  text-transform: uppercase;
  letter-spacing: 0.08em;
  font-size: 0.72rem;
  opacity: 0.7;
}

.dark .meta-pill {
  color: #e2e8f0;
}

.report-project__timeline {
  display: flex;
  flex-wrap: wrap;
  gap: 0.9rem;
  padding: 0.85rem 1rem;
  border-radius: 14px;
  background: rgba(15, 23, 42, 0.04);
  border: 1px solid rgba(15, 23, 42, 0.08);
}

.dark .report-project__timeline {
  background: rgba(148, 163, 184, 0.08);
  border-color: rgba(148, 163, 184, 0.2);
}

.timeline-item {
  display: inline-flex;
  align-items: center;
  gap: 0.6rem;
  min-width: 180px;
}

.timeline-item span.material-icons {
  font-size: 1.1rem;
  color: var(--accent);
}

.timeline-item label {
  font-size: 0.72rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: rgba(15, 23, 42, 0.58);
}

.timeline-item strong {
  display: block;
  font-size: 0.88rem;
  color: #0f172a;
}

.dark .timeline-item label {
  color: rgba(226, 232, 240, 0.65);
}

.dark .timeline-item strong {
  color: #e2e8f0;
}

.report-project__footer {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.9rem;
}

.report-project__type {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  font-weight: 600;
  color: var(--accent);
}

.report-project__actions {
  display: inline-flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.report-cta {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  border-radius: 999px;
  padding: 0.45rem 0.95rem;
  font-size: 0.82rem;
  font-weight: 600;
  border: 1px solid rgba(15, 23, 42, 0.12);
  background: rgba(255, 255, 255, 0.9);
  color: #0f172a;
  transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease;
}

.report-cta .material-icons {
  font-size: 1rem;
}

.report-cta:hover {
  border-color: var(--accent);
  color: var(--accent);
}

.report-cta--primary {
  background: var(--accent);
  color: #fff;
  border-color: transparent;
}

.report-cta--primary:hover {
  background: #1d4ed8;
  color: #fff;
}

.dark .report-cta {
  background: rgba(15, 23, 42, 0.72);
  border-color: rgba(148, 163, 184, 0.25);
  color: #e2e8f0;
}

.dark .report-cta:hover {
  border-color: var(--accent);
  color: #e0f2fe;
}

.report-empty {
  padding: 2.2rem 1.2rem;
  border-radius: 16px;
  border: 1px dashed var(--accent-soft);
  text-align: center;
  color: var(--accent);
  display: grid;
  justify-items: center;
  gap: 0.6rem;
  font-weight: 600;
}

.report-empty .material-icons {
  font-size: 1.6rem;
}

.report-pagination {
  display: flex;
  flex-wrap: wrap;
  gap: 0.45rem;
  justify-content: flex-end;
  padding: 0.9rem 0.2rem 0;
}

.pagination-btn {
  border: 1px solid rgba(148, 163, 184, 0.26);
  border-radius: 10px;
  padding: 0.32rem 0.7rem;
  background: rgba(255, 255, 255, 0.9);
  font-size: 0.78rem;
  font-weight: 600;
  transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease;
}

.pagination-btn:hover {
  border-color: var(--accent);
  color: var(--accent);
}

.pagination-btn.active {
  border-color: var(--accent);
  background: var(--accent);
  color: #fff;
}

.pagination-btn.disabled {
  opacity: 0.45;
  cursor: not-allowed;
}

.report-alert {
  margin-top: 1rem;
  padding: 0.95rem 1.2rem;
  border-radius: 14px;
  font-weight: 600;
  background: rgba(34, 197, 94, 0.16);
  border: 1px solid rgba(22, 163, 74, 0.3);
  color: #166534;
}

.dark .report-alert {
  background: rgba(21, 128, 61, 0.28);
  border-color: rgba(34, 197, 94, 0.4);
  color: #bbf7d0;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.report-panel {
  padding: 0 1.8rem 1.8rem;
  display: grid;
  gap: 1.3rem;
  border-top: 1px solid rgba(15, 23, 42, 0.08);
  background: rgba(255, 255, 255, 0.7);
}

.dark .report-panel {
  background: rgba(15, 23, 42, 0.68);
  border-color: rgba(148, 163, 184, 0.2);
}

.panel-metrics {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: 0.9rem;
}

.panel-metrics div {
  padding: 0.95rem 1.05rem;
  border-radius: 14px;
  background: rgba(59, 130, 246, 0.12);
  border: 1px solid rgba(59, 130, 246, 0.28);
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  font-weight: 600;
}

.panel-metrics span {
  text-transform: uppercase;
  letter-spacing: 0.06em;
  font-size: 0.78rem;
  color: rgba(15, 23, 42, 0.6);
}

.panel-metrics strong {
  font-size: 1.2rem;
  color: #0f172a;
}

.panel-table {
  display: grid;
  gap: 1rem;
}

.table-scroll {
  overflow-x: auto;
  border-radius: 18px;
  border: 1px solid rgba(148, 163, 184, 0.22);
  background: rgba(255, 255, 255, 0.9);
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.6);
}

.table-scroll table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  font-size: 0.92rem;
}

.table-scroll thead th {
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.12), rgba(14, 165, 233, 0.1));
  color: #0f172a;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  padding: 0.95rem 1rem;
  border-bottom: 1px solid rgba(148, 163, 184, 0.22);
}

.table-scroll tbody tr[data-report-row-toggle] {
  cursor: pointer;
  transition: background 0.18s ease;
}

.table-scroll tbody tr[data-report-row-toggle]:hover {
  background: rgba(59, 130, 246, 0.08);
}

.table-scroll tbody td {
  padding: 0.9rem 1rem;
  border-bottom: 1px solid rgba(148, 163, 184, 0.16);
  vertical-align: top;
}

.table-scroll tbody tr:last-child td {
  border-bottom: none;
}

.table-empty {
  text-align: center;
  font-weight: 600;
  padding: 2rem 0;
  color: rgba(15, 23, 42, 0.6);
}

.row-title {
  font-weight: 600;
  color: #0f172a;
  margin-bottom: 0.25rem;
}

.row-sub {
  font-size: 0.82rem;
  color: rgba(15, 23, 42, 0.55);
}

.chip {
  display: inline-flex;
  align-items: center;
  padding: 0.35rem 0.75rem;
  border-radius: 999px;
  font-size: 0.78rem;
  font-weight: 600;
  border: 1px solid transparent;
}

.chip--blue {
  background: rgba(14, 165, 233, 0.16);
  border-color: rgba(14, 165, 233, 0.28);
  color: #0c4a6e;
}

.chip--neutral {
  background: rgba(148, 163, 184, 0.18);
  border-color: rgba(148, 163, 184, 0.3);
  color: #475569;
}

.table-actions {
  display: inline-flex;
  gap: 0.75rem;
  align-items: center;
}

.table-actions a {
  font-size: 0.85rem;
  font-weight: 600;
  color: #2563eb;
  transition: color 0.2s ease;
}

.table-actions a:hover {
  color: #1e3a8a;
}

.table-danger {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.85rem;
  font-weight: 600;
  color: #dc2626;
  background: transparent;
  border: none;
  cursor: pointer;
  padding: 0;
}

.table-danger:hover {
  color: #b91c1c;
}

.table-danger:disabled {
  cursor: not-allowed;
  opacity: 0.6;
}

.table-danger .material-icons {
  font-size: 1rem;
}

.table-detail {
  background: rgba(15, 23, 42, 0.04);
}

.table-detail td {
  padding: 0;
  border: none;
}

.detail-grid {
  display: grid;
  grid-template-columns: minmax(0, 1.7fr) minmax(0, 1fr);
  gap: 1.2rem;
  padding: 1.5rem;
  background: rgba(255, 255, 255, 0.95);
}

.detail-section {
  margin-bottom: 1rem;
}

.detail-section h3 {
  font-size: 0.95rem;
  font-weight: 700;
  color: #0f172a;
  margin-bottom: 0.5rem;
}

.detail-section ul {
  display: grid;
  gap: 0.35rem;
  padding-left: 1.1rem;
  font-size: 0.9rem;
  color: rgba(15, 23, 42, 0.65);
}

.attachments {
  list-style: none;
  padding: 0;
  display: grid;
  gap: 0.35rem;
}

.attachments a {
  color: #2563eb;
  font-weight: 600;
}

.attachments a:hover {
  text-decoration: underline;
}

.detail-prose {
  padding: 1rem;
  border-radius: 14px;
  background: rgba(148, 163, 184, 0.12);
  max-height: 12rem;
  overflow: auto;
}

.detail-side {
  display: grid;
  gap: 0.75rem;
}

.meta-card {
  padding: 0.85rem 1rem;
  border-radius: 14px;
  background: rgba(37, 99, 235, 0.1);
  border: 1px solid rgba(37, 99, 235, 0.22);
  display: flex;
  flex-direction: column;
  gap: 0.32rem;
}

.meta-card span {
  font-size: 0.78rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: rgba(15, 23, 42, 0.6);
}

.meta-card strong {
  font-size: 0.98rem;
  color: #0f172a;
}

.dark .detail-grid {
  background: rgba(15, 23, 42, 0.78);
}

.dark .meta-card {
  background: rgba(37, 99, 235, 0.2);
  border-color: rgba(96, 165, 250, 0.35);
}

.dark .attachments a {
  color: #93c5fd;
}

.mono {
  font-family: 'Fira Code', 'JetBrains Mono', ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;
  font-size: 0.82rem;
  color: rgba(15, 23, 42, 0.7);
}

@media (max-width: 1024px) {
  .detail-grid {
    grid-template-columns: 1fr;
  }
}

.dark .pagination-btn {
  background: rgba(15, 23, 42, 0.78);
  border-color: rgba(148, 163, 184, 0.3);
  color: #e2e8f0;
}

.dark .pagination-btn.active {
  background: var(--accent);
}

.project-fade-enter-active,
.project-fade-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.project-fade-enter-from,
.project-fade-leave-to {
  opacity: 0;
  transform: translateY(6px);
}

@media (max-width: 1024px) {
  .report-surface {
    padding: 2.2rem 1rem 3.5rem;
  }

  .report-hero {
    padding: 2rem;
  }

  .accordion-trigger {
    align-items: flex-start;
  }
}

@media (max-width: 768px) {
  .report-actions {
    justify-content: stretch;
  }

  .report-primary-btn,
  .report-ghost-btn {
    width: 100%;
    justify-content: center;
  }

  .accordion-body {
    padding: 0 1.4rem 1.6rem;
  }
}
</style>
