<template>
  <div class="project-edit">
    <div class="edit-shell">
      <header class="edit-header">
        <div class="edit-header__left">
          <div>
            <p class="edit-breadcrumb">{{ breadcrumbLabel }}</p>
            <h1>{{ titleLabel }}</h1>
            <p class="edit-subtitle">{{ subtitleLabel }}</p>
          </div>
        </div>
        <aside class="edit-header__right">
          <StatusPill :status="form.status || 'new'" :label="statusDisplay" size="lg" />
          <StatusPill :status="project.status_id || 'status-id'" :label="`Status ID: ${project.status_id || '-'}`" size="sm" />
        </aside>
      </header>

      <form class="edit-form" @submit.prevent="handleSubmit">
        <nav class="wizard">
          <ol class="wizard__list">
            <li
              v-for="(step, index) in steps"
              :key="step.key"
              class="wizard__item"
              :class="{ 'is-active': index === currentStep, 'is-completed': index < currentStep }"
            >
              <button type="button" class="wizard__button" @click="goToStep(index)">
                <span class="wizard__indicator">{{ index + 1 }}</span>
                <span class="wizard__label">{{ step.label }}</span>
              </button>
              <span v-if="index < steps.length - 1" class="wizard__divider"></span>
            </li>
          </ol>
        </nav>
        <section v-if="mountedSteps.overview" v-show="showStep('overview')" class="panel" :class="{ 'panel--inactive': !showStep('overview') }">
          <div class="panel-title">
            <div class="panel-icon primary">
              <span class="material-icons">fact_check</span>
            </div>
            <div>
              <h2>Informasi Utama</h2>
              <p>Identitas project dan relasi ticket.</p>
            </div>
          </div>
          <div class="panel-grid">
            <div class="panel-field col-span-2">
              <label>Judul Project</label>
              <input v-model="form.title" type="text" placeholder="Contoh: Implementasi Dashboard Analytics" />
              <p v-if="form.errors.title" class="field-error">{{ form.errors.title }}</p>
            </div>
            <div class="panel-field">
              <label>Nomor Project</label>
              <input v-model="form.project_no" type="text" maxlength="20" placeholder="PRJ-2025-001" />
              <p v-if="form.errors.project_no" class="field-error">{{ form.errors.project_no }}</p>
            </div>
            <div class="panel-field">
              <label>Status Workflow</label>
              <FancySelect v-model="form.status" :options="workflowStatusSelectOptions" :disabled="statusLocked" />
              <p v-if="form.errors.status" class="field-error">{{ form.errors.status }}</p>
              <p v-if="statusLocked" class="field-hint">Status awal diset ke {{ statusDefaultLabel }}.</p>
              <ul v-else-if="statusGuide" class="mt-1 list-disc space-y-0.5 pl-5 text-xs text-slate-500 dark:text-slate-400">
                <li>{{ statusGuide.agent }}</li>
                <li>{{ statusGuide.requester }}</li>
                <li>{{ statusGuide.admin }}</li>
              </ul>
            </div>
            <div class="panel-field panel-field--full ticket-linker" ref="ticketCardRef">
              <label>Ticket Terkait</label>
              <div class="relative ticket-card rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
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
                <button
                  type="button"
                  class="mt-3 flex w-full items-center justify-between rounded-xl border border-slate-200 bg-slate-50/90 px-4 py-3 text-left text-sm font-semibold text-slate-700 transition hover:border-blue-400 dark:border-slate-700 dark:bg-slate-800/80 dark:text-slate-100"
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
                <div v-if="ticketRequester" class="mt-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600 dark:border-slate-700 dark:bg-slate-800/70 dark:text-slate-200">
                  <p class="font-semibold text-slate-700 dark:text-slate-100">Requester Ticket</p>
                  <p>{{ ticketRequester.name || 'Requester' }}</p>
                  <p v-if="ticketRequester.email" class="text-[11px] text-slate-500">{{ ticketRequester.email }}</p>
                </div>
                <transition name="fade-scale">
                  <div
                    v-if="ticketDropdownOpen"
                    class="mt-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-2xl dark:border-slate-700 dark:bg-slate-900"
                  >
                    <div class="unit-chips flex flex-wrap gap-2 border-b border-slate-100 pb-3 dark:border-slate-800">
                      <button
                        v-for="unit in ticketUnits"
                        :key="`ticket-unit-${unit}`"
                        type="button"
                        class="rounded-full border px-3 py-1 text-xs font-semibold transition"
                        :class="activeTicketUnit === unit ? 'border-indigo-500 bg-indigo-50 text-indigo-700 dark:border-indigo-400 dark:bg-indigo-500/10 dark:text-indigo-200' : 'border-slate-200 text-slate-500 hover:border-indigo-300 hover:text-indigo-600 dark:border-slate-700 dark:text-slate-300'"
                        @click="activeTicketUnit = unit"
                      >
                        {{ unit }}
                      </button>
                    </div>
                    <div class="mt-3 max-h-72 space-y-2 overflow-y-auto pr-1">
                      <label
                        v-for="ticket in activeTicketList"
                        :key="`ticket-option-${ticket.id}`"
                        class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 px-3 py-2 text-sm shadow-sm transition hover:border-indigo-400 dark:border-slate-700"
                        :class="normalizeSelectionId(ticket.id) === normalizeSelectionId(selectedTicketId) ? 'border-indigo-500 bg-indigo-50/70 dark:border-indigo-400/60 dark:bg-indigo-500/10' : 'border-slate-200 dark:border-slate-700'"
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
                            <p class="truncate text-xs text-slate-400">{{ ticket.title || 'Tanpa judul' }}</p>
                            <p class="text-[11px] text-slate-400">{{ ticket.unit || ticketUnitPlaceholder }}</p>
                          </div>
                    </div>
                    <StatusPill :status="ticket.status || ticket.status_badge" :label="ticket.status_label || 'Aktif'" size="sm" />
                  </label>
                    <p v-if="!activeTicketList.length" class="text-sm text-slate-400">Tidak ada ticket pada unit ini.</p>
                  </div>
                    <div class="mt-3 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-400">
                      <span>Hanya menampilkan ticket yang belum berstatus Done / Cancelled.</span>
                      <button type="button" class="text-indigo-500 hover:underline" @click="clearTicketSelection">Tanpa ticket</button>
                    </div>
                  </div>
                </transition>
                <p class="mt-2 text-xs text-slate-400">Jika tidak ada ticket yang relevan, biarkan kosong untuk project mandiri.</p>
                <p v-if="form.errors.ticket_id" class="field-error">{{ form.errors.ticket_id }}</p>
              </div>
            </div>
          </div>
        </section>

        <section v-if="mountedSteps.timeline" v-show="showStep('timeline')" class="panel" :class="{ 'panel--inactive': !showStep('timeline') }">
          <div class="panel-title">
            <div class="panel-icon indigo">
              <span class="material-icons">schedule</span>
            </div>
            <div>
              <h2>Deskripsi &amp; Timeline</h2>
              <p>Isi narasi project serta tentukan rentang waktunya.</p>
            </div>
          </div>
          <div class="panel-grid panel-grid--timeline">
            <div class="panel-field panel-field--full">
              <label>Deskripsi Project</label>
              <div class="rich-editor rounded-2xl border border-slate-200/80 dark:border-slate-700/60">
                <RichTextQuill v-model="form.description" />
              </div>
              <p v-if="form.errors.description" class="field-error">{{ form.errors.description }}</p>
            </div>
            <div class="panel-field">
              <label>Mulai Project</label>
              <DatePickerFlatpickr v-model="form.start_date" :config="dateConfig" placeholder="dd/mm/yyyy" />
              <p v-if="form.errors.start_date" class="field-error">{{ form.errors.start_date }}</p>
            </div>
            <div class="panel-field">
              <label>Selesai Project</label>
              <DatePickerFlatpickr v-model="form.end_date" :config="dateConfig" placeholder="dd/mm/yyyy" />
              <p v-if="form.errors.end_date" class="field-error">{{ form.errors.end_date }}</p>
            </div>
            <div class="panel-field panel-field--full timeline-hint text-xs text-slate-400">
              Pastikan tanggal selesai berada setelah tanggal mulai. Kosongkan jika jadwal belum ditentukan.
            </div>
          </div>
        </section>

        <section v-if="mountedSteps.team" v-show="showStep('team')" class="panel !overflow-visible relative z-30" :class="{ 'panel--inactive': !showStep('team') }">
          <div class="panel-title">
            <div class="panel-icon emerald">
              <span class="material-icons">groups</span>
            </div>
            <div>
              <h2>Tim Project</h2>
              <p>Kelola agent dan PIC yang terlibat.</p>
            </div>
          </div>
          <div class="grid gap-6 lg:grid-cols-2">
            <div ref="agentCardRef" class="relative z-20 rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
              <div class="flex items-start justify-between gap-3">
                <div>
                  <p class="text-sm font-semibold text-slate-700 dark:text-slate-100">Agent</p>
                  <p class="text-xs text-slate-400">Pilih beberapa agent lalu tandai yang utama.</p>
                </div>
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">
                  {{ agentMembers.length ? agentMembers.length + ' agent dipilih' : 'Belum ada pilihan' }}
                </span>
              </div>
              <p class="mt-2 text-xs text-slate-400">Pilih unit untuk melihat daftar anggota.</p>
              <div class="relative mt-3 w-full">
                <button
                  type="button"
                  class="flex w-full items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3 text-left text-sm font-semibold text-slate-700 shadow-sm transition hover:border-blue-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                  :class="agentDropdownOpen ? 'rounded-b-none border-blue-400' : ''"
                  @click="toggleAgentDropdown"
                >
                  <div class="flex flex-1 flex-wrap gap-1.5">
                    <span
                      v-for="person in selectedAgentDetails"
                      :key="`chip-agent-${person.id}`"
                      class="inline-flex items-center gap-1 rounded-lg border border-blue-200/70 bg-white px-2 py-1 text-xs font-medium text-blue-700 dark:border-blue-500/40 dark:bg-slate-900/70 dark:text-blue-200"
                    >
                      {{ person.label }}
                      <button type="button" class="text-slate-400 transition hover:text-rose-500" @click.stop="removeMemberEntry(person.member)">
                        <span class="material-icons text-[13px]">close</span>
                      </button>
                    </span>
                    <span v-if="!selectedAgentDetails.length" class="text-xs font-normal text-slate-400">Belum ada agent dipilih</span>
                  </div>
                  <span class="material-icons text-base text-slate-400 transition duration-200" :class="agentDropdownOpen ? 'rotate-180 text-blue-500' : ''">expand_more</span>
                </button>
                <transition name="fade-scale">
                  <div
                    v-if="agentDropdownOpen"
                    class="absolute left-0 right-0 top-full z-[9999] mt-2 w-full rounded-xl rounded-t-none border border-slate-200 bg-white p-3 shadow-2xl md:p-4 dark:border-slate-700 dark:bg-slate-900"
                    :class="agentDropdownOpen ? 'border-blue-400' : ''"
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
                      <p v-if="!allUnits.length" class="text-xs text-slate-400">Belum ada unit terdaftar.</p>
                    </div>
                    <div class="mt-3 max-h-64 space-y-2 overflow-y-auto pr-1">
                      <label
                        v-for="option in activeAgentList"
                        :key="`agent-option-${option.id}`"
                        class="flex items-start justify-between gap-3 rounded-xl border border-slate-200 px-3 py-2 text-sm shadow-sm transition hover:border-blue-400 dark:border-slate-700"
                      >
                        <div class="flex items-start gap-3">
                          <input
                            type="checkbox"
                            class="mt-1 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                            :checked="isAgentSelected(option.id)"
                            @change="toggleAgentSelection(option.id)"
                          />
                          <div>
                            <p class="font-semibold text-slate-700 dark:text-slate-100">{{ option.label }}</p>
                            <p class="text-xs text-slate-400">{{ option.email || '—' }} · {{ option.unit || unitPlaceholderLabel }}</p>
                          </div>
                        </div>
                        <button
                          type="button"
                          class="rounded-full px-2 py-1 text-[11px] font-semibold uppercase tracking-wide transition"
                          :class="agentPrimary === normalizeSelectionId(option.id) ? 'bg-blue-50 text-blue-600 dark:bg-blue-500/20 dark:text-blue-100' : 'text-slate-400 hover:text-blue-500'"
                          @click.stop="setAgentPrimary(option.id)"
                        >
                          {{ agentPrimary === normalizeSelectionId(option.id) ? 'Utama' : 'Jadikan Utama' }}
                        </button>
                      </label>
                      <p v-if="!activeAgentList.length" class="text-sm text-slate-400">Belum ada agent pada unit ini.</p>
                    </div>
                  </div>
                </transition>
              </div>
              <p class="mt-2 text-xs text-slate-400">
                {{ agentMembers.length ? agentMembers.length + ' agent terhubung' : 'Belum ada agent terpilih' }}
              </p>
              <div class="mt-4 space-y-3">
                <div
                  v-for="member in agentMembers"
                  :key="`agent-member-${member.user_id || form.project_pics.indexOf(member)}`"
                  class="rounded-2xl border border-blue-100 bg-blue-50/40 p-3 dark:border-blue-500/30 dark:bg-blue-500/5"
                >
                  <div class="flex items-start justify-between gap-3">
                    <div>
                      <p class="text-sm font-semibold text-slate-700 dark:text-slate-100">{{ userInfo(member)?.label || 'Anggota tidak dikenal' }}</p>
                      <p class="text-xs text-slate-400">
                        {{ userInfo(member)?.email || '—' }} · {{ userInfo(member)?.unit || unitPlaceholderLabel }}
                      </p>
                    </div>
                    <button type="button" class="text-rose-500 hover:text-rose-600" @click="removeMemberEntry(member)">
                      <span class="material-icons text-base">delete</span>
                    </button>
                  </div>
                  <label class="mt-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Peran</label>
                  <input
                    v-model="member.position"
                    type="text"
                    class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                    placeholder="Contoh: Project Manager"
                  />
                  <p v-if="projectPicError(member, 'position')" class="mt-1 text-xs text-red-500">{{ projectPicError(member, 'position') }}</p>
                  <div class="mt-2 flex items-center justify-between text-xs text-slate-500">
                    <button
                      type="button"
                      class="inline-flex items-center gap-1 rounded-full border px-3 py-1 text-[11px] font-semibold uppercase tracking-wide transition"
                      :class="member.is_primary ? 'border-blue-500 bg-blue-50 text-blue-600 dark:border-blue-300 dark:bg-blue-500/10 dark:text-blue-100' : 'border-slate-200 text-slate-500 hover:border-blue-300 hover:text-blue-600'"
                      @click="setAgentPrimary(member.user_id)"
                    >
                      <span class="material-icons text-xs">verified_user</span>
                      {{ member.is_primary ? 'Agent Utama' : 'Jadikan Utama' }}
                    </button>
                  </div>
                </div>
                <p v-if="!agentMembers.length" class="text-xs text-slate-400">Belum ada data.</p>
              </div>
            </div>
            <div ref="picCardRef" class="relative z-20 rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
              <div class="flex items-start justify-between gap-3">
                <div>
                  <p class="text-sm font-semibold text-slate-700 dark:text-slate-100">Penanggung Jawab Utama</p>
                  <p class="text-xs text-slate-400">Pilih PIC utama dari berbagai unit.</p>
                </div>
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">
                  {{ picMembers.length ? picMembers.length + ' PIC dipilih' : 'Belum ada pilihan' }}
                </span>
              </div>
              <p class="mt-2 text-xs text-slate-400">Pilih unit untuk melihat daftar anggota.</p>
              <div class="relative mt-3 w-full">
                <button
                  type="button"
                  class="flex w-full items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3 text-left text-sm font-semibold text-slate-700 shadow-sm transition hover:border-emerald-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                  :class="picDropdownOpen ? 'rounded-b-none border-emerald-400' : ''"
                  @click="togglePicDropdown"
                >
                  <div class="flex flex-1 flex-wrap gap-1.5">
                    <span
                      v-for="person in selectedPicDetails"
                      :key="`chip-pic-${person.id}`"
                      class="inline-flex items-center gap-1 rounded-lg border border-emerald-200/70 bg-white px-2 py-1 text-xs font-medium text-emerald-700 dark:border-emerald-500/40 dark:bg-slate-900/70 dark:text-emerald-200"
                    >
                      {{ person.label }}
                      <button type="button" class="text-slate-400 transition hover:text-rose-500" @click.stop="removeMemberEntry(person.member)">
                        <span class="material-icons text-[13px]">close</span>
                      </button>
                    </span>
                    <span v-if="!selectedPicDetails.length" class="text-xs font-normal text-slate-400">Belum ada PIC dipilih</span>
                  </div>
                  <span class="material-icons text-base text-slate-400 transition duration-200" :class="picDropdownOpen ? 'rotate-180 text-emerald-500' : ''">expand_more</span>
                </button>
                <transition name="fade-scale">
                  <div
                    v-if="picDropdownOpen"
                    class="absolute left-0 right-0 top-full z-[9999] mt-2 w-full rounded-xl rounded-t-none border border-slate-200 bg-white p-3 shadow-2xl md:p-4 dark:border-slate-700 dark:bg-slate-900"
                    :class="picDropdownOpen ? 'border-emerald-400' : ''"
                  >
                    <div class="flex flex-wrap gap-2 border-b border-slate-100 pb-3 dark:border-slate-800">
                      <button
                        v-for="unit in allUnits"
                        :key="`pic-unit-${unit}`"
                        type="button"
                        class="rounded-full border px-3 py-1 text-xs font-semibold transition"
                        :class="activePicUnit === unit ? 'border-emerald-500 bg-emerald-50 text-emerald-700 dark:border-emerald-400 dark:bg-emerald-500/10 dark:text-emerald-100' : 'border-slate-200 text-slate-500 hover:border-emerald-300 hover:text-emerald-600 dark:border-slate-700 dark:text-slate-300'"
                        @click="activePicUnit = unit"
                      >
                        {{ unit }}
                      </button>
                      <p v-if="!allUnits.length" class="text-xs text-slate-400">Belum ada unit terdaftar.</p>
                    </div>
                    <div class="mt-3 max-h-64 space-y-2 overflow-y-auto pr-1">
                      <label
                        v-for="option in activePicList"
                        :key="`pic-option-${option.id}`"
                        class="flex items-start justify-between gap-3 rounded-xl border border-slate-200 px-3 py-2 text-sm shadow-sm transition hover:border-emerald-400 dark:border-slate-700"
                      >
                        <div class="flex items-start gap-3">
                          <input
                            type="checkbox"
                            class="mt-1 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                            :checked="isPicSelected(option.id)"
                            @change="togglePicSelection(option.id)"
                          />
                          <div>
                            <p class="font-semibold text-slate-700 dark:text-slate-100">{{ option.label }}</p>
                            <p class="text-xs text-slate-400">{{ option.email || '—' }} · {{ option.unit || unitPlaceholderLabel }}</p>
                          </div>
                        </div>
                        <button
                          type="button"
                          class="rounded-full px-2 py-1 text-[11px] font-semibold uppercase tracking-wide transition"
                          :class="picPrimary === normalizeSelectionId(option.id) ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-100' : 'text-slate-400 hover:text-emerald-500'"
                          @click.stop="setPicPrimary(option.id)"
                        >
                          {{ picPrimary === normalizeSelectionId(option.id) ? 'Utama' : 'Jadikan Utama' }}
                        </button>
                      </label>
                      <p v-if="!activePicList.length" class="text-sm text-slate-400">Belum ada PIC pada unit ini.</p>
                    </div>
                  </div>
                </transition>
              </div>
              <p class="mt-2 text-xs text-slate-400">
                {{ picMembers.length ? picMembers.length + ' PIC terhubung' : 'Belum ada PIC' }}
              </p>
              <div class="mt-4 space-y-3">
                <div
                  v-for="member in picMembers"
                  :key="`pic-member-${member.user_id || form.project_pics.indexOf(member)}`"
                  class="rounded-2xl border border-emerald-100 bg-emerald-50/40 p-3 dark:border-emerald-500/30 dark:bg-emerald-500/5"
                >
                  <div class="flex items-start justify-between gap-3">
                    <div>
                      <p class="text-sm font-semibold text-slate-700 dark:text-slate-100">{{ userInfo(member)?.label || 'Anggota tidak dikenal' }}</p>
                      <p class="text-xs text-slate-400">
                        {{ userInfo(member)?.email || '—' }} · {{ userInfo(member)?.unit || unitPlaceholderLabel }}
                      </p>
                    </div>
                    <button type="button" class="text-rose-500 hover:text-rose-600" @click="removeMemberEntry(member)">
                      <span class="material-icons text-base">delete</span>
                    </button>
                  </div>
                  <label class="mt-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Peran</label>
                  <input
                    v-model="member.position"
                    type="text"
                    class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-200 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                    placeholder="Contoh: Project Manager"
                  />
                  <p v-if="projectPicError(member, 'position')" class="mt-1 text-xs text-red-500">{{ projectPicError(member, 'position') }}</p>
                  <div class="mt-2 flex items-center justify-between text-xs text-slate-500">
                    <button
                      type="button"
                      class="inline-flex items-center gap-1 rounded-full border px-3 py-1 text-[11px] font-semibold uppercase tracking-wide transition"
                      :class="member.is_primary ? 'border-emerald-500 bg-emerald-50 text-emerald-600 dark:border-emerald-300 dark:bg-emerald-500/10 dark:text-emerald-100' : 'border-slate-200 text-slate-500 hover:border-emerald-300 hover:text-emerald-600'"
                      @click="setPicPrimary(member.user_id)"
                    >
                      <span class="material-icons text-xs">verified_user</span>
                      {{ member.is_primary ? 'PIC Utama' : 'Jadikan Utama' }}
                    </button>
                  </div>
                </div>
                <p v-if="!picMembers.length" class="text-xs text-slate-400">Belum ada data.</p>
              </div>
            </div>
          </div>
          <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-700 dark:bg-slate-900/60">
            <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-100">Ringkasan Pilihan</h3>
            <div class="mt-3 grid gap-3 sm:grid-cols-2">
              <div class="rounded-xl border border-blue-200/60 bg-white p-3 dark:border-blue-500/30 dark:bg-slate-900">
                <div class="mb-2 flex items-center gap-2 text-sm font-semibold text-blue-700 dark:text-blue-200">
                  <span class="material-icons text-base">shield_person</span>
                  <span>Agent ({{ selectedAgentDetails.length }})</span>
                </div>
                <div class="flex flex-wrap gap-1">
                  <span
                    v-for="person in selectedAgentDetails"
                    :key="`summary-agent-${person.id}`"
                    class="text-xs rounded-md border border-blue-200 bg-white px-2 py-0.5 text-blue-600 dark:border-blue-400/40 dark:bg-slate-900 dark:text-blue-100"
                  >
                    {{ person.label }}
                    <span v-if="person.member.position" class="text-slate-400"> · {{ person.member.position }}</span>
                  </span>
                  <span v-if="!selectedAgentDetails.length" class="text-xs text-slate-400">Belum ada data</span>
                </div>
              </div>
              <div class="rounded-xl border border-emerald-200/60 bg-white p-3 dark:border-emerald-500/30 dark:bg-slate-900">
                <div class="mb-2 flex items-center gap-2 text-sm font-semibold text-emerald-700 dark:text-emerald-200">
                  <span class="material-icons text-base">verified_user</span>
                  <span>PIC ({{ selectedPicDetails.length }})</span>
                </div>
                <div class="flex flex-wrap gap-1">
                  <span
                    v-for="person in selectedPicDetails"
                    :key="`summary-pic-${person.id}`"
                    class="text-xs rounded-md border border-emerald-200 bg-white px-2 py-0.5 text-emerald-600 dark:border-emerald-400/40 dark:bg-slate-900 dark:text-emerald-100"
                  >
                    {{ person.label }}
                    <span v-if="person.member.position" class="text-slate-400"> · {{ person.member.position }}</span>
                  </span>
                  <span v-if="!selectedPicDetails.length" class="text-xs text-slate-400">Belum ada data</span>
                </div>
              </div>
            </div>
          </div>
          <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">
            Minimal satu PIC wajib ditetapkan. Agent bertindak sebagai penanggung jawab utama project.
          </p>
        </section>

        <section v-if="mountedSteps.team" v-show="showStep('team')" class="panel relative z-0" :class="{ 'panel--inactive': !showStep('team') }">
          <div class="panel-title">
            <div class="panel-icon indigo">
              <span class="material-icons">playlist_add_check</span>
            </div>
            <div>
              <h2>Action Plan</h2>
              <p>Daftar aktivitas utama project.</p>
            </div>
            <button type="button" class="chip-action" @click="addAction">
              <span class="material-icons">add_circle</span>
              Tambah Action
            </button>
          </div>

          <div v-if="!form.project_actions.length" class="empty-hint">
            Belum ada action. Tambahkan agar progress project tetap terpantau.
          </div>

          <div class="stacked-list">
            <div v-for="(action, index) in form.project_actions" :key="`action-${index}`" class="card-row">
              <div class="card-row__grid card-row__grid--two">
                <div class="panel-field col-span-2">
                  <label>Judul Action</label>
                  <input v-model="action.title" type="text" placeholder="Contoh: Workshop Implementasi" />
                  <p v-if="form.errors[`project_actions.${index}.title`]" class="field-error">{{ form.errors[`project_actions.${index}.title`] }}</p>
                </div>
                <div class="panel-field">
                  <label>Status</label>
                  <FancySelect v-model="action.status_id" :options="statusSelectOptions" />
                  <p class="mt-1 text-xs text-slate-400">Pilih status untuk action plan ini.</p>
                </div>
                <div class="panel-field">
                  <label>Progress (%)</label>
                  <input v-model.number="action.progress" type="number" min="0" max="100" />
                  <p v-if="form.errors[`project_actions.${index}.progress`]" class="field-error">{{ form.errors[`project_actions.${index}.progress`] }}</p>
                </div>
                <div class="panel-field">
                  <label>Mulai</label>
                  <DatePickerFlatpickr v-model="action.start_date" :config="dateConfig" placeholder="dd/mm/yyyy" />
                  <p v-if="form.errors[`project_actions.${index}.start_date`]" class="field-error">{{ form.errors[`project_actions.${index}.start_date`] }}</p>
                </div>
                <div class="panel-field">
                  <label>Selesai</label>
                  <DatePickerFlatpickr v-model="action.end_date" :config="dateConfig" placeholder="dd/mm/yyyy" />
                  <p v-if="form.errors[`project_actions.${index}.end_date`]" class="field-error">{{ form.errors[`project_actions.${index}.end_date`] }}</p>
                </div>
                <div class="panel-field col-span-2">
                  <label>Deskripsi</label>
                  <textarea v-model="action.description" rows="2" placeholder="Penjelasan singkat action"></textarea>
                  <p v-if="form.errors[`project_actions.${index}.description`]" class="field-error">{{ form.errors[`project_actions.${index}.description`] }}</p>
                </div>
              </div>
              <button type="button" class="card-remove" @click="removeAction(index)">
                <span class="material-icons">delete</span>
              </button>
            </div>
          </div>
        </section>

        <section v-if="mountedSteps.budget" v-show="showStep('budget')" class="panel" :class="{ 'panel--inactive': !showStep('budget') }">
          <div class="panel-title">
            <div class="panel-icon amber">
              <span class="material-icons">attach_money</span>
            </div>
            <div>
              <h2>Rincian Budget</h2>
              <p>Catat estimasi dan realisasi biaya.</p>
            </div>
            <button type="button" class="chip-action" @click="addCost">
              <span class="material-icons">add</span>
              Tambah Biaya
            </button>
          </div>

          <div v-if="!form.project_costs.length" class="empty-hint">
            Tambahkan biaya untuk memonitor alokasi budget.
          </div>

          <div class="stacked-list">
            <div v-for="(cost, index) in form.project_costs" :key="`cost-${index}`" class="card-row">
              <div class="card-row__grid card-row__grid--two">
                <div class="panel-field">
                  <label>Item Biaya</label>
                  <input v-model="cost.cost_item" type="text" placeholder="Contoh: Hardware" />
                  <p v-if="form.errors[`project_costs.${index}.cost_item`]" class="field-error">{{ form.errors[`project_costs.${index}.cost_item`] }}</p>
                </div>
                <div class="panel-field">
                  <label>Kategori</label>
                  <input v-model="cost.category" type="text" placeholder="Contoh: Infrastruktur" />
                  <p v-if="form.errors[`project_costs.${index}.category`]" class="field-error">{{ form.errors[`project_costs.${index}.category`] }}</p>
                </div>
                <div class="panel-field">
                  <label>Estimasi</label>
                  <input v-model.number="cost.estimated_cost" type="number" min="0" :max="MAX_COST_VALUE" step="0.01" placeholder="0" />
                  <p v-if="form.errors[`project_costs.${index}.estimated_cost`]" class="field-error">{{ form.errors[`project_costs.${index}.estimated_cost`] }}</p>
                </div>
                <div class="panel-field">
                  <label>Aktual</label>
                  <input v-model.number="cost.actual_cost" type="number" min="0" :max="MAX_COST_VALUE" step="0.01" placeholder="0" />
                  <p v-if="form.errors[`project_costs.${index}.actual_cost`]" class="field-error">{{ form.errors[`project_costs.${index}.actual_cost`] }}</p>
                </div>
                <div class="panel-field col-span-2">
                  <label>Catatan</label>
                  <textarea v-model="cost.notes" rows="2" placeholder="Catatan tambahan"></textarea>
                  <p v-if="form.errors[`project_costs.${index}.notes`]" class="field-error">{{ form.errors[`project_costs.${index}.notes`] }}</p>
                </div>
              </div>
              <button type="button" class="card-remove" @click="removeCost(index)">
                <span class="material-icons">delete</span>
              </button>
            </div>
          </div>
        </section>

        <section v-if="mountedSteps.budget" v-show="showStep('budget')" class="panel" :class="{ 'panel--inactive': !showStep('budget') }">
          <div class="panel-title">
            <div class="panel-icon rose">
              <span class="material-icons">warning_amber</span>
            </div>
            <div>
              <h2>Mitigasi Risiko</h2>
              <p>Kelola risiko dan rencana mitigasinya.</p>
            </div>
            <button type="button" class="chip-action" @click="addRisk">
              <span class="material-icons">add_alert</span>
              Tambah Risiko
            </button>
          </div>

          <div v-if="!form.project_risks.length" class="empty-hint">
            Identifikasi risiko untuk mengurangi potensi kendala.
          </div>

          <div class="stacked-list">
            <div v-for="(risk, index) in form.project_risks" :key="`risk-${index}`" class="card-row">
              <div class="card-row__grid card-row__grid--two">
                <div class="panel-field">
                  <label>Nama Risiko</label>
                  <input v-model="risk.name" type="text" placeholder="Contoh: Delay supplier" />
                  <p v-if="form.errors[`project_risks.${index}.name`]" class="field-error">{{ form.errors[`project_risks.${index}.name`] }}</p>
                </div>
                <div class="panel-field">
                  <label>Status</label>
                  <FancySelect v-model="risk.status_id" :options="statusSelectOptions" />
                  <p class="mt-1 text-xs text-slate-400">Pilih status risiko.</p>
                  <p v-if="form.errors[`project_risks.${index}.status_id`]" class="field-error">{{ form.errors[`project_risks.${index}.status_id`] }}</p>
                </div>
                <div class="panel-field">
                  <label>Dampak</label>
                  <FancySelect v-model="risk.impact" :options="riskImpactOptions" />
                  <p class="mt-1 text-xs text-slate-400">Pilih tingkat dampak risiko.</p>
                  <p v-if="form.errors[`project_risks.${index}.impact`]" class="field-error">{{ form.errors[`project_risks.${index}.impact`] }}</p>
                </div>
                <div class="panel-field">
                  <label>Kemungkinan</label>
                  <FancySelect v-model="risk.likelihood" :options="riskLikelihoodOptions" />
                  <p class="mt-1 text-xs text-slate-400">Pilih kemungkinan terjadinya risiko.</p>
                  <p v-if="form.errors[`project_risks.${index}.likelihood`]" class="field-error">{{ form.errors[`project_risks.${index}.likelihood`] }}</p>
                </div>
                <div class="panel-field col-span-2">
                  <label>Deskripsi</label>
                  <textarea v-model="risk.description" rows="2" placeholder="Jelaskan risiko"></textarea>
                  <p v-if="form.errors[`project_risks.${index}.description`]" class="field-error">{{ form.errors[`project_risks.${index}.description`] }}</p>
                </div>
                <div class="panel-field col-span-2">
                  <label>Rencana Mitigasi</label>
                  <textarea v-model="risk.mitigation_plan" rows="2" placeholder="Langkah mitigasi"></textarea>
                  <p v-if="form.errors[`project_risks.${index}.mitigation_plan`]" class="field-error">{{ form.errors[`project_risks.${index}.mitigation_plan`] }}</p>
                </div>
              </div>
              <button type="button" class="card-remove" @click="removeRisk(index)">
                <span class="material-icons">delete</span>
              </button>
            </div>
          </div>
        </section>

        <section v-if="mountedSteps.deliverables" v-show="showStep('deliverables')" class="panel" :class="{ 'panel--inactive': !showStep('deliverables') }">
          <div class="panel-title">
            <div class="panel-icon teal">
              <span class="material-icons">task_alt</span>
            </div>
            <div>
              <h2>Deliverables</h2>
              <p>Keluaran utama project dan status verifikasi.</p>
            </div>
            <button type="button" class="chip-action" @click="addDeliverable">
              <span class="material-icons">playlist_add</span>
              Tambah Deliverable
            </button>
          </div>

          <div v-if="!form.project_deliverables.length" class="empty-hint">
            Tambahkan deliverable untuk menandai output project.
          </div>

          <div class="stacked-list">
            <div v-for="(deliverable, index) in form.project_deliverables" :key="`deliverable-${index}`" class="card-row">
              <div class="card-row__grid card-row__grid--two">
                <div class="panel-field col-span-2">
                  <label>Nama Deliverable</label>
                  <input v-model="deliverable.name" type="text" placeholder="Contoh: Dashboard Analytics" />
                  <p v-if="form.errors[`project_deliverables.${index}.name`]" class="field-error">{{ form.errors[`project_deliverables.${index}.name`] }}</p>
                </div>
                <div class="panel-field">
                  <label>Status</label>
                  <FancySelect v-model="deliverable.status_id" :options="statusSelectOptions" />
                  <p class="mt-1 text-xs text-slate-400">Pilih status deliverable.</p>
                  <p v-if="form.errors[`project_deliverables.${index}.status_id`]" class="field-error">{{ form.errors[`project_deliverables.${index}.status_id`] }}</p>
                </div>
                <div class="panel-field">
                  <label>Verified By</label>
                  <FancySelect v-model="deliverable.verified_by" :options="verifiedByOptions" />
                  <p class="mt-1 text-xs text-slate-400">Pilih pihak yang memverifikasi deliverable.</p>
                  <p v-if="form.errors[`project_deliverables.${index}.verified_by`]" class="field-error">{{ form.errors[`project_deliverables.${index}.verified_by`] }}</p>
                </div>
                <div class="panel-field">
                  <label>Selesai Pada</label>
                  <DatePickerFlatpickr v-model="deliverable.completed_at" :config="dateTimeConfig" placeholder="dd/mm/yyyy HH:mm" />
                  <p v-if="form.errors[`project_deliverables.${index}.completed_at`]" class="field-error">{{ form.errors[`project_deliverables.${index}.completed_at`] }}</p>
                </div>
                <div class="panel-field">
                  <label>Terverifikasi Pada</label>
                  <DatePickerFlatpickr v-model="deliverable.verified_at" :config="dateTimeConfig" placeholder="dd/mm/yyyy HH:mm" />
                  <p v-if="form.errors[`project_deliverables.${index}.verified_at`]" class="field-error">{{ form.errors[`project_deliverables.${index}.verified_at`] }}</p>
                </div>
                <div class="panel-field col-span-2">
                  <label>Deskripsi</label>
                  <textarea v-model="deliverable.description" rows="2" placeholder="Detail deliverable"></textarea>
                  <p v-if="form.errors[`project_deliverables.${index}.description`]" class="field-error">{{ form.errors[`project_deliverables.${index}.description`] }}</p>
                </div>
              </div>
              <button type="button" class="card-remove" @click="removeDeliverable(index)">
                <span class="material-icons">delete</span>
              </button>
            </div>
          </div>
        </section>

        <section v-if="mountedSteps.deliverables" v-show="showStep('deliverables')" class="panel" :class="{ 'panel--inactive': !showStep('deliverables') }">
          <div class="panel-title">
            <div class="panel-icon slate">
              <span class="material-icons">attach_file</span>
            </div>
            <div>
              <h2>Lampiran</h2>
              <p>Unggah dokumen pendukung project.</p>
            </div>
            <label class="toggle">
              <input v-model="showUploader" type="checkbox" />
              <span>Tambahkan lampiran baru</span>
            </label>
          </div>

          <div v-if="showTicketAttachmentsSection" class="mt-3 space-y-2">
            <p class="text-xs font-medium text-slate-600 dark:text-slate-300">
              Lampiran dari ticket terkait:
              <span v-if="ticketAttachmentList.length">{{ ticketAttachmentList.length }} file.</span>
              <span v-else>Belum ada lampiran ticket.</span>
            </p>
            <TicketAttachmentList :attachments="ticketAttachmentList" />
          </div>

          <transition name="fade">
            <div v-show="showUploader" class="attachment-uploader space-y-3">
              <div class="space-y-2">
                <div>
                  <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Jenis File</label>
                  <FancySelect v-model="selectedAttachmentFilter" :options="attachmentFilterSelectOptions" class="mt-1" />
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
                    <span>{{ attachmentIds.length ? attachmentIds.length + ' file siap dikirim' : 'Belum ada file siap dikirim' }}</span>
                    <button
                      v-if="attachmentIds.length"
                      type="button"
                      class="text-indigo-600 hover:underline dark:text-indigo-400"
                      @click="clearNewAttachments"
                    >
                      Bersihkan Lampiran
                    </button>
                  </div>
                </div>
              </div>
              <p v-if="uploaderDisabled" class="field-hint text-amber-600">Pilih jenis file untuk mengaktifkan unggahan.</p>
              <p v-if="uploadError" class="field-error">{{ uploadError }}</p>
              <p v-if="form.errors.attachments" class="field-error">{{ form.errors.attachments }}</p>
            </div>
          </transition>

          <div v-if="existingAttachments.length" class="attachment-list">
            <header>Lampiran Saat Ini</header>
            <ul>
              <li v-for="item in existingAttachments" :key="item.id">
                <div>
                  <strong>{{ item.name }}</strong>
                  <span v-if="item.size" class="size">({{ formatSize(item.size) }})</span>
                </div>
                <div class="attachment-actions">
                  <a :href="item.view_url" target="_blank">Lihat</a>
                  <a :href="item.download_url">Unduh</a>
                  <button type="button" @click="removeExistingAttachment(item)">Hapus</button>
                </div>
              </li>
            </ul>
          </div>
        </section>

        <footer class="edit-actions">
          <Link :href="meta.backUrl || route('projects.report')" class="btn-secondary">
            <span class="material-icons">close</span>
            Batal
          </Link>
          <button type="submit" class="btn-primary" :disabled="form.processing">
            <span class="material-icons" v-if="form.processing">hourglass_top</span>
            <span class="material-icons" v-else>{{ primaryButtonIcon }}</span>
            {{ primaryButtonLabel }}
          </button>
        </footer>
      </form>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import DatePickerFlatpickr from '@/Components/DatePickerFlatpickr.vue';
