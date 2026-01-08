<template>
  <div class="mx-auto max-w-6xl space-y-6 px-4 py-8 pb-28 lg:px-6">
    <header class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-3xl font-semibold text-slate-900 dark:text-slate-100">Edit Task</h1>
        <p class="text-sm text-slate-500 dark:text-slate-300">Perbarui informasi task, penanggung jawab, dan lampiran.</p>
      </div>
      <Link
        :href="taskShowUrl"
        class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-4 py-2 text-sm text-slate-600 transition hover:bg-slate-100 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800"
      >
        <span class="material-icons text-base">visibility</span>
        Lihat Task
      </Link>
    </header>

    <form class="space-y-6" @submit.prevent="submit">
      <section class="rounded-3xl border border-slate-200 bg-white/90 p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
        <h2 class="section-title">Informasi Utama</h2>
        <div class="grid gap-4 md:grid-cols-2">
          <div>
            <label class="field-label" for="title">Judul Task</label>
            <input id="title" v-model="form.title" type="text" class="field-input" />
            <p v-if="form.errors.title" class="field-error">{{ form.errors.title }}</p>
          </div>
          <div>
            <label class="field-label">Status</label>
            <FancySelect v-model="form.status" :options="statusDropdownOptions" :disabled="statusLocked" />
            <p v-if="form.errors.status" class="field-error">{{ form.errors.status }}</p>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Status awal: {{ statusDefaultLabel }}.</p>
            <ul v-if="statusGuide" class="mt-1 list-disc space-y-0.5 pl-5 text-xs text-slate-500 dark:text-slate-400">
              <li>{{ statusGuide.agent }}</li>
              <li>{{ statusGuide.requester }}</li>
              <li>{{ statusGuide.admin }}</li>
            </ul>
          </div>
          <div>
            <label class="field-label">Prioritas</label>
            <FancySelect v-model="form.priority" :options="priorityOptions" />
            <p v-if="form.errors.priority" class="field-error">{{ form.errors.priority }}</p>
          </div>
          <div class="panel-field panel-field--full ticket-linker overflow-visible" ref="ticketCardRef">
            <label class="field-label">Ticket Terkait</label>
            <div class="relative z-30 ticket-card overflow-visible rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
              <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                  <p class="text-sm font-semibold text-slate-700 dark:text-slate-100">Hubungkan ke Ticket Aktif</p>
                  <p class="text-xs text-slate-400">Pilih ticket sesuai unit.</p>
                </div>
                <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 dark:text-slate-400">
                  <span>{{ selectedTicketOption ? '1 ticket terpilih' : 'Belum ada pilihan' }}</span>
                  <button
                    v-if="selectedTicketOption"
                    type="button"
                    class="rounded-full border border-rose-200 px-2 py-1 text-[11px] font-semibold uppercase tracking-wide text-rose-600 transition hover:bg-rose-50 dark:border-rose-500/40 dark:text-rose-200 dark:hover:bg-rose-500/10"
                    @click="clearTicketSelection"
                  >
                    Hapus
                  </button>
                </div>
              </div>
              <div class="relative mt-3 w-full overflow-visible">
                <button
                  type="button"
                  class="flex w-full items-center justify-between rounded-xl border border-slate-200 bg-slate-50/90 px-4 py-3 text-left text-sm font-semibold text-slate-700 transition hover:border-blue-400 dark:border-slate-700 dark:bg-slate-800/80 dark:text-slate-100"
                  :class="ticketDropdownOpen ? 'rounded-b-none border-blue-400' : ''"
                  @click="toggleTicketDropdown"
                >
                  <div class="flex flex-1 flex-wrap gap-1.5">
                    <span
                      v-if="selectedTicketOption"
                      class="inline-flex items-center gap-2 rounded-lg border border-indigo-200/70 bg-white px-3 py-1 text-xs font-medium text-indigo-700 dark:border-indigo-500/40 dark:bg-slate-900/70 dark:text-indigo-200"
                    >
                      <span class="font-semibold">{{ selectedTicketOption.ticket_no }}</span>
                      <span class="max-w-[160px] truncate text-[11px] text-slate-400">{{ selectedTicketOption.title || 'Tanpa judul' }}</span>
                      <span class="rounded-full border border-indigo-100 bg-indigo-50 px-2 py-0.5 text-[10px] uppercase tracking-wide text-indigo-600 dark:border-indigo-400/40 dark:bg-transparent">{{ selectedTicketOption.unit || ticketUnitPlaceholder }}</span>
                    </span>
                    <span v-else class="text-xs font-normal text-slate-400">Pilih ticket aktif sesuai unit Anda</span>
                  </div>
                  <span class="material-icons text-base text-slate-400 transition duration-200" :class="ticketDropdownOpen ? 'rotate-180 text-blue-500' : ''">expand_more</span>
                </button>
                <transition name="fade-scale">
                  <div
                    v-if="ticketDropdownOpen"
                    class="absolute left-0 right-0 top-full z-[9999] w-full rounded-xl rounded-t-none border border-slate-200 bg-white p-3 shadow-2xl md:p-4 dark:border-slate-700 dark:bg-slate-900 mt-2"
                    :class="ticketDropdownOpen ? 'border-blue-400' : ''"
                    style="box-shadow: 0 28px 50px rgba(15,23,42,0.28), 0 10px 24px rgba(59,130,246,0.16);"
                  >
                    <div class="unit-chips flex flex-wrap gap-1 border-b border-slate-100 pb-2 dark:border-slate-800">
                      <button
                        v-for="unit in ticketUnits"
                        :key="`ticket-unit-${unit}`"
                        type="button"
                        class="rounded-full border px-3 py-1.5 text-xs font-semibold transition"
                        :class="activeTicketUnit === unit ? 'border-indigo-500 bg-indigo-50 text-indigo-700 dark:border-indigo-400 dark:bg-indigo-500/10 dark:text-indigo-200' : 'border-slate-200 text-slate-500 hover:border-indigo-300 hover:text-indigo-600 dark:border-slate-700 dark:text-slate-300'"
                        @click="activeTicketUnit = unit"
                      >
                        {{ unit }}
                      </button>
                    </div>
                    <div class="mt-2 space-y-1.5 overflow-y-auto pr-1" style="height: 10rem; max-height: 10rem; scrollbar-gutter: stable;">
                      <label
                        v-for="ticket in activeTicketList"
                        :key="`ticket-option-${ticket.id}`"
                        class="flex items-center justify-between gap-2 rounded-lg px-3 py-2 text-[12px] shadow-sm transition hover:bg-indigo-50 dark:hover:bg-indigo-500/10"
                        :class="normalizeSelectionId(ticket.id) === normalizeSelectionId(selectedTicketId) ? 'border border-indigo-500 bg-indigo-50/70 dark:border-indigo-400/60 dark:bg-indigo-500/10' : 'border border-slate-200 hover:border-indigo-300 dark:border-slate-700'"
                      >
                        <div class="flex items-start gap-3">
                          <input
                            type="radio"
                            class="mt-1 h-4 w-4 border-indigo-300 text-indigo-600 focus:ring-indigo-500"
                            :value="ticket.id"
                            :checked="normalizeSelectionId(ticket.id) === normalizeSelectionId(selectedTicketId)"
                            @change="selectTicket(ticket.id)"
                          />
                          <div class="min-w-0">
                            <p class="font-semibold text-slate-700 dark:text-slate-100">{{ ticket.ticket_no }}</p>
                            <p class="truncate text-[11px] text-slate-400">{{ ticket.title || 'Tanpa judul' }}</p>
                            <p class="text-[10px] text-slate-400">{{ ticket.unit || ticketUnitPlaceholder }}</p>
                          </div>
                        </div>
                        <StatusPill :status="ticket.status || ticket.status_badge" :label="ticket.status_label || 'Aktif'" size="sm" />
                      </label>
                      <p v-if="!activeTicketList.length" class="px-1 text-sm text-slate-400">Tidak ada ticket pada unit ini.</p>
                    </div>
                    <div class="mt-2 flex flex-wrap items-center justify-between gap-2 text-[11px] text-slate-400">
                      <span>Hanya menampilkan ticket yang belum berstatus Done / Cancelled.</span>
                      <button type="button" class="text-indigo-500 hover:underline" @click="clearTicketSelection">Tanpa ticket</button>
                    </div>
                  </div>
                </transition>
              </div>
              <p class="mt-2 text-xs text-slate-400">Jika tidak ada ticket yang relevan, biarkan kosong untuk task mandiri.</p>
              <p v-if="form.errors.ticket_id" class="field-error">{{ form.errors.ticket_id }}</p>
            </div>
          </div>
        </div>

        <div class="mt-4 relative z-0">
          <label class="field-label">Deskripsi</label>
          <div class="rounded-2xl border border-slate-200/80 dark:border-slate-700/60">
            <RichTextQuill v-model="form.description" />
          </div>
          <p v-if="form.errors.description" class="field-error">{{ form.errors.description }}</p>
        </div>
      </section>

      <section class="rounded-3xl border border-slate-200 bg-white/90 p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
        <h2 class="section-title">Penugasan &amp; Timeline</h2>
        <div class="grid gap-4 md:grid-cols-2">
          <div class="relative" ref="agentCardRef">
            <label class="field-label">Agent</label>
            <button
              type="button"
              class="mt-1 flex w-full items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3 text-left text-sm font-semibold text-slate-700 shadow-sm transition hover:border-blue-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
              @click="toggleAgentDropdown"
            >
              <div class="flex flex-1 flex-wrap gap-1.5">
                <span
                  v-if="selectedAgentDetails.length"
                  class="inline-flex items-center gap-2 rounded-lg border border-blue-200/70 bg-white px-3 py-1 text-xs font-medium text-blue-700 dark:border-blue-500/40 dark:bg-slate-900/70 dark:text-blue-200"
                >
                  {{ selectedAgentDetails[0].label }}
                  <button type="button" class="text-slate-400 transition hover:text-rose-500" @click.stop="clearAgent">
                    <span class="material-icons text-[13px]">close</span>
                  </button>
                </span>
                <span v-else class="text-xs font-normal text-slate-400">Pilih agent utama</span>
              </div>
              <span class="material-icons text-base text-slate-400 transition duration-200" :class="agentDropdownOpen ? 'rotate-180 text-blue-500' : ''">expand_more</span>
            </button>
            <transition name="fade-scale">
              <div
                v-if="agentDropdownOpen"
                class="absolute inset-x-0 top-full z-[9999] mt-2 rounded-2xl border border-slate-200 bg-white p-3 shadow-2xl md:p-4 dark:border-slate-700 dark:bg-slate-900"
              >
                <div class="flex flex-wrap gap-2 border-b border-slate-100 pb-3 dark:border-slate-800">
                  <button
                    v-for="unit in allUnits"
                    :key="`agent-unit-${unit}`"
                    type="button"
                    class="rounded-full border px-3 py-1 text-xs font-semibold transition"
                    :class="activeAgentUnit === unit ? 'border-blue-500 bg-blue-50 text-blue-700 dark:border-blue-400 dark:bg-blue-500/10 dark:text-blue-200' : 'border-slate-200 text-slate-500 hover:border-blue-300 hover:text-blue-600 dark:border-slate-700 dark:text-slate-300'"
                    @click="activeAgentUnit = unit"
                  >
                    {{ unit }}
                  </button>
                  <p v-if="!allUnits.length" class="text-xs text-slate-400">Belum ada unit.</p>
                </div>
                <div class="mt-3 max-h-64 space-y-2 overflow-y-auto pr-1">
                  <label
                    v-for="user in activeAgentList"
                    :key="`agent-option-${user.id}`"
                    class="flex items-start justify-between gap-3 rounded-xl border border-slate-200 px-3 py-2 text-sm shadow-sm transition hover:border-blue-400 dark:border-slate-700"
                  >
                    <div class="flex items-start gap-3">
                      <input
                        type="radio"
                        class="mt-1 h-4 w-4 border-blue-300 text-blue-600 focus:ring-blue-500"
                        :checked="form.assignee_id === user.id"
                        @change="selectAgent(user.id)"
                      />
                      <div>
                        <p class="font-semibold text-slate-700 dark:text-slate-100">{{ user.label }}</p>
                        <p class="text-xs text-slate-400">{{ user.email || '-' }} - {{ user.unit || 'Unit tidak tercatat' }}</p>
                      </div>
                    </div>
                    <span v-if="form.assignee_id === user.id" class="rounded-full bg-blue-50 px-2 py-1 text-[11px] font-semibold uppercase tracking-wide text-blue-600 dark:bg-blue-500/10 dark:text-blue-200">Dipilih</span>
                  </label>
                  <p v-if="!activeAgentList.length" class="text-sm text-slate-400">Belum ada agent pada unit ini.</p>
                </div>
              </div>
            </transition>
            <p v-if="form.errors.assignee_id" class="field-error">{{ form.errors.assignee_id }}</p>
          </div>
          <div>
            <label class="field-label">Requester</label>
            <template v-if="canSelectRequester">
              <div ref="requesterCardRef" class="relative mt-1 w-full overflow-visible">
                <button
                  ref="requesterTriggerRef"
                  type="button"
                  class="flex w-full items-center justify-between rounded-2xl border border-slate-200 bg-white px-4 py-3 text-left text-sm font-semibold text-slate-700 shadow-sm transition hover:border-blue-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                  @click="toggleRequesterDropdown"
                >
                  <span class="truncate">{{ selectedRequesterLabel }}</span>
                  <span class="material-icons text-base text-slate-400 transition duration-200" :class="requesterDropdownOpen ? 'rotate-180 text-blue-500' : ''">expand_more</span>
                </button>
                <transition name="fade-scale">
                  <div
                    v-if="requesterDropdownOpen"
                    ref="requesterDropdownRef"
                    class="absolute left-0 right-0 top-full mt-2 z-[9999] w-full rounded-2xl border border-slate-200 bg-white p-3 shadow-2xl md:p-4 dark:border-slate-700 dark:bg-slate-900"
                  >
                    <div class="flex flex-wrap gap-2 border-b border-slate-100 pb-3 dark:border-slate-800">
                      <button
                        v-for="unit in allUnits"
                        :key="`requester-unit-${unit}`"
                        type="button"
                        class="rounded-full border px-3 py-1 text-xs font-semibold transition"
                        :class="activeRequesterUnit === unit ? 'border-blue-500 bg-blue-50 text-blue-700 dark:border-blue-400 dark:bg-blue-500/10 dark:text-blue-200' : 'border-slate-200 text-slate-500 hover:border-blue-300 hover:text-blue-600 dark:border-slate-700 dark:text-slate-300'"
                        @click="setActiveRequesterUnit(unit)"
                      >
                        {{ unit }}
                      </button>
                    </div>
                    <div class="mt-3 max-h-64 space-y-2 overflow-y-auto pr-1">
                      <template v-if="activeRequesterUnit">
                        <button
                          v-for="option in activeRequesterList"
                          :key="`requester-option-${option.id}`"
                          type="button"
                          class="flex w-full items-start justify-between gap-3 rounded-xl border border-slate-200 px-3 py-2 text-left text-sm shadow-sm transition hover:border-blue-400 hover:bg-indigo-50 dark:border-slate-700 dark:hover:bg-indigo-500/10"
                          :class="normalizeSelectionId(form.requester_id) === normalizeSelectionId(option.id) ? 'border-blue-400 dark:border-blue-500' : ''"
                          @click="setRequesterSelection(option.id)"
                        >
                          <div class="space-y-0.5">
                            <p class="font-semibold text-slate-700 dark:text-slate-100">{{ formatRequesterTitle(option) }}</p>
                            <p class="text-xs text-slate-400">{{ formatRequesterMeta(option) }}</p>
                          </div>
                          <span
                            class="material-icons text-base"
                            :class="normalizeSelectionId(form.requester_id) === normalizeSelectionId(option.id) ? 'text-blue-500' : 'text-transparent'"
                          >
                            check
                          </span>
                        </button>
                        <p v-if="!activeRequesterList.length" class="text-sm text-slate-400">Belum ada requester pada unit ini.</p>
                      </template>
                      <p v-else class="text-sm text-slate-400">Pilih unit untuk melihat requester.</p>
                    </div>
                  </div>
                </transition>
              </div>
            </template>
            <template v-else>
              <div class="field-input bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-300">
                {{ requesterLabel || 'Requester mengikuti data saat ini.' }}
              </div>
            </template>
            <p v-if="form.errors.requester_id" class="field-error">{{ form.errors.requester_id }}</p>
          </div>
        </div>

        <div class="mt-4 relative" ref="picCardRef">
          <label class="field-label">PIC</label>
          <button
            type="button"
            class="mt-1 flex w-full items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3 text-left text-sm font-semibold text-slate-700 shadow-sm transition hover:border-emerald-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
            @click="togglePicDropdown"
          >
            <div class="flex flex-1 flex-wrap gap-1.5">
              <span
                v-for="person in selectedPicDetails"
                :key="`chip-pic-${person.id}`"
                class="inline-flex items-center gap-1 rounded-lg border border-emerald-200/70 bg-white px-2 py-1 text-xs font-medium text-emerald-700 dark:border-emerald-500/40 dark:bg-slate-900/70 dark:text-emerald-200"
              >
                {{ person.label }}
                <button type="button" class="text-slate-400 transition hover:text-rose-500" @click.stop="removePic(person.id)">
                  <span class="material-icons text-[13px]">close</span>
                </button>
              </span>
              <span v-if="!selectedPicDetails.length" class="text-xs font-normal text-slate-400">Pilih PIC (bisa lebih dari satu)</span>
            </div>
            <span class="material-icons text-base text-slate-400 transition duration-200" :class="picDropdownOpen ? 'rotate-180 text-emerald-500' : ''">expand_more</span>
          </button>
          <transition name="fade-scale">
            <div
              v-if="picDropdownOpen"
              class="absolute inset-x-0 top-full z-[9999] mt-2 rounded-2xl border border-slate-200 bg-white p-3 shadow-2xl md:p-4 dark:border-slate-700 dark:bg-slate-900"
            >
              <div class="flex flex-wrap gap-2 border-b border-slate-100 pb-3 dark:border-slate-800">
                <button
                  v-for="unit in allUnits"
                  :key="`pic-unit-${unit}`"
                  type="button"
                  class="rounded-full border px-3 py-1 text-xs font-semibold transition"
                  :class="activePicUnit === unit ? 'border-emerald-500 bg-emerald-50 text-emerald-700 dark:border-emerald-400 dark:bg-emerald-500/10 dark:text-emerald-200' : 'border-slate-200 text-slate-500 hover:border-emerald-300 hover:text-emerald-600 dark:border-slate-700 dark:text-slate-300'"
                  @click="activePicUnit = unit"
                >
                  {{ unit }}
                </button>
                <p v-if="!allUnits.length" class="text-xs text-slate-400">Belum ada unit.</p>
              </div>
              <div class="mt-3 max-h-64 space-y-2 overflow-y-auto pr-1">
                <label
                  v-for="user in activePicList"
                  :key="`pic-option-${user.id}`"
                  class="flex items-start justify-between gap-3 rounded-xl border border-slate-200 px-3 py-2 text-sm shadow-sm transition hover:border-emerald-400 dark:border-slate-700"
                >
                  <div class="flex items-start gap-3">
                    <input
                      type="checkbox"
                      class="mt-1 h-4 w-4 rounded border-emerald-300 text-emerald-600 focus:ring-emerald-500"
                      :checked="form.assignees?.includes(user.id)"
                      @change="togglePicSelection(user.id)"
                    />
                    <div>
                      <p class="font-semibold text-slate-700 dark:text-slate-100">{{ user.label }}</p>
                      <p class="text-xs text-slate-400">{{ user.email || '-' }} - {{ user.unit || 'Unit tidak tercatat' }}</p>
                    </div>
                  </div>
                  <span
                    v-if="form.assignees?.includes(user.id)"
                    class="rounded-full bg-emerald-50 px-2 py-1 text-[11px] font-semibold uppercase tracking-wide text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-200"
                  >
                    Dipilih
                  </span>
                </label>
                <p v-if="!activePicList.length" class="text-sm text-slate-400">Belum ada PIC pada unit ini.</p>
              </div>
            </div>
          </transition>
          <p v-if="form.errors.assignees" class="field-error">{{ form.errors.assignees }}</p>
        </div>

        <div class="mt-4 grid gap-4 md:grid-cols-2">
          <div>
            <label class="field-label">Tenggat</label>
            <DatePickerFlatpickr v-model="form.due_at" :config="dateConfig" placeholder="Pilih tanggal & waktu" />
            <p v-if="form.errors.due_at || form.errors.due_date" class="field-error">{{ form.errors.due_at || form.errors.due_date }}</p>
          </div>
          <div>
            <label class="field-label">Output</label>
            <FancySelect v-model="form.output_type" :options="outputOptions" />
          </div>
        </div>

        <div class="mt-4 grid gap-4 md:grid-cols-2">
          <div>
            <label class="field-label">Mulai</label>
            <DatePickerFlatpickr v-model="form.start_date" :config="dateOnlyConfig" placeholder="Pilih tanggal mulai" />
            <p v-if="form.errors.start_date" class="field-error">{{ form.errors.start_date }}</p>
          </div>
          <div>
            <label class="field-label">Selesai</label>
            <DatePickerFlatpickr v-model="form.end_date" :config="dateOnlyConfig" placeholder="Pilih tanggal selesai" />
            <p v-if="form.errors.end_date" class="field-error">{{ form.errors.end_date }}</p>
          </div>
        </div>

        <div v-if="form.output_type === 'task_project'" class="mt-4 grid gap-4 md:grid-cols-3">
          <div>
            <label class="field-label">Judul Project</label>
            <input v-model="form.project_title" type="text" class="field-input" />
          </div>
          <div>
            <label class="field-label">Mulai</label>
            <input v-model="form.project_start" type="date" class="field-input" />
          </div>
          <div>
            <label class="field-label">Selesai</label>
            <input v-model="form.project_end" type="date" class="field-input" />
          </div>
        </div>
      </section>

      <section class="rounded-3xl border border-slate-200 bg-white/90 p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
        <h2 class="section-title">Lampiran</h2>
        <p class="text-xs text-slate-500 dark:text-slate-400">Unggah lampiran baru atau kelola lampiran yang sudah ada.</p>
        <div v-if="selectedTicketOption || initialTicketAttachments.length" class="mt-1 space-y-2">
          <p class="text-xs font-medium text-slate-600 dark:text-slate-300">
            Lampiran dari ticket terkait:
            <span v-if="ticketAttachmentCount">{{ ticketAttachmentCount }} file.</span>
            <span v-else>Belum ada lampiran ticket.</span>
          </p>
          <TicketAttachmentList :attachments="ticketAttachmentList" />
        </div>
        <div v-if="existingAttachments.length" class="mt-3 space-y-2">
          <div
            v-for="attachment in existingAttachments"
            :key="attachment.id"
            class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-700 dark:bg-slate-800/60"
          >
            <div>
              <div class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ attachment.name }}</div>
              <div v-if="formatSize(attachment.size)" class="text-xs text-slate-400">{{ formatSize(attachment.size) }}</div>
            </div>
            <div class="flex items-center gap-2">
              <a :href="attachment.view_url" target="_blank" class="inline-flex items-center gap-1 rounded-lg border border-blue-200 px-3 py-1.5 text-xs font-semibold text-blue-600 transition hover:bg-blue-50 dark:border-blue-400/40 dark:text-blue-200 dark:hover:bg-blue-500/10">Lihat</a>
              <a :href="attachment.download_url" class="inline-flex items-center gap-1 rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-100 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800">Unduh</a>
              <button
                v-if="attachment.delete_url"
                type="button"
                class="inline-flex items-center gap-1 rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-600 transition hover:bg-red-50 dark:border-red-400/40 dark:text-red-300 dark:hover:bg-red-500/10"
                :disabled="deletingId === attachment.id"
                @click="removeExisting(attachment)"
              >
                <span class="material-icons text-xs" v-if="deletingId === attachment.id">hourglass_top</span>
                <span class="material-icons text-xs" v-else>delete</span>
                Hapus
              </button>
            </div>
          </div>
        </div>
        <div class="mt-4 space-y-2">
          <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Jenis File</label>
            <FancySelect v-model="selectedAttachmentFilter" :options="attachmentFilterChoices" />
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ attachmentFilterHint }}</p>
          </div>
          <div>
            <FileUploaderPond
              ref="uploaderRef"
              multiple
              :accepted-file-types="attachmentMimeTypes"
              :allowed-extensions="attachmentAllowedExtensions"
              :disabled="uploaderDisabled"
              @uploaded="handleUploaded"
              @removed="handleRemoved"
              @error="handleUploadError"
            />
            <div class="mt-2 flex flex-wrap items-center justify-between text-xs text-slate-500 dark:text-slate-400">
              <span>{{ attachmentIds.length ? attachmentIds.length + ' file siap diunggah' : 'Belum ada file siap diunggah' }}</span>
            </div>
          </div>
        </div>
        <p v-if="uploaderDisabled" class="field-error text-amber-600">Pilih jenis file terlebih dahulu sebelum mengunggah.</p>
        <p v-if="form.errors.attachments" class="field-error">{{ form.errors.attachments }}</p>
        <p v-if="uploadError" class="field-error">{{ uploadError }}</p>
      </section>

      <div class="flex flex-wrap items-center justify-end gap-3">
        <Link
          :href="cancelHref"
          class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-4 py-2 text-sm text-slate-600 transition hover:bg-slate-100 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800"
        >
          Batal
        </Link>
        <button
          type="submit"
          class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 via-indigo-500 to-purple-500 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/30 transition hover:shadow-xl disabled:cursor-not-allowed disabled:opacity-70"
          :disabled="form.processing"
        >
          <span class="material-icons text-base" v-if="form.processing">hourglass_top</span>
          <span class="material-icons text-base" v-else>save</span>
          Simpan Perubahan
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch, watchEffect } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import resolveRoute from '@/utils/resolveRoute';
import DatePickerFlatpickr from '@/Components/DatePickerFlatpickr.vue';
import FileUploaderPond from '@/Components/FileUploaderPond.vue';
import RichTextQuill from '@/Components/RichTextQuill.vue';
import FancySelect from '@/Components/FancySelect.vue';
import StatusPill from '@/Components/StatusPill.vue';
import TicketAttachmentList from '@/Components/TicketAttachmentList.vue';
import { attachmentFilterOptions } from '@/utils/attachmentFilters';

