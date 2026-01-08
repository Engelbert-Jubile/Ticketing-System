<template>
  <div class="space-y-10 pb-16">
    <section
      class="relative overflow-visible rounded-[32px] border border-slate-200/70 bg-gradient-to-br from-indigo-200/40 via-white to-slate-50 p-8 shadow-lg dark:border-slate-700/70 dark:from-indigo-500/10 dark:via-slate-900 dark:to-slate-950">
      <div
        class="absolute -right-16 -top-16 h-56 w-56 rounded-full bg-indigo-500/10 blur-3xl dark:bg-indigo-400/20"></div>
      <div class="absolute -left-12 bottom-0 h-64 w-64 rounded-full bg-cyan-400/10 blur-3xl dark:bg-cyan-400/20"></div>
      <div class="relative z-10 space-y-8">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
          <div class="space-y-4">
            <div class="space-y-2">
              <p class="text-xs font-semibold uppercase tracking-[0.4em] text-slate-500 dark:text-slate-300">
                Performance Intelligence
              </p>
              <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-3xl font-bold text-slate-900 dark:text-white">Unit Performance Reports</h1>
                <span
                  class="rounded-full border border-white/60 bg-white/70 px-3 py-1 text-xs font-semibold text-slate-600 shadow-sm backdrop-blur dark:border-white/10 dark:bg-slate-900/70 dark:text-slate-200">
                  {{ filtersWindow.label }}
                </span>
              </div>
              <p class="text-sm text-slate-500 dark:text-slate-300">
                Narasi terintegrasi mengenai aktivitas tickets, tasks, projects, dan SLA dalam satu panel strategis.
              </p>
            </div>
            <div class="flex flex-wrap gap-4 text-xs text-slate-500 dark:text-slate-400">
              <span
                ><span class="font-semibold text-slate-700 dark:text-slate-200">Periode:</span>
                {{ filtersWindow.start }} → {{ filtersWindow.end }}</span
              >
              <span
                ><span class="font-semibold text-slate-700 dark:text-slate-200">Range:</span>
                {{ selectedRangeLabel }}</span
              >
              <span
                ><span class="font-semibold text-slate-700 dark:text-slate-200">Total Item:</span>
                {{ formatNumber(heroMeta.totalWork) }}</span
              >
            </div>
          </div>
          <div class="flex flex-col gap-2">
            <label class="text-xs font-semibold uppercase tracking-[0.4em] text-slate-500 dark:text-slate-300"
              >Rentang Analisis</label
            >
            <div class="relative min-w-[180px]">
              <button
                ref="rangeTriggerRef"
                type="button"
                class="flex w-full items-center justify-between rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-indigo-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"
                @click="toggleRangeDropdown"
              >
                <span>{{ selectedRangeLabel }}</span>
                <span
                  class="material-icons text-base text-slate-400 transition duration-200"
                  :class="rangeDropdownOpen ? 'rotate-180 text-indigo-500' : ''"
                >
                  expand_more
                </span>
              </button>
              <div
                v-if="rangeDropdownOpen"
                ref="rangePanelRef"
                class="absolute left-0 right-0 top-[calc(100%+12px)] z-[9999] rounded-2xl border border-slate-200 bg-white p-3 shadow-2xl md:p-4 dark:border-slate-700 dark:bg-slate-900"
              >
                <ul class="space-y-1">
                  <li v-for="option in rangeOptions" :key="option.value">
                    <button
                      type="button"
                      class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm transition hover:bg-indigo-50 dark:hover:bg-slate-800"
                      :class="
                        option.value === selectedRange
                          ? 'bg-indigo-50 font-semibold text-indigo-700 dark:bg-slate-800 dark:text-indigo-200'
                          : 'text-slate-700 dark:text-slate-200'
                      "
                      @click="selectRange(option.value)"
                    >
                      <span>{{ option.label }}</span>
                      <span
                        v-if="option.value === selectedRange"
                        class="material-icons text-sm text-indigo-600 dark:text-indigo-300"
                      >
                        check
                      </span>
                    </button>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
          <div
            class="rounded-2xl border border-white/60 bg-white/70 p-4 shadow-lg backdrop-blur dark:border-white/10 dark:bg-slate-900/80"
          >
            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-300">
              Active Units
            </p>
            <p class="mt-1 text-3xl font-bold text-slate-900 dark:text-white">
              {{ formatNumber(heroMeta.activeUnits) }}
            </p>
            <p class="text-xs text-slate-500 dark:text-slate-400">
              Unit terhubung dengan aktivitas dalam periode terpilih.
            </p>
          </div>
          <div
            class="rounded-2xl border border-white/60 bg-white/70 p-4 shadow-lg backdrop-blur dark:border-white/10 dark:bg-slate-900/80"
          >
            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-300">
              Completion
            </p>
            <p class="mt-1 text-3xl font-bold text-slate-900 dark:text-white">{{ heroMeta.completionRate }}%</p>
            <p class="text-xs text-slate-500 dark:text-slate-400">
              {{ formatNumber(heroMeta.completed) }} item berhasil diselesaikan.
            </p>
          </div>
          <div
            class="rounded-2xl border border-white/60 bg-white/70 p-4 shadow-lg backdrop-blur dark:border-white/10 dark:bg-slate-900/80"
          >
            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-300">
              SLA Compliance
            </p>
            <p class="mt-1 text-3xl font-bold text-slate-900 dark:text-white">
              {{ formatPercent(slaStats.compliance_rate) }}
            </p>
            <p class="text-xs text-slate-500 dark:text-slate-400">
              {{ formatNumber(slaStats.with_deadline) }} item terikat SLA aktif.
            </p>
          </div>
          <div
            class="rounded-2xl border border-white/60 bg-white/70 p-4 shadow-lg backdrop-blur dark:border-white/10 dark:bg-slate-900/80"
          >
            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-300">
              Pending Backlog
            </p>
            <p class="mt-1 text-3xl font-bold text-slate-900 dark:text-white">
              {{ formatNumber(workloadTotals.pending) }}
            </p>
            <p class="text-xs text-slate-500 dark:text-slate-400">
              Tickets {{ formatNumber(normalizedOverview.tickets.pending) }} • Tasks
              {{ formatNumber(normalizedOverview.tasks.pending) }} • Projects
              {{ formatNumber(normalizedOverview.projects.pending) }}
            </p>
          </div>
        </div>
      </div>
    </section>

    <section class="grid gap-4 xl:grid-cols-[2.2fr,1fr]">
      <article
        class="flex flex-col rounded-3xl border border-slate-200/70 bg-white p-6 shadow-sm dark:border-slate-700/70 dark:bg-slate-900">
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.4em] text-slate-400">Operational Timeline</p>
            <h3 class="text-xl font-semibold text-slate-900 dark:text-white">Output vs Completion</h3>
          </div>
          <span class="text-xs text-slate-500 dark:text-slate-400">Aggregated by ISO week</span>
        </div>
        <div
          class="mt-6 flex-1 min-h-[340px] w-full overflow-hidden rounded-2xl border border-slate-100/70 bg-white/70 p-3 sm:p-4 dark:border-slate-800/70 dark:bg-slate-900/70">
          <TrendLineChart class="h-full w-full" :labels="trendChart.labels" :datasets="trendChart.datasets" />
        </div>
      </article>
      <aside class="grid gap-4">
        <div
          class="rounded-3xl border border-slate-200/70 bg-white p-5 shadow-sm dark:border-slate-700/70 dark:bg-slate-900">
          <p class="text-xs font-semibold uppercase tracking-[0.35em] text-slate-400">Throughput Summary</p>
          <ul class="mt-3 space-y-3">
            <li
              v-for="card in trendSummaryCards"
              :key="card.key"
              class="flex items-center justify-between rounded-2xl border border-slate-100/70 bg-slate-50/60 px-4 py-3 dark:border-slate-700/70 dark:bg-slate-800/60">
              <div>
                <p class="text-sm font-semibold text-slate-700 dark:text-slate-100">{{ card.label }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                  Created {{ formatNumber(card.created) }} • Completed {{ formatNumber(card.completed) }}
                </p>
              </div>
              <div class="text-right">
                <p class="text-lg font-semibold text-slate-900 dark:text-white">{{ formatNumber(card.total) }}</p>
                <p class="text-[11px] text-slate-400">Completion {{ formatPercent(card.completionRate) }}</p>
              </div>
            </li>
          </ul>
        </div>
        <div
          class="rounded-3xl border border-slate-200/70 bg-white p-5 shadow-sm dark:border-slate-700/70 dark:bg-slate-900">
          <p class="text-xs font-semibold uppercase tracking-[0.35em] text-slate-400">Peak Activity</p>
          <ul v-if="trendHighlights.length" class="mt-3 space-y-2">
            <li
              v-for="peak in trendHighlights"
              :key="peak.label"
              class="flex items-center justify-between rounded-2xl border border-slate-100/70 bg-slate-50/60 px-4 py-2 dark:border-slate-700/70 dark:bg-slate-800/60">
              <div>
                <p class="text-sm font-semibold text-slate-700 dark:text-slate-100">{{ peak.label }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ peak.period ?? '—' }}</p>
              </div>
              <span class="text-lg font-semibold text-slate-900 dark:text-white">{{ formatNumber(peak.value) }}</span>
            </li>
          </ul>
          <p v-else class="mt-3 text-sm text-slate-500 dark:text-slate-400">Belum ada aktivitas signifikan.</p>
        </div>
      </aside>
    </section>

    <section class="space-y-5">
      <header class="flex flex-col gap-1 md:flex-row md:items-center md:justify-between">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.4em] text-slate-400">Workstream Snapshot</p>
          <h3 class="text-xl font-semibold text-slate-900 dark:text-white">Kapasitas lintas layanan</h3>
        </div>
      </header>
      <div class="grid gap-4 lg:grid-cols-2 xl:grid-cols-4">
        <article
          v-for="card in missionCards"
          :key="card.key"
          class="relative overflow-hidden rounded-3xl border border-slate-200/70 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg dark:border-slate-700/70 dark:bg-slate-900">
          <div class="absolute inset-x-4 top-4 h-1 rounded-full bg-gradient-to-r opacity-60" :class="card.accent"></div>
          <div class="space-y-5 pt-5">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">{{ card.subtitle }}</p>
                <h2 class="text-xl font-semibold text-slate-900 dark:text-white">{{ card.title }}</h2>
              </div>
              <span
                class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-500 dark:bg-slate-800 dark:text-slate-300"
                >{{ card.badge }}</span
              >
            </div>
            <div>
              <p class="text-3xl font-bold text-slate-900 dark:text-white">{{ formatNumber(card.stats.total) }}</p>
              <p class="text-xs text-slate-500 dark:text-slate-400">
                Aktif {{ formatNumber(card.stats.active) }} • Pending {{ formatNumber(card.stats.pending) }}
              </p>
            </div>
            <div>
              <div class="h-2 w-full rounded-full bg-slate-200/80 dark:bg-slate-800">
                <div
                  class="h-2 rounded-full bg-gradient-to-r transition-all"
                  :class="card.accent"
                  :style="{ width: `${completionRate(card.stats)}%` }"></div>
              </div>
              <div class="mt-1 flex items-center justify-between text-xs text-slate-400 dark:text-slate-500">
                <span>Completion</span>
                <span>{{ completionRate(card.stats) }}%</span>
              </div>
            </div>
            <div
              class="rounded-2xl border border-slate-100/70 bg-slate-50/70 px-4 py-3 text-xs text-slate-500 dark:border-slate-700/70 dark:bg-slate-800/60 dark:text-slate-300">
              Output {{ formatNumber(card.summary.created) }} • Completion {{ formatNumber(card.summary.completed) }}
            </div>
          </div>
        </article>
      </div>
    </section>

    <section class="grid gap-4 xl:grid-cols-[1.8fr,1fr]">
      <article
        class="rounded-3xl border border-slate-200/70 bg-white p-6 shadow-sm dark:border-slate-700/70 dark:bg-slate-900">
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.4em] text-slate-400">Status Distribution</p>
            <h3 class="text-xl font-semibold text-slate-900 dark:text-white">Health indicator per workstream</h3>
          </div>
          <span class="text-xs text-slate-500 dark:text-slate-400">{{ filtersWindow.label }}</span>
        </div>
        <div class="mt-6 space-y-6">
          <div v-for="dist in statusPanels" :key="dist.key" class="space-y-3">
            <div class="flex items-center justify-between">
              <div>
                <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-100">{{ dist.title }}</h4>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                  Total {{ formatNumber(dist.dataset.total) }} item
                </p>
              </div>
              <span
                class="inline-flex items-center gap-2 rounded-full border border-slate-200/70 bg-white/70 px-3 py-1 text-xs font-semibold text-slate-600 shadow-sm backdrop-blur dark:border-slate-700 dark:bg-slate-800/70 dark:text-slate-200"
              >
                Completion {{ formatPercent(dist.completionRate) }}
              </span>
            </div>
            <div class="space-y-2">
              <div v-for="item in dist.dataset.items" :key="item.label" class="space-y-1">
                <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                  <span class="font-semibold text-slate-600 dark:text-slate-200">{{ item.label }}</span>
                  <span>{{ formatNumber(item.value) }} • {{ item.percentage }}%</span>
                </div>
                <div class="h-2 w-full rounded-full bg-slate-200/80 dark:bg-slate-800">
                  <div
                    class="h-2 rounded-full bg-gradient-to-r from-indigo-500 to-sky-500"
                    :style="{ width: `${item.percentage}%` }"></div>
                </div>
              </div>
            </div>
            <div v-if="dist.segments.length" class="space-y-2 pt-2">
              <div
                v-for="segment in dist.segments"
                :key="segment.label"
                class="flex flex-wrap items-center gap-2 text-xs">
                <span class="text-[11px] uppercase tracking-[0.3em] text-slate-400">{{ segment.label }}</span>
                <span
                  v-for="item in segment.items"
                  :key="item.label"
                  class="inline-flex items-center gap-2 rounded-full border border-slate-200/70 bg-white/70 px-3 py-1 text-xs font-semibold text-slate-600 shadow-sm backdrop-blur dark:border-slate-700 dark:bg-slate-800/70 dark:text-slate-200"
                >
                  {{ item.label }} • {{ formatNumber(item.value) }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </article>
      <article
        class="grid gap-4 rounded-3xl border border-slate-200/70 bg-white p-6 text-slate-900 shadow-sm dark:border-slate-700/70 dark:bg-gradient-to-br dark:from-slate-900 dark:via-slate-900 dark:to-slate-800 dark:text-white">
        <header class="flex items-center justify-between">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.4em] text-slate-500 dark:text-white/60">
              SLA Command Center
            </p>
            <h3 class="text-xl font-semibold text-slate-900 dark:text-white">Realtime Compliance</h3>
          </div>
          <span
            class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700 dark:bg-white/10 dark:text-white">
            {{ formatPercent(slaAlerts.metrics.complianceRate) }} patuh
          </span>
        </header>
        <div class="grid gap-3 sm:grid-cols-2">
          <div
            class="rounded-2xl border border-slate-100/70 bg-slate-50/80 p-4 text-slate-700 dark:border-white/10 dark:bg-white/5 dark:text-white">
            <p class="text-xs uppercase tracking-[0.4em] text-slate-500 dark:text-white/60">Breached</p>
            <p class="mt-2 text-3xl font-semibold text-rose-500 dark:text-rose-300">
              {{ formatNumber(slaAlerts.metrics.breached) }}
            </p>
            <p class="text-xs text-slate-500 dark:text-white/60">
              {{ formatPercent(slaAlerts.metrics.breachRate) }} dari SLA aktif
            </p>
          </div>
          <div
            class="rounded-2xl border border-slate-100/70 bg-slate-50/80 p-4 text-slate-700 dark:border-white/10 dark:bg-white/5 dark:text-white">
            <p class="text-xs uppercase tracking-[0.4em] text-slate-500 dark:text-white/60">Due Soon</p>
            <p class="mt-2 text-3xl font-semibold text-amber-500 dark:text-amber-200">
              {{ formatNumber(slaAlerts.metrics.dueSoon) }}
            </p>
            <p class="text-xs text-slate-500 dark:text-white/60">
              Rata-rata terlambat {{ formatAverage(slaAlerts.metrics.avgDelay, 'jam') }}
            </p>
          </div>
        </div>
        <div class="rounded-2xl border border-slate-100/70 bg-slate-50/80 p-4 dark:border-white/10 dark:bg-white/5">
          <p class="text-xs uppercase tracking-[0.4em] text-slate-500 dark:text-white/60">SLA Due Soon</p>
          <ul class="mt-2 space-y-2" v-if="slaAlerts.dueSoon.length">
            <li
              v-for="item in slaAlerts.dueSoon"
              :key="item.id"
              class="flex items-center justify-between text-xs text-slate-600 dark:text-white/80">
              <span class="truncate">{{ item.title }}</span>
              <span class="text-slate-400 dark:text-white/50">{{ displayDate(item.due_at ?? item.due_date) }}</span>
            </li>
          </ul>
          <p v-else class="mt-2 text-xs text-slate-500 dark:text-white/60">
            Tidak ada SLA yang akan jatuh tempo dalam 3 hari.
          </p>
        </div>
        <div class="rounded-2xl border border-slate-100/70 bg-slate-50/80 p-4 dark:border-white/10 dark:bg-white/5">
          <p class="text-xs uppercase tracking-[0.4em] text-slate-500 dark:text-white/60">SLA Breached</p>
          <ul class="mt-2 space-y-2" v-if="slaAlerts.breached.length">
            <li
              v-for="item in slaAlerts.breached"
              :key="item.id"
              class="flex items-center justify-between text-xs text-slate-600 dark:text-white/80">
              <span class="truncate">{{ item.title }}</span>
              <span class="text-slate-400 dark:text-white/50">{{ formatAverage(item.overdue_hours, 'jam') }}</span>
            </li>
          </ul>
          <p v-else class="mt-2 text-xs text-slate-500 dark:text-white/60">Seluruh SLA berada pada status aman.</p>
        </div>
      </article>
    </section>

    <section class="grid gap-4 xl:grid-cols-3">
      <article
        class="rounded-3xl border border-slate-200/70 bg-white p-6 shadow-sm dark:border-slate-700/70 dark:bg-slate-900">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Ticket Leaderboard</h3>
        <div class="mt-4 space-y-4">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.35em] text-slate-400">Top Resolver</p>
            <ul class="mt-2 space-y-2" v-if="ticketAgents.length">
              <li
                v-for="agent in ticketAgents"
                :key="`agent-${agent.id}`"
                class="flex items-center justify-between rounded-2xl border border-slate-100/70 bg-slate-50/60 px-4 py-2 text-sm dark:border-slate-700/70 dark:bg-slate-800/60">
                <div>
                  <p class="font-semibold text-slate-700 dark:text-slate-100">{{ agent.name }}</p>
                  <p class="text-xs text-slate-500 dark:text-slate-400">Resolved {{ formatNumber(agent.value) }}</p>
                </div>
                <span class="text-xs text-slate-400">{{ formatAverage(agent.avg, agent.avg_unit) }}</span>
              </li>
            </ul>
            <p v-else class="mt-2 text-xs text-slate-500 dark:text-slate-400">Belum ada data resolver.</p>
          </div>
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.35em] text-slate-400">Unit Requester</p>
            <ul class="mt-2 space-y-2" v-if="ticketRequesters.length">
              <li
                v-for="req in ticketRequesters"
                :key="`req-${req.id}`"
                class="flex items-center justify-between rounded-2xl border border-slate-100/70 bg-slate-50/60 px-4 py-2 text-sm dark:border-slate-700/70 dark:bg-slate-800/60">
                <span class="font-semibold text-slate-700 dark:text-slate-100">{{ req.name }}</span>
                <span class="text-xs text-slate-400">{{ formatNumber(req.value) }} permintaan</span>
              </li>
            </ul>
            <p v-else class="mt-2 text-xs text-slate-500 dark:text-slate-400">Belum ada data requester aktif.</p>
          </div>
        </div>
      </article>
      <article
        class="rounded-3xl border border-slate-200/70 bg-white p-6 shadow-sm dark:border-slate-700/70 dark:bg-slate-900">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Task & Project Leaderboard</h3>
        <div class="mt-4 space-y-4">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.35em] text-slate-400">Task Assignee</p>
            <ul class="mt-2 space-y-2" v-if="taskAssignees.length">
              <li
                v-for="assignee in taskAssignees"
                :key="`assignee-${assignee.id}`"
                class="flex items-center justify-between rounded-2xl border border-slate-100/70 bg-slate-50/60 px-4 py-2 text-sm dark:border-slate-700/70 dark:bg-slate-800/60">
                <div>
                  <p class="font-semibold text-slate-700 dark:text-slate-100">{{ assignee.name }}</p>
                  <p class="text-xs text-slate-500 dark:text-slate-400">Selesai {{ formatNumber(assignee.value) }}</p>
                </div>
                <span class="text-xs text-slate-400">{{ formatAverage(assignee.avg, assignee.avg_unit) }}</span>
              </li>
            </ul>
            <p v-else class="mt-2 text-xs text-slate-500 dark:text-slate-400">Belum ada data penyelesai tugas.</p>
          </div>
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.35em] text-slate-400">Project Owner</p>
            <ul class="mt-2 space-y-2" v-if="projectOwners.length">
              <li
                v-for="owner in projectOwners"
                :key="`owner-${owner.id}`"
                class="flex items-center justify-between rounded-2xl border border-slate-100/70 bg-slate-50/60 px-4 py-2 text-sm dark:border-slate-700/70 dark:bg-slate-800/60">
                <div>
                  <p class="font-semibold text-slate-700 dark:text-slate-100">{{ owner.name }}</p>
                  <p class="text-xs text-slate-500 dark:text-slate-400">Deliver {{ formatNumber(owner.value) }}</p>
                </div>
                <span class="text-xs text-slate-400">{{ formatAverage(owner.avg, owner.avg_unit) }}</span>
              </li>
            </ul>
            <p v-else class="mt-2 text-xs text-slate-500 dark:text-slate-400">Belum ada project selesai.</p>
          </div>
        </div>
      </article>
      <article
        class="rounded-3xl border border-slate-200/70 bg-white p-6 shadow-sm dark:border-slate-700/70 dark:bg-slate-900">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Focus Alerts</h3>
        <div class="mt-4 space-y-4">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.35em] text-slate-400">Tasks due soon</p>
            <ul class="mt-2 space-y-2" v-if="focusAlerts.tasks.length">
              <li
                v-for="task in focusAlerts.tasks"
                :key="`focus-task-${task.id}`"
                class="flex items-center justify-between rounded-2xl border border-slate-100/70 bg-slate-50/60 px-4 py-2 text-xs dark:border-slate-700/70 dark:bg-slate-800/60">
                <span class="truncate text-slate-600 dark:text-slate-200">{{ task.title }}</span>
                <span class="text-slate-400">{{ displayDate(task.due_at) }}</span>
              </li>
            </ul>
            <p v-else class="mt-2 text-xs text-slate-500 dark:text-slate-400">Tidak ada tugas kritikal.</p>
          </div>
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.35em] text-slate-400">Projects ending soon</p>
            <ul class="mt-2 space-y-2" v-if="focusAlerts.projects.length">
              <li
                v-for="project in focusAlerts.projects"
                :key="`focus-project-${project.id}`"
                class="flex items-center justify-between rounded-2xl border border-slate-100/70 bg-slate-50/60 px-4 py-2 text-xs dark:border-slate-700/70 dark:bg-slate-800/60">
                <span class="truncate text-slate-600 dark:text-slate-200">{{ project.title }}</span>
                <span class="text-slate-400">{{ displayDate(project.due_date) }}</span>
              </li>
            </ul>
            <p v-else class="mt-2 text-xs text-slate-500 dark:text-slate-400">
              Seluruh project berada pada jalur yang aman.
            </p>
          </div>
        </div>
      </article>
    </section>

    <section class="grid gap-4 xl:grid-cols-2">
      <article
        class="rounded-3xl border border-slate-200/70 bg-white p-6 shadow-sm dark:border-slate-700/70 dark:bg-slate-900">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Top Performer</p>
            <h3 class="text-xl font-semibold text-slate-900 dark:text-white">Unit dengan aktivitas tertinggi</h3>
          </div>
          <span class="text-xs text-slate-500 dark:text-slate-400">Activity Score</span>
        </div>
        <div v-if="topUnits.length" class="mt-5 space-y-4">
          <div
            v-for="(unit, index) in topUnits"
            :key="unit.name"
            class="flex items-center justify-between rounded-2xl border border-slate-100/70 bg-slate-50/60 px-4 py-3 dark:border-slate-700 dark:bg-slate-800/70">
            <div>
              <p class="text-xs font-semibold text-slate-400">#{{ index + 1 }}</p>
              <p class="text-lg font-semibold text-slate-900 dark:text-white">{{ unit.name }}</p>
              <p class="text-xs text-slate-500 dark:text-slate-400">
                {{ formatNumber(unit.total_work) }} item • {{ unit.completion_rate }}% selesai
              </p>
            </div>
            <div class="text-right">
              <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ unit.activity_score }}</p>
              <p class="text-xs text-slate-400">score</p>
            </div>
          </div>
        </div>
        <p v-else class="mt-4 text-sm text-slate-500 dark:text-slate-400">
          Belum ada aktivitas yang dapat ditampilkan.
        </p>
      </article>
      <article
        class="rounded-3xl border border-slate-200/70 bg-white p-6 text-slate-900 shadow-sm dark:border-slate-700/70 dark:bg-gradient-to-br dark:from-slate-900 dark:via-slate-800 dark:to-slate-900 dark:text-white">
        <div class="flex items-center justify-between">
          <h3 class="text-xl font-semibold text-slate-900 dark:text-white">Unit Idle</h3>
          <span class="text-sm font-medium text-slate-500 dark:text-white/70">{{ idleUnits.length }} unit</span>
        </div>
        <p class="mt-2 text-sm text-slate-600 dark:text-white/70">
          Daftar unit tanpa update terbaru. Jadwalkan follow-up agar pipeline tetap bergerak.
        </p>
        <div class="mt-5 flex flex-wrap gap-2">
          <span
            v-for="unit in idleUnits"
            :key="unit.name"
            class="rounded-full border border-slate-200 bg-slate-50 px-4 py-1 text-sm text-slate-700 dark:border-white/20 dark:bg-white/5 dark:text-white/80">
            {{ unit.name }}
          </span>
          <span
            v-if="!idleUnits.length"
            class="rounded-full border border-slate-200 bg-slate-50 px-4 py-1 text-sm text-slate-700 dark:border-white/20 dark:bg-white/5 dark:text-white/80"
            >Seluruh unit aktif</span
          >
        </div>
      </article>
    </section>

    <section
      class="rounded-3xl border border-slate-200/70 bg-white p-6 shadow-sm dark:border-slate-700/70 dark:bg-slate-900">
      <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
          <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Multi-Unit Pulse</p>
          <h3 class="text-xl font-semibold text-slate-900 dark:text-white">Aktivitas per unit</h3>
        </div>
        <span class="text-xs text-slate-500 dark:text-slate-400">{{ units.length }} unit dipantau</span>
      </div>
      <div class="mt-4 overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-100 text-sm dark:divide-slate-800">
          <thead>
            <tr class="text-left text-xs font-semibold uppercase tracking-widest text-slate-400">
              <th class="px-4 py-3">Unit</th>
              <th class="px-4 py-3">Tickets</th>
              <th class="px-4 py-3">Tasks</th>
              <th class="px-4 py-3">Projects</th>
              <th class="px-4 py-3">Activity</th>
              <th class="px-4 py-3">Completion</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
            <tr v-for="unit in units" :key="unit.name" class="align-top">
              <td class="px-4 py-4">
                <div class="font-semibold text-slate-900 dark:text-white">{{ unit.name }}</div>
                <p class="text-xs text-slate-500 dark:text-slate-400">Score {{ unit.activity_score }}</p>
              </td>
              <td class="px-4 py-4">
                <p class="font-semibold text-slate-900 dark:text-white">{{ formatNumber(unit.tickets.total) }}</p>
                <p class="text-xs text-emerald-500">Done {{ formatNumber(unit.tickets.done) }}</p>
              </td>
              <td class="px-4 py-4">
                <p class="font-semibold text-slate-900 dark:text-white">{{ formatNumber(unit.tasks.total) }}</p>
                <p class="text-xs text-emerald-500">Done {{ formatNumber(unit.tasks.done) }}</p>
              </td>
              <td class="px-4 py-4">
                <p class="font-semibold text-slate-900 dark:text-white">{{ formatNumber(unit.projects.total) }}</p>
                <p class="text-xs text-emerald-500">Done {{ formatNumber(unit.projects.done) }}</p>
              </td>
              <td class="px-4 py-4">
                <div class="text-lg font-semibold text-slate-900 dark:text-white">{{ unit.activity_score }}</div>
                <p class="text-xs text-slate-500 dark:text-slate-400">Total {{ formatNumber(unit.total_work) }} item</p>
              </td>
              <td class="px-4 py-4">
                <div class="flex items-center gap-2">
                  <span class="text-sm font-semibold text-slate-900 dark:text-white">{{ unit.completion_rate }}%</span>
                  <div class="h-2 w-24 rounded-full bg-slate-200/80 dark:bg-slate-800">
                    <div
                      class="h-2 rounded-full bg-gradient-to-r from-emerald-400 to-emerald-600"
                      :style="{ width: `${Math.min(unit.completion_rate, 100)}%` }"></div>
                  </div>
                </div>
              </td>
            </tr>
            <tr v-if="!units.length">
              <td colspan="6" class="px-4 py-10 text-center text-slate-500 dark:text-slate-400">
                Belum ada data unit.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import TrendLineChart from '@/Components/Reports/TrendLineChart.vue';
