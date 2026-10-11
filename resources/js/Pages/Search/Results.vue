<template>
  <div class="mx-auto max-w-7xl space-y-6">
    <Head :title="heading || (personal ? $t('Pekerjaan Saya') : $t('Pencarian'))" />
    <header class="flex flex-wrap items-center justify-between gap-3">
      <div><h1 class="text-2xl font-bold">{{ heading || (personal ? $t("Pekerjaan Saya") : $t("Pencarian")) }}</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ personal ? $t("Mulai pekerjaan harian di sini: pilih prioritas, buka pekerjaan, lalu tindak lanjuti.") : $t("Cari nomor, judul, atau deskripsi ticket, task, dan project.") }}</p></div>
      <Link :href="resolveRoute('tickets.create')" class="rounded-xl bg-blue-600 px-4 py-2 font-semibold text-white">{{ $t("Buat ticket") }}</Link>
    </header>
    <aside v-if="personal" class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-blue-100 bg-blue-50 p-4 text-sm text-blue-900 dark:border-blue-900 dark:bg-blue-950 dark:text-blue-100">
      <p><strong>{{ $t("Fokus menu ini: pekerjaan yang perlu Anda perhatikan.") }}</strong><br />{{ $t("Untuk melihat tahapan dan catatan perpindahan status, gunakan Alur & Riwayat.") }}</p>
      <Link v-if="canViewWorkflows" :href="resolveRoute('workflows.index')" class="inline-flex items-center gap-1 rounded-lg px-3 py-2 font-semibold underline underline-offset-4 focus-visible:outline focus-visible:outline-2">{{ $t("Lihat alur proses") }} <span aria-hidden="true">→</span></Link>
    </aside>
    <nav v-if="personal" class="flex flex-wrap gap-2" :aria-label="$t(&quot;Antrean pekerjaan&quot;)">
      <button v-for="(label, value) in views" :key="value" type="button" :aria-pressed="form.view === value" @click="form.view = value; apply()"
        class="rounded-full border px-4 py-2 text-sm" :class="form.view === value ? 'border-blue-600 bg-blue-600 text-white' : 'border-slate-300 dark:border-slate-600'">{{ label }}</button>
    </nav>
    <div v-if="personal" class="flex flex-wrap items-center justify-between gap-3">
      <div><h2 class="font-semibold">{{ views[form.view] || views.mine }}</h2><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ viewDescriptions[form.view] || viewDescriptions.mine }}</p></div>
      <button type="button" :aria-expanded="showFilters" aria-controls="work-filters" @click="showFilters = !showFilters" class="rounded-lg border border-slate-300 px-4 py-2 text-sm dark:border-slate-600">{{ showFilters ? $t("Tutup pencarian & filter") : $t("Cari / filter pekerjaan") }}</button>
    </div>
    <div id="work-filters" v-show="!personal || showFilters" class="space-y-3">
    <form @submit.prevent="apply" class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900 sm:grid-cols-2 lg:grid-cols-4">
      <label class="text-sm">{{ $t("Kata kunci") }}<input v-model="form.query" maxlength="150" type="search" :placeholder="$t(&quot;Nomor atau judul pekerjaan&quot;)" class="mt-1 w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800" /></label>
      <label class="text-sm">{{ $t("Jenis") }}<select v-model="form.type" :disabled="Boolean(endpoint)" class="mt-1 w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800"><option value="">{{ $t("Semua jenis") }}</option><option value="ticket">Ticket</option><option value="task">Task</option><option value="project">Project</option></select></label>
      <label class="text-sm">Status<select v-model="form.status" class="mt-1 w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800"><option value="">{{ personal ? $t("Semua yang aktif") : endpoint ? $t("Dikerjakan / konfirmasi") : $t("Semua status") }}</option><option v-for="(label, value) in statuses" :key="value" :value="value">{{ label }}</option></select></label>
      <div class="flex items-end gap-2"><button type="submit" :disabled="loading" class="rounded-lg bg-blue-600 px-4 py-2 text-white disabled:opacity-50">{{ loading ? $t("Memuat…") : $t("Terapkan") }}</button><button type="button" @click="reset" class="rounded-lg border border-slate-300 px-4 py-2 dark:border-slate-600">Reset</button></div>
    </form>
    <div v-if="personal" class="flex flex-wrap items-center gap-2 text-sm">
      <button type="button" @click="saveView" class="rounded-lg border border-slate-300 px-3 py-2 dark:border-slate-600">{{ $t("Simpan filter di perangkat ini") }}</button>
      <button v-if="saved" type="button" @click="restoreView" class="rounded-lg px-3 py-2 text-blue-600 dark:text-blue-400">{{ $t("Gunakan filter tersimpan") }}</button>
      <span role="status">{{ savedMessage }}</span>
    </div>
    </div>
    <p class="text-sm text-slate-500 dark:text-slate-400" role="status">{{ results.total }} {{ personal ? $t("pekerjaan") : 'hasil' }} {{ $t("· Diurutkan berdasarkan deadline terdekat") }}</p>
    <section v-if="!results.data.length" class="rounded-2xl border border-dashed border-slate-300 p-10 text-center dark:border-slate-600">
      <h2 class="font-semibold">{{ $t("Tidak ada pekerjaan untuk filter ini") }}</h2><p class="mt-2 text-sm text-slate-500">{{ $t("Ubah kata kunci atau reset filter untuk melihat hasil lainnya.") }}</p>
    </section>
    <div v-else class="grid gap-3 md:grid-cols-2">
      <article v-for="item in results.data" :key="item.type + '-' + item.id" class="rounded-2xl border border-slate-200 bg-white p-5 transition hover:border-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-500 dark:border-slate-700 dark:bg-slate-900">
        <div class="flex flex-wrap items-center justify-between gap-2"><span class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ item.type }} · {{ item.number || '—' }}</span><StatusPill :status="item.status" :label="item.status_label" size="sm" /></div>
        <h3 class="mt-3 break-words font-semibold">{{ item.title }}</h3>
        <div class="mt-4 flex flex-wrap justify-between gap-2 text-sm"><span :class="item.overdue ? 'font-semibold text-rose-600 dark:text-rose-400' : 'text-slate-500 dark:text-slate-400'">{{ item.overdue ? $t("Terlambat · ") : 'Deadline · ' }}{{ date(item.due_at) }}</span><span v-if="item.priority" class="capitalize">{{ item.priority }}</span></div>
        <div v-if="personal" class="mt-4 rounded-xl bg-slate-50 p-3 text-sm text-slate-600 dark:bg-slate-800 dark:text-slate-300">
          <p class="font-semibold text-slate-800 dark:text-slate-100">{{ guidance(item).title }}</p><p class="mt-1">{{ guidance(item).body }}</p>
        </div>
        <Link :href="item.url" :aria-label="(personal ? $t(&quot;Buka pekerjaan: &quot;) : $t(&quot;Lihat detail: &quot;)) + item.title" class="mt-4 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500">{{ personal ? $t("Buka pekerjaan") : $t("Lihat detail") }} <span aria-hidden="true">→</span></Link>
      </article>
    </div>
    <nav v-if="results.last_page > 1" class="flex items-center justify-between gap-3" :aria-label="$t(&quot;Halaman hasil&quot;)">
      <Link v-if="results.prev_page_url" :href="results.prev_page_url" preserve-scroll class="rounded-lg border px-4 py-2">{{ $t("Sebelumnya") }}</Link><span v-else></span>
      <span class="text-sm">{{ results.current_page }} / {{ results.last_page }}</span>
      <Link v-if="results.next_page_url" :href="results.next_page_url" preserve-scroll class="rounded-lg border px-4 py-2">{{ $t("Berikutnya") }}</Link><span v-else></span>
    </nav>
  </div>