const props = defineProps({
  task: { type: Object, required: true },
  statusOptions: { type: Array, default: () => [] },
  priorityOptions: { type: Array, default: () => [] },
  ticketOptions: { type: Array, default: () => [] },
  userOptions: { type: Array, default: () => [] },
  meta: { type: Object, default: () => ({}) },
});

const normalizeMetaUrl = (candidate, fallbackName, fallbackParams = {}) => {
  if (typeof candidate === 'string') {
    const trimmed = candidate.trim();
    if (trimmed) {
      if (trimmed.startsWith('http')) {
        if (typeof window !== 'undefined') {
          try {
            const currentOrigin = window.location.origin;
            if (trimmed.startsWith(currentOrigin)) {
              return trimmed;
            }
          } catch (error) {
            // ignore and fallback
          }
        }
        return resolveRoute(fallbackName, fallbackParams);
      }
      return trimmed;
    }
  }
  return resolveRoute(fallbackName, fallbackParams);
};

const taskShowUrl = computed(() => resolveRoute('tasks.show', { taskSlug: props.task.slug }));
const cancelHref = computed(() => normalizeMetaUrl(props.meta?.backUrl ?? '', 'tasks.index'));
const submitUrl = computed(() => normalizeMetaUrl(props.meta?.submitUrl ?? '', 'tasks.update', { task: props.task.id }));

