<template>
  <div class="mx-auto max-w-6xl space-y-6 px-4 py-6 lg:px-6">
    <header class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
      <div>
        <h1 class="text-3xl font-bold text-slate-900 dark:text-slate-100">{{ project.title }}</h1>
        <p class="text-sm text-slate-500 dark:text-slate-300">Nomor Project: {{ project.project_no || '—' }}</p>
      </div>
      <div class="flex flex-col items-end gap-2">
        <div class="flex flex-wrap items-center gap-2">
          <Link
            v-if="project.links?.edit"
            :href="project.links.edit"
            class="inline-flex items-center gap-2 rounded-lg border border-blue-200 px-4 py-2 text-sm font-semibold text-blue-700 shadow-sm transition hover:bg-blue-50 dark:border-blue-500/40 dark:text-blue-200 dark:hover:bg-blue-500/10"
          >
            <span class="material-icons text-base" aria-hidden="true">edit</span>
            Edit Project
          </Link>
          <a
            v-if="project.links?.pdf"
            :href="project.links.pdf"
            target="_blank"
            rel="noopener"
            class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow transition hover:bg-blue-700"
          >
            <span class="material-icons text-base" aria-hidden="true">picture_as_pdf</span>
            Unduh PDF
          </a>
        </div>
        <StatusBadge :status="project.status" :label="project.status_label" />
        <span class="rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-600 dark:border-slate-700 dark:text-slate-300">Status ID: {{ project.status_id || '—' }}</span>
      </div>
    </header>

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
      <dl class="grid gap-4 text-sm text-slate-600 dark:text-slate-300 sm:grid-cols-2 lg:grid-cols-3">
        <div><dt class="font-semibold text-slate-800 dark:text-slate-100">Dimulai</dt><dd>{{ project.timeline.start }}</dd></div>
        <div><dt class="font-semibold text-slate-800 dark:text-slate-100">Target Selesai</dt><dd>{{ project.timeline.end }}</dd></div>
        <div><dt class="font-semibold text-slate-800 dark:text-slate-100">Dibuat</dt><dd>{{ project.timeline.created }}</dd></div>
        <div><dt class="font-semibold text-slate-800 dark:text-slate-100">Diupdate</dt><dd>{{ project.timeline.updated }}</dd></div>
        <div v-if="project.ticket"><dt class="font-semibold text-slate-800 dark:text-slate-100">Ticket</dt><dd>{{ project.ticket.ticket_no }}</dd></div>
      </dl>

      <div class="mt-6 space-y-2">
        <h3 class="text-base font-semibold text-slate-800 dark:text-slate-100">Deskripsi</h3>
        <article
          class="prose prose-sm max-w-none text-slate-700 dark:prose-invert dark:text-slate-200"
          v-html="project.description || `<p class='text-slate-400'>Tidak ada deskripsi.</p>`"
        ></article>
      </div>
    </section>

    <section v-if="project.ticket" class="rounded-3xl border border-blue-200 bg-blue-50/80 p-6 shadow-sm dark:border-blue-900/60 dark:bg-blue-950/40">
      <header class="mb-4 flex items-start justify-between">
        <div>
          <h2 class="text-lg font-semibold text-blue-900 dark:text-blue-200">Informasi Ticket</h2>
          <p class="text-sm text-blue-700/80 dark:text-blue-300/80">{{ project.ticket.title }}</p>
        </div>
        <StatusBadge :status="project.ticket.status" variant="status" size="sm" />
      </header>
      <dl class="grid gap-3 text-sm text-blue-900 dark:text-blue-100 sm:grid-cols-2">
        <div><dt class="font-semibold">Due Date</dt><dd>{{ project.ticket.due_display }}</dd></div>
        <div v-if="project.ticket.requester">
          <dt class="font-semibold">Requester</dt>
          <dd>
            {{ project.ticket.requester.name }}
            <span v-if="project.ticket.requester.email" class="block text-xs text-slate-400 dark:text-slate-500">{{ project.ticket.requester.email }}</span>
          </dd>
        </div>
      </dl>
      <div v-if="ticketAgent" class="mt-4">
        <div class="text-xs font-semibold uppercase text-blue-800/80 dark:text-blue-300/80">Agent</div>
        <div class="mt-2 flex flex-wrap gap-2">
          <TasksAssigneePill :name="ticketAgent.name" />
        </div>
      </div>
      <div v-if="ticketAssigned.length" class="mt-4">
        <div class="text-xs font-semibold uppercase text-blue-800/80 dark:text-blue-300/80">PIC</div>
        <div class="mt-2 flex flex-wrap gap-2">
          <TasksAssigneePill v-for="person in ticketAssigned" :key="person.id" :name="person.name" />
        </div>
      </div>
      <div v-if="project.ticket" class="mt-4 space-y-2">
        <div class="text-xs font-semibold uppercase text-blue-800/80 dark:text-blue-300/80">Ticket Attachments</div>
        <TicketAttachmentList :attachments="ticketAttachments" />
      </div>
    </section>

    <section v-if="relatedProjects.length" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
      <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <div>
          <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Daftar Project</h2>
          <p class="text-sm text-slate-500 dark:text-slate-300">Project lain yang terhubung ke ticket ini.</p>
        </div>
        <div class="flex flex-wrap gap-2 text-xs font-semibold">
          <span class="rounded-full bg-blue-100 px-3 py-1 text-blue-700 dark:bg-blue-500/10 dark:text-blue-100">Total {{ relatedSummary.total }}</span>
          <span class="rounded-full bg-amber-100 px-3 py-1 text-amber-700 dark:bg-amber-500/10 dark:text-amber-200">In Progress {{ relatedSummary.in_progress }}</span>
          <span class="rounded-full bg-emerald-100 px-3 py-1 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-200">Selesai {{ relatedSummary.done }}</span>
        </div>
      </div>

      <div class="mt-5 overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-700">
        <div class="overflow-x-auto">
          <table class="w-full min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
            <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800 dark:text-slate-300">
              <tr>
                <th class="px-4 py-3 text-left">#</th>
                <th class="px-4 py-3 text-left">Project</th>
                <th class="px-4 py-3 text-left">Status</th>
                <th class="px-4 py-3 text-left">Due</th>
                <th class="px-4 py-3 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-700 dark:bg-slate-900">
              <tr v-if="!relatedProjects.length">
                <td colspan="5" class="px-4 py-6 text-center text-slate-500 dark:text-slate-300">Belum ada project terkait ticket ini.</td>
              </tr>
              <tr v-for="(row, index) in relatedProjects" :key="row.id" class="transition hover:bg-slate-50 dark:hover:bg-slate-800/60">
                <td class="px-4 py-3">{{ index + 1 }}</td>
                <td class="px-4 py-3">
                  <div class="font-semibold text-slate-900 dark:text-slate-100">{{ row.title }}</div>
                  <div class="text-xs text-slate-500 dark:text-slate-300">{{ row.project_no || '—' }}</div>
                </td>
                <td class="px-4 py-3">
                  <StatusBadge :status="row.status" :label="row.status_label" size="sm" />
                </td>
                <td class="px-4 py-3 text-slate-600 dark:text-slate-200">{{ row.due_display || '—' }}</td>
                <td class="px-4 py-3">
                  <div class="flex items-center justify-end gap-3">
                    <Link v-if="row.links?.show" :href="row.links.show" class="text-sm text-blue-600 hover:underline dark:text-blue-400">Detail</Link>
                    <Link v-if="row.links?.edit" :href="row.links.edit" class="text-sm text-blue-600 hover:underline dark:text-blue-400">Edit</Link>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <section v-if="hasTeam" class="rounded-3xl border border-emerald-200 bg-emerald-50/80 p-6 shadow-sm dark:border-emerald-900/60 dark:bg-emerald-950/40">
      <h2 class="text-lg font-semibold text-emerald-900 dark:text-emerald-200">Tim Project</h2>
      <div class="mt-4 grid gap-6 md:grid-cols-2">
        <div v-if="teamAgents.length" class="team-group">
          <header class="team-group__header">
            <span class="team-group__title">Agent</span>
            <span class="team-group__hint">Penanggung jawab utama</span>
          </header>
          <div class="team-member-grid">
            <article v-for="agent in teamAgents" :key="`agent-${agent.id}`" class="team-card">
              <div class="team-card__head">
                <span class="team-card__name">{{ agent.name }}</span>
                <span v-if="agent.is_primary" class="team-chip">Utama</span>
              </div>
              <p class="team-card__role">{{ agent.position || '—' }}</p>
            </article>
          </div>
        </div>
        <div v-if="teamPics.length" class="team-group">
          <header class="team-group__header">
            <span class="team-group__title">PIC</span>
            <span class="team-group__hint">Pelaksana harian project</span>
          </header>
          <div class="team-member-grid">
            <article v-for="pic in teamPics" :key="`pic-${pic.id}`" class="team-card">
              <div class="team-card__head">
                <span class="team-card__name">{{ pic.name }}</span>
                <span v-if="pic.is_primary" class="team-chip team-chip--emerald">Utama</span>
              </div>
              <p class="team-card__role">{{ pic.position || '—' }}</p>
            </article>
          </div>
        </div>
      </div>
    </section>

    <section v-if="project.actions.length" class="rounded-3xl border border-indigo-200 bg-indigo-50/80 p-6 shadow-sm dark:border-indigo-900/60 dark:bg-indigo-950/40">
      <h2 class="text-lg font-semibold text-indigo-900 dark:text-indigo-200">Action Plan</h2>
      <div class="mt-4 space-y-4">
        <details v-for="action in project.actions" :key="action.id" class="overflow-hidden rounded-2xl border border-indigo-200 bg-white shadow-sm transition dark:border-indigo-800 dark:bg-indigo-900/60">
          <summary class="flex cursor-pointer items-center justify-between gap-4 px-4 py-3 text-sm text-indigo-900 dark:text-indigo-100">
            <div class="font-semibold">{{ action.title }}</div>
            <div class="flex items-center gap-3 text-xs text-indigo-700/80 dark:text-indigo-200/80">
              <span>Status: {{ action.status_id || '—' }}</span>
              <span>Progress: {{ action.progress ?? 0 }}%</span>
            </div>
          </summary>
          <div class="space-y-3 border-t border-indigo-100 px-4 py-4 text-sm text-slate-700 dark:border-indigo-800 dark:text-slate-200">
            <div class="grid gap-3 text-xs text-slate-500 dark:text-slate-300 sm:grid-cols-2">
              <div>Mulai: {{ action.start }}</div>
              <div>Selesai: {{ action.end }}</div>
            </div>
            <div v-if="action.description" class="rich-description rounded-xl bg-indigo-50/80 p-3 text-sm text-indigo-900 dark:bg-indigo-900/50 dark:text-indigo-100" v-html="action.description" />
            <div v-if="action.subactions?.length" class="space-y-2 border-l-2 border-indigo-200 pl-4 dark:border-indigo-800">
              <div class="text-xs font-semibold uppercase text-indigo-700/80 dark:text-indigo-200/80">Sub Aksi</div>
              <div v-for="sub in action.subactions" :key="sub.id" class="rounded-xl bg-white px-3 py-2 text-sm text-slate-700 shadow-sm dark:bg-slate-900/70 dark:text-slate-200">
                <div class="font-semibold text-slate-900 dark:text-slate-100">{{ sub.title }}</div>
                <div class="mt-1 grid gap-2 text-xs text-slate-500 dark:text-slate-300 sm:grid-cols-2">
                  <div>Status: {{ sub.status_id || '—' }}</div>
                  <div>Progress: {{ sub.progress ?? 0 }}%</div>
                  <div>Mulai: {{ sub.start }}</div>
                  <div>Selesai: {{ sub.end }}</div>
                </div>
                <div v-if="sub.description" class="rich-description mt-2 rounded-lg bg-slate-50 p-2 text-xs dark:bg-slate-900/60" v-html="sub.description" />
              </div>
            </div>
          </div>
        </details>
      </div>
    </section>

    <section v-if="project.costs.length" class="rounded-3xl border border-amber-200 bg-amber-50/80 p-6 shadow-sm dark:border-amber-900/60 dark:bg-amber-950/40">
      <h2 class="text-lg font-semibold text-amber-900 dark:text-amber-200">Rincian Biaya</h2>
      <div class="mt-4 overflow-x-auto">
        <table class="min-w-full divide-y divide-amber-200 text-sm text-amber-900 dark:divide-amber-800 dark:text-amber-100">
          <thead class="bg-amber-100/80 text-xs uppercase dark:bg-amber-900/60">
            <tr>
              <th class="px-3 py-2 text-left">Item</th>
              <th class="px-3 py-2 text-left">Kategori</th>
              <th class="px-3 py-2 text-right">Estimasi</th>
              <th class="px-3 py-2 text-right">Aktual</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-amber-100 dark:divide-amber-900">
            <tr v-for="cost in project.costs" :key="cost.id" class="bg-white dark:bg-amber-950/40">
              <td class="px-3 py-2">{{ cost.item || '—' }}</td>
              <td class="px-3 py-2">{{ cost.category || '—' }}</td>
              <td class="px-3 py-2 text-right">{{ formatCurrency(cost.estimated) }}</td>
              <td class="px-3 py-2 text-right">{{ formatCurrency(cost.actual) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <section v-if="project.risks.length" class="rounded-3xl border border-rose-200 bg-rose-50/80 p-6 shadow-sm dark:border-rose-900/60 dark:bg-rose-950/40">
      <h2 class="text-lg font-semibold text-rose-900 dark:text-rose-200">Mitigasi Risiko</h2>
      <div class="mt-4 grid gap-4 lg:grid-cols-2">
        <article v-for="risk in project.risks" :key="risk.id" class="rounded-2xl border border-rose-200 bg-white p-4 text-sm text-rose-900 shadow-sm dark:border-rose-800 dark:bg-rose-900/60 dark:text-rose-100">
          <h3 class="font-semibold">{{ risk.name }}</h3>
          <dl class="mt-2 space-y-1 text-xs">
            <div><dt class="font-semibold">Status</dt><dd>{{ risk.status_id || '—' }}</dd></div>
            <div><dt class="font-semibold">Dampak</dt><dd>{{ risk.impact || '—' }}</dd></div>
            <div><dt class="font-semibold">Kemungkinan</dt><dd>{{ risk.likelihood || '—' }}</dd></div>
          </dl>
          <div v-if="risk.description" class="mt-3 text-xs" v-html="risk.description" />
          <div v-if="risk.mitigation" class="mt-3 rounded-lg bg-rose-100/70 p-2 text-xs dark:bg-rose-900/40" v-html="risk.mitigation" />
        </article>
      </div>
    </section>

    <section v-if="project.deliverables.length" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
      <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Deliverables</h2>
      <div class="mt-4 grid gap-4 lg:grid-cols-2">
        <article v-for="deliverable in project.deliverables" :key="deliverable.id" class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700 shadow-sm dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-200">
          <header class="flex items-start justify-between">
            <h3 class="font-semibold text-slate-900 dark:text-slate-100">{{ deliverable.name }}</h3>
            <span class="rounded-full bg-slate-200 px-3 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ deliverable.status_id || '—' }}</span>
          </header>
          <dl class="mt-3 grid gap-2 text-xs text-slate-500 dark:text-slate-300 sm:grid-cols-2">
            <div><dt class="font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-200">Target</dt><dd>{{ deliverable.due || '—' }}</dd></div>
            <div><dt class="font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-200">Selesai</dt><dd>{{ deliverable.completed || '—' }}</dd></div>
            <div><dt class="font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-200">Verifikasi</dt><dd>{{ deliverable.verified || '—' }}</dd></div>
            <div><dt class="font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-200">Verifier</dt><dd>{{ deliverable.verified_by_label || deliverable.verified_by || '—' }}</dd></div>
          </dl>
          <div v-if="deliverable.description" class="rich-description mt-3" v-html="deliverable.description" />
        </article>
      </div>
    </section>

    <section v-if="project.attachments.length" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
      <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Lampiran Project</h2>
      <ul class="mt-4 space-y-2 text-sm">
        <li v-for="file in project.attachments" :key="file.id" class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 dark:border-slate-600 dark:bg-slate-800">
          <span class="text-slate-700 dark:text-slate-200">{{ file.name }}</span>
          <div class="flex items-center gap-3 text-xs">
            <span class="text-slate-400 dark:text-slate-500">{{ formatSize(file.size) }}</span>
            <a :href="file.view_url" target="_blank" class="text-blue-600 hover:underline dark:text-blue-400">Preview</a>
            <a :href="file.download_url" class="text-blue-600 hover:underline dark:text-blue-400">Download</a>
          </div>
        </li>
      </ul>
    </section>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import StatusBadge from '@/Components/StatusBadge.vue';
import TasksAssigneePill from '@/Components/TasksAssigneePill.vue';
import TicketAttachmentList from '@/Components/TicketAttachmentList.vue';

const props = defineProps({
  project: { type: Object, required: true },
  meta: { type: Object, default: () => ({}) },
});

const project = computed(() => props.project);

const relatedProjects = computed(() => project.value.related_projects || []);
const relatedSummary = computed(() => {
  const items = relatedProjects.value;
  let done = 0;
  let inProgress = 0;
  items.forEach(item => {
    const status = String(item.status || '').toLowerCase();
    if (status === 'done' || status === 'cancelled') {
      done += 1;
      return;
    }
    if (['in_progress', 'confirmation', 'revision', 'on_hold'].includes(status)) {
      inProgress += 1;
    }
  });

  return {
    total: items.length,
    in_progress: inProgress,
    done,
  };
});

const ticketAgent = computed(() => project.value.ticket?.assignee || null);
const ticketAssigned = computed(() => project.value.ticket?.assigned || []);
const ticketAttachments = computed(() => project.value.ticket?.attachments || []);
const fallbackTeam = computed(() => project.value.pics || []);

const teamAgents = computed(() => {
  if (project.value.team?.agents) {
    return project.value.team.agents;
  }

  return fallbackTeam.value.filter(member => (member.role_type ?? 'pic') === 'agent');
});

const teamPics = computed(() => {
  if (project.value.team?.pics) {
    return project.value.team.pics;
  }

  return fallbackTeam.value.filter(member => (member.role_type ?? 'pic') !== 'agent');
});

const hasTeam = computed(() => teamAgents.value.length || teamPics.value.length);

function formatCurrency(value) {
  if (value === null || value === undefined) {
    return '—';
  }
  try {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value);
  } catch (error) {
    return value;
  }
}

function formatSize(bytes) {
  if (!bytes) return '—';
  const units = ['B', 'KB', 'MB', 'GB'];
  let size = bytes;
  let unitIndex = 0;
  while (size >= 1024 && unitIndex < units.length - 1) {
    size /= 1024;
    unitIndex += 1;
  }
  return `${size.toFixed(unitIndex === 0 ? 0 : 1)} ${units[unitIndex]}`;
}
</script>

<style scoped>
.material-icons {
  font-size: inherit;
}

.rich-description :global(p) {
  margin-bottom: 0.6rem;
  color: inherit;
  line-height: 1.5;
}

.rich-description :global(p:last-child) {
  margin-bottom: 0;
}

.rich-description :global(ul),
.rich-description :global(ol) {
  padding-left: 1.25rem;
  margin-bottom: 0.6rem;
}

.rich-description :global(li) {
  margin-bottom: 0.25rem;
}

.rich-description :global(a) {
  color: inherit;
  text-decoration: none;
}

.team-group {
  border-radius: 1rem;
  border: 1px solid rgba(16, 185, 129, 0.35);
  background-color: rgba(255, 255, 255, 0.85);
  padding: 1rem;
}

.dark .team-group {
  background-color: rgba(4, 47, 46, 0.35);
  border-color: rgba(16, 185, 129, 0.4);
}

.team-group__header {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
  margin-bottom: 0.75rem;
}

.team-group__title {
  font-size: 0.9rem;
  font-weight: 600;
  color: #065f46;
}

.dark .team-group__title {
  color: #a7f3d0;
}

.team-group__hint {
  font-size: 0.8rem;
  color: rgba(5, 150, 105, 0.8);
}

.dark .team-group__hint {
  color: rgba(187, 247, 208, 0.8);
}

.team-member-grid {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.team-card {
  border-radius: 0.9rem;
  border: 1px solid rgba(16, 185, 129, 0.3);
  background-color: #fff;
  padding: 0.85rem 1rem;
  color: #065f46;
}

.dark .team-card {
  background-color: rgba(6, 95, 70, 0.25);
  border-color: rgba(16, 185, 129, 0.4);
  color: #ecfdf5;
}

.team-card__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
}

.team-card__name {
  font-weight: 600;
}

.team-card__role {
  margin-top: 0.3rem;
  font-size: 0.8rem;
  color: rgba(6, 95, 70, 0.75);
}

.dark .team-card__role {
  color: rgba(236, 253, 245, 0.75);
}

.team-chip {
  border-radius: 999px;
  padding: 0.1rem 0.65rem;
  font-size: 0.7rem;
  font-weight: 600;
  background-color: rgba(59, 130, 246, 0.15);
  color: #1d4ed8;
}

.team-chip--emerald {
  background-color: rgba(16, 185, 129, 0.15);
  color: #047857;
}
</style>
