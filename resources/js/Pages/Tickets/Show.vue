<template>
  <div class="mx-auto max-w-5xl space-y-6 px-4 py-6 lg:px-6">

    <section class="space-y-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900/70">
      <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div>
          <h1 class="text-3xl font-bold text-slate-900 dark:text-slate-100">{{ ticket.title }}</h1>
          <p class="text-sm text-slate-500 dark:text-slate-300">{{ $t("Ticket No:") }} {{ ticket.ticket_no ?? '—' }}</p>
        </div>
        <div class="flex flex-col items-start gap-3 md:items-end">
          <a
            v-if="ticket.links?.pdf"
            :href="ticket.links.pdf"
            target="_blank"
            rel="noopener"
            class="inline-flex items-center gap-2 self-stretch rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow transition hover:bg-blue-700 md:self-auto"
          >
            <span class="material-icons text-base" aria-hidden="true">picture_as_pdf</span>
            {{ $t("Unduh PDF") }}
          </a>
          <div class="flex flex-wrap gap-2">
            <StatusPill :status="ticket.status" :label="ticket.status_label" size="sm" />
            <StatusPill :status="ticket.priority" :label="ticket.priority ?? $t(&quot;Priority&quot;)" variant="priority" size="sm" />
          </div>
        </div>
      </div>

      <dl class="grid gap-4 text-sm text-slate-600 dark:text-slate-300 md:grid-cols-3">
        <div>
          <dt class="font-semibold text-slate-500 dark:text-slate-400">{{ $t("Status ID") }}</dt>
          <dd>
            {{ ticket.status_id ?? '—' }}
            <span v-if="ticket.status_id_label" class="ml-1 text-xs text-slate-400">({{ ticket.status_id_label }})</span>
          </dd>
        </div>
        <div>
          <dt class="font-semibold text-slate-500 dark:text-slate-400">{{ $t("Deadline") }}</dt>
          <dd>{{ formatDate(ticket.timeline?.due_at, true) }}</dd>
        </div>
        <div>
          <dt class="font-semibold text-slate-500 dark:text-slate-400">{{ $t("Mulai dikerjakan") }}</dt>
          <dd>{{ formatDate(ticket.timeline?.worked_on_at, true) }}</dd>
        </div>
        <div>
          <dt class="font-semibold text-slate-500 dark:text-slate-400">SLA</dt>
          <dd>{{ formatSla(ticket.sla) }}</dd>
        </div>
        <div>
          <dt class="font-semibold text-slate-500 dark:text-slate-400">{{ $t("Dibuat") }}</dt>
          <dd>{{ formatDate(ticket.timeline?.created_at, true) }}</dd>
        </div>
        <div>
          <dt class="font-semibold text-slate-500 dark:text-slate-400">{{ $t("Diperbarui") }}</dt>
          <dd>{{ formatDate(ticket.timeline?.updated_at, true) }}</dd>
        </div>
        <div v-if="ticket.status === 'done'">
          <dt class="font-semibold text-slate-500 dark:text-slate-400">{{ $t("Selesai") }}</dt>
          <dd>{{ formatDate(ticket.timeline?.finish_at, true) }}</dd>
        </div>
      </dl>
    </section>

    <section class="grid gap-6 md:grid-cols-2">
      <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900/70">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $t("Pemilik Ticket") }}</h2>
        <dl class="mt-3 space-y-2 text-sm text-slate-600 dark:text-slate-300">
          <div>
            <dt class="font-medium text-slate-500 dark:text-slate-400">{{ $t("Requester") }}</dt>
            <dd>{{ ticket.requester?.name ?? '—' }}</dd>
          </div>
          <div>
            <dt class="font-medium text-slate-500 dark:text-slate-400">{{ $t("Assigned Users") }}</dt>
            <dd>
              <template v-if="ticket.assigned_users?.length">
                <ul class="list-disc list-inside space-y-1">
                  <li v-for="user in ticket.assigned_users" :key="user.id">
                    {{ user.name }}
                    <span class="text-xs text-slate-400">{{ user.email }}</span>
                  </li>
                </ul>
              </template>
              <span v-else>—</span>
            </dd>
          </div>
        </dl>
      </article>

      <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900/70">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $t("Detail Tambahan") }}</h2>
        <dl class="mt-3 space-y-2 text-sm text-slate-600 dark:text-slate-300">
          <div>
            <dt class="font-medium text-slate-500 dark:text-slate-400">{{ $t("Reason") }}</dt>
            <dd>{{ ticket.reason ?? '—' }}</dd>
          </div>
          <div>
            <dt class="font-medium text-slate-500 dark:text-slate-400">{{ $t("Letter No") }}</dt>
            <dd>{{ ticket.letter_no ?? '—' }}</dd>
          </div>
        </dl>
      </article>
    </section>

    <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900/70">
      <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $t("Deskripsi") }}</h2>
      <div class="prose prose-sm max-w-none text-slate-700 dark:text-slate-200 dark:prose-invert" v-html="ticket.description || '<p>—</p>'"></div>
    </article>

    <article v-if="ticket.attachments?.length" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900/70">
      <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $t("Lampiran") }}</h2>
      <ul class="space-y-2 text-sm text-slate-600 dark:text-slate-300">
        <li v-for="attachment in ticket.attachments" :key="attachment.id" class="flex flex-wrap items-center gap-2">
          <a :href="attachment.view_url" target="_blank" rel="noopener" class="text-blue-600 hover:underline dark:text-blue-400">{{ $t("Lihat") }}</a>
          <span class="text-slate-400">·</span>
          <a :href="attachment.download_url" class="text-blue-600 hover:underline dark:text-blue-400">{{ $t("Unduh") }}</a>
          <span class="text-slate-600 dark:text-slate-400">{{ attachment.name }}</span>
          <span v-if="attachment.size" class="text-xs text-slate-400">({{ formatSize(attachment.size) }})</span>
        </li>
      </ul>
    </article>
    <section v-if="ticket.confirmations?.length" class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-900">
      <h2 class="font-semibold">{{ $t("Bukti konfirmasi") }}</h2>
      <article v-for="entry in ticket.confirmations" :key="entry.id" class="mt-3 space-y-2 border-t pt-3 dark:border-slate-700">
        <time class="text-xs text-slate-500">{{ formatDate(entry.created_at, true) }}</time>
        <p class="whitespace-pre-wrap break-words text-sm">{{ entry.note }}</p>
        <ul class="space-y-1">
          <li v-for="file in entry.evidence" :key="file.id">
            <a :href="file.download_url" class="text-sm font-medium text-indigo-700 hover:underline dark:text-indigo-300">{{ file.name }}</a>
          </li>
        </ul>
      </article>
    </section>

    <TicketCollaboration :ticket-id="ticket.id" />
  </div>
</template>

<script setup>
import { translateUi as uiText, uiLocale } from '../../i18n'

import StatusPill from '@/Components/StatusPill.vue';
import TicketCollaboration from '@/Components/TicketCollaboration.vue';


const props = defineProps({
  ticket: { type: Object, required: true },
  meta: { type: Object, default: () => ({}) },
});

function formatDate(value, withTime = false) {
  if (!value) return '—';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return '—';
  const options = withTime
    ? { dateStyle: 'medium', timeStyle: 'short' }
    : { dateStyle: 'medium' };
  return new Intl.DateTimeFormat(uiLocale.value, options).format(date);
}

function formatSla(value) {
  return { ontime: uiText('On Time'), late: uiText('Late') }[value] ?? '-';
}

function formatSize(size) {
  if (!size || Number.isNaN(Number(size))) return '';
  const kb = Number(size) / 1024;
  if (kb < 1024) return `${kb.toFixed(0)} KB`;
  const mb = kb / 1024;
  return `${mb.toFixed(1)} MB`;
}
</script>
