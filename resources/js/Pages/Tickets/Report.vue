<template>
  <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 lg:px-6">
    <header class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div class="space-y-1.5">
        <div>
          <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">Ticket Report</h1>
          <p class="text-sm text-slate-500 dark:text-slate-300">Pantau tiket berdasarkan tipe task maupun project dengan tampilan yang rapi dan seragam.</p>
        </div>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <a :href="downloadUrl" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-blue-600 transition hover:bg-blue-50 dark:border-slate-600 dark:bg-slate-900 dark:text-blue-300 dark:hover:bg-slate-800">
          <span class="material-icons text-base">picture_as_pdf</span>
          Download PDF
        </a>
      </div>
    </header>

    <transition name="fade">
      <div v-if="flashSuccess" class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 dark:border-emerald-500/40 dark:bg-emerald-900/40 dark:text-emerald-100">
        {{ flashSuccess }}
      </div>
    </transition>

    <form class="grid gap-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900" @submit.prevent="applyFilters">
      <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <div>
          <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">Kata Kunci</label>
          <div class="relative mt-1">
            <span class="material-icons pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">search</span>
            <input
              v-model="form.q"
              type="search"
              placeholder="Judul, deskripsi, atau nomor ticket"
              class="w-full rounded-lg border border-slate-300 bg-white py-2 pl-10 pr-3 text-sm text-slate-700 focus:border-blue-500 focus:outline-none dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
            />
          </div>
        </div>
        <div>
          <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">Status</label>
          <div class="mt-1 w-full">
            <FancySelect v-model="form.status" :options="statusSelectOptions" accent="subtle" />
          </div>
        </div>
        <div>
          <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">Dari</label>
          <div class="mt-1">
            <DatePickerFlatpickr v-model="form.from" :config="calendarConfig" placeholder="dd/mm/yyyy" />
          </div>
        </div>
        <div>
          <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">Sampai</label>
          <div class="mt-1">
            <DatePickerFlatpickr v-model="form.to" :config="calendarConfig" placeholder="dd/mm/yyyy" />
          </div>
        </div>
      </div>
      <div class="flex flex-wrap justify-end gap-2">
        <button
          type="button"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-600 hover:bg-slate-100 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800"
          :disabled="submitting"
          @click="resetFilters"
        >
          Reset
        </button>
        <button
          type="submit"
          class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-blue-700 disabled:opacity-70"
          :disabled="submitting"
        >
          <span class="material-icons text-base">{{ submitting ? 'hourglass_top' : 'filter_alt' }}</span>
          Terapkan
        </button>
      </div>
    </form>

    <section class="space-y-4">
      <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
        <button
          type="button"
          class="flex w-full items-center justify-between gap-3 bg-blue-100 px-6 py-4 text-left text-blue-900 transition dark:bg-blue-900/30 dark:text-blue-100"
          :aria-expanded="accordion.task"
          @click="toggleSection('task')"
        >
          <div>
            <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Ticket Tipe Task</h2>
            <p class="text-sm text-slate-500 dark:text-slate-300">{{ taskSummary.total }} ticket · {{ taskSummary.in_progress }} in progress · {{ taskSummary.done }} selesai</p>
          </div>
          <span class="material-icons text-blue-700 transition-transform duration-200 dark:text-blue-300" :class="{ 'rotate-180': accordion.task }" aria-hidden="true">expand_more</span>
        </button>
        <transition name="accordion">
          <div v-show="accordion.task" class="border-t border-slate-200 bg-blue-50/70 py-5 dark:border-slate-700 dark:bg-blue-500/10 report-accordion__content">
            <div class="space-y-5 px-6">
              <div class="grid gap-3 sm:grid-cols-3">
                <div class="rounded-2xl border border-blue-100 bg-blue-50 px-4 py-3 dark:border-blue-500/40 dark:bg-blue-500/10">
                  <p class="text-xs font-semibold uppercase tracking-wide text-blue-600 dark:text-blue-200">Total</p>
                  <p class="mt-1 text-2xl font-semibold text-blue-800 dark:text-blue-100">{{ taskSummary.total }}</p>
                </div>
                <div class="rounded-2xl border border-amber-100 bg-amber-50 px-4 py-3 dark:border-amber-500/40 dark:bg-amber-500/10">
                  <p class="text-xs font-semibold uppercase tracking-wide text-amber-600 dark:text-amber-200">In Progress</p>
                  <p class="mt-1 text-2xl font-semibold text-amber-700 dark:text-amber-100">{{ taskSummary.in_progress }}</p>
                </div>
                <div class="rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 dark:border-emerald-500/40 dark:bg-emerald-500/10">
                  <p class="text-xs font-semibold uppercase tracking-wide text-emerald-600 dark:text-emerald-200">Completed</p>
                  <p class="mt-1 text-2xl font-semibold text-emerald-700 dark:text-emerald-100">{{ taskSummary.done }}</p>
                </div>
              </div>

              <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white px-0 pb-4 pt-3 mt-4 dark:border-slate-700 dark:bg-slate-900">
                <div class="overflow-x-auto">
                  <table class="report-table w-full min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800 dark:text-slate-300">
                      <tr>
                        <th class="px-4 py-3 text-left">#</th>
                        <th class="px-4 py-3 text-left">Ticket</th>
                        <th class="px-4 py-3 text-left">Judul</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-left">Prioritas</th>
                        <th class="px-4 py-3 text-left">Due</th>
                        <th class="px-4 py-3 text-left">Requester</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-700 dark:bg-slate-900">
                      <tr v-if="loadingTask">
                        <td colspan="8" class="px-4 py-6 text-center text-slate-500 dark:text-slate-300">Memuat ticket tipe task...</td>
                      </tr>
                      <tr v-else-if="!taskData">
                        <td colspan="8" class="px-4 py-6 text-center text-slate-500 dark:text-slate-300">Klik bagian ini untuk memuat data.</td>
                      </tr>
                      <tr v-else-if="!taskData.data.length">
                        <td colspan="8" class="px-4 py-6 text-center text-slate-500 dark:text-slate-300">Tidak ada ticket tipe task.</td>
                      </tr>
                      <template v-else>
                        <template v-for="(row, index) in taskData.data" :key="`task-row-${row.id}`">
                          <tr
                            :data-report-row-toggle="`task-${row.id}`"
                            tabindex="0"
                            :aria-expanded="isDetailOpen('task', row.id)"
                            class="cursor-pointer transition hover:bg-transparent dark:hover:bg-transparent"
                            @click="toggleDetail('task', row.id)"
                            @keydown.enter.prevent="toggleDetail('task', row.id)"
                            @keydown.space.prevent="toggleDetail('task', row.id)"
                          >
                            <td class="px-4 py-3">{{ taskRowNumber(index) }}</td>
                            <td class="px-4 py-3">
                              <div class="font-semibold text-slate-900 dark:text-slate-100">{{ row.ticket_no || '—' }}</div>
                              <div class="text-xs text-slate-500 dark:text-slate-300">Task Ticket</div>
                              <div class="text-[11px] text-slate-400 dark:text-slate-500">
                                Tasks: {{ row.tasks_count ?? row.tasks?.length ?? 0 }}
                                <span v-if="row.projects_count">· Projects: {{ row.projects_count }}</span>
                              </div>
                            </td>
                            <td class="px-4 py-3 font-semibold text-slate-900 dark:text-slate-100">{{ row.title }}</td>
                            <td class="px-4 py-3">
                              <StatusPill :status="row.status" :label="row.status_label" size="sm" />
                            </td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-200">{{ row.priority_label }}</td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-200">{{ row.due_display }}</td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-200">{{ row.requester?.name || '—' }}</td>
                            <td class="px-4 py-3">
                              <div class="flex items-center justify-end gap-3">
                                <Link :href="row.links.show" class="text-sm text-blue-600 hover:underline dark:text-blue-400">Detail</Link>
                                <Link v-if="row.links.edit" :href="row.links.edit" class="text-sm text-blue-600 hover:underline dark:text-blue-400">Edit</Link>
                                <button
                                  v-if="row.links.delete"
                                  type="button"
                                  class="inline-flex items-center gap-1 text-sm font-semibold text-red-600 hover:text-red-700 disabled:opacity-60 dark:text-red-300 dark:hover:text-red-200"
                                  :disabled="isDeleting('task', row.id)"
                                  @click.stop="destroyTicket(row, 'task')"
                                >
                                  <span class="material-icons text-base" aria-hidden="true">{{ isDeleting('task', row.id) ? 'hourglass_top' : 'delete' }}</span>
                                  Hapus
                                </button>
                              </div>
                            </td>
                          </tr>
                          <tr v-if="isDetailOpen('task', row.id)" class="bg-slate-50/70 dark:bg-slate-800/40" :data-report-row="`task-${row.id}`">
                            <td colspan="8" class="px-4 py-6 sm:px-6">
                              <div class="grid gap-6 lg:grid-cols-[2fr,1fr]">
                                <div class="space-y-4">
                                  <section v-if="row.description" class="rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-600 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                                    <h3 class="mb-2 text-sm font-semibold text-slate-800 dark:text-slate-100">Deskripsi</h3>
                                    <div class="space-y-2 leading-relaxed" v-html="row.description"></div>
                                  </section>
                                  <section class="rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-600 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                                    <h3 class="mb-2 text-sm font-semibold text-slate-800 dark:text-slate-100">Informasi Administratif</h3>
                                    <dl class="space-y-1">
                                      <div class="flex justify-between text-xs sm:text-sm">
                                        <dt class="text-slate-500 dark:text-slate-400">Reason</dt>
                                        <dd class="font-semibold text-slate-900 dark:text-slate-100">{{ row.reason || '—' }}</dd>
                                      </div>
                                      <div class="flex justify-between text-xs sm:text-sm">
                                        <dt class="text-slate-500 dark:text-slate-400">Nomor Surat</dt>
                                        <dd class="font-semibold text-slate-900 dark:text-slate-100">{{ row.letter_no || '—' }}</dd>
                                      </div>
                                      <div class="flex justify-between text-xs sm:text-sm">
                                        <dt class="text-slate-500 dark:text-slate-400">Status ID</dt>
                                        <dd class="font-semibold text-slate-900 dark:text-slate-100">{{ statusIdDisplay(row) }}</dd>
                                      </div>
                                      <div class="flex justify-between text-xs sm:text-sm">
                                        <dt class="text-slate-500 dark:text-slate-400">SLA</dt>
                                        <dd class="font-semibold text-slate-900 dark:text-slate-100">{{ formatSla(row.sla) }}</dd>
                                      </div>
                                      <div class="flex justify-between text-xs sm:text-sm">
                                        <dt class="text-slate-500 dark:text-slate-400">Target Selesai</dt>
                                        <dd class="font-semibold text-slate-900 dark:text-slate-100">{{ row.finish_display || '—' }}</dd>
                                      </div>
                                    </dl>
                                  </section>
                                  <section v-if="row.assigned_users?.length" class="rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-600 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                                    <h3 class="mb-2 text-sm font-semibold text-slate-800 dark:text-slate-100">Assigned Users</h3>
                                    <ul class="space-y-1">
                                      <li v-for="user in row.assigned_users" :key="user.id" class="flex flex-col rounded-lg border border-slate-100 bg-slate-50/70 px-3 py-2 text-xs shadow-sm dark:border-slate-600 dark:bg-slate-800/40">
                                        <span class="font-semibold text-slate-900 dark:text-slate-100">{{ user.name || `User #${user.id}` }}</span>
                                        <span v-if="user.email" class="text-[11px] text-slate-500 dark:text-slate-300">{{ user.email }}</span>
                                      </li>
                                    </ul>
                                  </section>
                                  <section v-if="row.tasks?.length" class="rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-600 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                                    <h3 class="mb-2 text-sm font-semibold text-slate-800 dark:text-slate-100">Task Terkait</h3>
                                    <ul class="space-y-2">
                                      <li
                                        v-for="task in row.tasks"
                                        :key="task.id"
                                        class="rounded-lg border border-slate-200 bg-slate-50/70 p-3 text-xs shadow-sm dark:border-slate-700 dark:bg-slate-800/60"
                                      >
                                        <div class="flex items-center justify-between">
                                          <span class="font-semibold text-slate-900 dark:text-slate-100">{{ task.title }}</span>
                                          <StatusPill :status="task.status" :label="task.status_label" size="sm" />
                                        </div>
                                        <p v-if="task.assignee" class="mt-1 text-slate-500 dark:text-slate-300">Assignee: {{ task.assignee }}</p>
                                        <p class="text-slate-500 dark:text-slate-300">Due: {{ task.due_display }}</p>
                                      </li>
                                    </ul>
                                  </section>
                                  <section v-if="row.attachments?.length" class="rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-600 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                                    <h3 class="mb-2 text-sm font-semibold text-slate-800 dark:text-slate-100">Lampiran</h3>
                                    <ul class="space-y-1">
                                      <li v-for="attachment in attachmentsPreview(row).items" :key="attachment.id">
                                        <a :href="attachment.view_url" target="_blank" class="text-blue-600 hover:underline dark:text-blue-300">{{ attachment.name }}</a>
                                      </li>
                                      <li v-if="attachmentsPreview(row).remaining">… dan {{ attachmentsPreview(row).remaining }} lagi</li>
                                    </ul>
                                  </section>
                                </div>
                                <aside class="space-y-3">
                                  <div
                                    v-for="meta in taskMeta(row)"
                                    :key="meta.label"
                                    class="rounded-xl border border-slate-200 bg-white p-4 text-sm shadow-sm dark:border-slate-700 dark:bg-slate-900"
                                  >
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">{{ meta.label }}</p>
                                    <p class="mt-1 font-semibold text-slate-900 dark:text-slate-100">{{ meta.value }}</p>
                                  </div>
                                </aside>
                              </div>
                            </td>
                          </tr>
                        </template>
                      </template>
                    </tbody>
                  </table>
                </div>

                <nav
                  v-if="taskData?.links?.length > 3"
                  class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900"
                >
                  <button
                    v-for="link in taskData.links"
                    :key="`task-link-${link.label}`"
                    type="button"
                    class="rounded-lg border px-3 py-1.5 text-sm transition"
                    :class="
                      link.active
                        ? 'border-blue-500 bg-blue-500 text-white shadow-sm'
                        : link.url
                          ? 'border-slate-300 bg-white text-slate-600 hover:bg-slate-100 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800'
                          : 'border-slate-200 bg-slate-100 text-slate-400 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-500'
                    "
                    :disabled="!link.url"
                    @click="changeTaskPage(link)"
                    v-html="link.label"
                  />
                </nav>
              </div>
            </div>
          </div>
        </transition>
      </div>

      <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
        <button
          type="button"
          class="flex w-full items-center justify-between gap-3 bg-purple-100 px-6 py-4 text-left text-purple-900 transition dark:bg-purple-900/30 dark:text-purple-100"
          :aria-expanded="accordion.project"
          @click="toggleSection('project')"
        >
          <div>
            <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Ticket Tipe Project</h2>
            <p class="text-sm text-slate-500 dark:text-slate-300">{{ projectSummary.total }} ticket · {{ projectSummary.in_progress }} in progress · {{ projectSummary.done }} selesai</p>
          </div>
          <span class="material-icons text-purple-700 transition-transform duration-200 dark:text-purple-300" :class="{ 'rotate-180': accordion.project }" aria-hidden="true">expand_more</span>
        </button>
        <transition name="accordion">
          <div v-show="accordion.project" class="border-t border-slate-200 bg-purple-50/70 py-5 dark:border-slate-700 dark:bg-purple-500/10 report-accordion__content">
            <div class="space-y-5 px-6">
              <div class="grid gap-3 sm:grid-cols-3">
                <div class="rounded-2xl border border-purple-100 bg-purple-50 px-4 py-3 dark:border-purple-500/40 dark:bg-purple-500/10">
                  <p class="text-xs font-semibold uppercase tracking-wide text-purple-600 dark:text-purple-200">Total</p>
                  <p class="mt-1 text-2xl font-semibold text-purple-700 dark:text-purple-100">{{ projectSummary.total }}</p>
                </div>
                <div class="rounded-2xl border border-amber-100 bg-amber-50 px-4 py-3 dark:border-amber-500/40 dark:bg-amber-500/10">
                  <p class="text-xs font-semibold uppercase tracking-wide text-amber-600 dark:text-amber-200">In Progress</p>
                  <p class="mt-1 text-2xl font-semibold text-amber-700 dark:text-amber-100">{{ projectSummary.in_progress }}</p>
                </div>
                <div class="rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 dark:border-emerald-500/40 dark:bg-emerald-500/10">
                  <p class="text-xs font-semibold uppercase tracking-wide text-emerald-600 dark:text-emerald-200">Completed</p>
                  <p class="mt-1 text-2xl font-semibold text-emerald-700 dark:text-emerald-100">{{ projectSummary.done }}</p>
                </div>
              </div>

              <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white px-0 pb-4 pt-3 mt-4 dark:border-slate-700 dark:bg-slate-900">
                <div class="overflow-x-auto">
                  <table class="report-table w-full min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800 dark:text-slate-300">
                      <tr>
                        <th class="px-4 py-3 text-left">#</th>
                        <th class="px-4 py-3 text-left">Ticket</th>
                        <th class="px-4 py-3 text-left">Judul</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-left">Prioritas</th>
                        <th class="px-4 py-3 text-left">Due</th>
                        <th class="px-4 py-3 text-left">Project</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-700 dark:bg-slate-900">
                      <tr v-if="loadingProject">
                        <td colspan="8" class="px-4 py-6 text-center text-slate-500 dark:text-slate-300">Memuat ticket tipe project...</td>
                      </tr>
                      <tr v-else-if="!projectData">
                        <td colspan="8" class="px-4 py-6 text-center text-slate-500 dark:text-slate-300">Klik bagian ini untuk memuat data.</td>
                      </tr>
                      <tr v-else-if="!projectData.data.length">
                        <td colspan="8" class="px-4 py-6 text-center text-slate-500 dark:text-slate-300">Tidak ada ticket tipe project.</td>
                      </tr>
                      <template v-else>
                        <template v-for="(row, index) in projectData.data" :key="`project-row-${row.id}`">
                          <tr
                            :data-report-row-toggle="`project-${row.id}`"
                            tabindex="0"
                            :aria-expanded="isDetailOpen('project', row.id)"
                            class="cursor-pointer transition hover:bg-transparent dark:hover:bg-transparent"
                            @click="toggleDetail('project', row.id)"
                            @keydown.enter.prevent="toggleDetail('project', row.id)"
                            @keydown.space.prevent="toggleDetail('project', row.id)"
                          >
                            <td class="px-4 py-3">{{ projectRowNumber(index) }}</td>
                            <td class="px-4 py-3">
                              <div class="font-semibold text-slate-900 dark:text-slate-100">{{ row.ticket_no || '—' }}</div>
                              <div class="text-xs text-slate-500 dark:text-slate-300">Project Ticket</div>
                            </td>
                            <td class="px-4 py-3 font-semibold text-slate-900 dark:text-slate-100">{{ row.title }}</td>
                            <td class="px-4 py-3">
                              <StatusPill :status="row.status" :label="row.status_label" size="sm" />
                            </td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-200">{{ row.priority_label }}</td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-200">{{ row.due_display }}</td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-200">{{ row.project?.project_no || '—' }}</td>
                            <td class="px-4 py-3">
                              <div class="flex items-center justify-end gap-3">
                                <Link :href="row.links.show" class="text-sm text-blue-600 hover:underline dark:text-blue-400">Detail</Link>
                                <Link v-if="row.links.edit" :href="row.links.edit" class="text-sm text-blue-600 hover:underline dark:text-blue-400">Edit</Link>
                                <button
                                  v-if="row.links.delete"
                                  type="button"
                                  class="inline-flex items-center gap-1 text-sm font-semibold text-red-600 hover:text-red-700 disabled:opacity-60 dark:text-red-300 dark:hover:text-red-200"
                                  :disabled="isDeleting('project', row.id)"
                                  @click.stop="destroyTicket(row, 'project')"
                                >
                                  <span class="material-icons text-base" aria-hidden="true">{{ isDeleting('project', row.id) ? 'hourglass_top' : 'delete' }}</span>
                                  Hapus
                                </button>
                              </div>
                            </td>
                          </tr>
                          <tr v-if="isDetailOpen('project', row.id)" class="bg-slate-50/70 dark:bg-slate-800/40" :data-report-row="`project-${row.id}`">
                            <td colspan="8" class="px-4 py-6 sm:px-6">
                              <div class="grid gap-6 lg:grid-cols-[2fr,1fr]">
                                <div class="space-y-4">
                                  <section v-if="row.description" class="rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-600 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                                    <h3 class="mb-2 text-sm font-semibold text-slate-800 dark:text-slate-100">Deskripsi</h3>
                                    <div class="space-y-2 leading-relaxed" v-html="row.description"></div>
                                  </section>
                                  <section class="rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-600 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                                    <h3 class="mb-2 text-sm font-semibold text-slate-800 dark:text-slate-100">Informasi Administratif</h3>
                                    <dl class="space-y-1">
                                      <div class="flex justify-between text-xs sm:text-sm">
                                        <dt class="text-slate-500 dark:text-slate-400">Reason</dt>
                                        <dd class="font-semibold text-slate-900 dark:text-slate-100">{{ row.reason || '—' }}</dd>
                                      </div>
                                      <div class="flex justify-between text-xs sm:text-sm">
                                        <dt class="text-slate-500 dark:text-slate-400">Nomor Surat</dt>
                                        <dd class="font-semibold text-slate-900 dark:text-slate-100">{{ row.letter_no || '—' }}</dd>
                                      </div>
                                      <div class="flex justify-between text-xs sm:text-sm">
                                        <dt class="text-slate-500 dark:text-slate-400">Status ID</dt>
                                        <dd class="font-semibold text-slate-900 dark:text-slate-100">{{ statusIdDisplay(row) }}</dd>
                                      </div>
                                      <div class="flex justify-between text-xs sm:text-sm">
                                        <dt class="text-slate-500 dark:text-slate-400">SLA</dt>
                                        <dd class="font-semibold text-slate-900 dark:text-slate-100">{{ formatSla(row.sla) }}</dd>
                                      </div>
                                      <div class="flex justify-between text-xs sm:text-sm">
                                        <dt class="text-slate-500 dark:text-slate-400">Target Selesai</dt>
                                        <dd class="font-semibold text-slate-900 dark:text-slate-100">{{ row.finish_display || '—' }}</dd>
                                      </div>
                                    </dl>
                                  </section>
                                  <section v-if="row.assigned_users?.length" class="rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-600 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                                    <h3 class="mb-2 text-sm font-semibold text-slate-800 dark:text-slate-100">Assigned Users</h3>
                                    <ul class="space-y-1">
                                      <li v-for="user in row.assigned_users" :key="user.id" class="flex flex-col rounded-lg border border-slate-100 bg-slate-50/70 px-3 py-2 text-xs shadow-sm dark:border-slate-600 dark:bg-slate-800/40">
                                        <span class="font-semibold text-slate-900 dark:text-slate-100">{{ user.name || `User #${user.id}` }}</span>
                                        <span v-if="user.email" class="text-[11px] text-slate-500 dark:text-slate-300">{{ user.email }}</span>
                                      </li>
                                    </ul>
                                  </section>
                                  <section class="rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-600 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                                    <h3 class="mb-2 text-sm font-semibold text-slate-800 dark:text-slate-100">Info Project</h3>
                                    <div v-if="row.project" class="space-y-1 text-sm text-slate-600 dark:text-slate-200">
                                      <p><strong>Project No:</strong> {{ row.project.project_no || '-' }}</p>
                                      <p><strong>Status:</strong>
                                        <StatusPill :status="row.project.status" :label="row.project.status_label" size="sm" />
                                      </p>
                                      <p><strong>Start:</strong> {{ row.project.start_display || '—' }}</p>
                                      <p><strong>End:</strong> {{ row.project.end_display || '—' }}</p>
                                    </div>
                                    <p v-else class="text-xs text-slate-500 dark:text-slate-300">Project belum tersedia.</p>
                                  </section>
                                  <section v-if="row.attachments?.length" class="rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-600 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                                    <h3 class="mb-2 text-sm font-semibold text-slate-800 dark:text-slate-100">Lampiran</h3>
                                    <ul class="space-y-1">
                                      <li v-for="attachment in attachmentsPreview(row).items" :key="attachment.id">
                                        <a :href="attachment.view_url" target="_blank" class="text-blue-600 hover:underline dark:text-blue-300">{{ attachment.name }}</a>
                                      </li>
                                      <li v-if="attachmentsPreview(row).remaining">… dan {{ attachmentsPreview(row).remaining }} lagi</li>
                                    </ul>
                                  </section>
                                </div>
                                <aside class="space-y-3">
                                  <div
                                    v-for="meta in projectMeta(row)"
                                    :key="meta.label"
                                    class="rounded-xl border border-slate-200 bg-white p-4 text-sm shadow-sm dark:border-slate-700 dark:bg-slate-900"
                                  >
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">{{ meta.label }}</p>
                                    <p class="mt-1 font-semibold text-slate-900 dark:text-slate-100">{{ meta.value }}</p>
                                  </div>
                                </aside>
                              </div>
                            </td>
                          </tr>
                        </template>
                      </template>
                    </tbody>
                  </table>
                </div>

                <nav
                  v-if="projectData?.links?.length > 3"
                  class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900"
                >
                  <button
                    v-for="link in projectData.links"
                    :key="`project-link-${link.label}`"
                    type="button"
                    class="rounded-lg border px-3 py-1.5 text-sm transition"
                    :class="
                      link.active
                        ? 'border-purple-500 bg-purple-500 text-white shadow-sm'
                        : link.url
                          ? 'border-slate-300 bg-white text-slate-600 hover:bg-slate-100 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800'
                          : 'border-slate-200 bg-slate-100 text-slate-400 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-500'
                    "
                    :disabled="!link.url"
                    @click="changeProjectPage(link)"
                    v-html="link.label"
                  />
                </nav>
              </div>
            </div>
          </div>
        </transition>
      </div>
    </section>
  </div>
