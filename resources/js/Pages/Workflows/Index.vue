<template>
  <div class="mx-auto max-w-7xl space-y-6">
    <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <p class="text-xs font-bold uppercase tracking-[0.2em] text-blue-600">Automation</p>
        <h1 class="mt-1 text-3xl font-bold text-slate-900 dark:text-white">Workflow Management</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-300">Kelola alur kerja Ticket dan Task secara terstruktur.</p>
      </div>
      <Link v-if="can.create" :href="resolveRoute('workflows.create')" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
        <span class="material-icons text-lg">add</span> Buat Workflow
      </Link>
    </header>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
      <div class="grid gap-3 border-b border-slate-200 p-4 md:grid-cols-[1fr_180px_180px_auto] dark:border-slate-700">
        <label class="relative">
          <span class="material-icons pointer-events-none absolute left-3 top-2.5 text-lg text-slate-400">search</span>
          <input v-model="search" type="search" placeholder="Cari nama atau kode workflow..." class="w-full rounded-xl border border-slate-300 bg-white py-2.5 pl-10 pr-3 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white" @keyup.enter="applyFilters" />
        </label>
        <FancySelect v-model="type" :options="typeOptions" accent="blue" aria-label="Filter tipe workflow" />
        <FancySelect v-model="status" :options="statusOptions" accent="blue" aria-label="Filter status workflow" />
        <button type="button" class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-2.5 text-sm font-semibold text-blue-700 hover:bg-blue-100 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-300" @click="applyFilters">Terapkan</button>
      </div>

      <div v-if="loading" class="flex min-h-64 items-center justify-center">
        <span class="h-9 w-9 animate-spin rounded-full border-4 border-blue-100 border-t-blue-600"></span>
      </div>
      <div v-else-if="!workflows.data.length" class="flex min-h-72 flex-col items-center justify-center p-8 text-center">
        <span class="material-icons mb-3 text-6xl text-slate-300">account_tree</span>
        <h2 class="text-lg font-semibold text-slate-700 dark:text-slate-200">Belum ada workflow</h2>
        <p class="mt-1 max-w-md text-sm text-slate-500">Buat workflow pertama untuk mendefinisikan alur Ticket atau Task.</p>
      </div>
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
          <thead class="bg-slate-50 dark:bg-slate-800/70"><tr class="text-left text-xs font-bold uppercase tracking-wider text-slate-500">
            <th class="px-5 py-3">Workflow</th><th class="px-5 py-3">Tipe</th><th class="px-5 py-3">Tahapan</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Diperbarui</th><th class="px-5 py-3 text-right">Aksi</th>
          </tr></thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
            <tr v-for="item in workflows.data" :key="item.uuid" class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50">
              <td class="px-5 py-4"><Link :href="resolveRoute('workflows.show', { workflow: item.uuid })" class="font-semibold text-slate-900 hover:text-blue-600 dark:text-white">{{ item.name }}</Link><p class="mt-0.5 font-mono text-xs text-slate-400">{{ item.code }}</p></td>
              <td class="px-5 py-4"><span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold capitalize text-blue-700 dark:bg-blue-500/10 dark:text-blue-300">{{ item.entity_type }}</span></td>
              <td class="px-5 py-4 text-sm text-slate-600 dark:text-slate-300">{{ item.stages_count }} tahap</td>
              <td class="px-5 py-4"><span :class="item.is_active ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300'" class="rounded-full px-2.5 py-1 text-xs font-semibold">{{ item.is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
              <td class="px-5 py-4 text-sm text-slate-500">{{ formatDate(item.updated_at) }}</td>
              <td class="px-5 py-4"><div class="flex justify-end gap-1">
                <Link :href="resolveRoute('workflows.show', { workflow: item.uuid })" class="action" title="Detail"><span class="material-icons">visibility</span></Link>
                <Link v-if="can.update" :href="resolveRoute('workflows.edit', { workflow: item.uuid })" class="action" title="Edit"><span class="material-icons">edit</span></Link>
                <button v-if="can.toggle" class="action" :title="item.is_active ? 'Nonaktifkan' : 'Aktifkan'" @click="confirmToggle(item)"><span class="material-icons">{{ item.is_active ? 'toggle_on' : 'toggle_off' }}</span></button>
                <button v-if="can.delete" class="action text-rose-600" title="Hapus" @click="confirmDelete(item)"><span class="material-icons">delete</span></button>
              </div></td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="workflows.links?.length > 3" class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 px-5 py-4 dark:border-slate-700">
        <p class="text-xs text-slate-500">Menampilkan {{ workflows.from }}–{{ workflows.to }} dari {{ workflows.total }}</p>
        <nav class="flex gap-1"><Link v-for="link in workflows.links" :key="link.label" :href="link.url || '#'" v-html="link.label" class="rounded-lg border px-3 py-1.5 text-xs" :class="link.active ? 'border-blue-600 bg-blue-600 text-white' : 'border-slate-200 text-slate-600 dark:border-slate-700 dark:text-slate-300'" /></nav>
      </div>
    </section>

    <div v-if="dialog.open" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4" @click.self="closeDialog">
      <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl dark:bg-slate-900">
        <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ dialog.title }}</h3><p class="mt-2 text-sm text-slate-500">{{ dialog.message }}</p>
        <div class="mt-6 flex justify-end gap-2"><button class="rounded-xl border px-4 py-2 text-sm" @click="closeDialog">Batal</button><button class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white" @click="runDialog">Konfirmasi</button></div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import FancySelect from '../../Components/FancySelect.vue'
