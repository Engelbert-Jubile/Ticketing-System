<template>
  <div class="mx-auto max-w-7xl space-y-6">
    <Head :title="heading || (personal ? 'Pekerjaan Saya' : 'Pencarian')" />
    <header class="flex flex-wrap items-center justify-between gap-3">
      <div><h1 class="text-2xl font-bold">{{ heading || (personal ? 'Pekerjaan Saya' : 'Pencarian') }}</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ personal ? 'Tindak lanjuti pekerjaan dan konfirmasi yang membutuhkan perhatian Anda.' : 'Cari nomor, judul, atau deskripsi ticket, task, dan project.' }}</p></div>
      <Link :href="resolveRoute('tickets.create')" class="rounded-xl bg-blue-600 px-4 py-2 font-semibold text-white">Buat ticket</Link>
    </header>
    <nav v-if="personal" class="flex flex-wrap gap-2" aria-label="Antrean pekerjaan">
      <button v-for="(label, value) in views" :key="value" type="button" :aria-pressed="form.view === value" @click="form.view = value; apply()"
        class="rounded-full border px-4 py-2 text-sm" :class="form.view === value ? 'border-blue-600 bg-blue-600 text-white' : 'border-slate-300 dark:border-slate-600'">{{ label }}</button>
    </nav>
    <form @submit.prevent="apply" class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900 sm:grid-cols-2 lg:grid-cols-4">
      <label class="text-sm">Kata kunci<input v-model="form.query" maxlength="150" type="search" placeholder="Nomor atau judul pekerjaan" class="mt-1 w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800" /></label>
      <label class="text-sm">Jenis<select v-model="form.type" :disabled="Boolean(endpoint)" class="mt-1 w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800"><option value="">Semua jenis</option><option value="ticket">Ticket</option><option value="task">Task</option><option value="project">Project</option></select></label>
      <label class="text-sm">Status<select v-model="form.status" class="mt-1 w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-800"><option value="">{{ personal ? 'Semua yang aktif' : endpoint ? 'Dikerjakan / konfirmasi' : 'Semua status' }}</option><option v-for="(label, value) in statuses" :key="value" :value="value">{{ label }}</option></select></label>
      <div class="flex items-end gap-2"><button type="submit" :disabled="loading" class="rounded-lg bg-blue-600 px-4 py-2 text-white disabled:opacity-50">{{ loading ? 'Memuat…' : 'Terapkan' }}</button><button type="button" @click="reset" class="rounded-lg border border-slate-300 px-4 py-2 dark:border-slate-600">Reset</button></div>
    </form>
    <div v-if="personal" class="flex flex-wrap items-center gap-2 text-sm">
      <button type="button" @click="saveView" class="rounded-lg border border-slate-300 px-3 py-2 dark:border-slate-600">Simpan filter di perangkat ini</button>
      <button v-if="saved" type="button" @click="restoreView" class="rounded-lg px-3 py-2 text-blue-600 dark:text-blue-400">Gunakan filter tersimpan</button>
      <span role="status">{{ savedMessage }}</span>
    </div>
    <p class="text-sm text-slate-500 dark:text-slate-400" role="status">{{ results.total }} hasil · Diurutkan berdasarkan deadline terdekat</p>
    <section v-if="!results.data.length" class="rounded-2xl border border-dashed border-slate-300 p-10 text-center dark:border-slate-600">
      <h2 class="font-semibold">Tidak ada pekerjaan untuk filter ini</h2><p class="mt-2 text-sm text-slate-500">Ubah kata kunci atau reset filter untuk melihat hasil lainnya.</p>
    </section>
    <div v-else class="grid gap-3 md:grid-cols-2">
      <Link v-for="item in results.data" :key="item.type + '-' + item.id" :href="item.url" class="rounded-2xl border border-slate-200 bg-white p-5 transition hover:border-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-500 dark:border-slate-700 dark:bg-slate-900">
        <div class="flex flex-wrap items-center justify-between gap-2"><span class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ item.type }} · {{ item.number || '—' }}</span><StatusPill :status="item.status" :label="item.status_label" size="sm" /></div>
        <h2 class="mt-3 break-words font-semibold">{{ item.title }}</h2>
        <div class="mt-4 flex flex-wrap justify-between gap-2 text-sm"><span :class="item.overdue ? 'font-semibold text-rose-600 dark:text-rose-400' : 'text-slate-500 dark:text-slate-400'">{{ item.overdue ? 'Terlambat · ' : 'Deadline · ' }}{{ date(item.due_at) }}</span><span v-if="item.priority" class="capitalize">{{ item.priority }}</span></div>
      </Link>
    </div>
    <nav v-if="results.last_page > 1" class="flex items-center justify-between gap-3" aria-label="Halaman hasil">
      <Link v-if="results.prev_page_url" :href="results.prev_page_url" preserve-scroll class="rounded-lg border px-4 py-2">Sebelumnya</Link><span v-else></span>
      <span class="text-sm">{{ results.current_page }} / {{ results.last_page }}</span>
      <Link v-if="results.next_page_url" :href="results.next_page_url" preserve-scroll class="rounded-lg border px-4 py-2">Berikutnya</Link><span v-else></span>
    </nav>
  </div>
</template>

<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { computed, onMounted, reactive, ref, watch } from 'vue'
import StatusPill from '@/Components/StatusPill.vue'
import resolveRoute from '@/utils/resolveRoute'
const props = defineProps({ personal: Boolean, filters: Object, statuses: Object, results: Object, heading: String, endpoint: String })
const page = usePage()
const clean = f => ({ query: f?.query || '', type: f?.type || '', status: f?.status || '', view: f?.view || 'mine' })
const form = reactive(clean(props.filters))
const views = { mine: 'Pekerjaan aktif', waiting: 'Menunggu konfirmasi saya', due: 'Jatuh tempo 24 jam', overdue: 'Terlambat' }
const loading = ref(false)
const saved = ref(false)
const savedMessage = ref('')
const key = computed(() => 'tickora:work-filter:' + page.props.auth?.user?.id)
const apply = () => router.get(resolveRoute(props.endpoint || (props.personal ? 'work.index' : 'search')), { ...form }, { preserveState: true, preserveScroll: true, onStart: () => loading.value = true, onFinish: () => loading.value = false })
const reset = () => { Object.assign(form, clean(props.endpoint ? { type: props.filters.type } : {})); apply() }
const saveView = () => { try { localStorage.setItem(key.value, JSON.stringify(form)); saved.value = true; savedMessage.value = 'Filter tersimpan.' } catch (_) { savedMessage.value = 'Penyimpanan perangkat tidak tersedia.' } }
const restoreView = () => { try { const f = clean(JSON.parse(localStorage.getItem(key.value))); if (!['', 'ticket', 'task', 'project'].includes(f.type) || !(f.view in views) || (f.status && !(f.status in props.statuses))) throw new Error(); Object.assign(form, f); apply() } catch (_) { savedMessage.value = 'Filter tidak dapat dipulihkan.' } }
const date = value => value ? new Intl.DateTimeFormat(page.props.locale === 'en' ? 'en-GB' : 'id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value.replace(' ', 'T'))) : 'Belum ditentukan'
onMounted(() => { try { saved.value = Boolean(localStorage.getItem(key.value)) } catch (_) {} })
watch(() => props.filters, value => Object.assign(form, clean(value)))
</script>