import resolveRoute from '@/utils/resolveRoute';

const props = defineProps({
  overview: { type: Object, default: () => ({}) },
  units: { type: Array, default: () => [] },
  highlights: { type: Object, default: () => ({ topUnits: [], idleUnits: [] }) },
  sla: { type: Object, default: () => ({}) },
  filters: { type: Object, default: () => ({}) },
  trend: { type: Object, default: () => ({}) },
  statusMatrix: { type: Object, default: () => ({}) },
  leaders: { type: Object, default: () => ({}) },
  alerts: { type: Object, default: () => ({}) }
});

const formatNumber = value => new Intl.NumberFormat('id-ID').format(value ?? 0);
const formatPercent = value => `${Number(value ?? 0).toFixed(1)}%`;

const formatAverage = (value, unit = null) => {
  if (value === null || value === undefined || Number.isNaN(Number(value))) {
    return '—';
  }
  const display = Number(value).toLocaleString('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 1 });
  return unit ? `${display} ${unit}` : display;
};

const displayDate = value => {
  if (!value) return '—';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return '—';
  return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};

const defaults = { total: 0, active: 0, done: 0, pending: 0 };

const normalizedOverview = computed(() => ({
  tickets: { ...defaults, ...(props.overview?.tickets ?? {}) },
  tasks: { ...defaults, ...(props.overview?.tasks ?? {}) },
  projects: { ...defaults, ...(props.overview?.projects ?? {}) }
}));

const totalWork = computed(
  () =>
    normalizedOverview.value.tickets.total +
    normalizedOverview.value.tasks.total +
    normalizedOverview.value.projects.total
);
const totalDone = computed(
  () =>
    normalizedOverview.value.tickets.done + normalizedOverview.value.tasks.done + normalizedOverview.value.projects.done
);

const slaStats = computed(() => ({
  total: props.sla?.total ?? 0,
  with_deadline: props.sla?.with_deadline ?? props.sla?.total ?? 0,
  completed: props.sla?.completed ?? 0,
  pending: props.sla?.pending ?? 0,
  breached: props.sla?.breached ?? 0,
  on_track: props.sla?.on_track ?? 0,
  compliance_rate: props.sla?.compliance_rate ?? 0,
  breach_rate: props.sla?.breach_rate ?? 0
}));

const heroMeta = computed(() => ({
  totalWork: totalWork.value,
  completed: totalDone.value,
  completionRate: totalWork.value ? Number(((totalDone.value / totalWork.value) * 100).toFixed(1)) : 0,
  activeUnits: props.overview?.units ?? props.units?.length ?? 0,
  compliance: slaStats.value.compliance_rate
}));

const workloadTotals = computed(() => ({
  pending:
    normalizedOverview.value.tickets.pending +
    normalizedOverview.value.tasks.pending +
    normalizedOverview.value.projects.pending
}));

const filterState = computed(() => props.filters ?? {});
const filtersWindow = computed(() => filterState.value.window ?? { start: '—', end: '—', label: 'Tanpa Rentang' });
const rangeOptions = computed(() => filterState.value.options ?? []);
const selectedRange = ref(filterState.value.range ?? rangeOptions.value[0]?.value ?? 90);
const rangeDropdownOpen = ref(false);
const rangeTriggerRef = ref(null);
const rangePanelRef = ref(null);

watch(
  () => filterState.value.range,
  value => {
    if (typeof value === 'number') {
      selectedRange.value = value;
    }
  }
);

const selectedRangeLabel = computed(() => {
  const option = rangeOptions.value.find(opt => opt.value === selectedRange.value);
  return option?.label ?? `${selectedRange.value} Hari`;
});

const selectRange = value => {
  const numeric = Number(value ?? selectedRange.value);
  if (!Number.isFinite(numeric)) return;
  selectedRange.value = numeric;
  rangeDropdownOpen.value = false;
  router.get(
    resolveRoute('reports.index'),
    { range: numeric },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true
    }
  );
};

