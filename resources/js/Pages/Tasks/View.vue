<template>
  <div class="mx-auto max-w-5xl space-y-6 px-4 py-6 lg:px-6">
    <header class="flex flex-col gap-3 rounded-3xl border border-slate-200 bg-white/95 p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
      <div class="flex items-start justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ task.title }}</h1>
          <p v-if="task.task_no" class="text-sm text-slate-500 dark:text-slate-400">Task No: {{ task.task_no }}</p>
        </div>
        <StatusBadge :status="task.status" :label="task.status_label" variant="status" />
      </div>
      <div v-if="task.priority" class="text-sm text-slate-600 dark:text-slate-300">
        Prioritas: <StatusBadge :status="task.priority" :label="task.priority_label" variant="priority" size="sm" />
      </div>
    </header>

    <section v-if="task.description" class="rounded-3xl border border-slate-200 bg-white/95 p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
      <h2 class="mb-3 text-lg font-semibold text-slate-900 dark:text-slate-100">Deskripsi</h2>
      <div class="prose prose-sm max-w-none text-slate-700 dark:prose-invert" v-html="task.description"></div>
    </section>

    <section class="grid gap-6 md:grid-cols-2">
        <article class="card">
          <h3 class="card-title">Agent &amp; PIC</h3>
          <div class="space-y-4">
            <div>
              <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">Agent</p>
              <div v-if="assignmentAgent" class="mt-2 flex flex-wrap gap-2">
                <TasksAssigneePill :name="assignmentAgent.name" :email="assignmentAgent.email" role="Agent" />
              </div>
              <p v-else class="mt-2 text-sm font-medium text-slate-500 dark:text-slate-400">— Tidak ada agent —</p>
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
              <p v-else class="mt-2 text-sm font-medium text-slate-500 dark:text-slate-400">— Tidak ada PIC —</p>
            </div>
          </div>
        </article>

      <article class="card" v-if="task.requester">
        <h3 class="card-title">Pembuat</h3>
        <div class="text-sm text-slate-700 dark:text-slate-200">{{ task.requester.name }}</div>
      </article>

      <article class="card" v-if="task.ticket">
        <h3 class="card-title">Ticket Terkait</h3>
        <div class="text-sm text-slate-600 dark:text-slate-300">
          <div class="font-semibold text-slate-800 dark:text-slate-100">{{ task.ticket.ticket_no }}</div>
          <div class="line-clamp-2 text-xs">{{ task.ticket.title }}</div>
        </div>
      </article>

      <article class="card" v-if="task.project">
        <h3 class="card-title">Project</h3>
        <div class="text-sm text-slate-600 dark:text-slate-300">{{ task.project.title }}</div>
      </article>

      <article class="card" v-if="task.due_display !== '—'">
        <h3 class="card-title">Target Selesai</h3>
        <div class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ task.due_display }}</div>
      </article>
    </section>

    <section v-if="task.attachments.length" class="rounded-3xl border border-slate-200 bg-white/95 p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
      <h2 class="mb-4 text-lg font-semibold text-slate-900 dark:text-slate-100">Lampiran</h2>
      <ul class="space-y-2 text-sm text-slate-600 dark:text-slate-300">
        <li v-for="attachment in task.attachments" :key="attachment.id" class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-700 dark:bg-slate-800/60">
          <div class="flex-1 truncate">
            <div class="font-medium text-slate-900 dark:text-slate-100">{{ attachment.name }}</div>
            <div v-if="formatSize(attachment.size)" class="text-xs text-slate-400">{{ formatSize(attachment.size) }}</div>
          </div>
          <div class="ml-4 flex items-center gap-2">
            <a :href="attachment.view_url" target="_blank" class="inline-flex items-center gap-1 rounded-lg border border-blue-200 px-3 py-1.5 text-xs font-semibold text-blue-600 transition hover:bg-blue-50 dark:border-blue-400/40 dark:text-blue-200 dark:hover:bg-blue-500/10">Lihat</a>
            <a :href="attachment.download_url" class="inline-flex items-center gap-1 rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-100 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800">Unduh</a>
          </div>
        </li>
      </ul>
    </section>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import TasksAssigneePill from '@/Components/TasksAssigneePill.vue';

const props = defineProps({
  task: { type: Object, required: true },
});

function formatSize(size) {
  if (!size || Number.isNaN(Number(size))) return '';
  const kb = Number(size) / 1024;
  if (kb < 1024) return `${kb.toFixed(0)} KB`;
  const mb = kb / 1024;
  return `${mb.toFixed(1)} MB`;
}

const task = props.task;
const assignmentAgent = computed(() => props.task?.assignment?.agent ?? null);
const assignmentPics = computed(() => (Array.isArray(props.task?.assignment?.pics) ? props.task.assignment.pics : []));
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
  margin-bottom: 0.75rem;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: rgba(100, 116, 139, 0.9);
}

.dark .card-title {
  color: rgba(148, 163, 184, 0.85);
}
</style>