</template>

<script setup>
import { translateUi as uiText } from '../../i18n'

import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { computed, onMounted, reactive, ref, watch } from 'vue'
import StatusPill from '@/Components/StatusPill.vue'
import resolveRoute from '@/utils/resolveRoute'
const props = defineProps({ personal: Boolean, filters: Object, statuses: Object, results: Object, heading: String, endpoint: String, canViewWorkflows: Boolean })
const page = usePage()
const clean = f => ({ query: f?.query || '', type: f?.type || '', status: f?.status || '', view: f?.view || 'mine' })
const form = reactive(clean(props.filters))
const views = { mine: uiText("Pekerjaan aktif saya"), waiting: uiText("Perlu konfirmasi saya"), due: uiText("Jatuh tempo 24 jam"), overdue: uiText("Terlambat") }
const showFilters = ref(Boolean(props.filters?.query || props.filters?.type || props.filters?.status))
const viewDescriptions = {
  mine: uiText("Pekerjaan yang terkait dengan Anda, dari deadline paling dekat."),
  waiting: uiText("Buka pekerjaan dan periksa hasil yang menunggu konfirmasi Anda."),
  due: uiText("Tindak lanjuti pekerjaan yang akan jatuh tempo dalam 24 jam."),
  overdue: uiText("Periksa kendala dan koordinasikan pekerjaan yang melewati deadline."),
}
const guidance = item => {
  if (['done', 'cancelled'].includes(item.status)) return { title: uiText("Pekerjaan sudah ditutup"), body: uiText("Buka rincian untuk melihat hasil atau catatan pekerjaan.") }
  if (form.view === 'waiting' && item.status === 'confirmation') return { title: uiText("Periksa hasil pekerjaan"), body: uiText("Tinjau hasil dan catatan sebelum memberikan konfirmasi.") }
  return ({
    new: { title: uiText("Permintaan baru"), body: uiText("Buka rincian untuk memeriksa kebutuhan dan PIC pekerjaan.") },
    in_progress: { title: uiText("Pekerjaan sedang berjalan"), body: uiText("Periksa pembaruan dan koordinasikan langkah yang masih diperlukan.") },
    revision: { title: uiText("Ada revisi yang perlu ditinjau"), body: uiText("Baca catatan revisi dan koordinasikan perbaikannya dengan PIC.") },
    confirmation: { title: uiText("Hasil menunggu konfirmasi"), body: uiText("Buka rincian untuk memeriksa hasil dan tanggapan requester.") },
    on_hold: { title: uiText("Pekerjaan sedang ditunda"), body: uiText("Periksa alasan penundaan dan koordinasikan tindak lanjut.") },
  })[item.status] || { title: uiText('Periksa pembaruan'), body: uiText("Buka pekerjaan untuk melihat rincian dan catatan terbaru.") }
}
const loading = ref(false)
const saved = ref(false)
const savedMessage = ref('')
const key = computed(() => 'tickora:work-filter:' + page.props.auth?.user?.id)
const apply = () => router.get(resolveRoute(props.endpoint || (props.personal ? 'work.index' : 'search')), { ...form }, { preserveState: true, preserveScroll: true, onStart: () => loading.value = true, onFinish: () => loading.value = false })
const reset = () => { Object.assign(form, clean(props.endpoint ? { type: props.filters.type } : {})); apply() }
const saveView = () => { try { localStorage.setItem(key.value, JSON.stringify(form)); saved.value = true; savedMessage.value = uiText('Filter tersimpan.') } catch (_) { savedMessage.value = uiText("Penyimpanan perangkat tidak tersedia.") } }
const restoreView = () => { try { const f = clean(JSON.parse(localStorage.getItem(key.value))); if (!['', 'ticket', 'task', 'project'].includes(f.type) || !(f.view in views) || (f.status && !(f.status in props.statuses))) throw new Error(); Object.assign(form, f); apply() } catch (_) { savedMessage.value = uiText("Filter tidak dapat dipulihkan.") } }
const date = value => value ? new Intl.DateTimeFormat(page.props.locale === 'en' ? 'en-GB' : 'id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value.replace(' ', 'T'))) : uiText("Belum ditentukan")
onMounted(() => { try { saved.value = Boolean(localStorage.getItem(key.value)) } catch (_) {} })
watch(() => props.filters, value => { Object.assign(form, clean(value)); if (value?.query || value?.type || value?.status) showFilters.value = true })
</script>