const toggleRangeDropdown = () => {
  rangeDropdownOpen.value = !rangeDropdownOpen.value;
};

const handleClickOutside = event => {
  if (!rangeDropdownOpen.value) return;
  const target = event.target;
  if (rangeTriggerRef.value?.contains(target) || rangePanelRef.value?.contains(target)) {
    return;
  }
  rangeDropdownOpen.value = false;
};

onMounted(() => {
  window.addEventListener('click', handleClickOutside, true);
});

onBeforeUnmount(() => {
  window.removeEventListener('click', handleClickOutside, true);
});

const onRangeChange = event => {
  const value = Number(event.target.value ?? selectedRange.value);
  if (!Number.isFinite(value)) return;
  selectRange(value);
};

const rawTrend = computed(() => props.trend ?? {});

const trendChart = computed(() => {
  const labels = rawTrend.value.labels ?? [];
  const datasets = [
    {
      label: 'Tickets Created',
      data: rawTrend.value.tickets?.created ?? [],
      borderColor: '#6366f1',
      backgroundColor: 'rgba(99,102,241,0.18)',
      fill: true
    },
    {
      label: 'Tickets Completed',
      data: rawTrend.value.tickets?.completed ?? [],
      borderColor: '#312e81',
      backgroundColor: 'rgba(49,46,129,0.12)',
      borderDash: [6, 4],
      fill: false
    },
    {
      label: 'Tasks Created',
      data: rawTrend.value.tasks?.created ?? [],
      borderColor: '#f97316',
      backgroundColor: 'rgba(249,115,22,0.15)',
      fill: true
    },
    {
      label: 'Tasks Completed',
      data: rawTrend.value.tasks?.completed ?? [],
      borderColor: '#c2410c',
      backgroundColor: 'rgba(194,65,12,0.12)',
      borderDash: [6, 4],
      fill: false
    },
    {
      label: 'Projects Created',
      data: rawTrend.value.projects?.created ?? [],
      borderColor: '#10b981',
      backgroundColor: 'rgba(16,185,129,0.16)',
      fill: true
    },
    {
      label: 'Projects Completed',
      data: rawTrend.value.projects?.completed ?? [],
      borderColor: '#047857',
      backgroundColor: 'rgba(4,120,87,0.12)',
      borderDash: [6, 4],
      fill: false
    }
  ];
  return { labels, datasets };
});