import FileUploaderPond from '@/Components/FileUploaderPond.vue';
import FancySelect from '@/Components/FancySelect.vue';
import StatusPill from '@/Components/StatusPill.vue';
import RichTextQuill from '@/Components/RichTextQuill.vue';
import TicketAttachmentList from '@/Components/TicketAttachmentList.vue';
import { attachmentFilterOptions } from '@/utils/attachmentFilters';

const props = defineProps({
  project: { type: Object, required: true },
  options: { type: Object, required: true },
  meta: { type: Object, required: true },
});

const steps = [
  { key: 'overview', label: 'Informasi Utama' },
  { key: 'timeline', label: 'Deskripsi & Timeline' },
  { key: 'team', label: 'Tim & Action Plan' },
  { key: 'budget', label: 'Budget & Risiko' },
  { key: 'deliverables', label: 'Deliverables & Lampiran' },
];

const stepFieldPrefixes = {
  overview: ['title', 'project_no', 'status', 'ticket_id'],
  timeline: ['description', 'start_date', 'end_date'],
  team: ['project_pics', 'project_actions'],
  budget: ['project_costs', 'project_risks'],
  deliverables: ['project_deliverables', 'attachments'],
};

const isoDatePattern = /^\d{4}-\d{2}-\d{2}$/;
const isoDateTimePattern = /^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}$/;
const localeDatePattern = /^\d{2}\/\d{2}\/\d{4}$/;
const localeDateTimePattern = /^\d{2}\/\d{2}\/\d{4}\s+\d{2}:\d{2}$/;
const MAX_COST_VALUE = 999999999999.99;

