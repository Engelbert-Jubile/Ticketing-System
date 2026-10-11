<template>
  <div class="mx-auto max-w-6xl space-y-6">
    <Head :title="$t(&quot;Panduan dan template&quot;)" />
    <header><h1 class="text-2xl font-bold">{{ $t("Panduan dan template") }}</h1><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $t("Temukan solusi yang sudah diverifikasi atau mulai ticket dari template tim.") }}</p></header>
    <p v-if="page.props.flash?.success" role="status" class="rounded-lg bg-emerald-50 p-3 text-emerald-800">{{ page.props.flash.success }}</p>
    <form @submit.prevent="search" class="flex flex-wrap gap-3">
      <input v-model="q" :aria-label="$t(&quot;Cari panduan&quot;)" maxlength="150" type="search" :placeholder="$t(&quot;Cari masalah atau solusi&quot;)" class="min-w-0 flex-1 rounded-xl dark:bg-slate-800" />
      <select v-model="kind" :aria-label="$t(&quot;Jenis panduan&quot;)" class="rounded-xl dark:bg-slate-800"><option value="">{{ $t("Semua") }}</option><option value="article">{{ $t("Artikel solusi") }}</option><option value="template">{{ $t("Template ticket") }}</option></select>
      <button class="rounded-xl bg-blue-600 px-4 py-2 text-white">{{ $t("Cari") }}</button>
    </form>
    <details v-if="can_manage" ref="editor" class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-900">
      <summary class="cursor-pointer font-semibold">{{ form.id ? $t("Edit panduan") : $t("Tulis panduan atau template") }}</summary>
      <form @submit.prevent="save" class="mt-4 grid gap-3">
        <label>{{ $t("Judul") }}<input v-model="form.title" required maxlength="255" class="mt-1 w-full rounded-lg dark:bg-slate-800" /></label>
        <div class="grid gap-3 sm:grid-cols-2"><label>{{ $t("Jenis") }}<select v-model="form.kind" class="mt-1 w-full rounded-lg dark:bg-slate-800"><option value="article">{{ $t("Artikel solusi") }}</option><option value="template">{{ $t("Template ticket") }}</option></select></label><label>{{ $t("Kategori") }}<input v-model="form.category" maxlength="100" class="mt-1 w-full rounded-lg dark:bg-slate-800" /></label></div>
        <label>{{ $t("Isi / langkah penyelesaian") }}<textarea v-model="form.body" required maxlength="20000" rows="7" class="mt-1 w-full rounded-lg dark:bg-slate-800" /></label>
        <label v-if="form.kind === 'template'">{{ $t("Checklist (satu per baris)") }}<textarea v-model="checklistText" rows="3" class="mt-1 w-full rounded-lg dark:bg-slate-800" /></label>
        <label v-if="form.kind === 'template'">{{ $t("PIC yang disarankan") }}<select v-model="form.default_assignee_id" class="mt-1 w-full rounded-lg dark:bg-slate-800"><option :value="null">{{ $t("Dipilih saat membuat ticket") }}</option><option v-for="user in assignees" :key="user.id" :value="user.id">{{ user.first_name }} {{ user.last_name }} · {{ user.unit }}</option></select><span class="mt-1 block text-xs text-slate-500">{{ $t("Template akan mengisi PIC ini. Pembuat ticket tetap dapat mengubah pilihan.") }}</span></label>
        <label class="flex items-center gap-2"><input v-model="form.published" type="checkbox" />{{ $t("Terbitkan untuk unit saya") }}</label>
        <p v-for="(message, field) in form.errors" :key="field" role="alert" class="text-sm text-rose-600">{{ message }}</p>
        <div class="flex gap-2"><button :disabled="form.processing" class="rounded-xl bg-blue-600 px-4 py-2 text-white">{{ $t("Simpan") }}</button><button type="button" @click="form.reset(); checklistText = ''" class="rounded-xl border px-4 py-2">{{ $t("Baru") }}</button></div>
      </form>
    </details>
    <p class="text-sm text-slate-500">{{ entries.total }} {{ $t("panduan") }}</p>
    <p v-if="!entries.data.length" class="rounded-2xl border border-dashed p-8 text-center">{{ $t("Belum ada panduan yang sesuai. Coba kata kunci lain.") }}</p>
    <article v-for="entry in entries.data" :key="entry.id" class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-900">
      <p class="text-xs font-semibold uppercase text-blue-600 dark:text-blue-400">{{ entry.kind === 'template' ? 'Template' : $t("Artikel solusi") }} · {{ entry.category || $t("Umum") }} <span v-if="!entry.published">· Draft</span></p>
      <h2 class="mt-2 text-lg font-semibold">{{ entry.title }}</h2><details class="mt-3"><summary class="cursor-pointer text-sm">{{ $t("Baca panduan") }}</summary><p class="mt-3 whitespace-pre-wrap break-words text-sm leading-relaxed">{{ entry.body }}</p><ul v-if="entry.checklist?.length" class="mt-3 list-inside list-disc text-sm"><li v-for="item in entry.checklist" :key="item">{{ item }}</li></ul></details>
      <div class="mt-4 flex flex-wrap gap-4 text-sm"><Link v-if="entry.kind === 'template' && entry.published" :href="resolveRoute('tickets.create', { template: entry.id })" class="font-semibold text-blue-600 dark:text-blue-400">{{ $t("Gunakan template") }}</Link><button v-if="can_manage && (entry.author_id === page.props.auth?.user?.id || page.props.auth?.user?.roles?.includes('superadmin'))" @click="edit(entry)" class="text-blue-600 dark:text-blue-400">Edit</button></div>
    </article>
    <nav class="flex justify-between text-sm"><Link v-if="entries.prev_page_url" :href="entries.prev_page_url">{{ $t("Sebelumnya") }}</Link><span v-else></span><Link v-if="entries.next_page_url" :href="entries.next_page_url">{{ $t("Berikutnya") }}</Link></nav>
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