import AppLayout from '../../Layouts/AppLayout.vue'
import resolveRoute from '../../utils/resolveRoute'

defineOptions({ layout: AppLayout })
const props = defineProps({ workflows: { type: Object, required: true }, filters: { type: Object, default: () => ({}) }, can: { type: Object, default: () => ({}) } })
const typeOptions = [{ value: '', label: 'Semua tipe' }, { value: 'ticket', label: 'Ticket' }, { value: 'task', label: 'Task' }]
const statusOptions = [{ value: '', label: 'Semua status' }, { value: 'active', label: 'Aktif' }, { value: 'inactive', label: 'Nonaktif' }]
const search = ref(props.filters.search || ''), type = ref(props.filters.type || ''), status = ref(props.filters.status || ''), loading = ref(false)
const dialog = ref({ open: false, title: '', message: '', action: null })
const applyFilters = () => { loading.value = true; router.get(resolveRoute('workflows.index'), { search: search.value || undefined, type: type.value || undefined, status: status.value || undefined }, { preserveState: true, replace: true, onFinish: () => { loading.value = false } }) }
const openDialog = (title, message, action) => { dialog.value = { open: true, title, message, action } }
const closeDialog = () => { dialog.value = { open: false, title: '', message: '', action: null } }
const runDialog = () => { const action = dialog.value.action; closeDialog(); if (action) action() }
const confirmToggle = item => openDialog(item.is_active ? 'Nonaktifkan workflow?' : 'Aktifkan workflow?', item.name, () => router.patch(resolveRoute('workflows.toggle', { workflow: item.uuid }), {}, { preserveScroll: true }))
const confirmDelete = item => openDialog('Hapus workflow?', `${item.name} akan dihapus permanen.`, () => router.delete(resolveRoute('workflows.destroy', { workflow: item.uuid }), { preserveScroll: true }))
const formatDate = value => value ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium' }).format(new Date(value)) : '—'
</script>

<style scoped>
.action { display: inline-flex; height: 2.25rem; width: 2.25rem; align-items: center; justify-content: center; border-radius: .65rem; color: #64748b; transition: .2s; }
.action:hover { background: #eff6ff; color: #2563eb; }
.action .material-icons { font-size: 1.15rem; }
</style>