function toIsoDate(value) {
  if (!value) return null;
  if (value instanceof Date && !Number.isNaN(value.getTime())) {
    return value.toISOString().slice(0, 10);
  }
  const raw = String(value).trim();
  if (!raw) return null;
  if (isoDatePattern.test(raw)) {
    return raw;
  }
  if (isoDateTimePattern.test(raw)) {
    return raw.slice(0, 10);
  }
  if (localeDatePattern.test(raw)) {
    const [day, month, year] = raw.split('/');
    return `${year}-${month}-${day}`;
  }
  const parsed = new Date(raw);
  if (!Number.isNaN(parsed.getTime())) {
    return parsed.toISOString().slice(0, 10);
  }
  return raw;
}

function toIsoDateTime(value) {
  if (!value) return null;
  if (value instanceof Date && !Number.isNaN(value.getTime())) {
    return value.toISOString().replace('T', ' ').slice(0, 16);
  }
  const raw = String(value).trim();
  if (!raw) return null;
  if (localeDateTimePattern.test(raw)) {
    const [datePart, timePart] = raw.split(/\s+/);
    const [day, month, year] = datePart.split('/');
    return `${year}-${month}-${day} ${timePart}`;
  }
  if (localeDatePattern.test(raw)) {
    const [day, month, year] = raw.split('/');
    return `${year}-${month}-${day} 00:00`;
  }
  if (isoDateTimePattern.test(raw)) {
    return raw.replace('T', ' ').slice(0, 16);
  }
  if (isoDatePattern.test(raw)) {
    return `${raw} 00:00`;
  }
  const parsed = new Date(raw);
  if (!Number.isNaN(parsed.getTime())) {
    return parsed.toISOString().replace('T', ' ').slice(0, 16);
  }
  return raw;
}