const trendSummary = computed(() => ({
  tickets: rawTrend.value.summary?.tickets ?? { created: 0, completed: 0 },
  tasks: rawTrend.value.summary?.tasks ?? { created: 0, completed: 0 },
  projects: rawTrend.value.summary?.projects ?? { created: 0, completed: 0 }
}));

const trendSummaryCards = computed(() => [
  {
    key: 'tickets',
    label: 'Tickets',
    created: trendSummary.value.tickets.created,
    completed: trendSummary.value.tickets.completed,
    total: trendSummary.value.tickets.created + trendSummary.value.tickets.completed,
    completionRate: trendSummary.value.tickets.created
      ? Number(((trendSummary.value.tickets.completed / trendSummary.value.tickets.created) * 100).toFixed(1))
      : 0
  },
  {
    key: 'tasks',
    label: 'Tasks',
    created: trendSummary.value.tasks.created,
    completed: trendSummary.value.tasks.completed,
    total: trendSummary.value.tasks.created + trendSummary.value.tasks.completed,
    completionRate: trendSummary.value.tasks.created
      ? Number(((trendSummary.value.tasks.completed / trendSummary.value.tasks.created) * 100).toFixed(1))
      : 0
  },
  {
    key: 'projects',
    label: 'Projects',
    created: trendSummary.value.projects.created,
    completed: trendSummary.value.projects.completed,
    total: trendSummary.value.projects.created + trendSummary.value.projects.completed,
    completionRate: trendSummary.value.projects.created
      ? Number(((trendSummary.value.projects.completed / trendSummary.value.projects.created) * 100).toFixed(1))
      : 0
  }
]);

