<template>
  <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 lg:px-6">
    <header class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div class="flex items-start gap-3">
        <Link
          :href="route('dashboard')"
          class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:text-blue-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300"
        >
          <span class="material-icons">arrow_back</span>
        </Link>
        <div>
          <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">Task Reports</h1>
          <p class="text-sm text-slate-500 dark:text-slate-300">Atur pekerjaan tim, lihat status terbaru, dan tindak lanjuti pekerjaan yang tertunda.</p>
        </div>
      </div>
      <Link
        :href="route('tasks.create')"
        class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-500 via-teal-500 to-sky-500 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-500/30 transition hover:shadow-xl"
      >
        <span class="material-icons text-[18px]">add_task</span>
        Task Baru
      </Link>
    </header>

    <section class="flex flex-wrap gap-2 text-sm font-medium">
      <button
        type="button"
        class="filter-pill"
        :class="!filters.status ? 'filter-pill--active' : ''"
        @click="applyFilter('')"
      >Semua</button>
      <button
        v-for="option in statusOptions"
        :key="option.value"
        type="button"
        class="filter-pill"
        :class="filters.status === option.value ? 'filter-pill--active' : ''"
        @click="applyFilter(option.value)"
      >
        {{ option.label }}
      </button>
    </section>

    <div class="overflow-hidden rounded-3xl border border-slate-200/70 bg-white/90 shadow-sm backdrop-blur dark:border-slate-700/60 dark:bg-slate-900/70">
      <table class="min-w-full divide-y divide-slate-200/70 text-sm dark:divide-slate-700/60">
        <thead class="bg-slate-50/80 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/70 dark:text-slate-300">
          <tr>
            <th class="px-4 py-3">Title</th>
            <th class="px-4 py-3">Priority</th>
            <th class="px-4 py-3">Status</th>
            <th class="px-4 py-3">Status ID</th>
            <th class="px-4 py-3">Due</th>
            <th class="px-4 py-3">Attachments</th>
            <th class="px-4 py-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200/60 bg-white dark:divide-slate-800/70 dark:bg-slate-900/60">
          <tr v-for="task in tasks" :key="task.id" class="transition hover:bg-blue-50/60 dark:hover:bg-slate-800/80">
            <td class="px-4 py-3 font-semibold text-slate-900 dark:text-slate-100">
              <Link :href="task.links.show" class="hover:underline">{{ task.title }}</Link>
            </td>
            <td class="px-4 py-3 capitalize">
              <StatusBadge :status="task.priority" variant="priority" size="sm" />
            </td>
            <td class="px-4 py-3 align-middle">
              <StatusBadge :status="task.status" :label="task.status_label" variant="status" size="sm" />
            </td>
            <td class="px-4 py-3 font-semibold text-slate-600 dark:text-slate-300">{{ task.status_id }}</td>
            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ task.due_display }}</td>
            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">
              <template v-if="task.attachments.length">
                <ul class="list-disc list-inside text-xs">
                  <li v-for="attachment in task.attachments" :key="attachment.id">
                    <a :href="attachment.view_url" target="_blank" class="text-blue-600 hover:underline dark:text-blue-400">Lihat</a>
                    <span class="text-slate-400">·</span>
                    <a :href="attachment.download_url" class="text-blue-600 hover:underline dark:text-blue-400">Unduh</a>
                  </li>
                </ul>
              </template>
              <span v-else class="text-slate-400">-</span>
            </td>
            <td class="px-4 py-3">
              <div class="flex flex-wrap justify-end gap-2">
                <Link :href="task.links.show" class="action-btn action-btn--view">View</Link>
                <Link :href="task.links.edit" class="action-btn action-btn--edit">Edit</Link>
                <button type="button" class="action-btn action-btn--delete" @click="destroyTask(task)">Delete</button>
              </div>
            </td>
          </tr>
          <tr v-if="!tasks.length">
            <td colspan="7" class="px-4 py-10 text-center text-slate-500 dark:text-slate-300">
              Belum ada task untuk ditampilkan.
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import StatusBadge from '@/Components/StatusBadge.vue';

const props = defineProps({
  tasks: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
  statusOptions: { type: Array, default: () => [] },
});

const filters = computed(() => props.filters ?? {});

function applyFilter(value) {
  router.get(
    route('tasks.index'),
    { status: value || undefined },
    { preserveState: true, preserveScroll: true, replace: true }
  );
}

function destroyTask(task) {
  if (!task?.links?.delete) return;
  if (!confirm('Hapus task ini?')) {
    return;
  }
  router.delete(task.links.delete, { preserveScroll: true });
}
</script>

<style scoped>
.filter-pill {
  border-radius: 9999px;
  border: 1px solid transparent;
  background: rgba(255, 255, 255, 0.6);
  padding: 0.35rem 1.1rem;
  color: #475569;
  transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease;
}

.filter-pill--active {
  border-color: rgba(59, 130, 246, 0.4);
  background: rgba(191, 219, 254, 0.65);
  color: #1d4ed8;
  box-shadow: 0 5px 10px -8px rgba(37, 99, 235, 0.6);
}

.filter-pill:not(.filter-pill--active):hover {
  background: rgba(226, 232, 240, 0.7);
}

.dark .filter-pill {
  background: rgba(30, 41, 59, 0.6);
  color: #cbd5f5;
}

.dark .filter-pill--active {
  background: rgba(37, 99, 235, 0.2);
  border-color: rgba(96, 165, 250, 0.4);
  color: #bfdbfe;
}

.action-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  border-radius: 0.75rem;
  border: 1px solid transparent;
  padding: 0.35rem 0.9rem;
  font-size: 0.75rem;
  font-weight: 600;
  transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease;
}

.action-btn--view {
  border-color: rgba(129, 140, 248, 0.4);
  color: #4f46e5;
}

.action-btn--view:hover {
  background: rgba(129, 140, 248, 0.12);
}

.action-btn--edit {
  border-color: rgba(59, 130, 246, 0.4);
  color: #2563eb;
}

.action-btn--edit:hover {
  background: rgba(59, 130, 246, 0.12);
}

.action-btn--delete {
  border-color: rgba(248, 113, 113, 0.4);
  color: #dc2626;
}

.action-btn--delete:hover {
  background: rgba(248, 113, 113, 0.12);
}

.dark .action-btn--view { border-color: rgba(129, 140, 248, 0.3); color: #c7d2fe; }
.dark .action-btn--edit { border-color: rgba(96, 165, 250, 0.3); color: #bfdbfe; }
.dark .action-btn--delete { border-color: rgba(248, 113, 113, 0.3); color: #fecaca; }

.material-icons {
  font-size: inherit;
}
</style>
