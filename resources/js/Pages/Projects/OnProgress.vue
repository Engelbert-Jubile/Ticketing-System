<template>
  <div class="mx-auto max-w-6xl space-y-6 px-4 py-6 lg:px-6">
    <header class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Project In Progress</h1>
        <p class="text-sm text-slate-500 dark:text-slate-300">Daftar project yang sedang berjalan.</p>
      </div>
      <div class="flex items-center gap-3">
        <label class="text-sm text-slate-600 dark:text-slate-300">
          Tampilkan
          <select class="ml-2 rounded-lg border border-slate-300 px-3 py-1 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200" :value="filters.per_page" @change="changePerPage">
            <option value="6">6</option>
            <option value="12">12</option>
            <option value="24">24</option>
          </select>
          item
        </label>
      </div>
    </header>

    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white/90 shadow-sm dark:border-slate-700 dark:bg-slate-900/70">
      <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
        <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800 dark:text-slate-400">
          <tr>
            <th class="px-4 py-2 text-left">#</th>
            <th class="px-4 py-2 text-left">Project</th>
            <th class="px-4 py-2 text-left">Project No</th>
            <th class="px-4 py-2 text-left">Status</th>
            <th class="px-4 py-2 text-left">Target</th>
            <th class="px-4 py-2 text-left">Diupdate</th>
            <th class="px-4 py-2 text-left">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-700 dark:bg-slate-900/60">
          <tr v-for="(project, index) in projects.data" :key="project.id" class="transition hover:bg-slate-50 dark:hover:bg-slate-800/60">
            <td class="px-4 py-3 text-xs text-slate-500">{{ (projects.meta.current_page - 1) * projects.meta.per_page + index + 1 }}</td>
            <td class="px-4 py-3 font-medium text-slate-700 dark:text-slate-200">
              <Link :href="project.links.show" class="text-blue-600 hover:underline dark:text-blue-400">{{ project.title }}</Link>
            </td>
            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ project.project_no || '—' }}</td>
            <td class="px-4 py-3"><StatusBadge :status="project.status" :label="project.status_label" variant="status" size="sm" /></td>
            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ project.due_display }}</td>
            <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ project.updated_diff }}</td>
            <td class="px-4 py-3">
              <div class="flex flex-wrap items-center gap-2">
                <Link :href="project.links.show" class="action-btn action-btn--view">Detail</Link>
              </div>
            </td>
          </tr>
          <tr v-if="!projects.data.length">
            <td colspan="7" class="px-4 py-8 text-center text-slate-500 dark:text-slate-300">Tidak ada project yang sedang berjalan.</td>
          </tr>
        </tbody>
      </table>
    </section>

    <nav v-if="projects.links?.length > 3" class="flex flex-wrap items-center justify-end gap-2">
      <button
        v-for="link in projects.links"
        :key="link.label"
        type="button"
        class="rounded-lg border px-3 py-1.5 text-sm transition"
        :class="link.active
          ? 'border-blue-500 bg-blue-500 text-white shadow-sm'
          : link.url
            ? 'border-slate-300 bg-white text-slate-600 hover:bg-slate-100 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800'
            : 'border-slate-200 bg-slate-100 text-slate-400 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-500'"
        :disabled="!link.url"
        @click="goTo(link)"
        v-html="link.label"
      />
    </nav>
  </div>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3';
import StatusBadge from '@/Components/StatusBadge.vue';

const props = defineProps({
  projects: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
});

const filters = props.filters ?? {};

function changePerPage(event) {
  const perPage = Number(event.target.value || filters.per_page || 12);
  router.get(route('projects.on-progress'), { per_page: perPage }, {
    preserveState: true,
    replace: true,
    preserveScroll: true,
  });
}

function goTo(link) {
  if (!link.url || link.active) return;
  router.visit(link.url, {
    preserveState: true,
    preserveScroll: true,
  });
}
</script>

<style scoped>
.action-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  border-radius: 0.75rem;
  border: 1px solid rgba(59, 130, 246, 0.4);
  padding: 0.35rem 0.9rem;
  font-size: 0.75rem;
  font-weight: 600;
  color: #2563eb;
  transition: background-color 0.2s ease, color 0.2s ease;
}

.action-btn--view:hover { background: rgba(59, 130, 246, 0.12); }
.dark .action-btn { border-color: rgba(96, 165, 250, 0.3); color: #bfdbfe; }
.material-icons { font-size: inherit; }
</style>
