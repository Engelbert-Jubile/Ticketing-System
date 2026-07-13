<template>
  <div class="mx-auto max-w-7xl space-y-6">
    <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <p class="text-xs font-bold uppercase tracking-[0.2em] text-blue-600">Automation</p>
        <h1 class="mt-1 text-3xl font-bold text-slate-900 dark:text-white">Workflow Management</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-300">Pantau Ticket dan Task aktual beserta tahap workflow saat ini.</p>
      </div>
      <Link v-if="can.create" :href="resolveRoute('workflows.create')" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
        <span class="material-icons text-lg">add</span> Buat Workflow
      </Link>
    </header>

    <section class="grid gap-4 sm:grid-cols-3">
      <article class="summary-card">
        <span class="material-icons bg-blue-50 text-blue-600 dark:bg-blue-500/10">inventory_2</span>
        <div><p>Total Item</p><strong>{{ summary.total }}</strong></div>
      </article>
      <article class="summary-card">
        <span class="material-icons bg-amber-50 text-amber-600 dark:bg-amber-500/10">pending_actions</span>
        <div><p>Sedang Berjalan</p><strong>{{ summary.in_progress }}</strong></div>
      </article>
      <article class="summary-card">
        <span class="material-icons bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10">task_alt</span>
        <div><p>Selesai</p><strong>{{ summary.completed }}</strong></div>
      </article>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
      <p class="mb-3 text-xs font-bold uppercase tracking-wider text-slate-500">Jumlah per tahap</p>
      <div class="flex flex-wrap gap-2">
        <span v-for="stage in summary.stages" :key="stage.status_key" class="rounded-xl bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
          {{ stage.label }} <b class="ml-1 text-slate-900 dark:text-white">{{ stage.count }}</b>
        </span>
      </div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
      <div class="grid gap-3 border-b border-slate-200 p-4 md:grid-cols-[1fr_180px_200px_auto] dark:border-slate-700">
        <label class="relative">
          <span class="material-icons pointer-events-none absolute left-3 top-2.5 text-lg text-slate-400">search</span>
          <input v-model="search" type="search" placeholder="Cari nomor, judul, requester, atau PIC..." class="w-full rounded-xl border border-slate-300 bg-white py-2.5 pl-10 pr-3 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white" @keyup.enter="applyFilters" />
        </label>
        <FancySelect v-model="type" :options="typeOptions" accent="blue" aria-label="Filter tipe item" />
        <FancySelect v-model="status" :options="statusOptions" accent="blue" aria-label="Filter tahap item" />
        <button type="button" class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-2.5 text-sm font-semibold text-blue-700 hover:bg-blue-100 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-300" @click="applyFilters">Terapkan</button>
      </div>

      <div v-if="loading" class="flex min-h-64 items-center justify-center">
        <span class="h-9 w-9 animate-spin rounded-full border-4 border-blue-100 border-t-blue-600"></span>
      </div>
      <div v-else-if="!items.data.length" class="flex min-h-72 flex-col items-center justify-center p-8 text-center">
        <span class="material-icons mb-3 text-6xl text-slate-300">inbox</span>
        <h2 class="text-lg font-semibold text-slate-700 dark:text-slate-200">Tidak ada item workflow</h2>
        <p class="mt-1 max-w-md text-sm text-slate-500">Belum ada Ticket atau Task yang sesuai dengan pencarian, filter, dan akses akun Anda.</p>
      </div>
      <div v-else class="overflow-x-auto">
        <table class="min-w-[1080px] divide-y divide-slate-200 dark:divide-slate-700">
          <thead class="bg-slate-50 dark:bg-slate-800/70">
            <tr class="text-left text-xs font-bold uppercase tracking-wider text-slate-500">
              <th class="px-5 py-3">Nomor / Judul</th>
              <th class="px-5 py-3">Tipe</th>
              <th class="px-5 py-3">Requester</th>
              <th class="px-5 py-3">PIC / Assignee</th>
              <th class="px-5 py-3">Status / Tahap</th>
              <th class="px-5 py-3">Diperbarui</th>
              <th class="px-5 py-3 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
            <tr v-for="item in items.data" :key="item.id" class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50">
              <td class="px-5 py-4">
                <Link :href="item.detail_url" class="font-mono text-xs font-bold text-blue-600 hover:text-blue-700">{{ item.number }}</Link>
                <p class="mt-1 max-w-sm truncate text-sm font-semibold text-slate-900 dark:text-white">{{ item.title }}</p>
              </td>
              <td class="px-5 py-4"><span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold capitalize text-blue-700 dark:bg-blue-500/10 dark:text-blue-300">{{ item.type }}</span></td>
              <td class="px-5 py-4 text-sm text-slate-600 dark:text-slate-300">{{ item.requester }}</td>
              <td class="px-5 py-4 text-sm text-slate-600 dark:text-slate-300">{{ item.pic }}</td>
              <td class="px-5 py-4">
                <span :class="statusClass(item.status)" class="rounded-full px-2.5 py-1 text-xs font-semibold">{{ item.status_label }}</span>
                <p class="mt-1 text-xs text-slate-500">{{ item.stage_name }}</p>
              </td>
              <td class="px-5 py-4 text-sm text-slate-500">{{ formatDate(item.updated_at) }}</td>
              <td class="px-5 py-4 text-right"><Link :href="item.detail_url" class="action" title="Lihat detail"><span class="material-icons">visibility</span></Link></td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="items.links?.length > 3" class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 px-5 py-4 dark:border-slate-700">
        <p class="text-xs text-slate-500">Menampilkan {{ items.from }}–{{ items.to }} dari {{ items.total }}</p>
        <nav class="flex gap-1">
          <Link v-for="link in items.links" :key="link.label" :href="link.url || '#'" v-html="link.label" class="rounded-lg border px-3 py-1.5 text-xs" :class="link.active ? 'border-blue-600 bg-blue-600 text-white' : 'border-slate-200 text-slate-600 dark:border-slate-700 dark:text-slate-300'" />
        </nav>
      </div>
    </section>
  </div>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import FancySelect from '../../Components/FancySelect.vue'