const form = useForm({
  title: props.task.title ?? '',
  description: props.task.description ?? '',
  status: props.task.status ?? 'new',
  priority: props.task.priority ?? 'normal',
  ticket_id: props.task.ticket_id ?? null,
  assignees: props.task.assignees ?? [],
  assignee_id: props.task.assignee_id ?? null,
  assigned_to: props.task.assignees ?? [],
  requester_id: props.task.requester_id ?? null,
  due_at: props.task.due_at ?? null,
  start_date: props.task.start_date ?? null,
  end_date: props.task.end_date ?? null,
  output_type: props.task.output_type ?? 'task',
  project_title: props.task.project_title ?? '',
  project_start: props.task.project_start ?? null,
  project_end: props.task.project_end ?? null,
  attachments: [],
});

const unitPlaceholderLabel = 'Unit Tidak Tercatat';
const userOptions = computed(() => props.userOptions ?? []);
const statusGuide = computed(() => props.meta?.statusGuide ?? null);
const statusDefaultLabel = computed(() => props.meta?.statusDefault ?? 'New');
const canSelectRequester = computed(() => Boolean(props.meta?.canSelectRequester));
const requesterLabel = computed(() => props.meta?.requesterLabel ?? '');
const ticketOptionsList = computed(() => props.ticketOptions ?? []);

