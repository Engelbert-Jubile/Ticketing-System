<template>
  <div class="legacy-dashboard space-y-6 px-4 py-6 md:px-6">
    <div class="flex items-end justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ titleText }}</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400">{{ subtitleText }}</p>
      </div>
      <div class="text-sm text-slate-500 dark:text-slate-400">{{ dateLabel }}</div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3 fade-in">
      <Link
        v-for="action in quickActions"
        :key="action.label"
        :href="action.href"
        class="card qa"
        :class="action.variant"
      >
        <span class="material-icons" :class="action.iconClass">{{ action.icon }}</span>
        <div>
          <div class="font-semibold">{{ action.label }}</div>
          <div class="kpi-cap">{{ action.description }}</div>
        </div>
      </Link>
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-6">
      <div v-for="card in kpiCards" :key="card.title" class="card card--kpi">
        <div class="flex items-center justify-between">
          <div class="ttl">{{ card.title }}</div>
          <span class="material-icons" :class="card.iconClass">{{ card.icon }}</span>
        </div>
        <div class="val">{{ card.value }}</div>
        <div class="cap">{{ card.caption }}</div>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3 fade-in">
      <div class="card">
        <div class="flex items-center justify-between">
          <div class="text-sm font-semibold">{{ t('dashboard.cards.ticketCompletion') }}</div>
          <div class="kpi-cap">{{ ticketsDone }}/{{ ticketsTotal }}</div>
        </div>
        <div class="mt-2 meter" :class="meterClass(ticketRate)"><i :style="`--w: ${ticketRate}%`"></i></div>
        <div class="mt-1 text-xs text-slate-600 dark:text-slate-300">{{ t('dashboard.cards.ticketsDone', { percent: ticketRate }) }}</div>
        <div class="kpi-cap mt-1">{{ t('dashboard.cards.ticketsSummary', { newCount: ticketsNew, inProgress: ticketsInProgress, done: ticketsDone }) }}</div>
      </div>

      <div class="card">
        <div class="flex items-center justify-between">
          <div class="text-sm font-semibold">{{ t('dashboard.cards.taskCompletion') }}</div>
          <div class="kpi-cap">{{ tasksDone }}/{{ tasksTotal }}</div>
        </div>
        <div class="mt-2 meter" :class="meterClass(taskRate)"><i :style="`--w: ${taskRate}%`"></i></div>
        <div class="mt-1 text-xs text-slate-600 dark:text-slate-300">{{ t('dashboard.cards.tasksDone', { percent: taskRate }) }}</div>
        <div class="kpi-cap mt-1">{{ t('dashboard.cards.period', { period: tasksPeriod || t('common.none') }) }}</div>
      </div>

      <div class="card">
        <div class="flex items-center justify-between">
          <div class="text-sm font-semibold">{{ t('dashboard.cards.projectCompletion') }}</div>
          <div class="kpi-cap">{{ projectsCompleted }}/{{ projectsTotal }}</div>
        </div>
        <div class="mt-2 meter" :class="meterClass(projectRate)"><i :style="`--w: ${projectRate}%`"></i></div>
        <div class="mt-1 text-xs text-slate-600 dark:text-slate-300">{{ t('dashboard.cards.projectsDone', { percent: projectRate }) }}</div>
        <div class="kpi-cap mt-1">{{ t('dashboard.cards.status', { status: projectStatusSummary }) }}</div>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
      <div class="chart-card">
        <h3 class="chart-title">{{ t('dashboard.charts.ticketsByStatus') }}</h3>
        <span id="ticketsHint" class="chart-hint"></span>
        <div class="chart-body">
          <canvas
            id="ticketsChart"
            :data-labels="toJson(ticketsLabels)"
            :data-values="toJson(ticketsValues)"
          ></canvas>
        </div>
      </div>

      <div class="chart-card">
        <h3 class="chart-title">{{ t('dashboard.charts.taskReport') }}</h3>
        <span id="tasksReportHint" class="chart-hint"></span>
        <div class="chart-body">
          <canvas
            id="tasksReportChart"
            :data-labels="toJson(taskReportLabels)"
            :data-values-done="toJson(taskReportDoneCounts)"
            :data-values-progress="toJson(taskReportProgressCounts)"
            :data-period="tasksPeriod"
          ></canvas>
        </div>
      </div>

      <div class="chart-card">
        <h3 class="chart-title">{{ t('dashboard.charts.tasksByStatus') }}</h3>
        <span id="taskStatusHint_top" class="chart-hint"></span>
        <div class="chart-body">
          <canvas
            id="tasksStatusChart_top"
            :data-labels="toJson(taskStatusLabels)"
            :data-values="toJson(taskStatusCounts)"
          ></canvas>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
      <div class="chart-card">
        <h3 class="chart-title">{{ t('dashboard.charts.projectsCreated') }}</h3>
        <span id="projectsCreatedHint" class="chart-hint"></span>
        <div class="chart-body">
          <canvas
            id="projectsCreatedChart"
            :data-labels="toJson(projCreatedLabels)"
            :data-values="toJson(projCreatedCounts)"
            :data-period="projectsPeriod"
          ></canvas>
        </div>
      </div>

      <div class="chart-card">
        <h3 class="chart-title">{{ t('dashboard.charts.projectReport') }}</h3>
        <span id="projectsReportHint" class="chart-hint"></span>
        <div class="chart-body">
          <canvas
            id="projectsReportChart"
            :data-labels="toJson(projReportLabels)"
            :data-values-done="toJson(projReportDoneCounts)"
            :data-values-progress="toJson(projReportProgressCounts)"
            :data-period="projectsPeriod"
          ></canvas>
        </div>
      </div>

      <div class="chart-card">
        <h3 class="chart-title">{{ t('dashboard.charts.projectsByStatus') }}</h3>
        <span id="projectsHint" class="chart-hint"></span>
        <div class="chart-body">
          <canvas
            id="projectsChart"
            :data-labels="toJson(projectStatusLabels)"
            :data-values="toJson(projectStatusCounts)"
          ></canvas>
        </div>
      </div>
    </div>

    <GeminiChatWidget :endpoint="aiEndpoint" :snapshot="aiSnapshot" />
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, watch } from 'vue';
import GeminiChatWidget from '../../Components/GeminiChatWidget.vue';
import resolveRoute from '../../utils/resolveRoute';
import { useI18n } from '../../i18n';

