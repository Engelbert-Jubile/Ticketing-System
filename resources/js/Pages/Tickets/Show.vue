<template>
  <div class="mx-auto max-w-5xl space-y-6 px-4 py-6 lg:px-6">

    <section class="space-y-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900/70">
      <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div>
          <h1 class="text-3xl font-bold text-slate-900 dark:text-slate-100">{{ ticket.title }}</h1>
          <p class="text-sm text-slate-500 dark:text-slate-300">Ticket No: {{ ticket.ticket_no ?? '—' }}</p>
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
            Unduh PDF
          </a>
          <div class="flex flex-wrap gap-2">
            <StatusPill :status="ticket.status" :label="ticket.status_label" size="sm" />
            <StatusPill :status="ticket.priority" :label="ticket.priority ?? 'Priority'" variant="priority" size="sm" />
            <StatusPill :status="ticket.type" :label="ticket.type ?? 'Type'" size="sm" />
          </div>
        </div>
      </div>

      <dl class="grid gap-4 text-sm text-slate-600 dark:text-slate-300 md:grid-cols-3">
        <div>
          <dt class="font-semibold text-slate-500 dark:text-slate-400">Status ID</dt>
          <dd>
            {{ ticket.status_id ?? '—' }}
            <span v-if="ticket.status_id_label" class="ml-1 text-xs text-slate-400">({{ ticket.status_id_label }})</span>
          </dd>
        </div>
        <div>
          <dt class="font-semibold text-slate-500 dark:text-slate-400">Target Selesai</dt>
          <dd>{{ formatDate(ticket.timeline?.due_at) }}</dd>
        </div>
        <div>
          <dt class="font-semibold text-slate-500 dark:text-slate-400">SLA</dt>
          <dd>{{ formatSla(ticket.sla) }}</dd>
        </div>
        <div>
          <dt class="font-semibold text-slate-500 dark:text-slate-400">Dibuat</dt>
          <dd>{{ formatDate(ticket.timeline?.created_at, true) }}</dd>
        </div>
        <div>
          <dt class="font-semibold text-slate-500 dark:text-slate-400">Diperbarui</dt>
          <dd>{{ formatDate(ticket.timeline?.updated_at, true) }}</dd>
        </div>
        <div>
          <dt class="font-semibold text-slate-500 dark:text-slate-400">Selesai</dt>
          <dd>{{ formatDate(ticket.timeline?.finish_at, true) }}</dd>
        </div>
      </dl>
    </section>

    <section class="grid gap-6 md:grid-cols-2">
      <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900/70">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Pemilik Ticket</h2>
        <dl class="mt-3 space-y-2 text-sm text-slate-600 dark:text-slate-300">
          <div>
            <dt class="font-medium text-slate-500 dark:text-slate-400">Requester</dt>
            <dd>{{ ticket.requester?.name ?? '—' }}</dd>
          </div>
          <div>
            <dt class="font-medium text-slate-500 dark:text-slate-400">Agent</dt>
            <dd>{{ ticket.agent?.name ?? '—' }}</dd>
          </div>
          <div>
            <dt class="font-medium text-slate-500 dark:text-slate-400">Assigned Users</dt>
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
        <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Detail Tambahan</h2>
        <dl class="mt-3 space-y-2 text-sm text-slate-600 dark:text-slate-300">
          <div>
            <dt class="font-medium text-slate-500 dark:text-slate-400">Reason</dt>
            <dd>{{ ticket.reason ?? '—' }}</dd>
          </div>
          <div>
            <dt class="font-medium text-slate-500 dark:text-slate-400">Letter No</dt>
            <dd>{{ ticket.letter_no ?? '—' }}</dd>
          </div>
        </dl>
      </article>
    </section>

    <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900/70">
      <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Deskripsi</h2>
      <div class="prose prose-sm max-w-none text-slate-700 dark:prose-invert" v-html="ticket.description || '<p>—</p>'"></div>
    </article>

    <article v-if="ticket.projects?.length" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900/70">
      <div class="flex items-center justify-between gap-2">
        <div>
          <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Project Terkait</h2>
          <p class="text-xs text-slate-500 dark:text-slate-300">Semua project yang ditautkan ke ticket ini.</p>
        </div>
      </div>
      <div class="mt-4 grid gap-3">
        <div
          v-for="project in ticket.projects"
          :key="project.id"
          class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-blue-200 dark:border-slate-700 dark:bg-slate-900/70 dark:hover:border-blue-500/40"
        >
          <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
              <p class="font-semibold text-slate-900 dark:text-slate-100">{{ project.title }}</p>
              <p class="text-xs text-slate-500 dark:text-slate-300">No: {{ project.project_no || '—' }}</p>
            </div>
            <StatusPill :status="project.status" :label="project.status_label" size="sm" />
          </div>
          <div class="mt-2 flex flex-wrap items-center gap-3 text-xs text-slate-500 dark:text-slate-300">
            <span>Due: {{ project.due_display || '—' }}</span>
            <span>Update: {{ project.updated_display || '—' }}</span>
          </div>
          <div class="mt-3 flex flex-wrap items-center gap-3 text-sm">
            <a v-if="project.links?.show" :href="project.links.show" class="text-blue-600 hover:underline dark:text-blue-300">Detail</a>
            <a v-if="project.links?.edit" :href="project.links.edit" class="text-blue-600 hover:underline dark:text-blue-300">Edit</a>
          </div>
        </div>
      </div>
    </article>

    <article v-if="ticket.attachments?.length" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900/70">
      <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Lampiran</h2>
      <ul class="space-y-2 text-sm text-slate-600 dark:text-slate-300">
        <li v-for="attachment in ticket.attachments" :key="attachment.id" class="flex flex-wrap items-center gap-2">
          <a :href="attachment.view_url" target="_blank" rel="noopener" class="text-blue-600 hover:underline dark:text-blue-400">Lihat</a>
          <span class="text-slate-400">·</span>
          <a :href="attachment.download_url" class="text-blue-600 hover:underline dark:text-blue-400">Unduh</a>
          <span class="text-slate-600 dark:text-slate-400">{{ attachment.name }}</span>
          <span v-if="attachment.size" class="text-xs text-slate-400">({{ formatSize(attachment.size) }})</span>
        </li>
      </ul>
    </article>
  </div>
</template>

<script setup>
import StatusPill from '@/Components/StatusPill.vue';


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
  return new Intl.DateTimeFormat('id-ID', options).format(date);
}

function formatSla(value) {
  if (!value) return '—';
  return value.replace(/_/g, ' ').replace(/\b\w/g, char => char.toUpperCase());
}

function formatSize(size) {
  if (!size || Number.isNaN(Number(size))) return '';
  const kb = Number(size) / 1024;
  if (kb < 1024) return `${kb.toFixed(0)} KB`;
  const mb = kb / 1024;
  return `${mb.toFixed(1)} MB`;
}
</script>