const uploadError = ref('');
const attachmentIds = ref([]);
const deletingId = ref(null);
const attachmentFilterChoices = attachmentFilterOptions;
const selectedAttachmentFilter = ref('');
const activeAttachmentFilter = computed(
  () => attachmentFilterChoices.find(option => option.value === selectedAttachmentFilter.value) ?? null
);
const attachmentMimeTypes = computed(() => activeAttachmentFilter.value?.mimeTypes ?? []);
const attachmentAllowedExtensions = computed(() => activeAttachmentFilter.value?.extensions ?? []);
const uploaderDisabled = computed(() => !activeAttachmentFilter.value);
const attachmentFilterHint = computed(() => (
  activeAttachmentFilter.value ? `Hanya ${activeAttachmentFilter.value.label}` : 'Pilih jenis file untuk mengaktifkan unggahan.'
));
const uploaderRef = ref(null);
const agentDropdownOpen = ref(false);
const picDropdownOpen = ref(false);
const requesterDropdownOpen = ref(false);
const activeAgentUnit = ref('');
const activePicUnit = ref('');
const activeRequesterUnit = ref(null);
const ticketDropdownOpen = ref(false);
const activeTicketUnit = ref('');
const ticketCardRef = ref(null);
const agentCardRef = ref(null);
const picCardRef = ref(null);
const requesterCardRef = ref(null);
const requesterTriggerRef = ref(null);
const requesterDropdownRef = ref(null);

