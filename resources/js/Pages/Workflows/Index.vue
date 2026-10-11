<template>
  <div class="mx-auto max-w-7xl space-y-6">
    <Head :title="$t(&quot;Alur & Riwayat&quot;)" />
    <Transition name="fade"><div v-if="notice" :class="notice.type === 'error' ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'" class="fixed right-5 top-20 z-[70] max-w-sm rounded-xl border px-4 py-3 text-sm font-semibold shadow-lg">{{ notice.message }}</div></Transition>
    <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div><p class="text-xs font-bold uppercase tracking-[0.2em] text-cyan-600">{{ $t("Pemantauan proses") }}</p><h1 class="mt-1 text-3xl font-bold text-slate-900 dark:text-white">{{ $t("Alur & Riwayat") }}</h1><p class="mt-1 text-sm text-slate-500 dark:text-slate-300">{{ $t("Lihat tahap aktif, PIC, dan riwayat perpindahan status setiap ticket atau task.") }}</p></div>
      <Link v-if="can.create" :href="resolveRoute('workflows.create')" class="primary"><span class="material-icons text-lg">add</span> {{ $t("Buat definisi alur") }}</Link>
    </header>

    <aside class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-cyan-100 bg-cyan-50 p-4 text-sm text-cyan-950 dark:border-cyan-900 dark:bg-cyan-950 dark:text-cyan-100">
      <p><strong>{{ $t("Gunakan menu ini untuk menelusuri proses.") }}</strong><br />{{ $t("Untuk memilih pekerjaan dan mengejar deadline harian, buka Pekerjaan Saya.") }}</p>
      <Link :href="resolveRoute('work.index')" class="inline-flex items-center gap-1 rounded-lg px-3 py-2 font-semibold underline underline-offset-4 focus-visible:outline focus-visible:outline-2">{{ $t("Ke Pekerjaan Saya") }} <span aria-hidden="true">→</span></Link>
    </aside>
    <section class="grid gap-4 sm:grid-cols-3">
      <article v-for="metric in metrics" :key="metric.label" class="summary-card"><span class="material-icons border" :class="workflowStatusClass(metric.status, 'iconTone')">{{ metric.icon }}</span><div><p>{{ metric.label }}</p><strong>{{ metric.value }}</strong></div></article>
    </section>
    <section class="card p-4"><p class="mb-3 text-xs font-bold uppercase tracking-wider text-slate-500">{{ $t("Sebaran tahap proses") }}</p><div class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-7"><button v-for="stage in summary.stages" :key="stage.status_key" type="button" :class="[workflowStatusClass(stage.status_key), status === stage.status_key ? 'ring-2 ring-slate-400/60 ring-offset-2 dark:ring-offset-slate-900' : 'hover:-translate-y-px hover:shadow-sm']" class="flex min-h-11 w-full items-center justify-between gap-2 rounded-xl border px-3 py-2 text-xs font-semibold transition" @click="filterStage(stage.status_key)"><span class="flex min-w-0 items-center gap-1.5"><span class="material-icons text-base">{{ workflowStatusStyle(stage.status_key).icon }}</span><span class="truncate">{{ stage.label }}</span></span><b :class="workflowStatusClass(stage.status_key, 'counter')" class="flex h-6 min-w-6 shrink-0 items-center justify-center rounded-full px-1.5 text-[11px]">{{ stage.count }}</b></button></div></section>

    <section class="card !p-0">
      <div class="px-5 pt-5"><h2 class="font-semibold text-slate-900 dark:text-white">{{ $t("Telusuri alur pekerjaan") }}</h2><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $t("Buka alur untuk melihat catatan perpindahan tahap dan tindakan yang tersedia sesuai izin Anda.") }}</p></div>
      <div class="grid gap-3 border-b border-slate-200 p-4 md:grid-cols-[1fr_170px_190px_auto] dark:border-slate-700">
        <label class="relative"><span class="material-icons pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-lg text-slate-400">search</span><input v-model="search" :aria-label="$t(&quot;Cari alur berdasarkan nomor, judul, requester, atau PIC&quot;)" type="search" :placeholder="$t(&quot;Cari nomor, judul, requester, atau PIC...&quot;)" class="input !pl-12" @keyup.enter="applyFilters" /></label>
        <WorkflowSelect v-model="type" :options="typeOptions" accent="blue" :aria-label="$t(&quot;Filter tipe item&quot;)" />
        <WorkflowSelect v-model="status" :options="statusOptions" accent="blue" :aria-label="$t(&quot;Filter tahap item&quot;)" />
        <button type="button" class="secondary" @click="applyFilters">{{ $t("Terapkan") }}</button>
      </div>
      <div v-if="loading" class="flex min-h-64 items-center justify-center"><span class="spinner"></span></div>
      <div v-else-if="!items.data.length" class="flex min-h-72 flex-col items-center justify-center p-8 text-center"><span class="material-icons mb-3 text-6xl text-slate-300">account_tree</span><h2 class="text-lg font-semibold text-slate-700 dark:text-slate-200">{{ $t("Tidak ada item workflow") }}</h2><p class="mt-1 max-w-md text-sm text-slate-500">{{ $t("Tidak ada Ticket atau Task yang sesuai dengan filter dan akses akun Anda.") }}</p></div>
      <div v-else class="divide-y divide-slate-200 dark:divide-slate-700">
        <article v-for="item in items.data" :key="item.id" class="space-y-4 p-5 sm:p-6">
          <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
            <div class="min-w-0">
              <p class="font-mono text-xs font-semibold text-cyan-700 dark:text-cyan-300">{{ item.number }} · {{ item.type }}</p>
              <h3 class="mt-1 break-words text-lg font-semibold text-slate-900 dark:text-white">{{ item.title }}</h3>
              <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ item.workflow_name }}<span v-if="!item.workflow_active" class="ml-2 font-semibold text-amber-700 dark:text-amber-300">{{ $t("Definisi nonaktif") }}</span></p>
            </div>
            <div class="flex shrink-0 flex-wrap items-center gap-2">
              <button v-if="item.can_update_status" type="button" class="primary" @click="statusItem = item"><span class="material-icons text-lg" aria-hidden="true">published_with_changes</span>{{ $t('Update Status') }}</button>
              <Link :href="item.detail_url" :aria-label="$t(&quot;Lihat alur dan riwayat: &quot;) + item.title" class="secondary"><span class="material-icons text-lg" aria-hidden="true">route</span>{{ $t("Lihat alur & riwayat") }}</Link>
              <Dropdown v-if="item.can_view_workflow || item.can_update_workflow || item.can_toggle_workflow || item.can_delete_workflow" width-class="w-60" teleport>
                <template #trigger><span class="material-icons text-xl" aria-hidden="true">more_horiz</span><span class="sr-only">{{ $t("Pengaturan definisi") }} {{ item.workflow_name }}</span></template>
                <div class="py-1 text-left">
                  <p class="px-3 py-2 text-xs font-semibold text-slate-500">{{ $t("Definisi alur yang digunakan") }}</p>
                  <Link v-if="item.can_view_workflow" :href="item.workflow_url" class="menu"><span class="material-icons" aria-hidden="true">account_tree</span>{{ $t("Lihat definisi alur") }}</Link>
                  <Link v-if="item.can_update_workflow" :href="item.workflow_edit_url" class="menu"><span class="material-icons" aria-hidden="true">schema</span>{{ $t("Edit aturan & tahap") }}</Link>
                  <button v-if="item.can_toggle_workflow" type="button" class="menu w-full" @click="confirmToggle(item)"><span class="material-icons" aria-hidden="true">power_settings_new</span>{{ item.workflow_active ? $t("Nonaktifkan definisi") : $t("Aktifkan definisi") }}</button>
                  <button v-if="item.can_delete_workflow" type="button" class="menu danger w-full" @click="confirmDelete(item)"><span class="material-icons" aria-hidden="true">delete</span>{{ $t("Hapus definisi") }}</button>
                </div>
              </Dropdown>
            </div>
          </div>
          <dl class="grid gap-3 rounded-xl bg-slate-50 p-4 text-sm dark:bg-slate-800/60 sm:grid-cols-2 lg:grid-cols-4">
            <div><dt class="text-xs text-slate-500 dark:text-slate-400">{{ $t("Tahap saat ini") }}</dt><dd class="mt-1 font-semibold text-slate-900 dark:text-white">{{ item.stage_name }}</dd></div>
            <div><dt class="text-xs text-slate-500 dark:text-slate-400">{{ $t("PIC pekerjaan") }}</dt><dd class="mt-1 break-words text-slate-700 dark:text-slate-200">{{ item.pic }}</dd></div>
            <div><dt class="text-xs text-slate-500 dark:text-slate-400">Requester</dt><dd class="mt-1 break-words text-slate-700 dark:text-slate-200">{{ item.requester }}</dd></div>
            <div><dt class="text-xs text-slate-500 dark:text-slate-400">{{ $t("Masuk tahap ini") }}</dt><dd class="mt-1 text-slate-700 dark:text-slate-200">{{ formatDate(item.stage_started_at) }}</dd></div>
          </dl>
          <div>
            <p class="mb-2 text-xs font-medium text-slate-500 dark:text-slate-400">{{ $t("Tahap dalam definisi alur · tahap saat ini ditandai") }}</p>
            <ol :aria-label="$t(&quot;Tahap proses &quot;) + item.number" class="flex flex-wrap gap-2">
              <li v-for="stage in item.process_stages" :key="stage.id" :aria-current="stage.is_current ? 'step' : undefined" class="inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-xs" :class="stage.is_current ? 'border-cyan-500 bg-cyan-50 font-bold text-cyan-900 dark:bg-cyan-950 dark:text-cyan-100' : 'border-slate-200 text-slate-500 dark:border-slate-700 dark:text-slate-400'">
                <span v-if="stage.is_current" class="material-icons text-sm" aria-hidden="true">radio_button_checked</span>{{ stage.name }}<span v-if="stage.is_current" class="rounded bg-cyan-700 px-1.5 py-0.5 text-[10px] text-white">{{ $t("Saat ini") }}</span>
              </li>
            </ol>
          </div>
        </article>
      </div>
      <div v-if="items.links?.length > 3" class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 px-5 py-4 dark:border-slate-700"><p class="text-xs text-slate-500">{{ $t("Menampilkan") }} {{ items.from }}–{{ items.to }} {{ $t("dari") }} {{ items.total }}</p><nav class="flex gap-1"><template v-for="link in items.links" :key="link.label"><Link v-if="link.url" :href="link.url" preserve-scroll v-html="link.label" class="pager" :class="link.active ? 'border-cyan-600 bg-cyan-600 text-white' : 'border-slate-200 text-slate-600 dark:border-slate-700 dark:text-slate-300'" /><span v-else v-html="link.label" class="pager cursor-not-allowed border-slate-100 text-slate-300"></span></template></nav></div>
    </section>

    <WorkflowStatusDialog v-if="statusItem" :item="statusItem" @close="statusItem = null" @saved="statusSaved" />
    <Teleport to="body">
    <div v-if="confirmation" class="modal-backdrop" @click.self="confirmation = null"><div class="modal"><div class="flex h-12 w-12 items-center justify-center rounded-xl bg-rose-50 text-rose-600"><span class="material-icons">warning</span></div><h3 class="mt-4 text-xl font-bold dark:text-white">{{ confirmation.title }}</h3><p class="mt-2 text-sm text-slate-500">{{ confirmation.message }}</p><div class="modal-actions"><button type="button" class="secondary" @click="confirmation = null">{{ $t("Batal") }}</button><button type="button" :disabled="processing" class="rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white" @click="runConfirmation">{{ processing ? $t("Memproses...") : $t("Ya, lanjutkan") }}</button></div></div></div>
    </Teleport>
  </div>