const props = defineProps({
  pageTitle: { type: String, default: '' },
  pageSubtitle: { type: String, default: '' },
  dateLabel: { type: String, default: '' },
  ticketsNew: { type: Number, default: 0 },
  ticketsInProgress: { type: Number, default: 0 },
  ticketsDone: { type: Number, default: 0 },
  usersCount: { type: Number, default: 0 },
  tasksDone: { type: Number, default: 0 },
  projectsCompleted: { type: Number, default: 0 },
  ticketsLabels: { type: Array, default: () => [] },
  ticketsValues: { type: Array, default: () => [] },
  taskStatusLabels: { type: Array, default: () => [] },
  taskStatusCounts: { type: Array, default: () => [] },
  projectStatusLabels: { type: Array, default: () => [] },
  projectStatusCounts: { type: Array, default: () => [] },
  taskReportLabels: { type: Array, default: () => [] },
  taskReportDoneCounts: { type: Array, default: () => [] },
  taskReportProgressCounts: { type: Array, default: () => [] },
  projCreatedLabels: { type: Array, default: () => [] },
  projCreatedCounts: { type: Array, default: () => [] },
  projReportLabels: { type: Array, default: () => [] },
  projReportDoneCounts: { type: Array, default: () => [] },
  projReportProgressCounts: { type: Array, default: () => [] },
  tasksPeriod: { type: String, default: '' },
  projectsPeriod: { type: String, default: '' },
});

const toJson = value => JSON.stringify(value ?? []);
const sumArray = values => (values ?? []).reduce((total, value) => total + Number(value ?? 0), 0);

const { t } = useI18n();

const titleText = computed(() => t('dashboard.title'));
const subtitleText = computed(() => t('dashboard.subtitle'));
const dateLabel = computed(() => props.dateLabel || '');
const ticketsNew = computed(() => Number(props.ticketsNew ?? 0));
const ticketsInProgress = computed(() => Number(props.ticketsInProgress ?? 0));
const ticketsDone = computed(() => Number(props.ticketsDone ?? 0));
const usersCount = computed(() => Number(props.usersCount ?? 0));
const tasksDone = computed(() => Number(props.tasksDone ?? 0));
const projectsCompleted = computed(() => Number(props.projectsCompleted ?? 0));