</template>

<style scoped>
.accordion-enter-active,
.accordion-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
  transform-origin: top;
}

.accordion-enter-from,
.accordion-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}

.report-accordion__content {
  padding: 0 1.5rem 1.5rem;
  display: grid;
  gap: 1.25rem;
  border-top: 1px solid rgba(15, 23, 42, 0.06);
  background: rgba(255, 255, 255, 0.65);
}

.dark .report-accordion__content {
  background: rgba(15, 23, 42, 0.6);
  border-top-color: rgba(148, 163, 184, 0.18);
}

.report-table tbody tr {
  background: transparent;
  transition: background 0.15s ease;
}

.report-table tbody tr:hover,
.report-table tbody tr:focus,
.report-table tbody tr:focus-within {
  background: rgba(148, 163, 184, 0.12);
}
</style>

<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import DatePickerFlatpickr from '@/Components/DatePickerFlatpickr.vue';
import FancySelect from '@/Components/FancySelect.vue';
import StatusPill from '@/Components/StatusPill.vue';

const props = defineProps({
  filters: { type: Object, default: () => ({}) },
  statusOptions: { type: Array, default: () => [] },
  taskSummary: { type: Object, default: () => ({ total: 0, in_progress: 0, done: 0 }) },
  projectSummary: { type: Object, default: () => ({ total: 0, in_progress: 0, done: 0 }) },
  taskTickets: { type: [Object, null], default: null },
  projectTickets: { type: [Object, null], default: null },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success ?? null);

const deleting = reactive({ scope: null, id: null });

const statusSelectOptions = computed(() => [
  { value: '', label: 'Semua Status' },
  ...(Array.isArray(props.statusOptions) ? props.statusOptions : []),
]);

const form = reactive({
  q: props.filters.q || '',
  status: props.filters.status || '',
  from: props.filters.from || '',
  to: props.filters.to || '',
});

watch(
  () => props.filters,
  value => {
    form.q = value?.q || '';
    form.status = value?.status || '';
    form.from = value?.from || '';
    form.to = value?.to || '';
  }
);

const calendarConfig = {
  dateFormat: 'd/m/Y',
  allowInput: true,
};

const accordion = reactive({
  task: false,
  project: false,
});

const taskData = ref(props.taskTickets || null);
const projectData = ref(props.projectTickets || null);
const loadingTask = ref(false);
const loadingProject = ref(false);
const submitting = ref(false);

const localePrefix = () => {
  if (typeof window === 'undefined') return '';
  const match = window.location.pathname.match(/^\/(en|id)\b/);
  return match ? `/${match[1]}` : '';
};

const downloadUrl = computed(() => buildDownloadUrl(`${localePrefix()}/dashboard/tickets/report/download`, cleanPayload(props.filters || {})));

const openDetails = reactive({
  task: {},
  project: {},
});

watch(
  () => props.taskTickets,
  value => {
    if (value !== undefined) {
      taskData.value = value;
      loadingTask.value = false;
      openDetails.task = {};
    }
  }
);

watch(
  () => props.projectTickets,
  value => {
    if (value !== undefined) {
      projectData.value = value;
      loadingProject.value = false;
      openDetails.project = {};
    }
  }
);

watch(
  () => accordion.task,
  open => {
    if (open && !taskData.value) {
      loadTaskTickets();
    }
  }
);

watch(
  () => accordion.project,
  open => {
    if (open && !projectData.value) {
      loadProjectTickets();
    }
  }
);

function buildRoute() {
  return `${localePrefix()}/dashboard/tickets/report`;
}

function cleanPayload(payload) {
  const data = {};
  if (payload.q) data.q = payload.q;
  if (payload.status) data.status = payload.status;
  if (payload.from) data.from = payload.from;
  if (payload.to) data.to = payload.to;
  return data;
}

function buildDownloadUrl(base, payload = {}) {
  const searchable = Object.fromEntries(
    Object.entries(payload).filter(([, value]) => value !== undefined && value !== null && value !== '')
  );
  const query = new URLSearchParams(searchable).toString();
  return query ? `${base}?${query}` : base;
}

function toggleSection(scope) {
  accordion[scope] = !accordion[scope];
  if (!accordion[scope]) {
    openDetails[scope] = {};
  } else if (scope === 'task' && !taskData.value) {
    loadTaskTickets();
  } else if (scope === 'project' && !projectData.value) {
    loadProjectTickets();
  }
}

function applyFilters() {
  if (submitting.value) return;
  submitting.value = true;

  const payload = cleanPayload(form);

  router.get(buildRoute(), payload, {
    preserveScroll: true,
    replace: true,
    onStart: () => {
      taskData.value = null;
      projectData.value = null;
      loadingTask.value = accordion.task;
      loadingProject.value = accordion.project;
      openDetails.task = {};
      openDetails.project = {};
    },
    onFinish: () => {
      submitting.value = false;
    },
  });
}

function resetFilters() {
  if (submitting.value) return;
  form.q = '';
  form.status = '';
  form.from = '';
  form.to = '';
  applyFilters();
}

function toggleDetail(scope, id) {
  openDetails[scope][id] = !openDetails[scope][id];
}

function isDetailOpen(scope, id) {
  return !!openDetails[scope][id];
}

function taskRowNumber(index) {
  if (!taskData.value?.meta) return index + 1;
  return (taskData.value.meta.current_page - 1) * taskData.value.meta.per_page + index + 1;
}

function projectRowNumber(index) {
  if (!projectData.value?.meta) return index + 1;
  return (projectData.value.meta.current_page - 1) * projectData.value.meta.per_page + index + 1;
}

function attachmentsPreview(row) {
  const items = Array.isArray(row.attachments) ? row.attachments : [];
  return {
    items: items.slice(0, 5),
    remaining: items.length > 5 ? items.length - 5 : 0,
  };
}

function extractPage(url) {
  try {
    const searchParams = new URL(url, window.location.origin).searchParams;
    return searchParams.get('page') || searchParams.get('task_page') || searchParams.get('project_page');
  } catch (error) {
    return null;
  }
}

function changeTaskPage(link) {
  if (!link.url || link.active) return;
  loadingTask.value = true;
  const payload = cleanPayload(form);
  const page = extractPage(link.url);
  if (page) payload.task_page = page;

  router.get(buildRoute(), payload, {
    only: ['taskTickets', 'taskSummary'],
    preserveState: true,
    preserveScroll: true,
    replace: true,
    onFinish: () => {
      loadingTask.value = false;
    },
  });
}

function changeProjectPage(link) {
  if (!link.url || link.active) return;
  loadingProject.value = true;
  const payload = cleanPayload(form);
  const page = extractPage(link.url);
  if (page) payload.project_page = page;

  router.get(buildRoute(), payload, {
    only: ['projectTickets', 'projectSummary'],
    preserveState: true,
    preserveScroll: true,
    replace: true,
    onFinish: () => {
      loadingProject.value = false;
    },
  });
}

function loadTaskTickets() {
  if (loadingTask.value) return;
  loadingTask.value = true;
  const payload = cleanPayload(form);

  router.get(buildRoute(), payload, {
    only: ['taskTickets', 'taskSummary'],
    preserveState: true,
    preserveScroll: true,
    replace: true,
    onFinish: () => {
      loadingTask.value = false;
    },
  });
}

function loadProjectTickets() {
  if (loadingProject.value) return;
  loadingProject.value = true;
  const payload = cleanPayload(form);

  router.get(buildRoute(), payload, {
    only: ['projectTickets', 'projectSummary'],
    preserveState: true,
    preserveScroll: true,
    replace: true,
    onFinish: () => {
      loadingProject.value = false;
    },
  });
}

function destroyTicket(row, scope) {
  if (!row.links?.delete) return;
  if (!window.confirm(`Yakin ingin menghapus ticket "${row.title}"?`)) return;

  deleting.scope = scope;
  deleting.id = row.id;

  router.delete(row.links.delete, {
    preserveScroll: true,
    onFinish: () => {
      deleting.scope = null;
      deleting.id = null;
    },
  });
}

function isDeleting(scope, id) {
  return deleting.scope === scope && deleting.id === id;
}

function statusIdDisplay(row) {
  if (!row?.status_id) return '—';
  return row.status_id_label ? `${row.status_id} (${row.status_id_label})` : row.status_id;
}

function formatSla(value) {
  if (!value) return '—';
  return value.replace(/_/g, ' ').replace(/\b\w/g, char => char.toUpperCase());
}

function taskMeta(row) {
  return [
    { label: 'Ticket No', value: row.ticket_no || '—' },
    { label: 'Status ID', value: statusIdDisplay(row) },
    { label: 'Prioritas', value: row.priority_label || '—' },
    { label: 'Requester', value: row.requester?.name || '—' },
    { label: 'Agent', value: row.agent?.name || '—' },
    { label: 'Assignee', value: row.assignee?.name || '—' },
    { label: 'SLA', value: formatSla(row.sla) },
    { label: 'Due', value: row.due_display || '—' },
    { label: 'Selesai', value: row.finish_display || '—' },
  ];
}

function projectMeta(row) {
  return [
    { label: 'Ticket No', value: row.ticket_no || '—' },
    { label: 'Status ID', value: statusIdDisplay(row) },
    { label: 'Prioritas', value: row.priority_label || '—' },
    { label: 'Requester', value: row.requester?.name || '—' },
    { label: 'Agent', value: row.agent?.name || '—' },
    { label: 'Project', value: row.project?.project_no || '—' },
    { label: 'SLA', value: formatSla(row.sla) },
    { label: 'Due', value: row.due_display || '—' },
    { label: 'Selesai', value: row.finish_display || '—' },
  ];
}
</script>