</template>

<script setup>
import { translateUi as uiText, uiLocale } from '../../i18n'

import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import WorkflowStatusDialog from '../../Components/WorkflowStatusDialog.vue'
import Dropdown from '../../Components/Dropdown.vue'
import WorkflowSelect from '../../Components/WorkflowSelect.vue'
import AppLayout from '../../Layouts/AppLayout.vue'
import resolveRoute from '../../utils/resolveRoute'
import { workflowStatusClass, workflowStatusStyle } from '../../utils/workflowStatus'
defineOptions({ layout: AppLayout })
const props = defineProps({ items: { type: Object, required: true }, summary: { type: Object, required: true }, filters: { type: Object, default: () => ({}) }, can: { type: Object, default: () => ({}) } })
const statusItem = ref(null)
const statusSaved = message => { statusItem.value = null; notice.value = { type: 'success', message }; router.reload({ only: ['items', 'summary'], preserveScroll: true }) }
const page = usePage(); const notice = ref(null); const loading = ref(false); const processing = ref(false); const confirmation = ref(null)
const modalOpen = computed(() => Boolean(confirmation.value))
watch(modalOpen, value => { if (typeof document === 'undefined') return; document.documentElement.classList.toggle('workflow-modal-open', value); document.body.classList.toggle('workflow-modal-open', value) }, { immediate: true })
onBeforeUnmount(() => { if (typeof document === 'undefined') return; document.documentElement.classList.remove('workflow-modal-open'); document.body.classList.remove('workflow-modal-open') })
const metrics = computed(() => [{ label: uiText("Proses terpantau"), value: props.summary.total, icon: 'inventory_2', status: 'new' }, { label: uiText("Proses berjalan"), value: props.summary.in_progress, icon: 'pending_actions', status: 'in_progress' }, { label: uiText("Proses selesai"), value: props.summary.completed, icon: 'task_alt', status: 'done' }])
const typeOptions = [{ value: '', label: uiText("Semua tipe") }, { value: 'ticket', label: 'Ticket' }, { value: 'task', label: 'Task' }]
const statusOptions = [{ value: '', label: uiText("Semua tahap") }, ...['new','in_progress','confirmation','revision','done','on_hold','cancelled'].map(value => ({ value, label: value.replaceAll('_',' ').replace(/\b\w/g, c => c.toUpperCase()) }))]
const search = ref(props.filters.search || ''); const type = ref(props.filters.type || ''); const status = ref(props.filters.status || '')
watch(() => [page.props.flash?.success, page.props.flash?.error], ([success, error]) => { const message = error || success; if (!message) return; notice.value = { type: error ? 'error' : 'success', message }; setTimeout(() => { notice.value = null }, 3500) }, { immediate: true })
const applyFilters = () => { loading.value = true; router.get(resolveRoute('workflows.index'), { search: search.value || undefined, type: type.value || undefined, status: status.value || undefined }, { preserveState: true, replace: true, onFinish: () => { loading.value = false } }) }
watch(() => props.filters, value => { search.value = value.search || ''; type.value = value.type || ''; status.value = value.status || '' })
const filterStage = value => { status.value = status.value === value ? '' : value; applyFilters() }
const confirmToggle = item => { confirmation.value = { title: item.workflow_active ? uiText("Nonaktifkan workflow?") : uiText("Aktifkan workflow?"), message: (uiText("Perubahan berlaku pada definisi ") + (item.workflow_name) + uiText(" tanpa menghapus Ticket atau Task terkait.")), method: 'patch', url: item.workflow_toggle_url } }
const confirmDelete = item => { confirmation.value = { title: uiText("Hapus workflow?"), message: uiText("Workflow hanya dapat dihapus jika belum pernah digunakan. Data Ticket atau Task tidak akan dihapus."), method: 'delete', url: item.workflow_delete_url } }
const runConfirmation = () => { processing.value = true; const options = { preserveScroll: true, onSuccess: () => { confirmation.value = null }, onFinish: () => { processing.value = false } }; confirmation.value.method === 'delete' ? router.delete(confirmation.value.url, options) : router.patch(confirmation.value.url, {}, options) }
const formatDate = (value, time = true) => value ? new Intl.DateTimeFormat(uiLocale.value, time ? { dateStyle: 'medium', timeStyle: 'short' } : { dateStyle: 'medium' }).format(new Date(value)) : '—'
</script>