const trendHighlights = computed(() => rawTrend.value.peaks ?? []);

const completionRate = stats => {
  const total = stats?.total ?? 0;
  if (!total) return 0;
  const done = stats?.done ?? 0;
  return Math.min(100, Number(((done / total) * 100).toFixed(1)));
};

const missionCards = computed(() => [
  {
    key: 'tickets',
    title: 'Tickets',
    subtitle: 'Workload & SLA',
    badge: 'Tickets',
    accent: 'from-sky-400 via-indigo-400 to-blue-500',
    stats: normalizedOverview.value.tickets,
    summary: trendSummary.value.tickets
  },
  {
    key: 'tasks',
    title: 'Tasks',
    subtitle: 'Operational Flow',
    badge: 'Tasks',
    accent: 'from-amber-400 via-orange-500 to-red-500',
    stats: normalizedOverview.value.tasks,
    summary: trendSummary.value.tasks
  },
  {
    key: 'projects',
    title: 'Projects',
    subtitle: 'Strategic Delivery',
    badge: 'Projects',
    accent: 'from-emerald-400 via-green-500 to-teal-500',
    stats: normalizedOverview.value.projects,
    summary: trendSummary.value.projects
  },
  {
    key: 'sla',
    title: 'SLA Pulse',
    subtitle: 'Deadline Control',
    badge: 'SLA',
    accent: 'from-purple-500 via-fuchsia-500 to-pink-500',
    stats: {
      total: slaStats.value.with_deadline,
      active: slaStats.value.on_track,
      done: slaStats.value.completed,
      pending: slaStats.value.breached
    },
    summary: { created: slaStats.value.on_track, completed: slaStats.value.completed }
  }
]);

