<template>
  <div class="mx-auto max-w-6xl space-y-6 px-4 py-6 lg:px-6">
    <header class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Tickets - In Progress</h1>
        <p class="text-sm text-slate-500 dark:text-slate-300">Daftar ticket yang sedang diproses.</p>
      </div>
      <div class="flex items-center gap-2">
        <Link
          :href="route('tickets.index')"
          class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-3 py-1.5 text-sm text-slate-600 transition hover:bg-slate-100 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800"
        >
          <span class="material-icons text-sm">list</span>
          Semua Ticket
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
    </header>

    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white/80 shadow-sm dark:border-slate-700 dark:bg-slate-900/70">
      <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
        <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800 dark:text-slate-400">
          <tr>
            <th class="px-4 py-2 text-left">#</th>
            <th class="px-4 py-2 text-left">Judul</th>
            <th class="px-4 py-2 text-left">Deskripsi</th>
            <th class="px-4 py-2 text-left">Status</th>
            <th class="px-4 py-2 text-left">Updated</th>
            <th class="px-4 py-2 text-left">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-700 dark:bg-slate-900/60">
          <tr v-for="(ticket, index) in tickets" :key="ticket.id" class="transition hover:bg-slate-50 dark:hover:bg-slate-800/50">
            <td class="px-4 py-3 text-xs text-slate-500">{{ index + 1 }}</td>
            <td class="px-4 py-3 font-medium text-slate-700 dark:text-slate-200">
              <Link :href="ticket.links.show" class="text-blue-600 hover:underline dark:text-blue-400">{{ ticket.title }}</Link>
            </td>
            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">
              <span class="line-clamp-2">{{ ticket.description || '—' }}</span>
            </td>
            <td class="px-4 py-3">
              <StatusPill :status="ticket.status" :label="ticket.status_label" size="sm" />
            </td>
            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ ticket.updated_diff }}</td>
            <td class="px-4 py-3">
              <div class="flex flex-wrap items-center gap-2">
                <Link :href="ticket.links.show" class="text-blue-600 hover:underline dark:text-blue-400">Detail</Link>
                <div v-if="ticket.status_actions?.length" class="relative">
                  <select
                    class="w-44 appearance-none rounded-full border border-slate-300 bg-white px-3 py-1 text-xs text-slate-700 pr-7 transition focus:border-blue-400 focus:outline-none dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
                    @change="event => changeStatus(event, ticket)"
                  >
                    <option selected disabled>Pilih status…</option>
                    <option v-for="action in ticket.status_actions" :key="action.value" :value="action.url">{{ action.label }}</option>
                  </select>
                  <span class="material-icons pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 text-xs text-slate-400">expand_more</span>
                </div>
              </div>
            </td>
          </tr>
          <tr v-if="!tickets.length">
            <td colspan="6" class="px-4 py-6 text-center text-slate-500">Tidak ada ticket yang sedang diproses.</td>
          </tr>
        </tbody>
      </table>
    </section>
  </div>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import StatusPill from '@/Components/StatusPill.vue';

const props = defineProps({
  tickets: { type: Array, default: () => [] },
});

const refreshing = ref(false);

function refresh() {
  refreshing.value = true;
  router.get(route('tickets.on-progress'), {}, {
    only: ['tickets'],
    preserveScroll: true,
    replace: true,
    onFinish: () => {
      refreshing.value = false;
    },
  });
}

function changeStatus(event, ticket) {
  const url = event?.target?.value;
  event.target.selectedIndex = 0;
  if (!url) return;
  router.visit(url, {
    preserveScroll: true,
    onSuccess: () => refresh(),
  });
}
</script>
