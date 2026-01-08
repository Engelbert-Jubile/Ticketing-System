<template>
  <div class="mx-auto max-w-6xl space-y-6 px-4 py-6 lg:px-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <Link :href="backUrl" class="inline-flex items-center gap-2 rounded-full border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
        <span class="material-icons text-base" aria-hidden="true">arrow_back</span>
        Kembali ke Task Report
      </Link>
      <div class="flex flex-wrap items-center gap-2">
        <Link
          v-if="ticket.links?.ticket"
          :href="ticket.links.ticket"
          class="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-white px-4 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-50 dark:border-blue-500/40 dark:bg-slate-900 dark:text-blue-200"
        >
          <span class="material-icons text-base" aria-hidden="true">launch</span>
          Lihat Ticket
        </Link>
      </div>
    </div>

    <section class="space-y-5 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
      <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Ticket</p>
          <h1 class="mt-2 text-2xl font-semibold text-slate-900 dark:text-white">{{ ticket.title }}</h1>
          <p class="text-sm text-slate-500 dark:text-slate-300">{{ ticket.ticket_no }}</p>
        </div>
        <StatusPill :status="ticket.status" :label="ticket.status_label" size="sm" />
      </div>

      <div class="grid gap-3 sm:grid-cols-3">
        <div v-for="card in summaryCards" :key="card.label" :class="card.class">
          <p class="text-xs font-semibold uppercase tracking-wide">{{ card.label }}</p>
          <p class="mt-2 text-2xl font-bold">{{ card.value }}</p>
        </div>
      </div>

      <div class="grid gap-4 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4 text-sm text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Timeline</p>
          <ul class="mt-3 space-y-1">
            <li>Mulai: <strong class="text-slate-900 dark:text-white">{{ ticket.timeline?.start || '—' }}</strong></li>
            <li>Jatuh Tempo: <strong class="text-slate-900 dark:text-white">{{ ticket.timeline?.due || '—' }}</strong></li>
            <li>Selesai: <strong class="text-slate-900 dark:text-white">{{ ticket.timeline?.end || '—' }}</strong></li>
          </ul>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4 text-sm text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Pemilik</p>
          <div class="mt-3 space-y-2">
            <div>
              <p class="text-[11px] uppercase tracking-wide text-slate-500">Requester</p>
              <p class="font-semibold text-slate-900 dark:text-white">{{ ticket.requester?.name || '—' }}</p>
              <p class="text-xs text-slate-500 dark:text-slate-300">{{ ticket.requester?.email || '—' }}</p>
            </div>
            <div>
              <p class="text-[11px] uppercase tracking-wide text-slate-500">Agent</p>
              <p class="font-semibold text-slate-900 dark:text-white">{{ ticket.agent?.name || '—' }}</p>
              <p class="text-xs text-slate-500 dark:text-slate-300">{{ ticket.agent?.email || '—' }}</p>
            </div>
          </div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4 text-sm text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">PIC</p>
          <ul class="mt-3 space-y-1">
            <li v-if="!ticket.assigned?.length" class="text-slate-500 dark:text-slate-300">Tidak ada PIC.</li>
            <li v-for="user in ticket.assigned" :key="user.id">
              <strong class="text-slate-900 dark:text-white">{{ user.name }}</strong>
              <span class="block text-xs text-slate-500 dark:text-slate-300">{{ user.email }}</span>
            </li>
          </ul>
        </div>
      </div>

      <div class="grid gap-4 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 text-sm shadow dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Deskripsi</p>
          <div class="prose prose-sm mt-3 max-w-none text-slate-600 dark:prose-invert" v-html="ticketDescription" />
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 text-sm shadow dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Lampiran</p>
          <ul class="mt-3 space-y-2">
            <li v-if="!ticket.attachments?.length" class="text-slate-500 dark:text-slate-300">Tidak ada lampiran.</li>
            <li v-for="file in ticket.attachments" :key="file.id" class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2 text-slate-600 dark:border-slate-700 dark:text-slate-200">
              <span>{{ file.name }}</span>
              <div class="flex items-center gap-2 text-xs">
                <a :href="file.view_url" target="_blank" class="text-blue-600 hover:underline dark:text-blue-300">Lihat</a>
                <a :href="file.download_url" class="text-blue-600 hover:underline dark:text-blue-300">Unduh</a>
              </div>
            </li>
          </ul>
        </div>
      </div>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
      <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <div>
          <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Daftar Task</h2>
          <p class="text-sm text-slate-500 dark:text-slate-300">
            Agent, PIC, dan due mengikuti data tiket. Status task hanya dapat diubah dari halaman detail task.
          </p>
        </div>
        <div class="flex flex-wrap gap-2 text-xs font-semibold">
          <span class="rounded-full bg-blue-100 px-3 py-1 text-blue-700 dark:bg-blue-500/10 dark:text-blue-100">Total {{ summary.total }}</span>
          <span class="rounded-full bg-amber-100 px-3 py-1 text-amber-700 dark:bg-amber-500/10 dark:text-amber-200">In Progress {{ summary.in_progress }}</span>
          <span class="rounded-full bg-emerald-100 px-3 py-1 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-200">Selesai {{ summary.done }}</span>
        </div>
      </div>

      <div class="mt-5 overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-700">
        <div class="overflow-x-auto">
          <table class="w-full min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
            <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800 dark:text-slate-300">
              <tr>
                <th class="px-4 py-3 text-left">#</th>
                <th class="px-4 py-3 text-left">Task</th>
                <th class="px-4 py-3 text-left">Task Status</th>
                <th class="px-4 py-3 text-left">Prioritas</th>
                <th class="px-4 py-3 text-left">Due</th>
                <th class="px-4 py-3 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-700 dark:bg-slate-900">
              <tr v-if="!tasks.length">
                <td colspan="6" class="px-4 py-6 text-center text-slate-500 dark:text-slate-300">Belum ada task terkait tiket ini.</td>
              </tr>
              <tr v-for="(row, index) in tasks" :key="row.id" class="transition hover:bg-slate-50 dark:hover:bg-slate-800/60">
                <td class="px-4 py-3">{{ index + 1 }}</td>
                <td class="px-4 py-3">
                  <div class="font-semibold text-slate-900 dark:text-slate-100">{{ row.title }}</div>
                  <div class="text-xs text-slate-500 dark:text-slate-300">{{ row.task_no || '—' }}</div>
                </td>
              <td class="px-4 py-3">
                <StatusPill :status="row.status_task || row.status" :label="row.status_task_label || row.status_label" size="sm" />
              </td>
                <td class="px-4 py-3 text-slate-600 dark:text-slate-200">{{ row.priority_label }}</td>
                <td class="px-4 py-3 text-slate-600 dark:text-slate-200">{{ row.due_display }}</td>
                <td class="px-4 py-3">
                  <div class="flex items-center justify-end gap-3">
                    <Link v-if="row.links?.show" :href="row.links.show" class="text-sm text-blue-600 hover:underline dark:text-blue-400">Detail</Link>
                    <Link v-if="row.links?.edit" :href="row.links.edit" class="text-sm text-blue-600 hover:underline dark:text-blue-400">Edit</Link>
                    <button
                      v-if="row.links?.delete"
                      type="button"
                      class="inline-flex items-center gap-1 text-sm font-semibold text-red-600 hover:text-red-700 disabled:opacity-60 dark:text-red-300 dark:hover:text-red-200"
                      @click="destroyTask(row)"
                    >
                      <span class="material-icons text-base" aria-hidden="true">delete</span>
                      Hapus
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import StatusPill from '@/Components/StatusPill.vue';