import AppLayout from '../../Layouts/AppLayout.vue'
import resolveRoute from '../../utils/resolveRoute'

defineOptions({ layout: AppLayout })
const props = defineProps({
  items: { type: Object, required: true },
  summary: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
  can: { type: Object, default: () => ({}) },
})
const typeOptions = [{ value: '', label: 'Semua tipe' }, { value: 'ticket', label: 'Ticket' }, { value: 'task', label: 'Task' }]
const statusOptions = [
  { value: '', label: 'Semua tahap' },
  { value: 'new', label: 'Baru' },
  { value: 'in_progress', label: 'Dalam Proses' },
  { value: 'confirmation', label: 'Konfirmasi' },
  { value: 'revision', label: 'Revisi' },
  { value: 'done', label: 'Selesai' },
  { value: 'on_hold', label: 'Ditunda' },
  { value: 'cancelled', label: 'Dibatalkan' },
]
const search = ref(props.filters.search || '')
const type = ref(props.filters.type || '')
const status = ref(props.filters.status || '')
const loading = ref(false)
const applyFilters = () => {
  loading.value = true
  router.get(resolveRoute('workflows.index'), {
    search: search.value || undefined,
    type: type.value || undefined,
    status: status.value || undefined,
  }, { preserveState: true, replace: true, onFinish: () => { loading.value = false } })
}
const statusClass = value => value === 'done'
  ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300'
  : value === 'cancelled'
    ? 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300'
    : 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300'
const formatDate = value => value ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value)) : '—'
</script>

<style scoped>
.summary-card { display: flex; align-items: center; gap: 1rem; border: 1px solid #e2e8f0; border-radius: 1rem; background: white; padding: 1rem; box-shadow: 0 1px 2px rgb(15 23 42 / .04); }
.summary-card > span { display: inline-flex; height: 2.75rem; width: 2.75rem; align-items: center; justify-content: center; border-radius: .8rem; }
.summary-card p { font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #64748b; }
.summary-card strong { font-size: 1.5rem; color: #0f172a; }
.action { display: inline-flex; height: 2.25rem; width: 2.25rem; align-items: center; justify-content: center; border-radius: .65rem; color: #64748b; transition: .2s; }
.action:hover { background: #eff6ff; color: #2563eb; }
.action .material-icons { font-size: 1.15rem; }
.dark .summary-card { border-color: #334155; background: #0f172a; }
.dark .summary-card strong { color: white; }
</style>
