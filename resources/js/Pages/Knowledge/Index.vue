<template>
  <div class="mx-auto max-w-6xl space-y-6">
    <Head title="Panduan dan template" />
    <header><h1 class="text-2xl font-bold">Panduan dan template</h1><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Temukan solusi yang sudah diverifikasi atau mulai ticket dari template tim.</p></header>
    <p v-if="page.props.flash?.success" role="status" class="rounded-lg bg-emerald-50 p-3 text-emerald-800">{{ page.props.flash.success }}</p>
    <form @submit.prevent="search" class="flex flex-wrap gap-3">
      <input v-model="q" aria-label="Cari panduan" maxlength="150" type="search" placeholder="Cari masalah atau solusi" class="min-w-0 flex-1 rounded-xl dark:bg-slate-800" />
      <select v-model="kind" aria-label="Jenis panduan" class="rounded-xl dark:bg-slate-800"><option value="">Semua</option><option value="article">Artikel solusi</option><option value="template">Template ticket</option></select>
      <button class="rounded-xl bg-blue-600 px-4 py-2 text-white">Cari</button>
    </form>
    <details v-if="can_manage" ref="editor" class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-900">
      <summary class="cursor-pointer font-semibold">{{ form.id ? 'Edit panduan' : 'Tulis panduan atau template' }}</summary>
      <form @submit.prevent="save" class="mt-4 grid gap-3">
        <label>Judul<input v-model="form.title" required maxlength="255" class="mt-1 w-full rounded-lg dark:bg-slate-800" /></label>
        <div class="grid gap-3 sm:grid-cols-2"><label>Jenis<select v-model="form.kind" class="mt-1 w-full rounded-lg dark:bg-slate-800"><option value="article">Artikel solusi</option><option value="template">Template ticket</option></select></label><label>Kategori<input v-model="form.category" maxlength="100" class="mt-1 w-full rounded-lg dark:bg-slate-800" /></label></div>
        <label>Isi / langkah penyelesaian<textarea v-model="form.body" required maxlength="20000" rows="7" class="mt-1 w-full rounded-lg dark:bg-slate-800" /></label>
        <label v-if="form.kind === 'template'">Checklist (satu per baris)<textarea v-model="checklistText" rows="3" class="mt-1 w-full rounded-lg dark:bg-slate-800" /></label>
        <label v-if="form.kind === 'template'">PIC yang disarankan<select v-model="form.default_assignee_id" class="mt-1 w-full rounded-lg dark:bg-slate-800"><option :value="null">Dipilih saat membuat ticket</option><option v-for="user in assignees" :key="user.id" :value="user.id">{{ user.first_name }} {{ user.last_name }} · {{ user.unit }}</option></select><span class="mt-1 block text-xs text-slate-500">Template akan mengisi PIC ini. Pembuat ticket tetap dapat mengubah pilihan.</span></label>
        <label class="flex items-center gap-2"><input v-model="form.published" type="checkbox" />Terbitkan untuk unit saya</label>
        <p v-for="(message, field) in form.errors" :key="field" role="alert" class="text-sm text-rose-600">{{ message }}</p>
        <div class="flex gap-2"><button :disabled="form.processing" class="rounded-xl bg-blue-600 px-4 py-2 text-white">Simpan</button><button type="button" @click="form.reset(); checklistText = ''" class="rounded-xl border px-4 py-2">Baru</button></div>
      </form>
    </details>
    <p class="text-sm text-slate-500">{{ entries.total }} panduan</p>
    <p v-if="!entries.data.length" class="rounded-2xl border border-dashed p-8 text-center">Belum ada panduan yang sesuai. Coba kata kunci lain.</p>
    <article v-for="entry in entries.data" :key="entry.id" class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-900">
      <p class="text-xs font-semibold uppercase text-blue-600 dark:text-blue-400">{{ entry.kind === 'template' ? 'Template' : 'Artikel solusi' }} · {{ entry.category || 'Umum' }} <span v-if="!entry.published">· Draft</span></p>
      <h2 class="mt-2 text-lg font-semibold">{{ entry.title }}</h2><details class="mt-3"><summary class="cursor-pointer text-sm">Baca panduan</summary><p class="mt-3 whitespace-pre-wrap break-words text-sm leading-relaxed">{{ entry.body }}</p><ul v-if="entry.checklist?.length" class="mt-3 list-inside list-disc text-sm"><li v-for="item in entry.checklist" :key="item">{{ item }}</li></ul></details>
      <div class="mt-4 flex flex-wrap gap-4 text-sm"><Link v-if="entry.kind === 'template' && entry.published" :href="resolveRoute('tickets.create', { template: entry.id })" class="font-semibold text-blue-600 dark:text-blue-400">Gunakan template</Link><button v-if="can_manage && (entry.author_id === page.props.auth?.user?.id || page.props.auth?.user?.roles?.includes('superadmin'))" @click="edit(entry)" class="text-blue-600 dark:text-blue-400">Edit</button></div>
    </article>
    <nav class="flex justify-between text-sm"><Link v-if="entries.prev_page_url" :href="entries.prev_page_url">Sebelumnya</Link><span v-else></span><Link v-if="entries.next_page_url" :href="entries.next_page_url">Berikutnya</Link></nav>
  </div>
</template>
<script setup>
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3'
import { ref } from 'vue'
import resolveRoute from '@/utils/resolveRoute'
const props = defineProps({ entries: Object, filters: Object, can_manage: Boolean, assignees: Array })
const page = usePage(), q = ref(props.filters.q || ''), kind = ref(props.filters.kind || ''), checklistText = ref(''), editor = ref(null)
const form = useForm({ id: null, title: '', kind: 'article', category: '', body: '', published: false, checklist: [], default_assignee_id: null })
const search = () => router.get(resolveRoute('knowledge.index'), { q: q.value, kind: kind.value })
const save = () => { form.checklist = checklistText.value.split('\n').map(s => s.trim()).filter(Boolean); form.post(resolveRoute('knowledge.save'), { preserveScroll: true, onSuccess: () => { form.reset(); checklistText.value = '' } }) }
const edit = entry => { for (const key of Object.keys(form.data())) form[key] = entry[key] ?? form[key]; checklistText.value = (entry.checklist || []).join('\n'); editor.value.open = true; editor.value.scrollIntoView({ behavior: 'smooth' }) }
</script>
