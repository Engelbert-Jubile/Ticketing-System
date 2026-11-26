<template>
  <div class="mx-auto max-w-6xl space-y-6 px-4 py-6 lg:px-6">
    <section class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-blue-500 via-cyan-500 to-teal-500 p-6 text-white shadow-xl">
      <div class="relative z-[1] flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div>
          <div class="mb-2 text-sm font-semibold opacity-95" v-if="task.task_no">{{ task.task_no }}</div>
          <h1 class="text-3xl font-bold leading-tight">{{ task.title }}</h1>
          <p v-if="task.description" class="mt-2 line-clamp-2 text-sm text-white/80" v-html="strippedDescription"></p>
        </div>
        <div class="flex flex-wrap gap-2">
          <a
            v-if="task.links.pdf"
            :href="task.links.pdf"
            target="_blank"
            rel="noopener"
            class="action-chip"
          >
            <span class="material-icons text-sm" aria-hidden="true">picture_as_pdf</span>
            {{ $t("Unduh PDF") }}
          </a>
          <Link v-if="task.links.edit" :href="task.links.edit" class="action-chip">
            <span class="material-icons text-sm">edit</span>
            {{ $t("Edit Task") }}
          </Link>
          <Link v-if="task.links.view" :href="task.links.view" class="action-chip">
            <span class="material-icons text-sm">visibility</span>
            {{ $t("Mode Tampilan") }}
          </Link>
        </div>
      </div>
      <div class="absolute -right-12 -top-12 h-36 w-36 rounded-full bg-white/20 blur-2xl"></div>
    </section>

    <div class="grid gap-6 lg:grid-cols-3">
      <div class="space-y-6 lg:col-span-2">
        <article v-if="task.description" class="rounded-2xl border border-slate-200 bg-white/95 p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
          <h2 class="mb-4 text-lg font-bold text-slate-900 dark:text-slate-100">{{ $t("Deskripsi") }}</h2>
          <div class="prose prose-sm max-w-none text-slate-700 dark:prose-invert dark:text-slate-300" v-html="task.description"></div>
        </article>

        <article v-if="task.attachments.length" class="rounded-2xl border border-slate-200 bg-white/95 p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
          <h2 class="mb-4 text-lg font-bold text-slate-900 dark:text-slate-100">{{ $t("Lampiran (") }}{{ task.attachments.length }})</h2>
          <ul class="space-y-2 text-sm text-slate-600 dark:text-slate-300">
            <li v-for="attachment in task.attachments" :key="attachment.id" class="flex flex-col items-start gap-2 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between dark:border-slate-700 dark:bg-slate-800/60">
              <div class="w-full flex-1 truncate sm:w-auto">
                <div class="font-medium text-slate-900 dark:text-slate-100">{{ attachment.name }}</div>
                <div v-if="formatSize(attachment.size)" class="text-xs text-slate-400">{{ formatSize(attachment.size) }}</div>
              </div>
              <div class="flex w-full flex-wrap items-center gap-2 sm:ml-4 sm:w-auto sm:justify-end">
                <a :href="attachment.view_url" target="_blank" class="inline-flex items-center gap-1 rounded-lg border border-blue-200 px-3 py-1.5 text-xs font-semibold text-blue-600 transition hover:bg-blue-50 dark:border-blue-400/40 dark:text-blue-200 dark:hover:bg-blue-500/10">{{ $t("Lihat") }}</a>
                <a :href="attachment.download_url" class="inline-flex items-center gap-1 rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-100 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800">{{ $t("Unduh") }}</a>
              </div>
            </li>
          </ul>
        </article>

      </div>

      <aside class="space-y-6">
        <article class="card space-y-4">
          <div>
            <h3 class="card-title">Status</h3>
            <div>
              <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ $t("Status Ditampilkan") }}</p>
              <div class="mt-2">
                <StatusBadge :status="task.status" :label="task.status_label" variant="status" />
              </div>
            </div>
          </div>
          <div v-if="statusBreakdown.length" class="space-y-3 rounded-2xl border border-slate-200 bg-white/80 p-3 dark:border-slate-700 dark:bg-slate-900/60">
            <div v-for="panel in statusBreakdown" :key="panel.label">
              <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ panel.label }}</p>
              <div class="mt-1">
                <StatusBadge v-if="panel.labelText" :status="panel.status" :label="panel.labelText" variant="status" />
                <p v-else class="text-sm font-medium text-slate-500 dark:text-slate-400">—</p>
              </div>
            </div>
          </div>
        </article>

        <article v-if="task.priority" class="card">
          <h3 class="card-title">{{ $t("Prioritas") }}</h3>
          <StatusBadge :status="task.priority" :label="task.priority_label" variant="priority" />
        </article>

        <article class="card">
          <h3 class="card-title">{{ $t("Agent & PIC") }}</h3>
          <div class="space-y-4">
            <div>
              <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ $t("Agent") }}</p>
              <div v-if="assignmentAgent" class="mt-2 flex flex-wrap gap-2">
                <TasksAssigneePill :name="assignmentAgent.name" :email="assignmentAgent.email" role="Agent" />
              </div>
              <p v-else class="mt-2 text-sm font-medium text-slate-500 dark:text-slate-400">{{ $t("— Tidak ada agent —") }}</p>
            </div>
            <div>
              <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">PIC</p>
              <div v-if="assignmentPics.length" class="mt-2 flex flex-wrap gap-2">
                <TasksAssigneePill
                  v-for="person in assignmentPics"
                  :key="`${person.id ?? person.email ?? person.name}-pic`"
                  :name="person.name"
                  :email="person.email"
                  role="PIC"
                />
              </div>
              <p v-else class="mt-2 text-sm font-medium text-slate-500 dark:text-slate-400">{{ $t("— Tidak ada PIC —") }}</p>
            </div>
          </div>
        </article>

        <article v-if="task.requester" class="card">
          <h3 class="card-title">{{ $t("Pembuat") }}</h3>
          <div class="flex items-center gap-3">
            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-100 text-sm font-semibold text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-200">
              {{ task.requester.name?.charAt(0)?.toUpperCase() || '?' }}
            </div>
            <div class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ task.requester.name }}</div>
          </div>
        </article>



        <article v-if="task.due_display !== '—'" class="card">
          <h3 class="card-title">{{ $t("Target Selesai") }}</h3>
          <div class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ task.due_display }}</div>
        </article>

        <article v-if="timelinePanels.length" class="card">
          <h3 class="card-title">{{ $t("Timeline") }}</h3>
          <div class="space-y-4">
            <div
              v-for="panel in timelinePanels"
              :key="panel.label"
              class="rounded-2xl border border-slate-200 bg-white/80 p-3 text-sm text-slate-600 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-200"
            >
              <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ panel.label }}</p>
              <dl class="mt-2 space-y-2">
                <div>
                  <dt class="text-xs font-semibold text-slate-400 dark:text-slate-500">{{ $t("Mulai") }}</dt>
                  <dd class="font-semibold text-slate-900 dark:text-slate-100">{{ panel.data.start || '—' }}</dd>
                </div>
                <div>
                  <dt class="text-xs font-semibold text-slate-400 dark:text-slate-500">{{ $t("Due") }}</dt>
                  <dd class="font-semibold text-slate-900 dark:text-slate-100">{{ panel.data.due || '—' }}</dd>
                </div>
                <div>
                  <dt class="text-xs font-semibold text-slate-400 dark:text-slate-500">{{ $t("Selesai") }}</dt>
                  <dd class="font-semibold text-slate-900 dark:text-slate-100">{{ panel.data.end || '—' }}</dd>
                </div>
              </dl>
            </div>
          </div>
        </article>
      </aside>
    </div>
  </div>