const topUnits = computed(() => props.highlights?.topUnits ?? []);
const idleUnits = computed(() => props.highlights?.idleUnits ?? []);
const units = computed(() => props.units ?? []);

const buildDistribution = group => ({
  total: group?.total ?? 0,
  items: Array.isArray(group?.items) ? group.items : []
});

const matrix = computed(() => props.statusMatrix ?? {});
const ticketStatus = computed(() => buildDistribution(matrix.value.tickets?.status));
const ticketPriority = computed(() => buildDistribution(matrix.value.tickets?.priority));
const ticketType = computed(() => buildDistribution(matrix.value.tickets?.type));
const taskStatus = computed(() => buildDistribution(matrix.value.tasks?.status));
const projectStatus = computed(() => buildDistribution(matrix.value.projects?.status));

const statusPanels = computed(() => [
  {
    key: 'tickets',
    title: 'Tickets',
    dataset: ticketStatus.value,
    completionRate: completionRate(normalizedOverview.value.tickets),
    segments: [
      ticketPriority.value.items.length ? { label: 'Prioritas', items: ticketPriority.value.items } : null,
      ticketType.value.items.length ? { label: 'Tipe', items: ticketType.value.items } : null
    ].filter(Boolean)
  },
  {
    key: 'tasks',
    title: 'Tasks',
    dataset: taskStatus.value,
    completionRate: completionRate(normalizedOverview.value.tasks),
    segments: []
  },
  {
    key: 'projects',
    title: 'Projects',
    dataset: projectStatus.value,
    completionRate: completionRate(normalizedOverview.value.projects),
    segments: []
  }
]);