function clampCost(value) {
  if (value === null || value === undefined || value === '') {
    return null;
  }
  const numeric = Number(value);
  if (Number.isNaN(numeric)) {
    return null;
  }
  if (numeric < 0) {
    return 0;
  }
  if (numeric > MAX_COST_VALUE) {
    return MAX_COST_VALUE;
  }
  return Number(numeric.toFixed(2));
}

const statusCatalog = computed(() => props.options.statuses || []);
const workflowStatusOptions = computed(() => props.options.workflow_statuses || []);
const statusSelectOptions = computed(() => statusCatalog.value.map(status => ({
  value: status.id,
  label: status.name ?? status.label ?? String(status.id ?? 'Status'),
  disabled: status.disabled,
})));
const riskImpactOptions = computed(() => (props.options?.impact ?? []).map(option => ({
  value: option,
  label: capitalize(option),
})));
const riskLikelihoodOptions = computed(() => (props.options?.likelihood ?? []).map(option => ({
  value: option,
  label: formatLikelihood(option),
})));
const verifiedByOptions = computed(() => (props.options?.verified_by ?? []).map(option => ({
  value: option,
  label: capitalize(option),
})));
const mode = computed(() => String(props.meta?.mode ?? 'edit'));
const isCreate = computed(() => mode.value === 'create');