const existingAttachments = ref(props.task.attachments ?? []);

const dateConfig = computed(() => ({
  enableTime: true,
  altInput: true,
  altFormat: 'd M Y, H:i',
  dateFormat: 'Y-m-d H:i',
}));

const dateOnlyConfig = computed(() => ({
  altInput: true,
  altFormat: 'd M Y',
  dateFormat: 'Y-m-d',
}));

const statusChoices = computed(() => props.statusOptions?.map(option => option.value) ?? []);
const defaultStatusValue = computed(() => props.task.status ?? statusChoices.value[0] ?? 'new');
const workflowStatusOptions = computed(() => props.statusOptions?.map(option => ({
  id: option.value,
  name: option.label ?? option.value,
})) ?? []);

const allowedStatuses = computed(() => props.meta?.allowedStatuses ?? []);
const statusLocked = computed(() => Boolean(props.meta?.lockStatus));

watchEffect(() => {
  if (statusLocked.value) {
    form.status = props.task.status ?? 'new';
  }
});
watch(() => form.title, value => {
  if ((value ?? '').toString().trim()) {
    form.clearErrors('title');
  }
});
watch(() => form.status, value => {
  if ((value ?? '').toString().trim()) {
    form.clearErrors('status');
  }
});

const filteredWorkflowStatuses = computed(() => {
  const current = String(form.status ?? '').trim();
  const allowed = allowedStatuses.value;
  const options = workflowStatusOptions.value;

  if (!allowed.length) {
    const currentOption = options.find(opt => opt.id === current);
    return currentOption
      ? [{ ...currentOption, name: `${currentOption.name} (locked)`, disabled: true }]
      : [{ id: 'new', name: 'New (locked)', disabled: true }];
  }

  const normalizedAllowed = allowed.map(String);
  const list = options
    .filter(opt => normalizedAllowed.includes(opt.id) || opt.id === current)
    .map(opt => ({
      ...opt,
      disabled: !normalizedAllowed.includes(opt.id),
    }));

  if (!list.length && options.length) {
    list.push({ ...options[0], disabled: false });
  }

  return list;
});
const statusDropdownOptions = computed(() => (
  filteredWorkflowStatuses.value.map(option => ({
    value: option.id,
    label: option.name,
    disabled: option.disabled,
  }))
));
const outputOptions = [
  { value: 'task', label: 'Tetap sebagai Task' },
  { value: 'task_project', label: 'Buat sebagai Project' },
];