const ticketsLabels = computed(() => props.ticketsLabels ?? []);
const ticketsValues = computed(() => (props.ticketsValues ?? []).map(value => Number(value ?? 0)));
const taskStatusLabels = computed(() => props.taskStatusLabels ?? []);
const taskStatusCounts = computed(() => (props.taskStatusCounts ?? []).map(value => Number(value ?? 0)));
const projectStatusLabels = computed(() => props.projectStatusLabels ?? []);
const projectStatusCounts = computed(() => (props.projectStatusCounts ?? []).map(value => Number(value ?? 0)));
const taskReportLabels = computed(() => props.taskReportLabels ?? []);
const taskReportDoneCounts = computed(() => (props.taskReportDoneCounts ?? []).map(value => Number(value ?? 0)));
const taskReportProgressCounts = computed(() => (props.taskReportProgressCounts ?? []).map(value => Number(value ?? 0)));
const projCreatedLabels = computed(() => props.projCreatedLabels ?? []);
const projCreatedCounts = computed(() => (props.projCreatedCounts ?? []).map(value => Number(value ?? 0)));
const projReportLabels = computed(() => props.projReportLabels ?? []);
const projReportDoneCounts = computed(() => (props.projReportDoneCounts ?? []).map(value => Number(value ?? 0)));
const projReportProgressCounts = computed(() => (props.projReportProgressCounts ?? []).map(value => Number(value ?? 0)));
const tasksPeriod = computed(() => props.tasksPeriod ?? '');
const projectsPeriod = computed(() => props.projectsPeriod ?? '');

const aiEndpoint = computed(() => resolveRoute('ai.gemini.chat'));
const aiSnapshot = computed(() => ({
  ticketsNew: ticketsNew.value,
  ticketsInProgress: ticketsInProgress.value,
  ticketsDone: ticketsDone.value,
  tasksDone: tasksDone.value,
  projectsCompleted: projectsCompleted.value,
}));

const ticketsTotal = computed(() => ticketsNew.value + ticketsInProgress.value + ticketsDone.value);
const ticketRate = computed(() => (ticketsTotal.value ? Math.round((ticketsDone.value / ticketsTotal.value) * 100) : 0));

const tasksTotal = computed(() => {
  const total = sumArray(props.taskStatusCounts);
  return total || tasksDone.value;
});
const taskRate = computed(() => (tasksTotal.value ? Math.round((tasksDone.value / tasksTotal.value) * 100) : 0));

const projectsTotal = computed(() => {
  const total = sumArray(props.projectStatusCounts);
  return total || projectsCompleted.value;
});
const projectRate = computed(() => (projectsTotal.value ? Math.round((projectsCompleted.value / projectsTotal.value) * 100) : 0));

const projectStatusSummary = computed(() => (projectStatusLabels.value.length ? projectStatusLabels.value.join(', ') : t('common.none')));

const meterClass = rate => {
  if (rate < 40) return 'danger';
  if (rate < 70) return 'warning';
  return '';
};

const quickActions = computed(() => [
  {
    href: resolveRoute('tickets.create'),
    icon: 'add_task',
    iconClass: 'text-blue-600 dark:text-blue-400',
    label: t('dashboard.quickActions.createTicket.title'),
    description: t('dashboard.quickActions.createTicket.subtitle'),
    variant: 'qa-blue',
  },
  {
    href: resolveRoute('tasks.create'),
    icon: 'checklist',
    iconClass: 'text-emerald-600 dark:text-emerald-400',
    label: t('dashboard.quickActions.createTask.title'),
    description: t('dashboard.quickActions.createTask.subtitle'),
    variant: 'qa-emerald',
  },
  {
    href: resolveRoute('projects.create'),
    icon: 'folder_open',
    iconClass: 'text-violet-600 dark:text-violet-400',
    label: t('dashboard.quickActions.createProject.title'),
    description: t('dashboard.quickActions.createProject.subtitle'),
    variant: 'qa-violet',
  },
]);

const kpiCards = computed(() => [
  {
    title: t('dashboard.kpis.ticketsNew.title'),
    value: ticketsNew.value,
    icon: 'fiber_new',
    iconClass: 'text-blue-500',
    caption: t('dashboard.kpis.ticketsNew.caption'),
  },
  {
    title: t('dashboard.kpis.ticketsInProgress.title'),
    value: ticketsInProgress.value,
    icon: 'autorenew',
    iconClass: 'text-amber-500',
    caption: t('dashboard.kpis.ticketsInProgress.caption'),
  },
  {
    title: t('dashboard.kpis.ticketsDone.title'),
    value: ticketsDone.value,
    icon: 'task_alt',
    iconClass: 'text-emerald-500',
    caption: t('dashboard.kpis.ticketsDone.caption'),
  },
  {
    title: t('dashboard.kpis.users.title'),
    value: usersCount.value,
    icon: 'people',
    iconClass: 'text-indigo-500',
    caption: t('dashboard.kpis.users.caption'),
  },
  {
    title: t('dashboard.kpis.tasksDone.title'),
    value: tasksDone.value,
    icon: 'check_circle',
    iconClass: 'text-sky-500',
    caption: t('dashboard.kpis.tasksDone.caption'),
  },
  {
    title: t('dashboard.kpis.projectsDone.title'),
    value: projectsCompleted.value,
    icon: 'done_all',
    iconClass: 'text-pink-500',
    caption: t('dashboard.kpis.projectsDone.caption'),
  },
]);