const props = defineProps({
  ticket: { type: Object, required: true },
  summary: { type: Object, default: () => ({ total: 0, in_progress: 0, done: 0 }) },
  tasks: { type: Array, default: () => [] },
  meta: { type: Object, default: () => ({}) },
});

const backUrl = computed(() => props.meta?.backUrl || route('tasks.report'));

const summaryCards = computed(() => [
  { label: 'Total Task', value: props.summary.total ?? 0, class: 'rounded-2xl border border-blue-100 bg-blue-50 px-4 py-3 dark:border-blue-500/40 dark:bg-blue-500/10 dark:text-blue-100 text-blue-800' },
  { label: 'In Progress', value: props.summary.in_progress ?? 0, class: 'rounded-2xl border border-amber-100 bg-amber-50 px-4 py-3 dark:border-amber-500/40 dark:bg-amber-500/10 text-amber-700 dark:text-amber-100' },
  { label: 'Selesai', value: props.summary.done ?? 0, class: 'rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 dark:border-emerald-500/40 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-100' },
]);

const ticketDescription = computed(() => props.ticket?.description || '<p>Tidak ada deskripsi.</p>');

function destroyTask(row) {
  if (!row.links?.delete) return;
  if (!window.confirm(`Yakin ingin menghapus task "${row.title}"?`)) return;

  router.delete(row.links.delete, {
    preserveScroll: true,
  });
}
</script>

<style scoped>
.material-icons {
  font-size: inherit;
  line-height: 1;
}
.prose :global(p) {
  margin: 0 0 0.75rem;
}
</style>