const userLookup = computed(() => {
  const map = new Map();
  (props.userOptions || []).forEach(user => {
    if (user?.id !== undefined) {
      map.set(Number(user.id), user);
    }
  });
  return map;
});

const normalizeUnitValue = value => (typeof value === 'string' && value.trim() !== '' ? value.trim() : unitPlaceholderLabel);

const userUnit = id => {
  const user = userLookup.value.get(Number(id));
  return normalizeUnitValue(user?.unit);
};

const selectedAgentDetails = computed(() => {
  const unit = (activeAgentUnit.value || '').trim();
  const id = form.assignee_id ? Number(form.assignee_id) : null;
  if (!id || !unit) return [];
  if (userUnit(id) !== unit) return [];
  const user = userLookup.value.get(id);
  if (!user) return [];
  return [{ id, label: formatUserOptionLabel(user) }];
});

const selectedPicDetails = computed(() => {
  const unit = (activePicUnit.value || '').trim();
  if (!unit || !Array.isArray(form.assignees)) return [];
  return form.assignees
    .map(val => {
      const user = userLookup.value.get(Number(val));
      if (!user || userUnit(val) !== unit) return null;
      return { id: Number(val), label: formatUserOptionLabel(user) };
    })
    .filter(Boolean);
});

const allUnits = computed(() => {
  const set = new Set(props.meta?.unitOptions ?? []);
  userOptions.value.forEach(option => set.add(normalizeUnitValue(option.unit)));
  if (!set.size) {
    set.add(unitPlaceholderLabel);
  }
  return Array.from(set);
});

watch(allUnits, units => {
  if (!units.length) {
    activeAgentUnit.value = '';
    activePicUnit.value = '';
    activeRequesterUnit.value = null;
    return;
  }
  if (activeAgentUnit.value && !units.includes(activeAgentUnit.value)) {
    activeAgentUnit.value = '';
  }
  if (activePicUnit.value && !units.includes(activePicUnit.value)) {
    activePicUnit.value = '';
  }
  if (activeRequesterUnit.value && !units.includes(activeRequesterUnit.value)) {
    activeRequesterUnit.value = null;
  }
}, { immediate: true });

const activeAgentList = computed(() => {
  const unit = (activeAgentUnit.value || '').trim();
  if (!unit) return [];
  return userOptions.value.filter(user => normalizeUnitValue(user.unit) === unit);
});

const activePicList = computed(() => {
  const unit = (activePicUnit.value || '').trim();
  if (!unit) return [];
  return userOptions.value.filter(user => normalizeUnitValue(user.unit) === unit);
});

const requesterOptions = computed(() => userOptions.value.map(option => ({
  ...option,
  label: formatRequesterLabel(option),
})));
const selectedRequester = computed(() => requesterOptions.value.find(
  option => normalizeSelectionId(option.id) === normalizeSelectionId(form.requester_id)
) ?? null);
const selectedRequesterLabel = computed(() => selectedRequester.value?.label ?? 'Pilih requester');
const activeRequesterList = computed(() => {
  if (!activeRequesterUnit.value) return [];
  return requesterOptions.value.filter(option => normalizeUnitValue(option.unit) === activeRequesterUnit.value);
});