let chartsModule;

const refreshCharts = async () => {
  if (!chartsModule) {
    chartsModule = await import('../../dashboard.js');
  }
  if (typeof chartsModule?.destroyDashboardCharts === 'function') {
    chartsModule.destroyDashboardCharts();
  }
  await nextTick();
  if (typeof chartsModule?.initDashboardCharts === 'function') {
    await chartsModule.initDashboardCharts();
  }
};

onMounted(async () => {
  await nextTick();
  await refreshCharts();
});

watch(
  () => [
    props.ticketsLabels,
    props.ticketsValues,
    props.taskStatusLabels,
    props.taskStatusCounts,
    props.projectStatusLabels,
    props.projectStatusCounts,
    props.taskReportLabels,
    props.taskReportDoneCounts,
    props.taskReportProgressCounts,
    props.projCreatedLabels,
    props.projCreatedCounts,
    props.projReportLabels,
    props.projReportDoneCounts,
    props.projReportProgressCounts,
    props.tasksPeriod,
    props.projectsPeriod,
  ],
  async () => {
    await refreshCharts();
  },
  { deep: true }
);

onBeforeUnmount(() => {
  if (chartsModule?.destroyDashboardCharts) {
    chartsModule.destroyDashboardCharts();
  }
});
</script>

<style scoped>
/* Keep dashboard transitions even if OS requests reduced motion */
@media (prefers-reduced-motion: reduce) {
  .legacy-dashboard,
  .legacy-dashboard * {
    transition-duration: 0.25s !important;
    animation-duration: 0.25s !important;
    transition-timing-function: ease !important;
  }
}
</style>

<style scoped>
.legacy-dashboard {
  max-width: 1120px;
  margin-inline: auto;
}