const currentStep = ref(0);
const currentStepKey = computed(() => steps[currentStep.value].key);
const isLastStep = computed(() => currentStep.value === steps.length - 1);

const mountedSteps = ref({
  [steps[currentStep.value].key]: true,
});

function ensureStepMounted(key) {
  if (!mountedSteps.value[key]) {
    mountedSteps.value[key] = true;
  }
}

const breadcrumbLabel = computed(() => (isCreate.value ? 'Project � Buat' : 'Project � Ubah'));
const titleLabel = computed(() => (isCreate.value ? 'Buat Project' : 'Edit Project'));
const subtitleLabel = computed(() => (
  isCreate.value
    ? 'Lengkapi informasi project baru sebelum dipublikasikan.'
    : 'Perbarui informasi project tanpa meninggalkan tampilan Inertia.'
));
const submitButtonLabel = computed(() => (isCreate.value ? 'Simpan Project' : 'Simpan Perubahan'));
const ticketPlaceholder = computed(() => (isCreate.value ? '— Tanpa Ticket —' : '— Tidak diubah —'));
const ticketOptions = computed(() => props.options?.tickets || []);
const metaTicketUnits = computed(() => {
  const raw = props.meta?.ticketUnits;
  if (Array.isArray(raw)) {
    return raw.filter(unit => typeof unit === 'string' && unit.trim() !== '');
  }
  return [];
});
const ticketLookup = computed(() => {
  const map = new Map();
  ticketOptions.value.forEach(option => {
    if (option?.id !== undefined && option?.id !== null) {
      map.set(String(option.id), option);
    }
  });
  return map;
});
const ticketUnitPlaceholder = 'Unit Tidak Tercatat';
const ticketUnits = computed(() => {
  const set = new Set();
  // unit dari user/agent (allUnits sudah mencakup metaUnitOptions + userOptions) — ikuti urutan create task
  allUnits.value.forEach(unit => {
    if (typeof unit === 'string' && unit.trim() !== '') {
      set.add(unit.trim());
    }
  });
  // unit dari meta (server)
  metaTicketUnits.value.forEach(unit => {
    if (typeof unit === 'string' && unit.trim() !== '') {
      set.add(unit.trim());
    }
  });
  // unit dari ticket options
  ticketOptions.value.forEach(option => {
    const unit = typeof option?.unit === 'string' && option.unit.trim() !== '' ? option.unit.trim() : ticketUnitPlaceholder;
    set.add(unit);
  });
  if (!set.size) {
    set.add(ticketUnitPlaceholder);
  }
  return Array.from(set);
});
const ticketsByUnit = computed(() => {
  const map = {};
  ticketUnits.value.forEach(unit => {
    map[unit] = [];
  });
  ticketOptions.value.forEach(option => {
    const unit = typeof option?.unit === 'string' && option.unit.trim() !== '' ? option.unit.trim() : ticketUnitPlaceholder;
    if (!map[unit]) {
      map[unit] = [];
    }
    map[unit].push(option);
  });
  return map;
});
const primaryButtonLabel = computed(() => (isLastStep.value ? submitButtonLabel.value : 'Lanjut'));
const primaryButtonIcon = computed(() => (isLastStep.value ? 'save' : 'arrow_forward'));
const statusGuide = computed(() => props.meta?.statusGuide ?? null);
const statusLocked = computed(() => Boolean(props.meta?.lockStatus));
const statusDefaultLabel = computed(() => {
  const candidateId = props.project.status ?? workflowStatusOptions.value[0]?.id ?? 'new';
  const option = workflowStatusOptions.value.find(entry => entry.id === candidateId);
  return option?.name ?? props.project.status_label ?? 'Status default';
});

const submitUrl = computed(() => props.meta?.submitUrl ?? route('projects.update', props.project.id));
const submitMethod = computed(() => String(props.meta?.submitMethod ?? (isCreate.value ? 'post' : 'put')).toLowerCase());

const ROLE_AGENT = 'agent';
const ROLE_PIC = 'pic';

const normalizeRole = value => (value === ROLE_AGENT ? ROLE_AGENT : ROLE_PIC);
const normalizeSelectionId = value => {
  if (typeof value === 'number' && Number.isFinite(value)) {
    return value;
  }
  if (typeof value === 'string' && value.trim() !== '' && !Number.isNaN(Number(value))) {
    return Number(value);
  }
  return null;
};

function createMember(role = ROLE_PIC) {
  return {
    user_id: null,
    position: '',
    role_type: normalizeRole(role),
    is_primary: false,
  };
}

function buildMembersFromTicket() {
  const members = [];
  const ticket = props.project.ticket ?? null;
  if (!ticket) return members;

  const agentId = normalizeSelectionId(ticket.agent_id) || normalizeSelectionId(ticket.assigned_id);
  if (agentId) {
    members.push({
      ...createMember(ROLE_AGENT),
      user_id: agentId,
      is_primary: true,
    });
  }

  // Gunakan assigned_ids dari ticket (assignedUsers) untuk mengisi PIC awal.
  // Jika tidak ada assigned_ids, coba gunakan assigned_id sebagai fallback.
  const assignedIds = Array.isArray(ticket.assigned_ids)
    ? ticket.assigned_ids.map(normalizeSelectionId).filter(Boolean)
    : [];
  if (!assignedIds.length && normalizeSelectionId(ticket.assigned_id)) {
    assignedIds.push(normalizeSelectionId(ticket.assigned_id));
  }

  assignedIds.forEach(id => {
    if (id) {
      members.push({
        ...createMember(ROLE_PIC),
        user_id: id,
        is_primary: false,
      });
    }
  });

  return members;
}

function normalizeMember(item) {
  if (!item) {
    return createMember();
  }

  return {
    user_id: item.user_id ?? null,
    position: item.position ?? '',
    role_type: normalizeRole(item.role_type),
    is_primary: Boolean(item.is_primary),
  };
}

function enforcePrimary(list, role) {
  const targetRole = normalizeRole(role);
  const members = list.filter(member => normalizeRole(member.role_type) === targetRole);
  if (!members.length) {
    return;
  }

  let primaryFound = false;
  members.forEach(member => {
    member.role_type = normalizeRole(member.role_type);
    if (member.is_primary) {
      if (primaryFound) {
        member.is_primary = false;
      } else {
        primaryFound = true;
      }
    }
  });

  if (!members.some(member => member.is_primary)) {
    members[0].is_primary = true;
  }
}

const initialTeamMembers = props.project.pics?.length
  ? props.project.pics.map(item => normalizeMember(item))
  : buildMembersFromTicket();

enforcePrimary(initialTeamMembers, ROLE_AGENT);
enforcePrimary(initialTeamMembers, ROLE_PIC);

const form = useForm({
  title: props.project.title ?? '',
  project_no: props.project.project_no ?? '',
  status: props.project.status ?? workflowStatusOptions.value[0]?.id ?? 'new',
  ticket_id: props.project.ticket_id ?? null,
  description: props.project.description ?? '',
  start_date: props.project.start_date ?? null,
  end_date: props.project.end_date ?? null,
  project_pics: initialTeamMembers,
  project_actions: props.project.actions?.length
    ? props.project.actions.map(item => ({
        title: item.title ?? '',
        status_id: item.status_id ?? null,
        progress: item.progress ?? 0,
        start_date: item.start_date ?? null,
        end_date: item.end_date ?? null,
        description: item.description ?? '',
      }))
    : [],
  project_costs: props.project.costs?.length
    ? props.project.costs.map(item => ({
        cost_item: item.cost_item ?? '',
        category: item.category ?? '',
        estimated_cost: item.estimated_cost ?? null,
        actual_cost: item.actual_cost ?? null,
        notes: item.notes ?? '',
      }))
    : [],
  project_risks: props.project.risks?.length
    ? props.project.risks.map(item => ({
        name: item.name ?? '',
        status_id: item.status_id ?? null,
        impact: item.impact ?? null,
        likelihood: item.likelihood ?? null,
        description: item.description ?? '',
        mitigation_plan: item.mitigation_plan ?? '',
      }))
    : [],
  project_deliverables: props.project.deliverables?.length
    ? props.project.deliverables.map(item => ({
        name: item.name ?? '',
        status_id: item.status_id ?? null,
        verified_by: item.verified_by ?? null,
        completed_at: item.completed_at ?? null,
        verified_at: item.verified_at ?? null,
        description: item.description ?? '',
      }))
    : [],
  attachments: [],
  from: props.meta.backUrl ?? null,
});

const allowedStatusValues = computed(() => (Array.isArray(props.meta?.allowedStatuses) ? props.meta.allowedStatuses : []));
const filteredWorkflowStatuses = computed(() => {
  let baseOptions = workflowStatusOptions.value || [];
  // Hilangkan opsi New pada edit, kecuali status saat ini New (tampilkan sebagai terkunci)
  if (!isCreate.value && form.status !== 'new') {
    baseOptions = baseOptions.filter(option => option.id !== 'new');
  } else if (!isCreate.value && form.status === 'new' && !baseOptions.some(opt => opt.id === 'new')) {
    baseOptions = [{ id: 'new', name: 'New', disabled: true }, ...baseOptions];
  }
  if (!allowedStatusValues.value.length) {
    return baseOptions;
  }
  const allowedSet = new Set([
    ...allowedStatusValues.value,
    form.status ?? null,
  ].filter(Boolean));
  const filtered = baseOptions.filter(option => allowedSet.has(option.id));

  const finalOptions = filtered.length ? filtered : baseOptions;
  return finalOptions.map(option => {
    const isLockedNew = !isCreate.value && option.id === 'new';
    return {
      ...option,
      disabled: option.disabled || isLockedNew,
      name: isLockedNew ? 'New' : option.name,
    };
  });
});
const workflowStatusSelectOptions = computed(() => filteredWorkflowStatuses.value.map(option => ({
  value: option.id,
  label: option.name ?? option.label ?? String(option.id ?? 'Status'),
  disabled: option.disabled,
})));

