<template>
  <div class="mx-auto max-w-7xl space-y-6">
    <Transition name="fade"><div v-if="notice" :class="notice.type === 'error' ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'" class="fixed right-5 top-20 z-[70] max-w-sm rounded-xl border px-4 py-3 text-sm font-semibold shadow-lg">{{ notice.message }}</div></Transition>
    <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div><p class="text-xs font-bold uppercase tracking-[0.2em] text-cyan-600">Tickora Automation</p><h1 class="mt-1 text-3xl font-bold text-slate-900 dark:text-white">Workflow Command Center</h1><p class="mt-1 text-sm text-slate-500 dark:text-slate-300">Pantau dan tindak lanjuti alur Ticket serta Task sesuai akses Anda.</p></div>
      <Link v-if="can.create" :href="resolveRoute('workflows.create')" class="primary"><span class="material-icons text-lg">add</span> Buat Workflow</Link>
    </header>

    <section class="grid gap-4 sm:grid-cols-3">
      <article v-for="metric in metrics" :key="metric.label" class="summary-card"><span class="material-icons" :class="metric.color">{{ metric.icon }}</span><div><p>{{ metric.label }}</p><strong>{{ metric.value }}</strong></div></article>
    </section>
    <section class="card p-4"><p class="mb-3 text-xs font-bold uppercase tracking-wider text-slate-500">Distribusi tahap</p><div class="flex flex-wrap gap-2"><button v-for="stage in summary.stages" :key="stage.status_key" type="button" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-600 hover:border-cyan-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300" @click="filterStage(stage.status_key)">{{ stage.label }} <b class="ml-1 text-slate-900 dark:text-white">{{ stage.count }}</b></button></div></section>

    <section class="card !p-0">
      <div class="grid gap-3 border-b border-slate-200 p-4 md:grid-cols-[1fr_170px_190px_auto] dark:border-slate-700">
        <label class="relative"><span class="material-icons pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-lg text-slate-400">search</span><input v-model="search" type="search" placeholder="Cari nomor, judul, requester, atau PIC..." class="input pl-12" @keyup.enter="applyFilters" /></label>
        <WorkflowSelect v-model="type" :options="typeOptions" accent="blue" aria-label="Filter tipe item" />
        <WorkflowSelect v-model="status" :options="statusOptions" accent="blue" aria-label="Filter tahap item" />
        <button type="button" class="secondary" @click="applyFilters">Terapkan</button>
      </div>
      <div v-if="loading" class="flex min-h-64 items-center justify-center"><span class="spinner"></span></div>
      <div v-else-if="!items.data.length" class="flex min-h-72 flex-col items-center justify-center p-8 text-center"><span class="material-icons mb-3 text-6xl text-slate-300">account_tree</span><h2 class="text-lg font-semibold text-slate-700 dark:text-slate-200">Tidak ada item workflow</h2><p class="mt-1 max-w-md text-sm text-slate-500">Tidak ada Ticket atau Task yang sesuai dengan filter dan akses akun Anda.</p></div>
      <div v-else class="overflow-x-auto">
        <table class="min-w-[1180px] w-full divide-y divide-slate-200 dark:divide-slate-700">
          <thead class="bg-slate-50 dark:bg-slate-800/70"><tr class="text-left text-xs font-bold uppercase tracking-wider text-slate-500"><th class="px-5 py-3">Nomor / Judul</th><th class="px-5 py-3">Workflow</th><th class="px-5 py-3">Requester</th><th class="px-5 py-3">PIC</th><th class="px-5 py-3">Status / Tahap</th><th class="px-5 py-3">Target</th><th class="px-5 py-3">Diperbarui</th><th class="px-5 py-3 text-right">Aksi</th></tr></thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800"><tr v-for="item in items.data" :key="item.id" class="hover:bg-cyan-50/40 dark:hover:bg-slate-800/50">
            <td class="px-5 py-4"><Link :href="item.detail_url" class="font-mono text-xs font-bold text-cyan-600">{{ item.number }}</Link><p class="mt-1 max-w-xs truncate text-sm font-semibold text-slate-900 dark:text-white">{{ item.title }}</p><span class="mt-1 inline-block text-[11px] uppercase text-slate-400">{{ item.type }}</span></td>
            <td class="px-5 py-4"><Link v-if="item.can_view_workflow" :href="item.workflow_url" class="text-sm font-semibold text-slate-700 hover:text-cyan-600 dark:text-slate-200">{{ item.workflow_name }}</Link><span v-else class="text-sm font-semibold text-slate-600 dark:text-slate-300">{{ item.workflow_name }}</span><p class="mt-1 text-xs text-slate-400">{{ item.workflow_active ? 'Aktif' : 'Nonaktif' }}</p></td>
            <td class="px-5 py-4 text-sm text-slate-600 dark:text-slate-300">{{ item.requester }}</td><td class="px-5 py-4 text-sm text-slate-600 dark:text-slate-300">{{ item.pic }}</td>
            <td class="px-5 py-4"><span :class="statusClass(item.status)" class="rounded-full px-2.5 py-1 text-xs font-semibold">{{ item.status_label }}</span><p class="mt-1 text-xs text-slate-500">{{ item.stage_name }}</p></td>
            <td class="px-5 py-4 text-sm text-slate-500">{{ formatDate(item.target_date, false) }}</td><td class="px-5 py-4 text-sm text-slate-500">{{ formatDate(item.updated_at) }}</td>
            <td class="px-5 py-4 text-right"><Dropdown width-class="w-56"><template #trigger><span class="material-icons text-xl">more_horiz</span></template><div class="py-1 text-left">
              <Link :href="item.detail_url" class="menu"><span class="material-icons">visibility</span> Lihat Detail</Link><Link v-if="item.can_view_workflow" :href="item.workflow_url" class="menu"><span class="material-icons">account_tree</span> Detail Workflow</Link>
              <Link v-if="item.can_edit" :href="item.edit_url" class="menu"><span class="material-icons">edit</span> Edit {{ item.type === 'ticket' ? 'Ticket' : 'Task' }}</Link>
              <button v-if="item.can_update_status" type="button" class="menu w-full" @click="openStatus(item)"><span class="material-icons">published_with_changes</span> Update Tahap / Status</button>
              <Link v-if="item.can_update_workflow" :href="item.workflow_edit_url" class="menu"><span class="material-icons">schema</span> Edit Struktur</Link>
              <button v-if="item.can_toggle_workflow" type="button" class="menu w-full" @click="confirmToggle(item)"><span class="material-icons">power_settings_new</span> {{ item.workflow_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
              <button v-if="item.can_delete_workflow" type="button" class="menu danger w-full" @click="confirmDelete(item)"><span class="material-icons">delete</span> Hapus Workflow</button>
            </div></Dropdown></td>
          </tr></tbody>
        </table>
      </div>
      <div v-if="items.links?.length > 3" class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 px-5 py-4 dark:border-slate-700"><p class="text-xs text-slate-500">Menampilkan {{ items.from }}–{{ items.to }} dari {{ items.total }}</p><nav class="flex gap-1"><template v-for="link in items.links" :key="link.label"><Link v-if="link.url" :href="link.url" preserve-scroll v-html="link.label" class="pager" :class="link.active ? 'border-cyan-600 bg-cyan-600 text-white' : 'border-slate-200 text-slate-600 dark:border-slate-700 dark:text-slate-300'" /><span v-else v-html="link.label" class="pager cursor-not-allowed border-slate-100 text-slate-300"></span></template></nav></div>
    </section>

    <Teleport to="body">
    <div v-if="statusItem" class="modal-backdrop" @click.self="statusItem = null"><form class="modal" @submit.prevent="submitStatus"><div class="modal-head"><div><p class="eyebrow">Runtime action</p><h3>Update Tahap / Status</h3></div><button type="button" class="icon-btn" @click="statusItem = null">×</button></div><p class="text-sm text-slate-500">{{ statusItem.number }} · {{ statusItem.title }}</p><label class="field mt-5">Status tujuan<WorkflowSelect v-model="statusForm.status" :options="item.status_options" placeholder="Pilih transisi yang tersedia" aria-label="Status tujuan" /><small v-if="statusForm.errors.status" class="text-rose-600">{{ statusForm.errors.status }}</small></label><div class="modal-actions"><button type="button" class="secondary" @click="statusItem = null">Batal</button><button :disabled="statusForm.processing" class="primary">{{ statusForm.processing ? 'Memproses...' : 'Simpan' }}</button></div></form></div>
    <div v-if="confirmation" class="modal-backdrop" @click.self="confirmation = null"><div class="modal"><div class="flex h-12 w-12 items-center justify-center rounded-xl bg-rose-50 text-rose-600"><span class="material-icons">warning</span></div><h3 class="mt-4 text-xl font-bold dark:text-white">{{ confirmation.title }}</h3><p class="mt-2 text-sm text-slate-500">{{ confirmation.message }}</p><div class="modal-actions"><button type="button" class="secondary" @click="confirmation = null">Batal</button><button type="button" :disabled="processing" class="rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white" @click="runConfirmation">{{ processing ? 'Memproses...' : 'Ya, lanjutkan' }}</button></div></div></div>
    </Teleport>
  </div>
</template>

<script setup>
import { Link, router, useForm, usePage } from '@inertiajs/vue3'
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import Dropdown from '../../Components/Dropdown.vue'
import WorkflowSelect from '../../Components/WorkflowSelect.vue'
import AppLayout from '../../Layouts/AppLayout.vue'
import resolveRoute from '../../utils/resolveRoute'
defineOptions({ layout: AppLayout })
const props = defineProps({ items: { type: Object, required: true }, summary: { type: Object, required: true }, filters: { type: Object, default: () => ({}) }, can: { type: Object, default: () => ({}) } })
const page = usePage(); const notice = ref(null); const loading = ref(false); const processing = ref(false); const statusItem = ref(null); const confirmation = ref(null)
const modalOpen = computed(() => Boolean(statusItem.value || confirmation.value))
watch(modalOpen, value => { if (typeof document === 'undefined') return; document.documentElement.classList.toggle('workflow-modal-open', value); document.body.classList.toggle('workflow-modal-open', value) }, { immediate: true })
onBeforeUnmount(() => { if (typeof document === 'undefined') return; document.documentElement.classList.remove('workflow-modal-open'); document.body.classList.remove('workflow-modal-open') })
const metrics = computed(() => [{ label: 'Total Item', value: props.summary.total, icon: 'inventory_2', color: 'bg-cyan-50 text-cyan-600' }, { label: 'Sedang Berjalan', value: props.summary.in_progress, icon: 'pending_actions', color: 'bg-amber-50 text-amber-600' }, { label: 'Selesai', value: props.summary.completed, icon: 'task_alt', color: 'bg-emerald-50 text-emerald-600' }])
const typeOptions = [{ value: '', label: 'Semua tipe' }, { value: 'ticket', label: 'Ticket' }, { value: 'task', label: 'Task' }]
const statusOptions = [{ value: '', label: 'Semua tahap' }, ...['new','in_progress','confirmation','revision','done','on_hold','cancelled'].map(value => ({ value, label: value.replaceAll('_',' ').replace(/\b\w/g, c => c.toUpperCase()) }))]
const search = ref(props.filters.search || ''); const type = ref(props.filters.type || ''); const status = ref(props.filters.status || '')
const statusForm = useForm({ status: '' })
watch(() => [page.props.flash?.success, page.props.flash?.error], ([success, error]) => { const message = error || success; if (!message) return; notice.value = { type: error ? 'error' : 'success', message }; setTimeout(() => { notice.value = null }, 3500) }, { immediate: true })
const applyFilters = () => { loading.value = true; router.get(resolveRoute('workflows.index'), { search: search.value || undefined, type: type.value || undefined, status: status.value || undefined }, { preserveState: true, replace: true, onFinish: () => { loading.value = false } }) }
const filterStage = value => { status.value = status.value === value ? '' : value; applyFilters() }
const openStatus = item => { statusItem.value = item; statusForm.reset(); statusForm.clearErrors() }
const submitStatus = () => statusForm.patch(statusItem.value.status_update_url, { preserveScroll: true, onSuccess: () => { statusItem.value = null }, onError: () => { notice.value = { type: 'error', message: 'Status gagal diperbarui. Periksa pilihan Anda.' } } })
const confirmToggle = item => { confirmation.value = { title: item.workflow_active ? 'Nonaktifkan workflow?' : 'Aktifkan workflow?', message: `Perubahan berlaku pada definisi ${item.workflow_name} tanpa menghapus Ticket atau Task terkait.`, method: 'patch', url: item.workflow_toggle_url } }
const confirmDelete = item => { confirmation.value = { title: 'Hapus workflow?', message: 'Workflow hanya dapat dihapus jika belum pernah digunakan. Data Ticket atau Task tidak akan dihapus.', method: 'delete', url: item.workflow_delete_url } }
const runConfirmation = () => { processing.value = true; const options = { preserveScroll: true, onSuccess: () => { confirmation.value = null }, onFinish: () => { processing.value = false } }; confirmation.value.method === 'delete' ? router.delete(confirmation.value.url, options) : router.patch(confirmation.value.url, {}, options) }
const statusClass = value => value === 'done' ? 'bg-emerald-50 text-emerald-700' : value === 'cancelled' ? 'bg-rose-50 text-rose-700' : value === 'on_hold' ? 'bg-pink-50 text-pink-700' : 'bg-cyan-50 text-cyan-700'
const formatDate = (value, time = true) => value ? new Intl.DateTimeFormat('id-ID', time ? { dateStyle: 'medium', timeStyle: 'short' } : { dateStyle: 'medium' }).format(new Date(value)) : '—'
</script>

<style scoped>
.card{border:1px solid #e2e8f0;border-radius:1rem;background:white;box-shadow:0 1px 2px rgb(15 23 42/.04)}.dark .card{border-color:#334155;background:#0f172a}.summary-card{display:flex;align-items:center;gap:1rem;border:1px solid #e2e8f0;border-radius:1rem;background:white;padding:1rem}.dark .summary-card{border-color:#334155;background:#0f172a}.summary-card>span{display:flex;height:2.75rem;width:2.75rem;align-items:center;justify-content:center;border-radius:.8rem}.summary-card p{font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#64748b}.summary-card strong{font-size:1.5rem;color:#0f172a}.dark .summary-card strong{color:white}.primary,.secondary{display:inline-flex;align-items:center;justify-content:center;gap:.4rem;border-radius:.75rem;padding:.65rem 1rem;font-size:.875rem;font-weight:650}.primary{background:#0891b2;color:white}.secondary{border:1px solid #cbd5e1;color:#475569}.input{width:100%;border:1px solid #cbd5e1;border-radius:.75rem;background:transparent;padding:.65rem .8rem;font-size:.875rem}.dark .input{border-color:#475569;color:#e2e8f0}.menu{display:flex;align-items:center;gap:.65rem;padding:.6rem .85rem;color:#475569}.menu:hover{background:#ecfeff;color:#0e7490}.menu .material-icons{font-size:1.1rem}.menu.danger{color:#e11d48}.pager{border-width:1px;border-radius:.5rem;padding:.35rem .7rem;font-size:.75rem}.spinner{height:2.25rem;width:2.25rem;animation:spin 1s linear infinite;border-radius:999px;border:4px solid #cffafe;border-top-color:#0891b2}.modal-backdrop{position:fixed;inset:0;z-index:2147483000;display:flex;align-items:center;justify-content:center;background:rgb(15 23 42/.55);padding:1rem}.modal{width:100%;max-width:30rem;border-radius:1rem;background:white;padding:1.5rem;box-shadow:0 24px 60px rgb(15 23 42/.3)}.dark .modal{background:#0f172a}.modal-head{display:flex;align-items:flex-start;justify-content:space-between}.modal-head h3{font-size:1.25rem;font-weight:700}.eyebrow{font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.12em;color:#0891b2}.icon-btn{font-size:1.6rem;color:#94a3b8}.field{display:flex;flex-direction:column;gap:.4rem;font-size:.8rem;font-weight:650;color:#475569}.modal-actions{margin-top:1.5rem;display:flex;justify-content:flex-end;gap:.75rem}@keyframes spin{to{transform:rotate(360deg)}}.fade-enter-active,.fade-leave-active{transition:opacity .2s}.fade-enter-from,.fade-leave-to{opacity:0}
:global(html.workflow-modal-open),:global(body.workflow-modal-open){overflow:hidden!important;overscroll-behavior:none}</style>