const ticketLookup = computed(() => {
  const map = new Map();
  ticketOptionsList.value.forEach(option => {
    if (option?.id !== undefined && option?.id !== null) {
      map.set(Number(option.id), option);
    }
  });
  return map;
});
const ticketUnitPlaceholder = unitPlaceholderLabel;
const ticketUnits = computed(() => {
  const set = new Set();
  // unit dari user/agent
  (props.meta?.unitOptions ?? []).forEach(unit => {
    if (typeof unit === 'string' && unit.trim() !== '') {
      set.add(unit.trim());
    }
  });
  // unit dari meta ticketUnits
  (props.meta?.ticketUnits ?? []).forEach(unit => {
    if (typeof unit === 'string' && unit.trim() !== '') {
      set.add(unit.trim());
    }
  });
  // unit dari ticket options
  ticketOptionsList.value.forEach(option => {
    const unit = typeof option?.unit === 'string' && option.unit.trim() !== '' ? option.unit.trim() : ticketUnitPlaceholder;
    set.add(unit);
  });
  if (!set.size) set.add(ticketUnitPlaceholder);
  return Array.from(set);
});
const selectedTicketId = computed(() => (form.ticket_id ? Number(form.ticket_id) : null));
const selectedTicketOption = computed(() => {
  const id = selectedTicketId.value;
  if (!id && id !== 0) return null;
  const option = ticketLookup.value.get(id) ?? null;
  if (option && !activeTicketUnit.value && option.unit) {
    activeTicketUnit.value = option.unit;
  }
  return option;
});
const activeTicketList = computed(() => {
  const unitRaw = (activeTicketUnit.value || '').trim();
  if (!unitRaw) {
    return ticketOptionsList.value;
  }
  const unit = unitRaw || ticketUnitPlaceholder;
  return ticketOptionsList.value.filter(option => {
    const u = typeof option?.unit === 'string' && option.unit.trim() !== '' ? option.unit.trim() : ticketUnitPlaceholder;
    return u === unit;
  });
});
const initialTicketAttachments = computed(() => props.task.ticket_attachments ?? []);
const ticketAttachmentList = computed(() => {
  const option = selectedTicketOption.value;
  if (Array.isArray(option?.attachments) && option.attachments.length) {
    return option.attachments;
  }
  if (Array.isArray(option?.ticket_attachments)) {
    return option.ticket_attachments;
  }
  return initialTicketAttachments.value;
});
const ticketAttachmentCount = computed(() => {
  if (ticketAttachmentList.value.length) {
    return ticketAttachmentList.value.length;
  }
  const option = selectedTicketOption.value;
  const count = option?.attachments_count
    ?? option?.attachment_count
    ?? option?.attachmentsCount
    ?? option?.attachmentCount
    ?? props.task.ticket_attachment_count
    ?? initialTicketAttachments.value.length;
  const numeric = Number(count);
  return Number.isFinite(numeric) ? numeric : 0;
});

function ensureCoreFields() {
  const trimmedTitle = (form.title ?? '').toString().trim();
  if (!trimmedTitle) {
    const fallbackTitle = props.task.title ?? `Task #${props.task.id}`;
    form.title = fallbackTitle;
  } else {
    form.title = trimmedTitle;
    form.clearErrors('title');
  }

  const normalizedStatus = (form.status ?? '').toString().trim();
  if (!normalizedStatus || !statusChoices.value.includes(normalizedStatus)) {
    form.status = defaultStatusValue.value;
  } else {
    form.status = normalizedStatus;
    form.clearErrors('status');
  }
}

function formatSize(size) {
  if (!size || Number.isNaN(Number(size))) return '';
  const kb = Number(size) / 1024;
  if (kb < 1024) return `${kb.toFixed(0)} KB`;
  const mb = kb / 1024;
  return `${mb.toFixed(1)} MB`;
}

function onTicketChange(event) {
  const value = event.target.value;
  form.ticket_id = value ? Number(value) : null;
}

function onAssigneeChange(event) {
  const value = event.target.value;
  form.assignee_id = value ? Number(value) : null;
  syncAssignedArray();
}

function onAssigneesMultiChange(event) {
  const selected = Array.from(event.target.selectedOptions).map(option => Number(option.value));
  form.assignees = selected;
  if (!form.assignee_id && selected.length) {
    form.assignee_id = selected[0];
  }
  syncAssignedArray();
}

function toggleTicketDropdown() {
  ticketDropdownOpen.value = !ticketDropdownOpen.value;
  if (ticketDropdownOpen.value) {
    agentDropdownOpen.value = false;
    picDropdownOpen.value = false;
    requesterDropdownOpen.value = false;
  }
}

function toggleAgentDropdown() {
  agentDropdownOpen.value = !agentDropdownOpen.value;
  if (agentDropdownOpen.value) {
    ticketDropdownOpen.value = false;
    picDropdownOpen.value = false;
    requesterDropdownOpen.value = false;
  }
}

function togglePicDropdown() {
  picDropdownOpen.value = !picDropdownOpen.value;
  if (picDropdownOpen.value) {
    agentDropdownOpen.value = false;
    ticketDropdownOpen.value = false;
    requesterDropdownOpen.value = false;
  }
}

function toggleRequesterDropdown() {
  requesterDropdownOpen.value = !requesterDropdownOpen.value;
  if (requesterDropdownOpen.value) {
    ticketDropdownOpen.value = false;
    agentDropdownOpen.value = false;
    picDropdownOpen.value = false;
  }
}

function setActiveRequesterUnit(unit) {
  activeRequesterUnit.value = unit;
}

function setRequesterSelection(rawId) {
  const normalized = normalizeSelectionId(rawId);
  form.requester_id = normalized ?? null;
  requesterDropdownOpen.value = false;
}

function selectAgent(userId) {
  form.assignee_id = Number(userId);
  agentDropdownOpen.value = false;
  syncAssignedArray();
}

function clearAgent() {
  form.assignee_id = null;
  syncAssignedArray();
}

function togglePicSelection(userId) {
  const id = Number(userId);
  if (!Array.isArray(form.assignees)) {
    form.assignees = [];
  }
  if (form.assignees.includes(id)) {
    form.assignees = form.assignees.filter(value => value !== id);
  } else {
    form.assignees = [...form.assignees, id];
  }
  syncAssignedArray();
}

function removePic(userId) {
  form.assignees = (form.assignees ?? []).filter(id => id !== userId);
  syncAssignedArray();
}

function selectTicket(id) {
  form.ticket_id = id ? Number(id) : null;
  ticketDropdownOpen.value = false;
}

function clearTicketSelection() {
  form.ticket_id = null;
  ticketDropdownOpen.value = false;
}