.fade-in {
  animation: fadeInUp 0.4s var(--ease-smooth, cubic-bezier(.22, .61, .36, 1)) forwards;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(8px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.card {
  border-radius: 1rem;
  background: var(--card-bg, #fff);
  box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08);
  padding: 1rem;
  transition: box-shadow 0.18s ease;
}

.card--kpi {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  min-height: 150px;
}

.dark .card {
  --card-bg: rgba(30, 41, 59, 0.85);
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.35);
}

.qa {
  position: relative;
  display: flex;
  align-items: center;
  gap: 0.9rem;
  border: 1px solid var(--qa-border, rgba(229, 231, 235, 1));
  border-radius: 1rem;
  transition: background-color 0.18s ease, border-color 0.18s ease, box-shadow 0.2s ease, transform 0.18s ease;
  isolation: isolate;
  text-decoration: none;
}

.qa::after {
  content: "";
  position: absolute;
  inset: 0;
  border-radius: inherit;
  background: var(--qa-hover-bg, transparent);
  opacity: 0;
  transition: opacity 0.18s ease;
  pointer-events: none;
}

.qa:hover {
  box-shadow: 0 4px 22px -12px rgba(15, 23, 42, 0.35);
  transform: translateY(-1px);
}

.qa:hover::after {
  opacity: 1;
}

.qa:focus-visible {
  outline: 3px solid var(--qa-ring, rgba(59, 130, 246, 0.28));
  outline-offset: 2px;
}

.qa-blue {
  --qa-border: rgba(59, 130, 246, 0.25);
  --qa-hover-bg: rgba(59, 130, 246, 0.08);
  --qa-ring: rgba(59, 130, 246, 0.28);
}

.dark .qa-blue {
  --qa-border: rgba(59, 130, 246, 0.32);
  --qa-hover-bg: rgba(59, 130, 246, 0.14);
  --qa-ring: rgba(59, 130, 246, 0.36);
}

.qa-emerald {
  --qa-border: rgba(16, 185, 129, 0.25);
  --qa-hover-bg: rgba(16, 185, 129, 0.08);
  --qa-ring: rgba(16, 185, 129, 0.28);
}

.dark .qa-emerald {
  --qa-border: rgba(16, 185, 129, 0.32);
  --qa-hover-bg: rgba(16, 185, 129, 0.14);
  --qa-ring: rgba(16, 185, 129, 0.34);
}

.qa-violet {
  --qa-border: rgba(139, 92, 246, 0.25);
  --qa-hover-bg: rgba(139, 92, 246, 0.08);
  --qa-ring: rgba(139, 92, 246, 0.3);
}

.dark .qa-violet {
  --qa-border: rgba(139, 92, 246, 0.32);
  --qa-hover-bg: rgba(139, 92, 246, 0.15);
  --qa-ring: rgba(129, 140, 248, 0.36);
}

.qa .material-icons {
  font-size: 1.65rem;
}

.kpi-cap {
  font-size: 0.68rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #64748b;
}

.ttl {
  font-size: 0.72rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #64748b;
}

.val {
  margin-top: 0.3rem;
  font-size: 1.9rem;
  font-weight: 700;
  color: #0f172a;
}

.cap {
  font-size: 0.72rem;
  color: #64748b;
  line-height: 1.35;
}

.card--kpi .cap {
  margin-top: auto;
}

.dark .kpi-cap,
.dark .ttl,
.dark .cap {
  color: rgba(203, 213, 225, 0.78);
}

.dark .val {
  color: #f8fafc;
}

.meter {
  height: 0.6rem;
  border-radius: 0.5rem;
  background: rgba(148, 163, 184, 0.2);
  overflow: hidden;
}

.meter > i {
  display: block;
  height: 100%;
  border-radius: 0.5rem;
  width: var(--w, 0%);
  background: linear-gradient(90deg, #22c55e, #16a34a);
  box-shadow: 0 1px 3px rgba(16, 185, 129, 0.35) inset;
}

.meter.warning > i {
  background: linear-gradient(90deg, #f59e0b, #d97706);
  box-shadow: 0 1px 3px rgba(245, 158, 11, 0.35) inset;
}

.meter.danger > i {
  background: linear-gradient(90deg, #ef4444, #b91c1c);
  box-shadow: 0 1px 3px rgba(239, 68, 68, 0.35) inset;
}

.dark .meter {
  background: rgba(148, 163, 184, 0.25);
}

.chart-card {
  position: relative;
  height: 320px;
  border-radius: 1rem;
  background: var(--card-bg, #fff);
  box-shadow: 0 1px 2px rgba(15, 23, 42, 0.1);
  padding: 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.chart-title {
  font-size: 0.95rem;
  font-weight: 600;
  color: #1f2937;
}

.dark .chart-card {
  --card-bg: rgba(30, 41, 59, 0.85);
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.45);
}

.dark .chart-title {
  color: #e5e7eb;
}

.chart-body {
  flex: 1 1 auto;
}

.chart-body canvas {
  width: 100%;
  height: 100%;
}

.chart-hint {
  position: absolute;
  top: 0.75rem;
  right: 1rem;
  font-size: 0.68rem;
  font-weight: 500;
  text-align: right;
  color: #6b7280;
  white-space: pre-line;
}

.dark .chart-hint {
  color: rgba(203, 213, 225, 0.78);
}

.chart-hint .k {
  font-weight: 600;
  margin-right: 0.25rem;
  display: inline-block;
}

.chart-hint .v {
  font-weight: 600;
  color: #1d4ed8;
}

.dark .chart-hint .v {
  color: #bfdbfe;
}

:deep(.mini-legend) {
  position: absolute;
  top: 0.35rem;
  left: 50%;
  transform: translateX(-50%);
  display: inline-flex;
  gap: 0.5rem;
  z-index: 5;
}

:deep(.mini-legend .ml-item) {
  width: 18px;
  height: 12px;
  border-radius: 0.25rem;
  border: 2px solid var(--ml-color, #94a3b8);
  background: var(--ml-bg, rgba(148, 163, 184, 0.28));
  box-shadow: 0 0 0 1px rgba(15, 23, 42, 0.05);
  position: relative;
  cursor: pointer;
  transition: transform 0.18s ease;
}

:deep(.mini-legend .ml-item:hover) {
  transform: translateY(-1px);
}

:deep(.mini-legend .ml-item::before) {
  content: "";
  position: absolute;
  left: -2px;
  right: -2px;
  top: 50%;
  height: 2px;
  background: #111;
  transform: translateY(-50%) scaleX(0);
  transform-origin: center;
  transition: transform 0.18s ease;
}

:deep(.mini-legend .ml-item.is-off::before) {
  transform: translateY(-50%) scaleX(1);
}

@media (max-width: 1023px) {
  .legacy-dashboard {
    padding-inline: 1rem;
  }
  .chart-card {
    height: 300px;
  }
}
</style>




