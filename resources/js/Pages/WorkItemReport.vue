<template>
  <div class="mx-auto max-w-7xl space-y-5 px-4 py-6">
    <Head :title="heading" />
    <header class="flex flex-wrap items-center justify-between gap-3">
      <h1 class="text-2xl font-bold">{{ heading }}</h1>
      <a :href="downloadUrl" class="rounded-lg border px-4 py-2 text-sm font-semibold dark:border-slate-700">{{ $t('Unduh PDF') }}</a>
    </header>
    <form class="grid gap-3 rounded-2xl border bg-white p-4 dark:border-slate-700 dark:bg-slate-900 md:grid-cols-[2fr_1fr_1fr_1fr_auto] md:items-end" @submit.prevent="apply">
      <label class="min-w-0 text-sm">{{ $t('Kata Kunci') }}
        <input v-model="form.q" type="search" class="mt-1 w-full rounded-lg border-slate-300 bg-transparent text-sm dark:border-slate-600" />
      </label>
      <label class="min-w-0 text-sm">Status
        <select v-model="form.status" class="mt-1 w-full rounded-lg border-slate-300 bg-white text-sm dark:border-slate-600 dark:bg-slate-900">
          <option value="">{{ $t('Semua') }}</option>
          <option v-for="option in statusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
        </select>
      </label>
      <label class="text-sm">{{ $t('Dari') }}
        <input v-model="form.from" type="date" class="mt-1 w-full rounded-lg border-slate-300 bg-transparent text-sm dark:border-slate-600" />
      </label>
      <label class="text-sm">{{ $t('Sampai') }}
        <input v-model="form.to" type="date" class="mt-1 w-full rounded-lg border-slate-300 bg-transparent text-sm dark:border-slate-600" />
      </label>
      <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">{{ $t('Terapkan') }}</button>
    </form>
    <dl class="grid grid-cols-3 gap-3">
      <div v-for="metric in metrics" :key="metric.key" class="rounded-xl border bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
        <dt class="text-sm text-slate-500 dark:text-slate-400">{{ metric.label }}</dt>
        <dd class="text-xl font-bold">{{ summary[metric.key] ?? 0 }}</dd>
      </div>
    </dl>
    <div class="overflow-x-auto rounded-2xl border bg-white dark:border-slate-700 dark:bg-slate-900">
      <table class="w-full text-left text-sm">
        <thead class="bg-slate-50 text-slate-600 dark:bg-slate-800 dark:text-slate-300">
          <tr>
            <th scope="col" class="px-4 py-3">{{ $t('Nomor') }}</th>
            <th scope="col" class="px-4 py-3">{{ $t('Judul') }}</th>
            <th scope="col" class="px-4 py-3">Status</th>
            <th v-if="kind !== 'project'" scope="col" class="px-4 py-3">{{ $t('Prioritas') }}</th>
            <th scope="col" class="px-4 py-3">{{ $t('Deadline') }}</th>
            <th v-if="kind === 'ticket'" scope="col" class="px-4 py-3">{{ $t('Mulai dikerjakan') }}</th>
            <th scope="col" class="px-4 py-3">{{ $t('Dibuat') }}</th>
            <th scope="col" class="px-4 py-3">{{ $t('Aksi') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
          <tr v-for="item in items.data" :key="item.id">
            <td class="whitespace-nowrap px-4 py-3 font-mono text-xs">{{ item[kind + '_no'] || '—' }}</td>
            <td class="min-w-40 px-4 py-3 font-semibold"><Link class="hover:underline" :href="item.links.show">{{ item.title }}</Link></td>
            <td class="whitespace-nowrap px-4 py-3"><StatusPill :status="item.status" :label="item.status_label" size="sm" /></td>
            <td v-if="kind !== 'project'" class="px-4 py-3 capitalize">{{ item.priority ? $t(formatStatusLabel(item.priority)) : '—' }}</td>
            <td class="whitespace-nowrap px-4 py-3">{{ item.due_display || '—' }}</td>
            <td v-if="kind === 'ticket'" class="whitespace-nowrap px-4 py-3">{{ item.worked_on_display || '—' }}</td>
            <td class="whitespace-nowrap px-4 py-3">{{ item.created_display || '—' }}</td>
            <td class="space-x-3 whitespace-nowrap px-4 py-3">
              <Link v-if="item.links.edit" class="font-medium text-indigo-700 hover:underline dark:text-indigo-300"
                :href="item.links.edit">{{ $t('Edit') }}</Link>
              <button v-if="item.links.delete" type="button" class="font-medium text-rose-700 hover:underline dark:text-rose-300"
                @click="remove(item)">{{ $t('Hapus') }}</button>
            </td>
          </tr>
          <tr v-if="!items.data.length">
            <td :colspan="kind === 'ticket' ? 8 : kind === 'task' ? 7 : 6" class="p-6 text-center text-slate-500">{{ $t('Belum ada data') }}</td>
          </tr>
        </tbody>
      </table>
      <nav v-if="items.links?.length > 3" class="flex flex-wrap justify-end gap-2 border-t p-4 dark:border-slate-700" :aria-label="$t('Halaman')">
        <template v-for="(link, index) in items.links" :key="index">
          <Link v-if="link.url" :href="link.url" :aria-current="link.active ? 'page' : undefined" preserve-scroll
            class="rounded-lg border px-3 py-1.5 text-sm dark:border-slate-700" :class="{ 'bg-indigo-600 text-white': link.active }">
            {{ index === 0 ? $t('Sebelumnya') : index === items.links.length - 1 ? $t('Berikutnya') : link.label }}
          </Link>
        </template>
      </nav>
    </div>
  </div>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { computed, reactive } from 'vue'
import { useI18n } from '../i18n'
import AppLayout from '../Layouts/AppLayout.vue'
import StatusPill from '../Components/StatusPill.vue'
import { formatStatusLabel } from '../utils/statusTheme'
import resolveRoute from '../utils/resolveRoute'

defineOptions({ layout: AppLayout })
const props = defineProps({
  kind: { type: String, required: true },
  items: { type: Object, required: true },
  summary: { type: Object, required: true },
  statusOptions: { type: Array, required: true },
  filters: { type: Object, default: () => ({}) },
})
const { t } = useI18n()
const heading = computed(() => t({ ticket: 'Laporan Tiket', task: 'Laporan Task', project: 'Laporan Project' }[props.kind]))
const metrics = computed(() => [
  { key: 'total', label: t('Total Data') },
  { key: 'in_progress', label: t('In Progress') },
  { key: 'done', label: t('Done') },
])
const form = reactive({ q: '', status: '', from: '', to: '', ...props.filters })
const report = computed(() => props.kind + 's.report')
const downloadUrl = computed(() => resolveRoute(report.value + '.download', props.filters))

function apply() {
  router.get(resolveRoute(report.value), { ...form }, { preserveState: true, preserveScroll: true })
}

function remove(item) {
  if (window.confirm(t('Hapus item ini?'))) {
    router.delete(item.links.delete, { data: { from: window.location.href }, preserveScroll: true })
  }
}
</script>
