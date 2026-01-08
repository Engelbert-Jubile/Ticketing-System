<template>
  <div class="space-y-8 px-4 py-6 md:px-6">
    <header class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
      <div>
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Unit {{ unitName || '—' }}</p>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ titleText }}</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400">{{ subtitleText }}</p>
      </div>
      <div class="text-sm text-slate-500 dark:text-slate-400">{{ dateLabel }}</div>
    </header>

    <section class="grid grid-cols-1 gap-4 md:grid-cols-3">
      <Link
        v-for="action in quickActions"
        :key="action.label"
        :href="action.href"
        class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-700 dark:bg-slate-900"
      >
        <span class="material-icons text-2xl" :class="action.iconClass">{{ action.icon }}</span>
        <div>
          <div class="font-semibold text-slate-900 dark:text-slate-100">{{ action.label }}</div>
          <div class="text-xs text-slate-500 dark:text-slate-400">{{ action.description }}</div>
        </div>
      </Link>
    </section>

    <section class="grid grid-cols-1 gap-4 md:grid-cols-4">
      <div class="summary-card">
        <div class="summary-label">Tickets</div>
        <div class="summary-value text-blue-600">{{ ticketsTotal }}</div>
        <p class="summary-hint">New: {{ ticketsNew }} · Progress: {{ ticketsInProgress }} · Done: {{ ticketsDone }}</p>
      </div>
      <div class="summary-card">
        <div class="summary-label">Tasks</div>
        <div class="summary-value text-emerald-600">{{ tasksTotal }}</div>
        <p class="summary-hint">Done: {{ tasksDone }} · Active: {{ tasksActive }}</p>
      </div>
      <div class="summary-card">
        <div class="summary-label">Projects</div>
        <div class="summary-value text-indigo-600">{{ projectsTotal }}</div>
        <p class="summary-hint">Done: {{ projectsCompleted }} · Active: {{ projectsActive }}</p>
      </div>
      <div class="summary-card">
        <div class="summary-label">Member Unit</div>
        <div class="summary-value text-amber-600">{{ usersCount }}</div>
        <p class="summary-hint">Active Agent</p>
      </div>
    </section>

    <section class="grid grid-cols-1 gap-4 lg:grid-cols-3">
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
          <p class="panel-hint">New: {{ ticketsNew }} • In Progress: {{ ticketsInProgress }} • Done: {{ ticketsDone }}</p>
          <p class="panel-hint">Periode: {{ projectsPeriod }}</p>
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
          <p class="panel-hint">Selesai: {{ tasksDone }} • Total: {{ tasksTotal }}</p>
          <p class="panel-hint">Periode: {{ tasksPeriod }}</p>
        </div>
      </div>

      <div class="panel">
        <header class="panel-head">
          <div>
            <p class="panel-kicker">Performa Unit</p>
            <h2 class="panel-title">Project</h2>
          </div>
          <span class="panel-badge">{{ projectsCompletion }}% selesai</span>
        </header>
        <div class="panel-body">
          <div class="meter" :class="meterClass(projectsCompletion)"><i :style="`--w:${projectsCompletion}%`"></i></div>
          <p class="panel-hint">Selesai: {{ projectsCompleted }} • Total: {{ projectsTotal }}</p>
          <p class="panel-hint">Periode: {{ projectsPeriod }}</p>
        </div>
      </div>
    </section>

    <section class="panel">
      <header class="panel-head">
        <div>
          <p class="panel-kicker">Tim Unit</p>
          <h2 class="panel-title">User</h2>
        </div>
        <span class="panel-badge">{{ agents.length }} orang</span>
      </header>
      <div class="panel-body overflow-x-auto">
        <table class="w-full min-w-[640px] text-left">
          <thead>
            <tr class="text-xs uppercase tracking-wide text-slate-500">
              <th class="py-2">Nama</th>
              <th class="py-2">Email</th>
              <th class="py-2 text-center">Tickets</th>
              <th class="py-2 text-center">Tasks</th>
              <th class="py-2 text-center">Projects</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!agents.length">
              <td colspan="5" class="py-4 text-center text-sm text-slate-500">Belum ada anggota di unit ini.</td>
            </tr>
            <tr
              v-for="agent in agents"
              :key="agent.id"
              class="border-t border-slate-200 text-sm dark:border-slate-700"
            >
              <td class="py-3 font-semibold text-slate-900 dark:text-slate-100">{{ agent.name }}</td>
              <td class="py-3 text-slate-600 dark:text-slate-300">{{ agent.email || '—' }}</td>
              <td class="py-3 text-center font-semibold">{{ agent.tickets }}</td>
              <td class="py-3 text-center font-semibold">{{ agent.tasks }}</td>
              <td class="py-3 text-center font-semibold">{{ agent.projects }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import resolveRoute from '../../utils/resolveRoute';

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
});

const titleText = computed(() => props.pageTitle || 'Unit Reports');
const subtitleText = computed(() => props.pageSubtitle || 'Ringkasan unit.');

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

const ticketsCompletion = computed(() =>
  ticketsTotal.value ? Math.round((ticketsDone.value / ticketsTotal.value) * 100) : 0
);
const tasksCompletion = computed(() =>
  tasksTotal.value ? Math.round((tasksDone.value / tasksTotal.value) * 100) : 0
);
const projectsCompletion = computed(() =>
  projectsTotal.value ? Math.round((projectsCompleted.value / projectsTotal.value) * 100) : 0
);

const meterClass = rate => {
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
  {
    href: resolveRoute('projects.create'),
    icon: 'folder_open',
    iconClass: 'text-indigo-600 dark:text-indigo-400',
    label: 'Project Baru',
    description: 'Kelola scope & progress unit',
  },
]);
</script>

<style scoped>
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