const statusDisplay = computed(() => {
  if (props.project.status_label && form.status === (props.project.status ?? form.status)) {
    return props.project.status_label;
  }

  const option = workflowStatusOptions.value.find(entry => entry.id === form.status);
  if (option?.name) {
    return option.name;
  }

  return props.project.status_label || 'Status Project';
});

const teamError = computed(() => form.errors?.project_pics ?? '');
const userLookup = computed(() => {
  const registry = new Map();
  (props.options.users || []).forEach(user => {
    if (user?.id) {
      registry.set(user.id, user);
    }
  });

  return registry;
});

function userInfo(member) {
  if (!member?.user_id) {
    return null;
  }

  return userLookup.value.get(member.user_id) ?? null;
}

const unitPlaceholderLabel = 'Unit Tidak Tersedia';
const metaUnitOptions = computed(() => {
  const raw = props.meta?.unitOptions;
  if (Array.isArray(raw)) {
    return raw.filter(unit => typeof unit === 'string' && unit.trim() !== '');
  }
  return [];
});
const userOptions = computed(() => props.options.users || []);
const allUnits = computed(() => {
  const registry = new Set(metaUnitOptions.value);
  userOptions.value.forEach(user => {
    const unit = typeof user?.unit === 'string' && user.unit.trim() ? user.unit.trim() : unitPlaceholderLabel;
    registry.add(unit);
  });

  return Array.from(registry);
});

const usersByUnit = computed(() => {
  const map = {};
  allUnits.value.forEach(unit => {
    map[unit] = [];
  });
  userOptions.value.forEach(user => {
    const unit = typeof user?.unit === 'string' && user.unit.trim() ? user.unit.trim() : unitPlaceholderLabel;
    if (!map[unit]) {
      map[unit] = [];
    }
    map[unit].push(user);
  });

  return map;
});

const agentCardRef = ref(null);
const picCardRef = ref(null);
const agentDropdownOpen = ref(false);
const picDropdownOpen = ref(false);
const activeAgentUnit = ref('');
const activePicUnit = ref('');
const activeAgentList = computed(() => usersByUnit.value[activeAgentUnit.value] ?? []);
const activePicList = computed(() => usersByUnit.value[activePicUnit.value] ?? []);
const ticketCardRef = ref(null);
const ticketDropdownOpen = ref(false);
const activeTicketUnit = ref('');
const selectedTicketId = ref(form.ticket_id ?? null);
const activeTicketList = computed(() => ticketsByUnit.value[activeTicketUnit.value] ?? []);
const selectedTicketOption = computed(() => {
  const normalized = normalizeSelectionId(selectedTicketId.value);
  if (!normalized) return null;
  return ticketLookup.value.get(String(normalized)) ?? null;
});
const ticketRequester = computed(() => props.project.ticket?.requester || null);
const initialTicketAttachments = computed(
  () => props.project.ticket_attachments ?? props.project.ticket?.attachments ?? []
);
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
const showTicketAttachmentsSection = computed(
  () => Boolean(form.ticket_id ?? selectedTicketId.value ?? initialTicketAttachments.value.length)
);

const teamInitialized = ref(false);
let stopUnitsWatch = null;

function initTeamSideEffects() {
  if (teamInitialized.value) return;
  teamInitialized.value = true;

  stopUnitsWatch = watch(allUnits, units => {
    if (!units.length) {
      activeAgentUnit.value = '';
      activePicUnit.value = '';
      return;
    }
    if (!units.includes(activeAgentUnit.value)) {
      activeAgentUnit.value = '';
    }
    if (!units.includes(activePicUnit.value)) {
      activePicUnit.value = '';
    }
    if (!ticketUnits.value.includes(activeTicketUnit.value)) {
      activeTicketUnit.value = '';
    }
  }, { immediate: true });

document.addEventListener('mousedown', handleTeamClickOutside);
  document.addEventListener('keydown', handleTeamKeydown, true);
}

watch(ticketUnits, units => {
  // Jangan otomatis memilih unit; biarkan user memilih sendiri
  if (!units.includes(activeTicketUnit.value)) {
    activeTicketUnit.value = '';
  }
}, { immediate: true });

watch(() => form.ticket_id, value => {
  const normalized = normalizeSelectionId(value);
  if (normalizeSelectionId(selectedTicketId.value) !== normalized) {
    selectedTicketId.value = normalized;
  }
});

watch(selectedTicketId, value => {
  const normalized = normalizeSelectionId(value);
  if (normalized && !ticketLookup.value.has(String(normalized))) {
    selectedTicketId.value = null;
    form.ticket_id = null;
    return;
  }
  form.ticket_id = normalized ?? null;
});

watch(ticketOptions, () => {
  const normalized = normalizeSelectionId(selectedTicketId.value);
  if (normalized && !ticketLookup.value.has(String(normalized))) {
    selectedTicketId.value = null;
  }
});

watch(selectedTicketOption, option => {
  if (option?.unit && typeof option.unit === 'string') {
    activeTicketUnit.value = option.unit;
  }
});

watch(
  () => mountedSteps.value.team,
  enabled => {
    if (enabled) {
      initTeamSideEffects();
    }
  },
  { immediate: true }
);

onMounted(() => {
  window.addEventListener('click', handleTicketOutsideClick, true);
});

const agentMembers = computed(() => form.project_pics.filter(member => normalizeRole(member.role_type) === ROLE_AGENT));
const picMembers = computed(() => form.project_pics.filter(member => normalizeRole(member.role_type) === ROLE_PIC));

const agentPrimary = computed(() => {
  const entry = agentMembers.value.find(member => member.is_primary);
  return normalizeSelectionId(entry?.user_id) ?? null;
});

const picPrimary = computed(() => {
  const entry = picMembers.value.find(member => member.is_primary);
  return normalizeSelectionId(entry?.user_id) ?? null;
});

const selectedAgentDetails = computed(() => agentMembers.value
  .map(member => {
    const info = userLookup.value.get(member.user_id);
    if (!info) {
      return null;
    }
    return { ...info, member };
  })
  .filter(Boolean));

const selectedPicDetails = computed(() => picMembers.value
  .map(member => {
    const info = userLookup.value.get(member.user_id);
    if (!info) {
      return null;
    }
    return { ...info, member };
  })
  .filter(Boolean));

function toggleAgentDropdown() {
  agentDropdownOpen.value = !agentDropdownOpen.value;
  if (agentDropdownOpen.value) {
    picDropdownOpen.value = false;
  }
}

function toggleTicketDropdown() {
  ticketDropdownOpen.value = !ticketDropdownOpen.value;
  if (ticketDropdownOpen.value) {
    agentDropdownOpen.value = false;
    picDropdownOpen.value = false;
  }
}

function selectTicket(id) {
  selectedTicketId.value = normalizeSelectionId(id);
  ticketDropdownOpen.value = false;
}

function clearTicketSelection() {
  selectedTicketId.value = null;
  ticketDropdownOpen.value = false;
}

function togglePicDropdown() {
  picDropdownOpen.value = !picDropdownOpen.value;
  if (picDropdownOpen.value) {
    agentDropdownOpen.value = false;
  }
}

function closeTeamDropdowns() {
  agentDropdownOpen.value = false;
  picDropdownOpen.value = false;
}

function handleTeamClickOutside(event) {
  if (
    (agentDropdownOpen.value && agentCardRef.value && agentCardRef.value.contains(event.target)) ||
    (picDropdownOpen.value && picCardRef.value && picCardRef.value.contains(event.target)) ||
    (ticketDropdownOpen.value && ticketCardRef.value && ticketCardRef.value.contains(event.target))
  ) {
    return;
  }
  closeTeamDropdowns();
  ticketDropdownOpen.value = false;
}

function handleTeamKeydown(event) {
  if (event.key !== 'Escape') return;
  closeTeamDropdowns();
  ticketDropdownOpen.value = false;
}

function handleTicketOutsideClick(event) {
  if (!ticketDropdownOpen.value) return;
  if (ticketCardRef.value && ticketCardRef.value.contains(event.target)) {
    return;
  }
  ticketDropdownOpen.value = false;
}

onBeforeUnmount(() => {
  if (stopUnitsWatch) {
    stopUnitsWatch();
    stopUnitsWatch = null;
  }
  document.removeEventListener('mousedown', handleTeamClickOutside);
  document.removeEventListener('keydown', handleTeamKeydown, true);
  window.removeEventListener('click', handleTicketOutsideClick, true);
});

function findMemberIndexByRole(role, userId) {
  const normalized = normalizeSelectionId(userId);
  if (!normalized) {
    return -1;
  }
  return form.project_pics.findIndex(member => (
    normalizeRole(member.role_type) === normalizeRole(role) &&
    normalizeSelectionId(member.user_id) === normalized
  ));
}

function addMemberEntry(role, userId) {
  const normalized = normalizeSelectionId(userId);
  if (!normalized || findMemberIndexByRole(role, normalized) >= 0) {
    return;
  }
  form.project_pics.push({
    user_id: normalized,
    position: '',
    role_type: normalizeRole(role),
    is_primary: false,
  });
  ensurePrimary(role);
}

function removeMemberEntry(member) {
  const index = form.project_pics.indexOf(member);
  if (index >= 0) {
    const role = normalizeRole(member.role_type);
    form.project_pics.splice(index, 1);
    ensurePrimary(role);
  }
}

function toggleAgentSelection(userId) {
  const idx = findMemberIndexByRole(ROLE_AGENT, userId);
  if (idx >= 0) {
    form.project_pics.splice(idx, 1);
    ensurePrimary(ROLE_AGENT);
    return;
  }
  addMemberEntry(ROLE_AGENT, userId);
}

function togglePicSelection(userId) {
  const idx = findMemberIndexByRole(ROLE_PIC, userId);
  if (idx >= 0) {
    form.project_pics.splice(idx, 1);
    ensurePrimary(ROLE_PIC);
    return;
  }
  addMemberEntry(ROLE_PIC, userId);
}

function isAgentSelected(userId) {
  return findMemberIndexByRole(ROLE_AGENT, userId) >= 0;
}

function isPicSelected(userId) {
  return findMemberIndexByRole(ROLE_PIC, userId) >= 0;
}

