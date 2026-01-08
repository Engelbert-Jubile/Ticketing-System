<template>
  <div class="mx-auto max-w-6xl space-y-6 px-4 py-6 lg:px-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Task In Progress</h1>
        <p class="text-sm text-slate-500 dark:text-slate-300">Daftar task yang sedang dikerjakan.</p>
      </div>
      <div class="flex items-center gap-2">
        <Link
          :href="route('tasks.create')"
          class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-400"
        >
          <span class="material-icons text-sm">add</span>
          Task Baru
        </Link>
        <button
          type="button"
          class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-3 py-1.5 text-sm text-slate-600 transition hover:bg-slate-100 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800"
          :disabled="refreshing"
          @click="refresh"
        >
          <span class="material-icons text-sm" v-if="refreshing">hourglass_top</span>
          <span class="material-icons text-sm" v-else>refresh</span>
          Segarkan
        </button>
      </div>
    </div>

    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white/90 shadow-sm dark:border-slate-700 dark:bg-slate-900/70">
      <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
        <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800 dark:text-slate-400">
          <tr>
            <th class="px-4 py-2 text-left">#</th>
            <th class="px-4 py-2 text-left">Judul</th>
            <th class="px-4 py-2 text-left">Deskripsi</th>
            <th class="px-4 py-2 text-left">Status</th>
            <th class="px-4 py-2 text-left">Diupdate</th>
            <th class="px-4 py-2 text-left">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-700 dark:bg-slate-900/60">
          <tr v-for="(task, index) in tasks" :key="task.id" class="transition hover:bg-slate-50 dark:hover:bg-slate-800/60">
            <td class="px-4 py-3 text-xs text-slate-500">{{ index + 1 }}</td>
            <td class="px-4 py-3 font-medium text-slate-700 dark:text-slate-200">
              <Link :href="task.links.show" class="text-blue-600 hover:underline dark:text-blue-400">{{ task.title }}</Link>
            </td>
            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ task.description || '—' }}</td>
            <td class="px-4 py-3">
              <StatusBadge :status="task.status" :label="task.status_label" variant="status" size="sm" />
            </td>
            <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ task.updated_diff }}</td>
            <td class="px-4 py-3">
              <div class="flex flex-wrap items-center gap-2">
                <Link :href="task.links.show" class="action-btn action-btn--view">Detail</Link>
                <Link v-if="task.links.edit" :href="task.links.edit" class="action-btn action-btn--edit">Edit</Link>
                <button v-if="task.links.delete" type="button" class="action-btn action-btn--delete" @click="destroyTask(task)">Delete</button>
              </div>
            </td>
          </tr>
          <tr v-if="!tasks.length">
            <td colspan="6" class="px-4 py-8 text-center text-slate-500 dark:text-slate-300">Tidak ada task in progress.</td>
          </tr>
        </tbody>
      </table>
    </section>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import StatusBadge from '@/Components/StatusBadge.vue';

const props = defineProps({
  tasks: { type: Array, default: () => [] },
});

const refreshing = ref(false);

function refresh() {
  refreshing.value = true;
  router.get(route('tasks.on-progress'), {}, {
    only: ['tasks'],
    preserveScroll: true,
    replace: true,
    onFinish: () => {
      refreshing.value = false;
    },
  });
}

function destroyTask(task) {
  if (!task?.links?.delete) return;
  if (!confirm('Hapus task ini?')) {
    return;
  }
  router.delete(task.links.delete, {
    preserveScroll: true,
    onSuccess: () => refresh(),
  });
}
</script>

<style scoped>
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
.action-btn--view:hover { background: rgba(129, 140, 248, 0.12); }

.action-btn--edit {
  border-color: rgba(59, 130, 246, 0.4);
  color: #2563eb;
}
.action-btn--edit:hover { background: rgba(59, 130, 246, 0.12); }

.action-btn--delete {
  border-color: rgba(248, 113, 113, 0.4);
  color: #dc2626;
}
.action-btn--delete:hover { background: rgba(248, 113, 113, 0.12); }

.dark .action-btn--view { border-color: rgba(129, 140, 248, 0.3); color: #c7d2fe; }
.dark .action-btn--edit { border-color: rgba(96, 165, 250, 0.3); color: #bfdbfe; }
.dark .action-btn--delete { border-color: rgba(248, 113, 113, 0.3); color: #fecaca; }

.material-icons { font-size: inherit; }
</style>
