<template>
  <div class="space-y-8 px-4 py-8">
    <Head :title="`Pencarian: ${query}`" />
    <div>
      <Link
        :href="backUrl"
        class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 transition hover:text-blue-600 dark:text-slate-400 dark:hover:text-blue-400"
      >
        <span class="material-icons text-base">arrow_back</span>
        <span>Kembali</span>
      </Link>
      <h1 class="mt-4 text-3xl font-bold text-slate-900 dark:text-slate-100">
        Hasil Pencarian: <span class="text-blue-600">"{{ query }}"</span>
      </h1>
    </div>

    <div v-if="empty" class="flex flex-col items-center justify-center rounded-3xl bg-white/90 p-16 text-center shadow-xl backdrop-blur dark:bg-slate-900/90">
      <span class="material-icons mb-4 text-6xl text-slate-300 dark:text-slate-600">search_off</span>
      <h2 class="text-2xl font-semibold text-slate-700 dark:text-slate-200">Tidak ada hasil ditemukan</h2>
      <p class="mt-2 max-w-lg text-sm text-slate-500 dark:text-slate-400">Coba gunakan kata kunci lain yang lebih umum.</p>
    </div>

    <div
      v-else
      class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3"
    >
      <article
        v-for="project in projects"
        :key="`project-${project.id}`"
        class="flex h-full flex-col overflow-hidden rounded-3xl bg-white/95 shadow-lg transition hover:-translate-y-1 hover:shadow-2xl dark:bg-slate-900/95"
      >
        <Link :href="project.url" class="flex h-full flex-col">
          <div class="flex items-center gap-3 border-b border-slate-100 px-6 py-5 dark:border-slate-700">
            <span class="material-icons text-blue-500">inventory_2</span>
            <h3 class="line-clamp-1 text-lg font-bold text-slate-900 dark:text-slate-100" :title="project.title">{{ project.title }}</h3>
          </div>
          <p class="flex-1 px-6 py-5 text-sm text-slate-500 dark:text-slate-400 line-clamp-3">{{ project.description }}</p>
          <div class="flex items-center justify-between border-t border-slate-100 px-6 py-4 text-xs text-slate-500 dark:border-slate-700 dark:text-slate-400">
            <span class="font-semibold">PROJECT</span>
            <span>{{ project.created_at }}</span>
          </div>
        </Link>
      </article>

      <article
        v-for="ticket in tickets"
        :key="`ticket-${ticket.id}`"
        class="flex h-full flex-col overflow-hidden rounded-3xl bg-white/95 shadow-lg transition hover:-translate-y-1 hover:shadow-2xl dark:bg-slate-900/95"
      >
        <Link :href="ticket.url" class="flex h-full flex-col">
          <div class="flex items-center gap-3 border-b border-slate-100 px-6 py-5 dark:border-slate-700">
            <span class="material-icons text-green-500">confirmation_number</span>
            <h3 class="line-clamp-1 text-lg font-bold text-slate-900 dark:text-slate-100" :title="ticket.title">{{ ticket.title }}</h3>
          </div>
          <p class="flex-1 px-6 py-5 text-sm text-slate-500 dark:text-slate-400 line-clamp-3">{{ ticket.description }}</p>
          <div class="flex flex-wrap items-center gap-2 border-t border-slate-100 px-6 py-4 text-xs dark:border-slate-700">
            <StatusPill
              v-if="ticket.status"
              :status="ticket.status.value || ticket.status.name || ticket.status"
              :label="ticket.status.name || ticket.status.label || 'Status'"
              size="sm"
            />
            <StatusPill
              v-if="ticket.priority"
              :status="ticket.priority.value || ticket.priority.name || ticket.priority"
              :label="ticket.priority.name || ticket.priority.label"
              variant="priority"
              size="sm"
            />
            <span class="ml-auto text-slate-500 dark:text-slate-400">{{ ticket.created_diff }}</span>
          </div>
        </Link>
      </article>

      <article
        v-for="task in tasks"
        :key="`task-${task.id}`"
        class="flex h-full flex-col overflow-hidden rounded-3xl bg-white/95 shadow-lg transition hover:-translate-y-1 hover:shadow-2xl dark:bg-slate-900/95"
      >
        <Link :href="task.url" class="flex h-full flex-col">
          <div class="flex items-center gap-3 border-b border-slate-100 px-6 py-5 dark:border-slate-700">
            <span class="material-icons text-purple-500">task_alt</span>
            <h3 class="line-clamp-1 text-lg font-bold text-slate-900 dark:text-slate-100" :title="task.title">{{ task.title }}</h3>
          </div>
          <p class="flex-1 px-6 py-5 text-sm text-slate-500 dark:text-slate-400 line-clamp-3">{{ task.description }}</p>
          <div class="flex flex-wrap items-center gap-2 border-t border-slate-100 px-6 py-4 text-xs dark:border-slate-700">
            <StatusPill :status="task.status?.value || task.status" :label="task.status?.label" size="sm" />
            <span v-if="task.requester" class="text-slate-500 dark:text-slate-400">Oleh: {{ task.requester.name }}</span>
            <span class="ml-auto text-slate-500 dark:text-slate-400">{{ task.created_diff }}</span>
          </div>
        </Link>
      </article>
    </div>
  </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import StatusPill from '@/Components/StatusPill.vue';

const props = defineProps({
  query: {
    type: String,
    required: true,
  },
  backUrl: {
    type: String,
    required: true,
  },
  tickets: {
    type: Array,
    default: () => [],
  },
  tasks: {
    type: Array,
    default: () => [],
  },
  projects: {
    type: Array,
    default: () => [],
  },
});

const empty = computed(() => !props.tickets.length && !props.tasks.length && !props.projects.length);
</script>
