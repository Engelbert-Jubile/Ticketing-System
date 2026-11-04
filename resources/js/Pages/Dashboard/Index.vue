<template>
  <div class="legacy-dashboard space-y-6 px-4 py-6 md:px-6">
    <Head :title="$t(&quot;Dashboard&quot;)" />
    <Link :href="resolveRoute('work.index')" class="flex items-center justify-between gap-3 rounded-2xl border border-blue-200 bg-blue-50 p-5 text-blue-900 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-100">
      <span><strong>{{ $t("Pekerjaan Saya") }}</strong><span class="mt-1 block text-sm">{{ $t("Lihat pekerjaan aktif, konfirmasi yang menunggu Anda, dan deadline terdekat.") }}</span></span>
      <span class="material-icons" aria-hidden="true">arrow_forward</span>
    </Link>
    <div class="flex items-end justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ titleText }}</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400">{{ subtitleText }}</p>
      </div>
      <div class="text-sm text-slate-500 dark:text-slate-400">{{ dateLabel }}</div>
    </div>

    <section class="usage-grid" :aria-label="usageText('Statistik penggunaan', 'Usage statistics')">
      <article v-for="metric in usageCards" :key="metric.icon" class="usage-card">
        <div class="usage-icon" :class="metric.variant"><span class="material-icons" aria-hidden="true">{{ metric.icon }}</span></div>
        <div class="usage-content"><h2 class="usage-label">{{ metric.label }}</h2><p class="usage-value">{{ formatUsage(metric.value) }}</p><p class="usage-caption">{{ metric.caption }}</p></div>
        <div class="usage-meta"><span class="usage-scope">{{ usageMetrics.scope === 'all' ? usageText('Semua pengguna', 'All users') : usageText('Anda', 'You') }}</span><span class="usage-since" :title="usageText('Pencatatan dimulai saat fitur diaktifkan', 'Tracking starts when this feature is enabled')">{{ usageMetrics.since ? usageText('Sejak ', 'Since ') + usageMetrics.since : usageText('Belum ada data', 'No data yet') }}</span></div>
      </article>
    </section>

    <div class="grid grid-cols-1 gap-4 fade-in" :class="showProjectDashboard ? 'md:grid-cols-3' : 'md:grid-cols-2'">
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

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2" :class="showProjectDashboard ? 'lg:grid-cols-6' : 'lg:grid-cols-5'">
      <div v-for="card in kpiCards" :key="card.title" class="card card--kpi">
        <div class="flex items-center justify-between">
          <div class="ttl">{{ card.title }}</div>
          <span class="material-icons" :class="card.iconClass">{{ card.icon }}</span>
        </div>
        <div class="val">{{ card.value }}</div>
        <div class="cap">{{ card.caption }}</div>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-4 fade-in" :class="showProjectDashboard ? 'md:grid-cols-3' : 'md:grid-cols-2'">
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

      <div v-if="showProjectDashboard" class="card">
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

    <div v-if="showProjectDashboard" class="grid grid-cols-1 gap-5 lg:grid-cols-3">
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
import { translateUi as uiText } from '../../i18n'

import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, watch } from 'vue';
import GeminiChatWidget from '../../Components/GeminiChatWidget.vue';
import resolveRoute from '../../utils/resolveRoute';
import { useI18n } from '../../i18n';

const props = defineProps({
  usageMetrics: { type: Object, default: () => ({ visits: null, logins: null, since: null, scope: 'personal' }) },
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
const usagePage = usePage();
const usageText = (id, en) => usagePage.props.locale === 'en' ? en : id;
const usageCards = computed(() => [
  { label: 'Total Visit', value: props.usageMetrics.visits, icon: 'visibility', variant: 'usage-icon--visits', caption: usageText('Kunjungan dashboard', 'Dashboard visits') },
  { label: usageText('Total Frekuensi Login', 'Total Login Frequency'), value: props.usageMetrics.logins, icon: 'login', variant: 'usage-icon--logins', caption: usageText('Login berhasil', 'Successful sign-ins') },
]);
const formatUsage = value => value === null || value === undefined ? '—' : Number(value).toLocaleString();
const showProjectDashboard = false;

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

.usage-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem}
.usage-card{display:flex;align-items:center;gap:1rem;min-width:0;padding:1.2rem 1.25rem;border:1px solid #e2e8f0;border-radius:1rem;background:#fff;box-shadow:0 2px 5px -3px rgb(15 23 42/.12)}
.usage-icon{display:flex;align-items:center;justify-content:center;flex-shrink:0;width:3rem;height:3rem;border-radius:.95rem}
.usage-icon--visits{background:#eff6ff;color:#2563eb}.usage-icon--logins{background:#f0fdfa;color:#0d9488}.usage-icon .material-icons{font-size:1.5rem}
.usage-content{min-width:0}.usage-label{font-size:.8rem;font-weight:600;color:#475569;line-height:1.4}.usage-value{margin-top:.2rem;font-size:1.85rem;font-weight:750;line-height:1.15;letter-spacing:-.04em;font-variant-numeric:tabular-nums;color:#0f172a;overflow-wrap:anywhere}.usage-caption{margin-top:.35rem;font-size:.7rem;color:#64748b}
.usage-meta{display:flex;flex-direction:column;align-items:flex-end;align-self:stretch;justify-content:space-between;gap:.75rem;margin-left:auto;text-align:right;flex-shrink:0}.usage-scope{border-radius:999px;padding:.3rem .55rem;background:#f1f5f9;color:#64748b;font-size:.62rem;font-weight:600;white-space:nowrap}.usage-since{font-size:.62rem;color:#94a3b8;white-space:nowrap}
.dark .usage-card{background:#1e293b;border-color:#334155}.dark .usage-label{color:#cbd5e1}.dark .usage-value{color:#f8fafc}.dark .usage-caption{color:#94a3b8}.dark .usage-scope{background:#334155;color:#cbd5e1}.dark .usage-icon--visits{background:rgb(59 130 246/.15);color:#93c5fd}.dark .usage-icon--logins{background:rgb(20 184 166/.15);color:#5eead4}
@media(max-width:767px){.usage-card{flex-wrap:wrap;padding:1rem;gap:.75rem}.usage-meta{flex-basis:100%;flex-direction:row;align-items:center;margin-left:0;padding-top:.5rem;border-top:1px solid #f1f5f9}.dark .usage-meta{border-color:#334155}.usage-value{font-size:1.6rem}.usage-icon{height:2.5rem;width:2.5rem}}
@media(max-width:479px){.usage-grid{grid-template-columns:1fr}.usage-card{flex-wrap:nowrap}.usage-meta{flex-basis:auto;flex-direction:column;align-items:flex-end;margin-left:auto;padding-top:0;border:0}.usage-since{white-space:normal}}

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




