<template>
  <div class="mx-auto max-w-6xl space-y-6">
    <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"><div><Link :href="resolveRoute('workflows.index')" class="text-sm font-semibold text-blue-600">← Workflows</Link><div class="mt-2 flex flex-wrap items-center gap-3"><h1 class="text-3xl font-bold text-slate-900 dark:text-white">{{ workflow.name }}</h1><span :class="workflow.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600'" class="rounded-full px-3 py-1 text-xs font-bold">{{ workflow.is_active ? 'Aktif' : 'Nonaktif' }}</span></div><p class="mt-1 font-mono text-sm text-slate-400">{{ workflow.code }} · {{ workflow.entity_type }}</p></div><Link v-if="can.update" :href="resolveRoute('workflows.edit', { workflow: workflow.uuid })" class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white">Edit Workflow</Link></header>
    <section class="grid gap-4 sm:grid-cols-3"><div class="card"><p class="label">Versi</p><p class="value">v{{ workflow.version }}</p></div><div class="card"><p class="label">Tahapan</p><p class="value">{{ workflow.stages.length }}</p></div><div class="card"><p class="label">Digunakan</p><p class="value">{{ workflow.instances_count }} item</p></div></section>
    <section class="card"><h2 class="text-lg font-bold dark:text-white">Alur workflow</h2><p class="mt-1 text-sm text-slate-500">{{ workflow.description || 'Tidak ada deskripsi.' }}</p><div class="mt-6 overflow-x-auto pb-2"><div class="flex min-w-max items-stretch"><template v-for="(stage, index) in workflow.stages" :key="stage.id"><article class="w-64 rounded-2xl border border-blue-100 bg-blue-50/60 p-4 dark:border-blue-500/20 dark:bg-blue-500/5"><div class="flex items-center gap-3"><span class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-600 font-bold text-white">{{ index + 1 }}</span><div><h3 class="font-bold text-slate-800 dark:text-white">{{ stage.name }}</h3><p class="text-xs capitalize text-blue-600">{{ stage.status_key.replaceAll('_', ' ') }}</p></div></div><div class="mt-4 space-y-2 text-xs text-slate-500"><p>Role: <b>{{ stage.responsible_role || '—' }}</b></p><p>PIC: <b>{{ stage.responsible_user_name || '—' }}</b></p><p>Aksi: <b>{{ stage.action_label || '—' }}</b></p></div></article><div v-if="index < workflow.stages.length - 1" class="flex w-14 items-center justify-center text-blue-400"><span class="material-icons">arrow_forward</span></div></template></div></div></section>
    <div class="grid gap-6 lg:grid-cols-2"><section class="card"><h2 class="text-lg font-bold dark:text-white">Kondisi pemicu</h2><div v-if="!workflow.trigger_conditions.length" class="mt-4 text-sm text-slate-500">Berlaku untuk semua {{ workflow.entity_type }}.</div><ul v-else class="mt-4 space-y-2"><li v-for="(item, index) in workflow.trigger_conditions" :key="index" class="rounded-xl bg-slate-50 px-4 py-3 text-sm dark:bg-slate-800"><b class="capitalize">{{ item.field }}</b> {{ operator(item.operator) }} “{{ item.value }}”</li></ul></section><section class="card"><h2 class="text-lg font-bold dark:text-white">Riwayat perubahan</h2><div v-if="!workflow.histories.length" class="mt-4 text-sm text-slate-500">Belum ada riwayat.</div><ol v-else class="mt-4 space-y-4"><li v-for="history in workflow.histories" :key="history.id" class="border-l-2 border-blue-200 pl-4"><p class="text-sm font-semibold capitalize text-slate-700 dark:text-slate-200">{{ history.event }}</p><p class="text-xs text-slate-500">{{ history.actor_name }} · {{ formatDate(history.created_at) }}</p></li></ol></section></div>
  </div>
</template>
<script setup>
import { Link } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import resolveRoute from '../../utils/resolveRoute'
defineOptions({ layout: AppLayout })
defineProps({ workflow: { type: Object, required: true }, can: { type: Object, default: () => ({}) } })
const operator = value => ({ equals: 'sama dengan', not_equals: 'tidak sama dengan', contains: 'mengandung' }[value] || value)
const formatDate = value => value ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value)) : '—'
</script>
<style scoped>
.card { border: 1px solid #e2e8f0; border-radius: 1rem; background: white; padding: 1.25rem; box-shadow: 0 1px 2px rgb(15 23 42 / .04); }
.dark .card { border-color: #334155; background: #0f172a; }
.label { font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: #94a3b8; }
.value { margin-top: .35rem; font-size: 1.35rem; font-weight: 700; color: #0f172a; }
.dark .value { color: white; }
</style>