function setPrimaryForRole(role, userId) {
  const normalized = normalizeSelectionId(userId);
  let found = false;
  form.project_pics.forEach(member => {
    if (normalizeRole(member.role_type) !== normalizeRole(role)) {
      return;
    }
    const isTarget = normalizeSelectionId(member.user_id) === normalized && normalized !== null;
    member.is_primary = isTarget;
    if (isTarget) {
      found = true;
    }
  });
  if (!found) {
    ensurePrimary(role);
  }
}

function setAgentPrimary(userId) {
  setPrimaryForRole(ROLE_AGENT, userId);
}

function setPicPrimary(userId) {
  setPrimaryForRole(ROLE_PIC, userId);
}

function projectPicError(member, field) {
  if (!form.errors) {
    return '';
  }
  const index = form.project_pics.indexOf(member);
  if (index === -1) {
    return '';
  }
  return form.errors[`project_pics.${index}.${field}`] ?? '';
}

const dateConfig = {
  dateFormat: 'd/m/Y',
  allowInput: true,
};

const dateTimeConfig = {
  dateFormat: 'd/m/Y H:i',
  allowInput: true,
  enableTime: true,
  time_24hr: true,
};

const attachmentIds = ref([]);
const uploadError = ref('');
const showUploader = ref(true);
const existingAttachments = ref(props.project.attachments ? [...props.project.attachments] : []);
const attachmentFilterChoices = attachmentFilterOptions;
const attachmentFilterSelectOptions = computed(() => [
  { value: '', label: 'Pilih extensi file' },
  ...attachmentFilterChoices,
]);
const selectedAttachmentFilter = ref('');
const activeAttachmentFilter = computed(
  () => attachmentFilterChoices.find(option => option.value === selectedAttachmentFilter.value) ?? null
);
const attachmentMimeTypes = computed(() => activeAttachmentFilter.value?.mimeTypes ?? []);
const attachmentAllowedExtensions = computed(() => activeAttachmentFilter.value?.extensions ?? []);
const uploaderDisabled = computed(() => !showUploader.value || !activeAttachmentFilter.value);
const attachmentFilterHint = computed(() => (
  activeAttachmentFilter.value ? `Hanya ${activeAttachmentFilter.value.label}` : 'Pilih jenis file untuk mengaktifkan unggahan.'
));
const uploaderRef = ref(null);

function ensurePrimary(role) {
  enforcePrimary(form.project_pics, role);
}

function addAction() {
  form.project_actions.push({
    title: '',
    status_id: null,
    progress: 0,
    start_date: null,
    end_date: null,
    description: '',
  });
}

function removeAction(index) {
  form.project_actions.splice(index, 1);
}

function addCost() {
  form.project_costs.push({ cost_item: '', category: '', estimated_cost: null, actual_cost: null, notes: '' });
}

function removeCost(index) {
  form.project_costs.splice(index, 1);
}

function addRisk() {
  form.project_risks.push({
    name: '',
    status_id: null,
    impact: null,
    likelihood: null,
    description: '',
    mitigation_plan: '',
  });
}

function removeRisk(index) {
  form.project_risks.splice(index, 1);
}

function addDeliverable() {
  form.project_deliverables.push({
    name: '',
    status_id: null,
    verified_by: null,
    completed_at: null,
    verified_at: null,
    description: '',
  });
}

function removeDeliverable(index) {
  form.project_deliverables.splice(index, 1);
}

function handleUploaded(payload) {
  uploadError.value = '';
  const id = payload?.id ?? null;
  if (!id) return;
  if (!attachmentIds.value.includes(id)) {
    attachmentIds.value.push(id);
  }
  form.attachments = [...attachmentIds.value];
}

function handleRemoved(id) {
  attachmentIds.value = attachmentIds.value.filter(entry => entry !== id);
  form.attachments = [...attachmentIds.value];
}

function handleUploadError(error) {
  uploadError.value = typeof error === 'string' ? error : 'Upload gagal, coba lagi.';
}

function clearNewAttachments() {
  if (!attachmentIds.value.length) {
    return;
  }
  attachmentIds.value = [];
  form.attachments = [];
  uploaderRef.value?.reset();
  uploadError.value = '';
}

watch(selectedAttachmentFilter, () => {
  clearNewAttachments();
});

watch(showUploader, value => {
  if (!value) {
    clearNewAttachments();
  }
});

function formatSize(size) {
  if (!size) return '';
  const kb = size / 1024;
  if (kb < 1024) return `${kb.toFixed(0)} KB`;
  return `${(kb / 1024).toFixed(2)} MB`;
}

function capitalize(value) {
  if (!value) return '';
  return value.charAt(0).toUpperCase() + value.slice(1).replace(/_/g, ' ');
}

function formatLikelihood(value) {
  if (!value) return '';
  return value.replace(/_/g, ' ').replace(/\b\w/g, char => char.toUpperCase());
}

function removeExistingAttachment(item) {
  if (!item?.id) return;
  if (!confirm('Hapus lampiran ini?')) return;

  router.delete(route('attachments.destroy', { attachment: item.id }), {
    preserveScroll: true,
    onSuccess: () => {
      existingAttachments.value = existingAttachments.value.filter(entry => entry.id !== item.id);
    },
  });
}

function sanitizeArrays() {
  form.start_date = toIsoDate(form.start_date);
  form.end_date = toIsoDate(form.end_date);

  form.project_pics = form.project_pics
    .filter(member => member.user_id && member.position?.trim())
    .map(member => ({
      user_id: member.user_id,
      position: member.position.trim(),
      role_type: normalizeRole(member.role_type),
      is_primary: Boolean(member.is_primary),
    }));

  ensurePrimary(ROLE_AGENT);
  ensurePrimary(ROLE_PIC);

  form.project_actions = form.project_actions
    .filter(action => action.title?.trim())
    .map(action => {
      const next = { ...action };
      next.start_date = toIsoDate(next.start_date);
      next.end_date = toIsoDate(next.end_date);
      if (Array.isArray(next.subactions)) {
        next.subactions = next.subactions
          .filter(sub => sub.title?.trim())
          .map(sub => ({
            ...sub,
            start_date: toIsoDate(sub.start_date),
            end_date: toIsoDate(sub.end_date),
          }));
      }
      return next;
    });

  form.project_costs = form.project_costs
    .filter(cost => cost.cost_item?.trim() && cost.category?.trim())
    .map(cost => ({
      ...cost,
      estimated_cost: clampCost(cost.estimated_cost),
      actual_cost: clampCost(cost.actual_cost),
    }));
  form.project_risks = form.project_risks.filter(risk => risk.name?.trim());
  form.project_deliverables = form.project_deliverables
    .filter(deliverable => deliverable.name?.trim())
    .map(deliverable => ({
      ...deliverable,
      completed_at: toIsoDateTime(deliverable.completed_at),
      verified_at: toIsoDateTime(deliverable.verified_at),
    }));
}

function showStep(key) {
  return currentStepKey.value === key;
}

function goToStep(index) {
  const nextIndex = Math.min(Math.max(index, 0), steps.length - 1);
  const key = steps[nextIndex]?.key;
  if (key) {
    ensureStepMounted(key);
  }
  currentStep.value = nextIndex;
}

function nextStep() {
  if (!isLastStep.value) {
    goToStep(currentStep.value + 1);
  }
}

function focusFirstError(errors) {
  if (!errors) return;
  const keys = Object.keys(errors);
  if (!keys.length) return;
  for (let index = 0; index < steps.length; index += 1) {
    const stepKey = steps[index].key;
    const prefixes = stepFieldPrefixes[stepKey] ?? [];
    const matched = prefixes.some(prefix => keys.some(key => key === prefix || key.startsWith(`${prefix}.`)));
    if (matched) {
      goToStep(index);
      return;
    }
  }
  goToStep(0);
}

function handleSubmit() {
  if (!isLastStep.value) {
    nextStep();
    return;
  }
  submitForm();
}

function submitForm() {
  sanitizeArrays();

  form.ticket_id = form.ticket_id || null;
  form.attachments = [...attachmentIds.value];

  const options = {
    preserveScroll: true,
    onSuccess: () => {
      attachmentIds.value = [];
      uploadError.value = '';
    },
    onError: focusFirstError,
  };

  if (submitMethod.value === 'post') {
    form.post(submitUrl.value, options);
    return;
  }

  if (submitMethod.value === 'patch') {
    form.patch(submitUrl.value, options);
    return;
  }

  form.put(submitUrl.value, options);
}
</script>

<style scoped>
.project-edit {
  padding: 1.5rem 0;
}

.edit-shell {
  max-width: 1120px;
  margin: 0 auto;
  padding: 0 1.25rem 4rem;
}

.edit-header {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  gap: 1.5rem;
  margin-bottom: 2.5rem;
}

.edit-header__left {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
}

.edit-back {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 46px;
  height: 46px;
  border-radius: 50%;
  border: 1px solid rgba(148, 163, 184, 0.35);
  background: rgba(255, 255, 255, 0.85);
  color: #475569;
  transition: all 0.2s ease;
}

.edit-back:hover {
  color: #2563eb;
  border-color: #93c5fd;
}


.edit-breadcrumb {
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #64748b;
  margin-bottom: 0.25rem;
}

.edit-header h1 {
  font-size: 2rem;
  font-weight: 700;
  color: #0f172a;
  margin-bottom: 0.25rem;
}

.edit-subtitle {
  font-size: 0.9rem;
  color: #475569;
}

.edit-header__right {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.5rem;
}

.edit-form {
  display: flex;
  flex-direction: column;
  gap: 1.75rem;
}

.panel {
  border: 1px solid rgba(148, 163, 184, 0.25);
  border-radius: 24px;
  padding: 1.75rem;
  background: rgba(255, 255, 255, 0.96);
}

.panel--inactive {
  backdrop-filter: none;
}

.panel-title {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.5rem;
  border-bottom: 1px solid rgba(148, 163, 184, 0.2);
  padding-bottom: 1rem;
}

.panel-icon {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #1e293b;
}

.panel-icon.primary {
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.15), rgba(14, 165, 233, 0.15));
  color: #2563eb;
}

.panel-icon.violet {
  background: linear-gradient(135deg, rgba(139, 92, 246, 0.15), rgba(192, 132, 252, 0.15));
  color: #7c3aed;
}

.panel-icon.emerald {
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(52, 211, 153, 0.15));
  color: #047857;
}

.panel-icon.indigo {
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.15), rgba(129, 140, 248, 0.15));
  color: #4338ca;
}

.panel-icon.amber {
  background: linear-gradient(135deg, rgba(245, 158, 11, 0.15), rgba(251, 191, 36, 0.15));
  color: #c2410c;
}

.panel-icon.rose {
  background: linear-gradient(135deg, rgba(244, 114, 182, 0.18), rgba(251, 191, 36, 0.12));
  color: #be123c;
}

