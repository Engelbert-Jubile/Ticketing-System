<template>
  <div class="unit-reports-page space-y-8 px-4 py-6 md:px-6">
    <header class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
      <div>
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Unit {{ unitName || '-' }}</p>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ titleText }}</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400">{{ subtitleText }}</p>
      </div>
      <div class="text-sm text-slate-500 dark:text-slate-400">{{ dateLabel }}</div>
    </header>

    <section class="grid grid-cols-1 gap-4" :class="showProjectUnit ? 'md:grid-cols-3' : 'md:grid-cols-2'">
      <Link
        v-for="action in quickActions"
        :key="action.label"
        :href="action.href"
        class="quick-action"
      >
        <span class="material-icons text-2xl" :class="action.iconClass">{{ action.icon }}</span>
        <div>
          <div class="font-semibold text-slate-900 dark:text-slate-100">{{ action.label }}</div>
          <div class="text-xs text-slate-500 dark:text-slate-400">{{ action.description }}</div>
        </div>
      </Link>
    </section>

    <section class="grid grid-cols-1 gap-4 sm:grid-cols-2" :class="showProjectUnit ? 'xl:grid-cols-4' : 'xl:grid-cols-3'">
      <div class="summary-card">
        <div class="summary-label">Tickets</div>
        <div class="summary-value text-blue-600">{{ ticketsTotal }}</div>
        <p class="summary-hint">New: {{ ticketsNew }} - Progress: {{ ticketsInProgress }} - Done: {{ ticketsDone }}</p>
      </div>
      <div class="summary-card">
        <div class="summary-label">Tasks</div>
        <div class="summary-value text-emerald-600">{{ tasksTotal }}</div>
        <p class="summary-hint">Done: {{ tasksDone }} - Active: {{ tasksActive }}</p>
      </div>
      <div class="summary-card">
        <div class="summary-label">Member Unit</div>
        <div class="summary-value text-amber-600">{{ usersCount }}</div>
        <p class="summary-hint">Active Agent</p>
      </div>
    </section>

    <section class="grid grid-cols-1 gap-4" :class="showProjectUnit ? 'xl:grid-cols-3' : 'xl:grid-cols-2'">
      <div class="panel">
        <header class="panel-head">
          <div>
            <p class="panel-kicker">Performa Unit</p>
            <h2 class="panel-title">Ticket</h2>
          </div>
          <span class="panel-badge">{{ ticketsCompletion }}% selesai</span>
        </header>
        <div class="panel-body">
          <div class="meter" :class="meterClass(ticketsCompletion)"><i :style="`--w:${ticketsCompletion}%`"></i></div>
          <p class="panel-hint">New: {{ ticketsNew }} - In Progress: {{ ticketsInProgress }} - Done: {{ ticketsDone }}</p>
          <p class="panel-hint">Periode: {{ tasksPeriod }}</p>
        </div>
      </div>

      <div class="panel">
        <header class="panel-head">
          <div>
            <p class="panel-kicker">Performa Unit</p>
            <h2 class="panel-title">Task</h2>
          </div>
          <span class="panel-badge">{{ tasksCompletion }}% selesai</span>
        </header>
        <div class="panel-body">
          <div class="meter" :class="meterClass(tasksCompletion)"><i :style="`--w:${tasksCompletion}%`"></i></div>
          <p class="panel-hint">Selesai: {{ tasksDone }} - Total: {{ tasksTotal }}</p>
          <p class="panel-hint">Periode: {{ tasksPeriod }}</p>
        </div>
      </div>

    </section>

    <section class="panel">
        <header class="panel-head">
          <div>
            <p class="panel-kicker">Tim Unit</p>
              <h2 class="panel-title">{{ isSuperadmin && !selectedUnitModel ? "Unit" : "User" }}</h2>
          </div>

          <div class="flex items-center gap-2">
            <template v-if="isSuperadmin">
              <label class="text-xs font-semibold text-slate-500 dark:text-slate-300">Filter Unit</label>
                <div class="min-w-[220px] max-w-[520px]">
                  <FancySelect
                    v-model="selectedUnitModel"
                    :options="unitSelectOptions"
                    accent="subtle"
                  />
                </div>
            </template>

              <span class="panel-badge" v-if="isSuperadmin && !selectedUnitModel">{{ unitSummary.length }} unit</span>
            <span class="panel-badge" v-else>{{ agents.length }} orang</span>
          </div>
        </header>

          <div class="panel-body overflow-x-auto" v-if="isSuperadmin && !selectedUnitModel">
          <table class="w-full min-w-[640px] text-left">
            <thead>
              <tr class="text-xs uppercase tracking-wide text-slate-500">
                <th class="py-2">Unit</th>
                <th class="py-2 text-center">Users</th>
                <th class="py-2 text-center">Tickets</th>
                <th class="py-2 text-center">Tasks</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!unitSummary.length">
                <td colspan="4" class="py-4 text-center text-sm text-slate-500">Belum ada data unit.</td>
              </tr>
              <tr
                v-for="row in unitSummary"
                :key="row.unit"
                class="border-t border-slate-200 text-sm dark:border-slate-700"
              >
                <td class="py-3 font-semibold text-slate-900 dark:text-slate-100">
                    <Link :href="resolveRoute('dashboard.unit-reports', { unit: row.unit })">
                    {{ row.unit }}
                  </Link>
                </td>
                <td class="py-3 text-center font-semibold">{{ row.users }}</td>
                <td class="py-3 text-center font-semibold">{{ row.tickets }}</td>
                <td class="py-3 text-center font-semibold">{{ row.tasks }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="panel-body overflow-x-auto" v-else>
          <table class="w-full min-w-[640px] text-left">
            <thead>
              <tr class="text-xs uppercase tracking-wide text-slate-500">
                <th class="py-2">Nama</th>
                <th class="py-2">Email</th>
                <th class="py-2 text-center">Tickets</th>
                <th class="py-2 text-center">Tasks</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!agents.length">
                <td colspan="4" class="py-4 text-center text-sm text-slate-500">Belum ada anggota di unit ini.</td>
              </tr>
              <tr
                v-for="agent in agents"
                :key="agent.id"
                class="border-t border-slate-200 text-sm dark:border-slate-700"
              >
                <td class="py-3 font-semibold text-slate-900 dark:text-slate-100">{{ agent.name }}</td>
                <td class="py-3 text-slate-600 dark:text-slate-300">{{ agent.email || "-" }}</td>
                <td class="py-3 text-center font-semibold">{{ agent.tickets }}</td>
                <td class="py-3 text-center font-semibold">{{ agent.tasks }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
  </div>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import resolveRoute from '../../utils/resolveRoute';
import FancySelect from "../../Components/FancySelect.vue";

const showProjectUnit = false;

const props = defineProps({
  pageTitle: { type: String, default: 'Unit Reports' },
  pageSubtitle: { type: String, default: '' },
  unitName: { type: String, default: '' },
  dateLabel: { type: String, default: '' },
  ticketsNew: { type: Number, default: 0 },
  ticketsInProgress: { type: Number, default: 0 },
  ticketsDone: { type: Number, default: 0 },
  usersCount: { type: Number, default: 0 },
  tasksDone: { type: Number, default: 0 },
  projectsCompleted: { type: Number, default: 0 },
  taskStatusCounts: { type: Array, default: () => [] },
  projectStatusCounts: { type: Array, default: () => [] },
  tasksPeriod: { type: String, default: '' },
  projectsPeriod: { type: String, default: '' },
  agents: { type: Array, default: () => [] },

  // superadmin
  isSuperadmin: { type: Boolean, default: false },
  unitFilter: { type: String, default: '' },
  unitOptions: { type: Array, default: () => [] },
  unitSummary: { type: Array, default: () => [] },
});

// Superadmin controls
const isSuperadmin = computed(() => Boolean(props.isSuperadmin));
const unitFilter = computed(() => String(props.unitFilter ?? ''));
const unitOptions = computed(() => (Array.isArray(props.unitOptions) ? props.unitOptions : []));
const unitSummary = computed(() => (Array.isArray(props.unitSummary) ? props.unitSummary : []));

// FancySelect - Unit filter
const unitSelectOptions = computed(() => [{ value: "", label: "Semua Unit" }].concat(
  unitOptions.value.map(u => ({ value: String(u), label: String(u) }))
));

const selectedUnitModel = computed({
  get() {
    if (typeof window === "undefined") return unitFilter.value;
    const q = new URLSearchParams(window.location.search);
    return q.get("unit") ?? "";
  },
  set(v) {
    const value = v == null ? "" : String(v);
    router.visit(resolveRoute("dashboard.unit-reports"), {
      data: value ? { unit: value } : {},
      preserveState: false,
    });
  },
});



const titleText = computed(() => props.pageTitle || 'Unit Reports');
const subtitleText = computed(() => (props.pageSubtitle || 'Ringkasan ticket dan task untuk unit.').replace('ticket, task, dan project untuk unit', 'ticket dan task untuk unit').replace('ticket, task, dan project', 'ticket dan task'));
const dateLabel = computed(() => props.dateLabel || '');
const usersCount = computed(() => Number(props.usersCount ?? 0));
const tasksPeriod = computed(() => props.tasksPeriod || '-');

const ticketsNew = computed(() => Number(props.ticketsNew ?? 0));
const ticketsInProgress = computed(() => Number(props.ticketsInProgress ?? 0));
const ticketsDone = computed(() => Number(props.ticketsDone ?? 0));
const tasksDone = computed(() => Number(props.tasksDone ?? 0));
const projectsCompleted = computed(() => Number(props.projectsCompleted ?? 0));

const ticketsTotal = computed(() => ticketsNew.value + ticketsInProgress.value + ticketsDone.value);
const tasksTotal = computed(() => {
  const raw = props.taskStatusCounts ?? [];
  const sum = raw.reduce((acc, val) => acc + Number(val ?? 0), 0);
  return sum || tasksDone.value;
});
const projectsTotal = computed(() => {
  const raw = props.projectStatusCounts ?? [];
  const sum = raw.reduce((acc, val) => acc + Number(val ?? 0), 0);
  return sum || projectsCompleted.value;
});

const tasksActive = computed(() => Math.max(tasksTotal.value - tasksDone.value, 0));
const projectsActive = computed(() => Math.max(projectsTotal.value - projectsCompleted.value, 0));

const ticketsCompletion = computed(() => (ticketsTotal.value ? Math.round((ticketsDone.value / ticketsTotal.value) * 100) : 0));
const tasksCompletion = computed(() => (tasksTotal.value ? Math.round((tasksDone.value / tasksTotal.value) * 100) : 0));
const projectsCompletion = computed(() => (projectsTotal.value ? Math.round((projectsCompleted.value / projectsTotal.value) * 100) : 0));

const meterClass = (rate) => {
  if (rate < 40) return 'danger';
  if (rate < 70) return 'warning';
  return 'ok';
};

const quickActions = computed(() => [
  {
    href: resolveRoute('tickets.create'),
    icon: 'add_task',
    iconClass: 'text-blue-600 dark:text-blue-400',
    label: 'Buat Ticket',
    description: 'Catat masalah baru untuk unit',
  },
  {
    href: resolveRoute('tasks.create'),
    icon: 'checklist',
    iconClass: 'text-emerald-600 dark:text-emerald-400',
    label: 'Tambah Task',
    description: 'Breakdown pekerjaan tim unit',
  },
]);
</script>

<style scoped>
.unit-reports-page {
  max-width: 1280px;
  margin-inline: auto;
}
.quick-action {
  @apply flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-700 dark:bg-slate-900;
}
.summary-card {
  @apply rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900;
}
.summary-label {
  @apply text-sm font-semibold text-slate-600 dark:text-slate-300;
}
.summary-value {
  @apply text-3xl font-bold;
}
.summary-hint {
  @apply text-xs text-slate-500 dark:text-slate-400;
}
.panel {
  @apply rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900;
}
.panel-head {
  @apply flex items-center justify-between gap-3 border-b border-slate-200 px-4 py-3 dark:border-slate-700;
}
.panel-kicker {
  @apply text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300;
}
.panel-title {
  @apply text-lg font-semibold text-slate-900 dark:text-slate-100;
}
.panel-badge {
  @apply rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200;
}
.panel-body {
  @apply space-y-2 px-4 py-4;
}
.panel-hint {
  @apply text-xs text-slate-500 dark:text-slate-400;
}
.meter {
  position: relative;
  height: 8px;
  border-radius: 9999px;
  background: linear-gradient(to right, #e5e7eb, #e5e7eb);
  overflow: hidden;
}
.meter i {
  position: absolute;
  inset: 0;
  width: var(--w, 0%);
  border-radius: 9999px;
  background: linear-gradient(90deg, #22c55e, #2563eb);
}
.meter.danger i {
  background: linear-gradient(90deg, #f87171, #f59e0b);
}
.meter.warning i {
  background: linear-gradient(90deg, #f59e0b, #22c55e);
}
</style>