<style scoped>
.card{border:1px solid #e2e8f0;border-radius:1rem;background:white;box-shadow:0 1px 2px rgb(15 23 42/.04)}.dark .card{border-color:#334155;background:#0f172a}.summary-card{display:flex;align-items:center;gap:1rem;border:1px solid #e2e8f0;border-radius:1rem;background:white;padding:1rem}.dark .summary-card{border-color:#334155;background:#0f172a}.summary-card>span{display:flex;height:2.75rem;width:2.75rem;align-items:center;justify-content:center;border-radius:.8rem}.summary-card p{font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#64748b}.summary-card strong{font-size:1.5rem;color:#0f172a}.dark .summary-card strong{color:white}.primary,.secondary{display:inline-flex;align-items:center;justify-content:center;gap:.4rem;border-radius:.75rem;padding:.65rem 1rem;font-size:.875rem;font-weight:650}.primary{background:#0891b2;color:white}.secondary{border:1px solid #cbd5e1;color:#475569}.input{width:100%;border:1px solid #cbd5e1;border-radius:.75rem;background:transparent;padding:.65rem .8rem;font-size:.875rem}.dark .input{border-color:#475569;color:#e2e8f0}.menu{display:flex;align-items:center;gap:.65rem;padding:.6rem .85rem;color:#475569}.menu:hover{background:#ecfeff;color:#0e7490}.menu .material-icons{font-size:1.1rem}.menu.danger{color:#e11d48}.pager{border-width:1px;border-radius:.5rem;padding:.35rem .7rem;font-size:.75rem}.spinner{height:2.25rem;width:2.25rem;animation:spin 1s linear infinite;border-radius:999px;border:4px solid #cffafe;border-top-color:#0891b2}.modal-backdrop{position:fixed;inset:0;z-index:2147483000;display:flex;align-items:center;justify-content:center;background:rgb(15 23 42/.55);padding:1rem}.modal{width:100%;max-width:30rem;border-radius:1rem;background:white;padding:1.5rem;box-shadow:0 24px 60px rgb(15 23 42/.3)}.dark .modal{background:#0f172a}.modal-head{display:flex;align-items:flex-start;justify-content:space-between}.modal-head h3{font-size:1.25rem;font-weight:700}.eyebrow{font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.12em;color:#0891b2}.icon-btn{font-size:1.6rem;color:#94a3b8}.field{display:flex;flex-direction:column;gap:.4rem;font-size:.8rem;font-weight:650;color:#475569}.modal-actions{margin-top:1.5rem;display:flex;justify-content:flex-end;gap:.75rem}@keyframes spin{to{transform:rotate(360deg)}}.fade-enter-active,.fade-leave-active{transition:opacity .2s}.fade-enter-from,.fade-leave-to{opacity:0}
:global(html.workflow-modal-open),:global(body.workflow-modal-open){overflow:hidden!important;overscroll-behavior:none}</style>