function normalizeSelectionId(value) {
  if (typeof value === 'number' && Number.isFinite(value)) return value;
  if (typeof value === 'string' && value.trim() !== '' && !Number.isNaN(Number(value))) return Number(value);
  return null;
}

function handleOutsideClick(event) {
  const target = event.target;
  if (ticketDropdownOpen.value && ticketCardRef.value && !ticketCardRef.value.contains(target)) {
    ticketDropdownOpen.value = false;
  }
  if (agentDropdownOpen.value && agentCardRef.value && !agentCardRef.value.contains(target)) {
    agentDropdownOpen.value = false;
  }
  if (picDropdownOpen.value && picCardRef.value && !picCardRef.value.contains(target)) {
    picDropdownOpen.value = false;
  }
  if (requesterDropdownOpen.value && requesterCardRef.value && !requesterCardRef.value.contains(target)) {
    requesterDropdownOpen.value = false;
  }
}

function handleEscapeKey(event) {
  if (event.key !== 'Escape') return;
  ticketDropdownOpen.value = false;
  agentDropdownOpen.value = false;
  picDropdownOpen.value = false;
  requesterDropdownOpen.value = false;
}

onMounted(() => {
  document.addEventListener('click', handleOutsideClick);
  document.addEventListener('keydown', handleEscapeKey, true);

  // Prefill unit tabs so selected Agent/PIC terlihat setelah load
  const agentUnit = userUnit(form.assignee_id);
  if (agentUnit) {
    activeAgentUnit.value = agentUnit;
  }
  const firstPicWithUnit = Array.isArray(form.assignees)
    ? form.assignees.map(userUnit).find(unit => unit)
    : null;
  if (firstPicWithUnit) {
    activePicUnit.value = firstPicWithUnit;
  }
});

onBeforeUnmount(() => {
  document.removeEventListener('click', handleOutsideClick);
  document.removeEventListener('keydown', handleEscapeKey, true);
});

const initialAssigneeId = ref(form.assignee_id);
const initialAssignees = ref(Array.isArray(form.assignees) ? [...form.assignees] : []);

function syncAssignedArray() {
  const ids = new Set([
    ...(Array.isArray(form.assignees) ? form.assignees : []),
    form.assignee_id ?? undefined,
  ].filter(Boolean));
  form.assigned_to = Array.from(ids);
}

function handleUploaded(payload) {
  uploadError.value = '';
  const id = payload?.id;
  if (!id) return;
  if (!attachmentIds.value.includes(id)) {
    attachmentIds.value.push(id);
  }
  form.attachments = [...attachmentIds.value];
}

function handleRemoved(id) {
  attachmentIds.value = attachmentIds.value.filter(value => value !== id);
  form.attachments = [...attachmentIds.value];
}

function handleUploadError(error) {
  if (typeof error === 'string') {
    uploadError.value = error;
  } else if (error?.response?.data?.message) {
    uploadError.value = error.response.data.message;
  } else {
    uploadError.value = 'Gagal mengunggah file.';
  }
}

watch(selectedAttachmentFilter, () => {
  attachmentIds.value = [];
  form.attachments = [];
  uploaderRef.value?.reset();
  uploadError.value = '';
});

function removeExisting(attachment) {
  if (!attachment?.delete_url) return;
  if (!confirm('Hapus lampiran ini?')) {
    return;
  }
  deletingId.value = attachment.id;
  router.delete(attachment.delete_url, {
    preserveScroll: true,
    onFinish: () => {
      deletingId.value = null;
    },
    onSuccess: () => {
      existingAttachments.value = existingAttachments.value.filter(item => item.id !== attachment.id);
    },
  });
}

function submit() {
  uploadError.value = '';
  form.clearErrors('title', 'status');
  ensureCoreFields();
  syncAssignedArray();
  form.transform(data => ({
    ...data,
    title: (data.title ?? '').toString().trim(),
    status: (data.status ?? '').toString().trim() || defaultStatusValue.value,
  })).put(submitUrl.value, {
    preserveScroll: true,
    onSuccess: () => {
      attachmentIds.value = [];
    },
  });
}

function formatRequesterTitle(option) {
  const unit = option?.unit?.trim?.() || unitPlaceholderLabel;
  const name = option?.name ?? option?.label ?? `User #${option?.id ?? '-'}`;
  const role = option?.role ? ` (${option.role})` : '';

  return `[${unit}] ${name}${role}`.trim();
}

function formatRequesterMeta(option) {
  return option?.email || 'Email tidak tersedia';
}

function formatRequesterLabel(option) {
  const title = formatRequesterTitle(option);
  const email = option?.email ? ` — ${option.email}` : '';

  return `${title}${email}`.trim();
}

function formatUserOptionLabel(option) {
  const name = option?.name ?? option?.label ?? `User #${option?.id ?? '-'}`;
  const unit = option?.unit ? `[${option.unit}] ` : '';
  const email = option?.email ? ` - ${option.email}` : '';

  return `${unit}${name}${email}`.trim();
}
const task = props.task;
</script>

<style scoped>
.section-title {
  margin-bottom: 1.25rem;
  font-size: 1.1rem;
  font-weight: 600;
  color: #0f172a;
}

.dark .section-title {
  color: #e2e8f0;
}

.field-label {
  display: block;
  margin-bottom: 0.35rem;
  font-size: 0.9rem;
  font-weight: 600;
  color: #1e293b;
}

.dark .field-label {
  color: #e2e8f0;
}

.field-input {
  width: 100%;
  border-radius: 0.75rem;
  border: 1px solid rgba(148, 163, 184, 0.6);
  background: rgba(255, 255, 255, 0.95);
  padding: 0.65rem 0.85rem;
  font-size: 0.9rem;
  color: #0f172a;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.field-input:focus {
  outline: none;
  border-color: rgba(59, 130, 246, 0.6);
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
}

.dark .field-input {
  background: rgba(15, 23, 42, 0.9);
  color: #e2e8f0;
  border-color: rgba(71, 85, 105, 0.7);
}

.field-error {
  margin-top: 0.35rem;
  font-size: 0.75rem;
  color: #dc2626;
}

.material-icons {
  font-size: inherit;
}

</style>
