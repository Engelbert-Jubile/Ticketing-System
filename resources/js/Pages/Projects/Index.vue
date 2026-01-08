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
          <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">Project Overview</h1>
          <p class="text-sm text-slate-500 dark:text-slate-300">Ringkasan status project terkini.</p>
        </div>
      </div>
      <div class="flex items-center gap-3">
        <label class="text-sm text-slate-600 dark:text-slate-300">
          Tampilkan
          <select class="ml-2 rounded-lg border border-slate-300 px-3 py-1 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200" :value="filters.per_page" @change="changePerPage">
            <option value="6">6</option>
            <option value="12">12</option>
            <option value="24">24</option>
            <option value="36">36</option>
          </select>
          item
        </label>
      </div>
    </header>

    <section class="overflow-hidden rounded-3xl border border-slate-200/70 bg-white/90 shadow-sm backdrop-blur dark:border-slate-700/60 dark:bg-slate-900/70">
      <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
        <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800 dark:text-slate-400">
          <tr>
            <th class="px-4 py-3 text-left">Project</th>
            <th class="px-4 py-3 text-left">Project No</th>
            <th class="px-4 py-3 text-left">Status</th>
            <th class="px-4 py-3 text-left">Ticket</th>
            <th class="px-4 py-3 text-left">Target</th>
            <th class="px-4 py-3 text-left">Updated</th>
            <th class="px-4 py-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-700 dark:bg-slate-900/60">
          <tr v-for="project in projects.data" :key="project.id" class="transition hover:bg-slate-50 dark:hover:bg-slate-800/60">
            <td class="px-4 py-3 font-semibold text-slate-900 dark:text-slate-100">{{ project.title }}</td>
            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ project.project_no || '—' }}</td>
            <td class="px-4 py-3"><StatusBadge :status="project.status" :label="project.status_label" variant="status" size="sm" /></td>
            <td class="px-4 py-3 text-slate-600 dark:text-slate-400">
              <span v-if="project.ticket" class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                {{ project.ticket.ticket_no }}
              </span>
              <span v-else class="text-slate-400">—</span>
            </td>
            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ project.due_display }}</td>
            <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ project.updated_diff }}</td>
            <td class="px-4 py-3">
              <div class="flex justify-end">
                <Link :href="project.links.show" class="action-btn action-btn--view">Detail</Link>
              </div>
            </td>
          </tr>
          <tr v-if="!projects.data.length">
            <td colspan="7" class="px-4 py-8 text-center text-slate-500 dark:text-slate-300">Belum ada project.</td>
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
  router.get(route('projects.index'), { per_page: perPage }, {
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

.action-btn--view:hover {
  background: rgba(59, 130, 246, 0.12);
}

.dark .action-btn {
  border-color: rgba(96, 165, 250, 0.3);
  color: #bfdbfe;
}

.material-icons {
  font-size: inherit;
}
</style>