</template>

<script setup>
import { translateUi as uiText } from '../../i18n'

import { computed, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import StatusBadge from '@/Components/StatusBadge.vue';
import TasksAssigneePill from '@/Components/TasksAssigneePill.vue';

const props = defineProps({
  task: { type: Object, required: true },
});

const strippedDescription = computed(() => {
  if (!props.task.description) return '';
  if (typeof window === 'undefined') {
    return props.task.description.replace(/<[^>]+>/g, ' ').trim();
  }
  const div = window.document.createElement('div');
  div.innerHTML = props.task.description;
  return div.textContent?.trim() || '';
});

function formatSize(size) {
  if (!size || Number.isNaN(Number(size))) return '';
  const kb = Number(size) / 1024;
  if (kb < 1024) return `${kb.toFixed(0)} KB`;
  const mb = kb / 1024;
  return `${mb.toFixed(1)} MB`;
}

const task = computed(() => props.task);


const statusBreakdown = computed(() => {
  const panels = [];
  panels.push({ label: uiText('Task status'), status: task.value?.status_task, labelText: task.value?.status_task_label });

  return panels.filter(panel => panel.labelText);
});

const timelinePanels = computed(() => {
  const panels = [];
  const taskTimeline = task.value?.timeline?.task;

  if (taskTimeline && hasTimelineContent(taskTimeline)) {
    panels.push({ label: uiText('Task'), data: taskTimeline });
  }

  return panels;
});

function hasTimelineContent(timeline) {
  if (!timeline) return false;
  return ['start', 'due', 'end'].some(key => {
    const value = timeline[key];
    return value && value !== '—';
  });
}

const assignmentAgent = computed(() => task.value?.assignment?.agent ?? null);
const assignmentPics = computed(() => (Array.isArray(task.value?.assignment?.pics) ? task.value.assignment.pics : []));
</script>

<style scoped>
.card {
  border-radius: 1.25rem;
  border: 1px solid rgba(148, 163, 184, 0.35);
  background: rgba(255, 255, 255, 0.95);
  padding: 1.5rem;
  box-shadow: 0 6px 18px -12px rgba(15, 23, 42, 0.25);
}

.dark .card {
  border-color: rgba(51, 65, 85, 0.6);
  background: rgba(15, 23, 42, 0.85);
}

.card-title {
  margin-bottom: 1rem;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  color: rgba(100, 116, 139, 0.9);
}

.dark .card-title {
  color: rgba(148, 163, 184, 0.85);
}

.action-chip {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  border-radius: 9999px;
  border: 1px solid rgba(255, 255, 255, 0.6);
  background: rgba(255, 255, 255, 0.18);
  padding: 0.4rem 0.9rem;
  font-size: 0.75rem;
  font-weight: 600;
  color: #0f172a;
  backdrop-filter: blur(4px);
  transition: transform 0.2s ease, background-color 0.2s ease;
}

.action-chip:hover {
  transform: translateY(-1px);
  background: rgba(255, 255, 255, 0.3);
}

.material-icons {
  font-size: inherit;
}
</style>