.panel-icon.teal {
  background: linear-gradient(135deg, rgba(20, 184, 166, 0.16), rgba(45, 212, 191, 0.12));
  color: #0f766e;
}

.panel-icon.slate {
  background: rgba(148, 163, 184, 0.18);
  color: #1f2937;
}

.panel-title h2 {
  font-size: 1.2rem;
  font-weight: 600;
  color: #0f172a;
}

.panel-title p {
  margin-top: 0.25rem;
  font-size: 0.85rem;
  color: #64748b;
}

.chip-action {
  margin-left: auto;
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.8rem;
  padding: 0.4rem 0.9rem;
  border-radius: 999px;
  border: none;
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.15), rgba(14, 165, 233, 0.15));
  color: #1d4ed8;
  cursor: pointer;
}

.chip-action:hover {
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.2), rgba(14, 165, 233, 0.2));
}

.team-actions {
  margin-left: auto;
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.team-actions .chip-action {
  margin-left: 0;
}

.primary-toggle {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.85rem;
  color: #475569;
}

.primary-toggle input {
  width: 16px;
  height: 16px;
  accent-color: #2563eb;
}

.word-counter {
  margin-left: auto;
  font-size: 0.75rem;
  color: #7c3aed;
  font-weight: 600;
}

.panel-grid {
  display: grid;
  gap: 1rem;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
}

.panel-grid--timeline {
  grid-template-columns: repeat(2, minmax(240px, 1fr));
  align-items: flex-start;
}

.timeline-hint {
  grid-column: 1 / -1;
}

.panel-field--full {
  grid-column: 1 / -1;
}

.ticket-linker {
  width: 100%;
  padding: 0;
}

.ticket-card {
  width: 100%;
  padding: 1rem;
  border-radius: 1rem;
}

.ticket-card .unit-chips {
  border-radius: 1rem;
  padding: 0.65rem 0.5rem 0.35rem;
}

.ticket-linker .ticket-card {
  width: 100%;
}

.ticket-linker .unit-chips {
  flex-wrap: wrap;
}

.panel-field {
  display: flex;
  flex-direction: column;
}

.panel-field.col-span-2 {
  grid-column: span 2;
}

.panel-field label {
  font-size: 0.85rem;
  font-weight: 600;
  color: #1e293b;
  margin-bottom: 0.4rem;
}

.panel-field input,
.panel-field select,
.panel-field textarea {
  border: 1px solid rgba(148, 163, 184, 0.35);
  border-radius: 14px;
  padding: 0.65rem 0.9rem;
  font-size: 0.9rem;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.panel-field textarea {
  resize: vertical;
  min-height: 84px;
}

.panel-field input:focus,
.panel-field select:focus,
.panel-field textarea:focus {
  border-color: rgba(59, 130, 246, 0.6);
  box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.12);
  outline: none;
}

.field-error {
  margin-top: 0.35rem;
  font-size: 0.75rem;
  color: #dc2626;
}

.field-hint {
  margin-top: 0.35rem;
  font-size: 0.75rem;
  color: #64748b;
}

.panel-stack {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.rich-editor {
  border-radius: 18px;
  overflow: hidden;
  border: 1px solid rgba(148, 163, 184, 0.3);
}

.rich-editor .ql-editor {
  min-height: 140px;
}

.empty-hint {
  margin-bottom: 1.25rem;
  padding: 1rem 1.2rem;
  border-radius: 16px;
  background: rgba(248, 250, 252, 0.8);
  color: #475569;
  font-size: 0.85rem;
}

.stacked-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.card-row {
  position: relative;
  border: 1px solid rgba(148, 163, 184, 0.3);
  border-radius: 20px;
  padding: 1.1rem 1.1rem 1.1rem 1.1rem;
  background: rgba(255, 255, 255, 0.75);
}

.card-row__grid {
  display: grid;
  gap: 1rem;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
}

.card-row__grid--two {
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
}

.card-remove {
  position: absolute;
  top: 0.75rem;
  right: 0.75rem;
  border: none;
  background: transparent;
  color: #ef4444;
  cursor: pointer;
}

.card-remove:hover {
  color: #b91c1c;
}

.toggle {
  margin-left: auto;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.85rem;
  color: #475569;
}

.attachment-uploader {
  margin-top: 1rem;
}

.attachment-list {
  margin-top: 1.25rem;
  border: 1px solid rgba(148, 163, 184, 0.25);
  border-radius: 18px;
  overflow: hidden;
}

.attachment-list header {
  background: rgba(248, 250, 252, 0.9);
  padding: 0.8rem 1.1rem;
  font-size: 0.85rem;
  font-weight: 600;
  color: #0f172a;
}

.attachment-list ul {
  list-style: none;
  margin: 0;
  padding: 0;
  max-height: 260px;
  overflow-y: auto;
}

.attachment-list li {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  padding: 0.85rem 1.1rem;
  border-top: 1px solid rgba(148, 163, 184, 0.15);
}

.attachment-list li:first-child {
  border-top: none;
}

.attachment-list strong {
  font-size: 0.9rem;
  color: #1e293b;
}

.attachment-list .size {
  margin-left: 0.4rem;
  font-size: 0.75rem;
  color: #64748b;
}

.attachment-actions {
  display: inline-flex;
  align-items: center;
  gap: 0.6rem;
}

.attachment-actions a,
.attachment-actions button {
  font-size: 0.75rem;
  font-weight: 600;
  color: #2563eb;
  background: transparent;
  border: none;
  cursor: pointer;
}

.attachment-actions button {
  color: #ef4444;
}

.attachment-actions button:hover {
  color: #b91c1c;
}

.edit-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  margin-top: 0.25rem;
}

.wizard {
  display: flex;
  justify-content: center;
  margin-bottom: 1.75rem;
  overflow-x: auto;
}

.wizard__list {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  list-style: none;
  padding: 0;
  margin: 0;
}

.wizard__item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  color: #64748b;
}

.wizard__button {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.4rem;
  background: transparent;
  border: none;
  padding: 0;
  color: inherit;
  cursor: pointer;
  transition: color 0.2s ease;
}

.wizard__button:hover {
  color: #1d4ed8;
}

.wizard__indicator {
  width: 36px;
  height: 36px;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: rgba(148, 163, 184, 0.3);
  color: #1d4ed8;
  font-weight: 600;
  transition: all 0.2s ease;
}

.wizard__label {
  font-size: 0.78rem;
  font-weight: 600;
  white-space: nowrap;
  text-align: center;
}

.wizard__divider {
  flex: 1;
  height: 2px;
  min-width: 32px;
  background: rgba(148, 163, 184, 0.35);
  border-radius: 999px;
}

.wizard__item.is-active .wizard__indicator {
  background: linear-gradient(120deg, #2563eb 0%, #4338ca 100%);
  color: #fff;
  box-shadow: 0 6px 18px rgba(37, 99, 235, 0.24);
}

.wizard__item.is-completed .wizard__indicator {
  background: #2563eb;
  color: #fff;
}

.wizard__item.is-completed .wizard__divider {
  background: #2563eb;
}

.wizard__button:focus-visible {
  outline: 2px solid #2563eb;
  outline-offset: 3px;
}

.btn-primary,
.btn-secondary {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  border-radius: 999px;
  font-weight: 600;
  padding: 0.7rem 1.4rem;
  font-size: 0.9rem;
  cursor: pointer;
  border: none;
}

.btn-secondary {
  background: rgba(15, 23, 42, 0.05);
  color: #1e293b;
}

.btn-secondary:hover {
  background: rgba(15, 23, 42, 0.09);
}

.btn-primary {
  background: linear-gradient(135deg, #2563eb, #7c3aed);
  color: #fff;
  box-shadow: 0 20px 35px -18px rgba(59, 130, 246, 0.6);
}

.btn-primary:disabled {
  opacity: 0.7;
  cursor: not-allowed;
  box-shadow: none;
}

.dark .wizard__item {
  color: #cbd5f5;
}

.dark .wizard__indicator {
  background: rgba(148, 163, 184, 0.18);
  color: #93c5fd;
}

.dark .wizard__divider {
  background: rgba(148, 163, 184, 0.25);
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

@media (max-width: 768px) {
  .panel {
    padding: 1.25rem;
  }

  .card-remove {
    position: static;
    margin-top: 0.5rem;
  }

  .card-row {
    padding-right: 1rem;
  }

  .panel-title {
    flex-wrap: wrap;
  }

  .chip-action,
  .word-counter,
  .toggle {
    margin-left: 0;
  }

  .panel-grid--timeline {
    grid-template-columns: 1fr;
  }

  .panel-grid--timeline .panel-field--full,
  .panel-grid--timeline .timeline-hint {
    grid-column: span 1;
  }

  .edit-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .edit-header__right {
    align-items: flex-start;
  }
}

.dark .project-edit {
  color: #e2e8f0;
}

.dark .panel {
  background: rgba(15, 23, 42, 0.8);
  border-color: rgba(148, 163, 184, 0.25);
}

.dark .panel-field input,
.dark .panel-field select,
.dark .panel-field textarea {
  background: rgba(15, 23, 42, 0.75);
  color: #e2e8f0;
  border-color: rgba(100, 116, 139, 0.4);
}

.dark .panel-field input:focus,
.dark .panel-field select:focus,
.dark .panel-field textarea:focus {
  border-color: rgba(96, 165, 250, 0.7);
  box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.18);
}

.dark .empty-hint {
  background: rgba(30, 41, 59, 0.7);
  color: #cbd5f5;
}

.dark .attachment-list header {
  background: rgba(30, 41, 59, 0.9);
  color: #e2e8f0;
}

.dark .attachment-list li {
  border-color: rgba(71, 85, 105, 0.4);
}

.dark .btn-secondary {
  background: rgba(30, 41, 59, 0.6);
  color: #e2e8f0;
}

.dark .btn-secondary:hover {
  background: rgba(51, 65, 85, 0.8);
}

.dark .btn-primary {
  box-shadow: 0 25px 40px -20px rgba(79, 70, 229, 0.55);
}

.dark .edit-header h1 {
  color: #e2e8f0;
}

.dark .edit-subtitle {
  color: #94a3b8;
}

.dark .panel-title h2 {
  color: #e2e8f0;
}

.dark .panel-title p {
  color: #94a3b8;
}

.dark .panel-field label {
  color: #e2e8f0;
}

.dark .toggle {
  color: #94a3b8;
}

.dark .card-row {
  background: rgba(30, 41, 59, 0.4);
  border-color: rgba(148, 163, 184, 0.2);
}

.dark .edit-breadcrumb {
  color: #94a3b8;
}

.dark .field-hint {
  color: #94a3b8;
}

.ticket-linker {
  width: 100%;
}

.ticket-linker .ticket-card {
  width: 100%;
}

.ticket-linker .unit-chips {
  flex-wrap: wrap;
}
</style>