const leaderboards = computed(() => props.leaders ?? {});
const ticketAgents = computed(() => leaderboards.value.tickets?.agents ?? []);
const ticketRequesters = computed(() => leaderboards.value.tickets?.requesters ?? []);
const taskAssignees = computed(() => leaderboards.value.tasks?.assignees ?? []);
const projectOwners = computed(() => leaderboards.value.projects?.owners ?? []);

const alertState = computed(() => props.alerts ?? {});

const slaAlerts = computed(() => {
  const base = alertState.value.sla ?? {};
  const metrics = {
    breached: base.metrics?.breached ?? 0,
    dueSoon: base.metrics?.dueSoon ?? 0,
    avgDelay: base.metrics?.avgDelayHours ?? 0,
    onTrack: base.metrics?.onTrack ?? 0,
    withDeadline: base.metrics?.withDeadline ?? 0,
    complianceRate: slaStats.value.compliance_rate,
    breachRate: slaStats.value.breach_rate
  };
  return {
    metrics,
    dueSoon: base.dueSoon ?? [],
    breached: base.breached ?? []
  };
});

const focusAlerts = computed(() => ({
  tasks: alertState.value.focus?.tasks_due_soon ?? [],
  projects: alertState.value.focus?.projects_due_soon ?? []
}));
</script>
