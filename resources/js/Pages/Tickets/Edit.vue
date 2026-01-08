<template>
  <div class="mx-auto max-w-5xl space-y-6 px-4 py-6 pt-8 lg:px-6 lg:pt-10">
    <header class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Edit Ticket</h1>
        <p class="text-sm text-slate-500 dark:text-slate-300">Perbarui informasi ticket dan tambahkan lampiran baru bila diperlukan.</p>
      </div>
      <Link
        :href="route('tickets.report.detail.view', { ticket: ticket.ticket_no })"
        class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-4 py-2 text-sm text-slate-600 transition hover:bg-slate-100 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800"
      >
        <span class="material-icons text-base">visibility</span>
        Lihat Ticket
      </Link>
    </header>

    <form class="mt-4 space-y-6 rounded-3xl border border-slate-200 bg-white/90 p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900/70" @submit.prevent="submit">
      <section class="grid gap-4 md:grid-cols-2">
        <div>
          <label class="block text-sm font-semibold text-slate-600 dark:text-slate-300">Judul</label>
          <input
            v-model="form.title"
            type="text"
            :readonly="coreFieldsLocked"
            :disabled="coreFieldsLocked"
            class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200
                   disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500 disabled:opacity-70
                   dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-blue-500 dark:focus:ring-blue-500/40
                   dark:disabled:bg-slate-800/70 dark:disabled:text-slate-400"
          />
          <p v-if="form.errors.title" class="mt-1 text-xs text-red-500">{{ form.errors.title }}</p>
        </div>

        <div>
          <label class="block text-sm font-semibold text-slate-600 dark:text-slate-300">Nomor Surat</label>
          <input
            v-model="form.letter_no"
            type="text"
            class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-blue-500 dark:focus:ring-blue-500/40"
          />
          <p v-if="form.errors.letter_no" class="mt-1 text-xs text-red-500">{{ form.errors.letter_no }}</p>
        </div>

        <div class="md:col-span-2">
          <label class="block text-sm font-semibold text-slate-600 dark:text-slate-300">Reason</label>
          <input
            v-model="form.reason"
            type="text"
            class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-blue-500 dark:focus:ring-blue-500/40"
          />
          <p v-if="form.errors.reason" class="mt-1 text-xs text-red-500">{{ form.errors.reason }}</p>
        </div>

        <div>
          <label class="block text-sm font-semibold text-slate-600 dark:text-slate-300">Prioritas</label>
          <select
            v-model="form.priority"
            :disabled="coreFieldsLocked"
            class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200 disabled:cursor-not-allowed disabled:opacity-70 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-blue-500 dark:focus:ring-blue-500/40"
          >
            <option v-for="option in priorityOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
          </select>
          <p v-if="form.errors.priority" class="mt-1 text-xs text-red-500">{{ form.errors.priority }}</p>
        </div>

        <div>
          <label class="block text-sm font-semibold text-slate-600 dark:text-slate-300">Tipe</label>
          <select
            v-model="form.type"
            :disabled="coreFieldsLocked"
            class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200 disabled:cursor-not-allowed disabled:opacity-70 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-blue-500 dark:focus:ring-blue-500/40"
          >
            <option v-for="option in typeOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
          </select>
          <p v-if="form.errors.type" class="mt-1 text-xs text-red-500">{{ form.errors.type }}</p>
        </div>

        <div>
          <label class="block text-sm font-semibold text-slate-600 dark:text-slate-300">Status</label>
          <div class="relative mt-1 w-full">
            <FancySelect
              v-model="form.status"
              :options="statusSelectOptions"
              :disabled="statusLocked"
              accent="blue"
            />
          </div>
          <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Pilih status yang sesuai dengan progres ticket.</p>
          <p v-if="form.errors.status" class="mt-1 text-xs text-red-500">{{ form.errors.status }}</p>
          <p v-if="shouldShowStatusReminder" class="mt-1 text-xs text-amber-600 dark:text-amber-300">
            Status saat ini: <span class="font-semibold">{{ currentStatusLabel }}</span> (tidak dapat Anda ubah).
          </p>
          <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Status awal: {{ statusDefaultLabel }}.</p>
          <ul v-if="statusGuide" class="mt-1 list-disc space-y-0.5 pl-5 text-xs text-slate-500 dark:text-slate-400">
            <li>{{ statusGuide.agent }}</li>
            <li>{{ statusGuide.requester }}</li>
            <li>{{ statusGuide.admin }}</li>
          </ul>
        </div>

        

        <div>
          <label class="block text-sm font-semibold text-slate-600 dark:text-slate-300">SLA</label>
          <select
            v-model="form.sla"
            :disabled="coreFieldsLocked"
            class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200 disabled:cursor-not-allowed disabled:opacity-70 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-blue-500 dark:focus:ring-blue-500/40"
          >
            <option :value="null">Tidak ada</option>
            <option v-for="option in slaOptions" :key="option" :value="option">{{ formatSla(option) }}</option>
          </select>
          <p v-if="form.errors.sla" class="mt-1 text-xs text-red-500">{{ form.errors.sla }}</p>
        </div>

        <div>
          <label class="block text-sm font-semibold text-slate-600 dark:text-slate-300">Tenggat</label>
          <DatePickerFlatpickr v-model="form.due_at" :config="dateConfig" placeholder="Pilih tanggal & waktu" :disabled="coreFieldsLocked" />
          <p v-if="form.errors.due_at || form.errors.due_date" class="mt-1 text-xs text-red-500">{{ form.errors.due_at || form.errors.due_date }}</p>
        </div>

        <div>
          <label class="block text-sm font-semibold text-slate-600 dark:text-slate-300">Target Selesai</label>
          <DatePickerFlatpickr v-model="form.finish_at" :config="dateConfig" placeholder="Pilih tanggal & waktu" :disabled="coreFieldsLocked" />
          <p v-if="form.errors.finish_at || form.errors.finish_date" class="mt-1 text-xs text-red-500">{{ form.errors.finish_at || form.errors.finish_date }}</p>
        </div>
      </section>

      <section class="grid gap-4 md:grid-cols-2">
        <div class="md:col-span-2">
          <label class="block text-sm font-semibold text-slate-600 dark:text-slate-300">Requester</label>
          <template v-if="canSelectRequester">
            <div ref="requesterCardRef" class="relative mt-1 w-full">
              <button
                ref="requesterTriggerRef"
                type="button"
                class="flex w-full items-center justify-between rounded-2xl border border-slate-200 bg-white px-4 py-3 text-left text-sm font-semibold text-slate-700 shadow-sm transition hover:border-blue-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                @click="toggleRequesterDropdown"
              >
                <span class="truncate">
                  {{ selectedRequester?.label || 'Pilih requester' }}
                </span>
                <span class="material-icons text-base text-slate-400 transition duration-200" :class="requesterDropdownOpen ? 'rotate-180 text-blue-500' : ''">expand_more</span>
              </button>
              <transition name="fade-scale">
                <div
                  v-if="requesterDropdownOpen"
                  ref="requesterDropdownRef"
                  class="absolute left-0 right-0 top-full mt-2 z-[9999] w-full rounded-2xl border border-slate-200 bg-white p-4 shadow-2xl dark:border-slate-700 dark:bg-slate-900"
                  style="width:100%;min-width:100%;max-width:100%;box-sizing:border-box;"
                >
                  <div class="flex flex-wrap gap-2 border-b border-slate-100 pb-3 dark:border-slate-800">
                    <button
                      v-for="unit in allUnits"
                      :key="`requester-unit-${unit}`"
                      type="button"
                      class="rounded-full border px-3 py-1 text-xs font-semibold transition"
                      :class="activeRequesterUnit === unit
                        ? 'border-blue-500 bg-blue-50 text-blue-700 dark:border-blue-400 dark:bg-blue-500/10 dark:text-blue-200'
                        : 'border-slate-200 text-slate-500 hover:border-blue-300 hover:text-blue-600 dark:border-slate-700 dark:text-slate-300'"
                      @click="activeRequesterUnit = unit"
                    >
                      {{ unit }}
                    </button>
                  </div>
                  <div class="mt-3 max-h-64 space-y-2 overflow-y-auto pr-1">
                    <template v-if="activeRequesterUnit">
                      <button
                        v-for="option in activeRequesterList"
                        :key="`req-option-${option.id}`"
                        type="button"
                        class="flex w-full items-start justify-between gap-3 rounded-xl border border-slate-200 px-3 py-2 text-sm text-left shadow-sm transition hover:border-blue-400 dark:border-slate-700"
                        :class="form.requester_id === option.id ? 'border-blue-400 dark:border-blue-500' : ''"
                        @click="setRequesterSelection(option.id)"
                      >
                        <div class="space-y-0.5">
                          <p class="font-semibold text-slate-700 dark:text-slate-100">{{ option.label }}</p>
                          <p class="text-xs text-slate-400">{{ option.email || '—' }}</p>
                        </div>
                        <span
                          class="material-icons text-base"
                          :class="form.requester_id === option.id ? 'text-blue-500' : 'text-transparent'"
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
            <div class="mt-1 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-600 dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-200">
              {{ requesterLabel || 'Menggunakan requester saat ini' }}
            </div>
          </template>
          <p v-if="form.errors.requester_id" class="mt-1 text-xs text-red-500">{{ form.errors.requester_id }}</p>
        </div>
        <div class="md:col-span-2">
          <div class="grid gap-6 lg:grid-cols-2">
            <div ref="agentCardRef" class="relative overflow-visible rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
              <div class="flex items-start justify-between gap-3">
                <div>
                  <p class="text-sm font-semibold text-slate-700 dark:text-slate-100">Agent</p>
                  <p class="text-xs text-slate-400">Pilih beberapa agent lalu tandai yang utama.</p>
                </div>
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">
                  {{ agentSelections.length ? agentSelections.length + ' agent dipilih' : 'Belum ada pilihan' }}
                </span>
              </div>
              <div class="relative mt-3 w-full">
                <button
                  type="button"
                  class="flex w-full items-center justify-between rounded-2xl border border-slate-200 bg-white px-4 py-3 text-left text-sm font-semibold text-slate-700 shadow-sm transition hover:border-blue-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                  :class="agentDropdownOpen ? 'border-blue-400 dark:border-blue-500' : ''"
                  @click="toggleAgentDropdown"
                >
                  <div class="flex flex-1 flex-wrap gap-1.5">
                    <span
                      v-for="person in selectedAgentDetails"
                      :key="`chip-agent-${person.id}`"
                      class="inline-flex items-center gap-1 rounded-lg border border-blue-200/70 bg-white px-2 py-1 text-xs font-medium text-blue-700 dark:border-blue-500/40 dark:bg-slate-900/70 dark:text-blue-200"
                    >
                      {{ person.label }}
                      <button type="button" class="text-slate-400 transition hover:text-rose-500" @click.stop="removeAgentSelection(person.id)">
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
                    class="absolute left-0 right-0 top-full mt-2 z-[9999] w-full rounded-2xl border border-slate-200 bg-white p-4 shadow-2xl dark:border-slate-700 dark:bg-slate-900"
                    :class="agentDropdownOpen ? 'border-blue-400 dark:border-blue-500' : ''"
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
              <p class="mt-2 text-xs text-slate-400">{{ agentSelections.length ? agentSelections.length + ' agent terhubung' : 'Belum ada agent terpilih' }}</p>
              <p v-if="form.errors.agent_id" class="mt-2 text-xs text-red-500">{{ form.errors.agent_id }}</p>
            </div>

            <div ref="picCardRef" class="relative overflow-visible rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
              <div class="flex items-start justify-between gap-3">
                <div>
                  <p class="text-sm font-semibold text-slate-700 dark:text-slate-100">Penanggung Jawab Utama</p>
                  <p class="text-xs text-slate-400">Pilih PIC utama dari berbagai unit.</p>
                </div>
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">
                  {{ picSelections.length ? picSelections.length + ' PIC dipilih' : 'Belum ada pilihan' }}
                </span>
              </div>
              <div class="relative mt-3 w-full">
                <button
                  type="button"
                  class="flex w-full items-center justify-between rounded-2xl border border-slate-200 bg-white px-4 py-3 text-left text-sm font-semibold text-slate-700 shadow-sm transition hover:border-emerald-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                  :class="picDropdownOpen ? 'border-emerald-400 dark:border-emerald-500' : ''"
                  @click="togglePicDropdown"
                >
                  <div class="flex flex-1 flex-wrap gap-1.5">
                    <span
                      v-for="person in selectedPicDetails"
                      :key="`chip-pic-${person.id}`"
                      class="inline-flex items-center gap-1 rounded-lg border border-emerald-200/70 bg-white px-2 py-1 text-xs font-medium text-emerald-700 dark:border-emerald-500/40 dark:bg-slate-900/70 dark:text-emerald-200"
                    >
                      {{ person.label }}
                      <button type="button" class="text-slate-400 transition hover:text-rose-500" @click.stop="removePicSelection(person.id)">
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
                    class="absolute left-0 right-0 top-full mt-2 z-[9999] w-full rounded-2xl border border-slate-200 bg-white p-4 shadow-2xl dark:border-slate-700 dark:bg-slate-900"
                    :class="picDropdownOpen ? 'border-emerald-400 dark:border-emerald-500' : ''"
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
              <p class="mt-2 text-xs text-slate-400">{{ picSelections.length ? picSelections.length + ' PIC terhubung' : 'Belum ada PIC terpilih' }}</p>
              <p v-if="form.errors.assigned_id" class="mt-2 text-xs text-red-500">{{ form.errors.assigned_id }}</p>
            </div>
          </div>
        </div>

        <div class="md:col-span-2 rounded-2xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-700 dark:bg-slate-900/60">
          <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-100">Ringkasan Pilihan</h3>
          <div class="mt-3 grid gap-3 sm:grid-cols-2">
            <div class="rounded-xl border border-blue-200/60 bg-white p-3 dark:border-blue-500/30 dark:bg-slate-900">
              <div class="mb-2 flex items-center gap-2 text-sm font-semibold text-blue-700 dark:text-blue-200">
                <span class="material-icons text-base">groups</span>
                <span>Agent ({{ selectedAgentDetails.length }})</span>
              </div>
              <div class="flex flex-wrap gap-1">
                <span
                  v-for="person in selectedAgentDetails"
                  :key="`summary-agent-${person.id}`"
                  class="text-xs rounded-md border border-blue-200 bg-white px-2 py-0.5 text-blue-600 dark:border-blue-400/40 dark:bg-slate-900 dark:text-blue-100"
                >
                  {{ person.label }}
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
                </span>
                <span v-if="!selectedPicDetails.length" class="text-xs text-slate-400">Belum ada data</span>
              </div>
            </div>
          </div>
          <p v-if="form.errors.assigned_user_ids" class="mt-2 text-xs text-red-500">{{ form.errors.assigned_user_ids }}</p>
        </div>
      </section>

      <section>
        <div class="flex items-center justify-between">
          <label class="text-sm font-semibold text-slate-600 dark:text-slate-300">Deskripsi</label>
          <span class="text-xs text-slate-400">Maksimal 255 karakter.</span>
        </div>
        <div class="mt-2 overflow-hidden rounded-2xl border border-slate-200/80 dark:border-slate-700/60">
          <RichTextQuill v-model="form.description" />
        </div>
        <p v-if="form.errors.description" class="mt-1 text-xs text-red-500">{{ form.errors.description }}</p>
      </section>

      <section class="space-y-3">
        <div>
          <h2 class="text-sm font-semibold text-slate-600 dark:text-slate-300">Lampiran Baru</h2>
          <p class="text-xs text-slate-400">Pilih jenis file terlebih dahulu. Batas ukuran 10MB.</p>
        </div>

        <div class="space-y-2">
          <div class="w-full">
            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Jenis File</label>
            <div class="mt-1 w-full">
              <FancySelect v-model="selectedAttachmentFilter" :options="attachmentFilterChoices" accent="emerald" />
            </div>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ attachmentFilterHint }}</p>
          </div>
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
          <div class="flex flex-wrap items-center justify-between text-xs text-slate-500 dark:text-slate-400">
            <span>{{ attachmentIds.length ? attachmentIds.length + ' file siap diunggah' : 'Belum ada file siap diunggah' }}</span>
            <span v-if="attachmentIds.length" class="text-blue-600 dark:text-blue-400">{{ attachmentIds.length }} file baru</span>
          </div>
        </div>

        <p v-if="uploaderDisabled" class="mt-1 text-xs text-amber-600">Pilih jenis file terlebih dahulu sebelum mengunggah.</p>
        <p v-if="form.errors.attachments" class="text-xs text-red-500">{{ form.errors.attachments }}</p>
        <p v-if="uploadError" class="text-xs text-red-500">{{ uploadError }}</p>

        <div v-if="ticket.attachments?.length" class="rounded-2xl border border-slate-200/70 p-4 dark:border-slate-700/60">
          <div class="mb-2 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-600 dark:text-slate-300">Lampiran Saat Ini</h3>
            <Link
              :href="route('tickets.attachments.manage', { ticket: ticket.id })"
              class="text-xs font-semibold text-blue-600 hover:underline dark:text-blue-400"
            >
              Kelola lampiran
            </Link>
          </div>
          <ul class="space-y-2 text-sm text-slate-600 dark:text-slate-300">
            <li v-for="item in ticket.attachments" :key="item.id" class="flex flex-wrap items-center gap-2">
              <span class="material-icons text-base text-slate-400">attach_file</span>
              <span class="font-medium">{{ item.name }}</span>
              <span v-if="item.size" class="text-xs text-slate-400">({{ formatSize(item.size) }})</span>
              <span class="text-slate-400">·</span>
              <a :href="item.view_url" target="_blank" class="text-blue-600 hover:underline dark:text-blue-400">Lihat</a>
              <span class="text-slate-400">·</span>
              <a :href="item.download_url" class="text-blue-600 hover:underline dark:text-blue-400">Unduh</a>
            </li>
          </ul>
        </div>
      </section>

      <div class="flex flex-wrap items-center justify-end gap-3">
        <Link
          :href="backLink"
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
          <span v-else class="material-icons text-base">save</span>
          Simpan Perubahan
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch, watchEffect } from 'vue';
import { route as ziggyRoute } from 'ziggy-js';
import { Ziggy } from '@/ziggy';
import DatePickerFlatpickr from '@/Components/DatePickerFlatpickr.vue';
import FileUploaderPond from '@/Components/FileUploaderPond.vue';
import RichTextQuill from '@/Components/RichTextQuill.vue';
import FancySelect from '@/Components/FancySelect.vue';
import { attachmentFilterOptions } from '@/utils/attachmentFilters';

const props = defineProps({
  ticket: { type: Object, required: true },
  statusOptions: { type: Array, default: () => [] },
  priorityOptions: { type: Array, default: () => [] },
  typeOptions: { type: Array, default: () => [] },
  slaOptions: { type: Array, default: () => [] },
  userOptions: { type: Array, default: () => [] },
  meta: { type: Object, default: () => ({}) },
});

const route = (name, params = undefined, absolute = true, config = Ziggy) => {
  if (typeof window !== 'undefined' && typeof window.route === 'function') {
    return window.route(name, params, absolute, config);
  }

  return ziggyRoute(name, params, absolute, config);
};

const routeWithFrom = (name, params = {}) => {
  const finalParams = { ...params };
  if (props.meta?.from) {
    finalParams.from = props.meta.from;
  }

  return route(name, finalParams);
};

const toInputDateTime = value => {
  if (!value) return null;
  if (typeof value === 'string' && value.includes('T')) {
    return value.replace('T', ' ').slice(0, 16);
  }
  if (typeof value === 'string') {
    return value.slice(0, 16);
  }
  return null;
};

const form = useForm({
  title: props.ticket.title ?? '',
  description: props.ticket.description ?? '',
  reason: props.ticket.reason ?? '',
  letter_no: props.ticket.letter_no ?? '',
  priority: props.ticket.priority ?? 'medium',
  type: props.ticket.type ?? 'task',
  status: props.ticket.status ?? 'new',
  sla: props.ticket.sla ?? null,
  due_at: toInputDateTime(props.ticket.due_at),
  finish_at: toInputDateTime(props.ticket.finish_at),
  requester_id: props.ticket.requester_id ?? null,
  agent_id: props.ticket.agent_id ?? null,
  assigned_id: props.ticket.assigned_id ?? null,
  assigned_user_ids: props.ticket.assigned_user_ids ?? [],
  attachments: [],
});

const attachmentIds = ref([]);
const uploadError = ref('');
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
const backLink = computed(() => props.meta?.from || route('tickets.index'));

const userOptions = computed(() => props.userOptions ?? []);
const canSelectRequester = computed(() => Boolean(props.meta?.canSelectRequester));
const requesterLabel = computed(() => props.meta?.requesterLabel ?? '');
const statusGuide = computed(() => props.meta?.statusGuide ?? null);
const statusLocked = computed(() => Boolean(props.meta?.lockStatus));
const coreFieldsLocked = computed(() => Boolean(props.meta?.lockCoreFields));
const statusDefaultLabel = computed(() => props.meta?.statusDefault ?? 'New');
watchEffect(() => {
  if (statusLocked.value) {
    form.status = props.ticket.status ?? 'new';
  }
});
const allowedStatusValues = computed(() => (Array.isArray(props.meta?.allowedStatuses) ? props.meta.allowedStatuses : []));
const statusOptionMap = computed(() => {
  const map = new Map();
  (props.statusOptions ?? []).forEach(option => {
    map.set(option.value, option);
  });

  return map;
});
const humanizeStatus = status => {
  if (!status) return '';
  const base = status.replace(/_/g, ' ');
  return base.replace(/\b\w/g, char => char.toUpperCase());
};
const currentStatusValue = computed(() => props.ticket.status ?? 'new');
const currentStatusLabel = computed(() => statusOptionMap.value.get(currentStatusValue.value)?.label ?? humanizeStatus(currentStatusValue.value));
const displayStatusOptions = computed(() => {
  // Jika ada daftar allowed -> filter sesuai peran.
  if (allowedStatusValues.value.length) {
    const allowedSet = new Set(allowedStatusValues.value);
    return (props.statusOptions ?? [])
      .filter(option => allowedSet.has(option.value))
      .map(option => ({ ...option }));
  }
  // Jika tidak ada allowed (form terkunci), tampilkan hanya status saat ini.
  return (props.statusOptions ?? [])
    .filter(option => option.value === currentStatusValue.value)
    .map(option => ({ ...option }));
});
const shouldShowStatusReminder = computed(() => allowedStatusValues.value.length > 0 && !allowedStatusValues.value.includes(currentStatusValue.value));
const statusSelectOptions = computed(() => displayStatusOptions.value ?? []);

watch(allowedStatusValues, allowed => {
  if (!allowed?.length) {
    return;
  }
  if (form.status && !allowed.includes(form.status)) {
    form.status = '';
  }
}, { immediate: true });
const userLookup = computed(() => {
  const map = new Map();
  userOptions.value.forEach(option => {
    map.set(String(option.id), option);
  });
  return map;
});

const unitPlaceholderLabel = 'Unit Tidak Tercatat';
const metaUnitOptions = computed(() => {
  const raw = props.meta?.unitOptions;
  if (Array.isArray(raw)) {
    return raw.filter(unit => typeof unit === 'string' && unit.trim() !== '');
  }
  return [];
});

const allUnits = computed(() => {
  const set = new Set(metaUnitOptions.value);
  userOptions.value.forEach(option => {
    const unit = typeof option.unit === 'string' && option.unit.trim() !== '' ? option.unit.trim() : unitPlaceholderLabel;
    set.add(unit);
  });
  if (!set.size) {
    set.add(unitPlaceholderLabel);
  }
  return Array.from(set);
});

const agentsByUnit = computed(() => {
  const map = {};
  allUnits.value.forEach(unit => {
    map[unit] = [];
  });
  userOptions.value.forEach(option => {
    const key = typeof option.unit === 'string' && option.unit.trim() !== '' ? option.unit.trim() : unitPlaceholderLabel;
    if (!map[key]) {
      map[key] = [];
    }
    map[key].push(option);
  });
  return map;
});
const requestersByUnit = computed(() => {
  const map = {};
  allUnits.value.forEach(unit => {
    map[unit] = [];
  });
  userOptions.value.forEach(option => {
    const key = typeof option.unit === 'string' && option.unit.trim() !== '' ? option.unit.trim() : unitPlaceholderLabel;
    if (!map[key]) {
      map[key] = [];
    }
    map[key].push(option);
  });
  return map;
});

const agentCardRef = ref(null);
const picCardRef = ref(null);
const requesterCardRef = ref(null);
const requesterTriggerRef = ref(null);
const requesterDropdownRef = ref(null);
const agentDropdownOpen = ref(false);
const picDropdownOpen = ref(false);
const requesterDropdownOpen = ref(false);
const activeAgentUnit = ref('');
const activePicUnit = ref('');
const activeRequesterUnit = ref(null);

const agentSelections = ref([]);
const picSelections = ref([]);
const agentPrimary = ref(form.agent_id ?? null);
const picPrimary = ref(form.assigned_id ?? null);
const requesterOptions = computed(() => userOptions.value.map(option => ({
  ...option,
  label: formatUserOptionLabel(option),
})));
const selectedRequester = computed(() => requesterOptions.value.find(option => option.id === form.requester_id) || null);
const activeRequesterList = computed(() => requestersByUnit.value[activeRequesterUnit.value] ?? []);
const scrollToTop = () => {
  if (typeof window === 'undefined' || typeof window.scrollTo !== 'function') {
    return;
  }
  const execute = () => {
    try {
      window.scrollTo({ top: 0, left: 0, behavior: 'auto' });
    } catch (error) {
      window.scrollTo(0, 0);
    }
  };
  requestAnimationFrame(() => {
    execute();
    setTimeout(execute, 50);
  });
};

const normalizeSelectionId = value => {
  if (typeof value === 'number') {
    return value;
  }
  if (typeof value === 'string' && value.trim() !== '') {
    const numeric = Number(value);
    return Number.isFinite(numeric) ? numeric : value.trim();
  }
  return null;
};

const activeAgentList = computed(() => agentsByUnit.value[activeAgentUnit.value] ?? []);
const activePicList = computed(() => agentsByUnit.value[activePicUnit.value] ?? []);

const selectedAgentDetails = computed(() => {
  const lookup = userLookup.value;
  return agentSelections.value
    .map(id => lookup.get(String(normalizeSelectionId(id))))
    .filter(Boolean);
});

const selectedPicDetails = computed(() => {
  const lookup = userLookup.value;
  return picSelections.value
    .map(id => lookup.get(String(normalizeSelectionId(id))))
    .filter(Boolean);
});

const isAgentSelected = id => agentSelections.value
  .map(normalizeSelectionId)
  .includes(normalizeSelectionId(id));
const isPicSelected = id => picSelections.value
  .map(normalizeSelectionId)
  .includes(normalizeSelectionId(id));

const syncAssignedUsers = () => {
  const merged = Array.from(new Set([
    ...agentSelections.value.map(normalizeSelectionId),
    ...picSelections.value.map(normalizeSelectionId),
  ])).filter(value => value !== null && value !== '');
  form.assigned_user_ids = merged;
};

watch(allUnits, units => {
  if (!units.length) {
    activeAgentUnit.value = '';
    activePicUnit.value = '';
    activeRequesterUnit.value = null;
    return;
  }
  if (!units.includes(activeAgentUnit.value)) {
    activeAgentUnit.value = units[0];
  }
  if (!units.includes(activePicUnit.value)) {
    activePicUnit.value = units[0];
  }
  if (activeRequesterUnit.value && !units.includes(activeRequesterUnit.value)) {
    activeRequesterUnit.value = null;
  }
}, { immediate: true });

watch(agentSelections, value => {
  const normalized = value.map(normalizeSelectionId).filter(val => val !== null && val !== '');
  if (normalized.length !== value.length) {
    agentSelections.value = normalized;
    return;
  }
  if (!normalized.length) {
    agentPrimary.value = null;
  } else if (!normalized.includes(normalizeSelectionId(agentPrimary.value))) {
    agentPrimary.value = normalized[0];
  }
  syncAssignedUsers();
}, { deep: true });

watch(picSelections, value => {
  const normalized = value.map(normalizeSelectionId).filter(val => val !== null && val !== '');
  if (normalized.length !== value.length) {
    picSelections.value = normalized;
    return;
  }
  if (!normalized.length) {
    picPrimary.value = null;
  } else if (!normalized.includes(normalizeSelectionId(picPrimary.value))) {
    picPrimary.value = normalized[0];
  }
  syncAssignedUsers();
}, { deep: true });

watch(agentPrimary, value => {
  const normalized = normalizeSelectionId(value);
  if (normalized && !agentSelections.value.map(normalizeSelectionId).includes(normalized)) {
    return;
  }
  form.agent_id = normalized ?? null;
}, { immediate: true });

watch(picPrimary, value => {
  const normalized = normalizeSelectionId(value);
  if (normalized && !picSelections.value.map(normalizeSelectionId).includes(normalized)) {
    return;
  }
  form.assigned_id = normalized ?? null;
}, { immediate: true });

watch(selectedAttachmentFilter, () => {
  attachmentIds.value = [];
  form.attachments = [];
  uploadError.value = '';
  uploaderRef.value?.reset();
});

function toggleAgentSelection(rawId) {
  const id = normalizeSelectionId(rawId);
  if (id === null || id === '') return;
  const set = new Set(agentSelections.value.map(normalizeSelectionId));
  if (set.has(id)) {
    set.delete(id);
  } else {
    set.add(id);
  }
  agentSelections.value = Array.from(set);
}

function togglePicSelection(rawId) {
  const id = normalizeSelectionId(rawId);
  if (id === null || id === '') return;
  const set = new Set(picSelections.value.map(normalizeSelectionId));
  if (set.has(id)) {
    set.delete(id);
  } else {
    set.add(id);
  }
  picSelections.value = Array.from(set);
}

function removeAgentSelection(rawId) {
  const id = normalizeSelectionId(rawId);
  if (id === null) return;
  agentSelections.value = agentSelections.value.filter(value => normalizeSelectionId(value) !== id);
  if (normalizeSelectionId(agentPrimary.value) === id) {
    agentPrimary.value = agentSelections.value[0] ?? null;
  }
}

function removePicSelection(rawId) {
  const id = normalizeSelectionId(rawId);
  if (id === null) return;
  picSelections.value = picSelections.value.filter(value => normalizeSelectionId(value) !== id);
  if (normalizeSelectionId(picPrimary.value) === id) {
    picPrimary.value = picSelections.value[0] ?? null;
  }
}

function setAgentPrimary(rawId) {
  const id = normalizeSelectionId(rawId);
  if (id === null) return;
  if (!isAgentSelected(id)) {
    toggleAgentSelection(id);
  }
  agentPrimary.value = id;
  form.agent_id = id;
}

function setPicPrimary(rawId) {
  const id = normalizeSelectionId(rawId);
  if (id === null) return;
  if (!isPicSelected(id)) {
    togglePicSelection(id);
  }
  picPrimary.value = id;
  form.assigned_id = id;
}

function setRequesterSelection(rawId) {
  const id = normalizeSelectionId(rawId);
  form.requester_id = id;
  requesterDropdownOpen.value = false;
}

function toggleAgentDropdown() {
  agentDropdownOpen.value = !agentDropdownOpen.value;
  if (agentDropdownOpen.value) {
    picDropdownOpen.value = false;
    requesterDropdownOpen.value = false;
    if (!activeAgentUnit.value && allUnits.value.length) {
      activeAgentUnit.value = allUnits.value[0];
    }
  }
}

function togglePicDropdown() {
  picDropdownOpen.value = !picDropdownOpen.value;
  if (picDropdownOpen.value) {
    agentDropdownOpen.value = false;
    requesterDropdownOpen.value = false;
    if (!activePicUnit.value && allUnits.value.length) {
      activePicUnit.value = allUnits.value[0];
    }
  }
}

function toggleRequesterDropdown() {
  requesterDropdownOpen.value = !requesterDropdownOpen.value;
  if (requesterDropdownOpen.value) {
    agentDropdownOpen.value = false;
    picDropdownOpen.value = false;
  }
}

function handleOutsideClick(event) {
  const target = event.target;
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

function initializeAssignmentSelections() {
  const baseAssigned = Array.isArray(form.assigned_user_ids)
    ? form.assigned_user_ids.map(normalizeSelectionId).filter(val => val !== null && val !== '')
    : [];

  const agentSeed = new Set(baseAssigned);
  const agentId = normalizeSelectionId(form.agent_id);
  if (agentId !== null && agentId !== '') {
    agentSeed.add(agentId);
  }
  agentSelections.value = Array.from(agentSeed);

  const picSeed = new Set(baseAssigned);
  const assignedId = normalizeSelectionId(form.assigned_id);
  if (assignedId !== null && assignedId !== '') {
    picSeed.add(assignedId);
  }
  picSelections.value = Array.from(picSeed);

  agentPrimary.value = agentId ?? (agentSelections.value[0] ?? null);
  picPrimary.value = assignedId ?? (picSelections.value[0] ?? null);

  syncAssignedUsers();
}

onMounted(() => {
  scrollToTop();
  window.addEventListener('click', handleOutsideClick, true);
  initializeAssignmentSelections();
});

onBeforeUnmount(() => {
  window.removeEventListener('click', handleOutsideClick, true);
});

const formatUserOptionLabel = option => {
  if (!option) return '';
  const parts = [];
  if (option.unit) {
    parts.push(`[${option.unit}]`);
  }
  if (option.label) {
    parts.push(option.label);
  }
  if (option.agent_label && option.agent_label !== '—') {
    parts.push(`(${option.agent_label})`);
  }
  const base = parts.join(' ').replace(/\s+/g, ' ').trim();
  if (!base) {
    return option.email ?? '';
  }
  return option.email ? `${base} · ${option.email}` : base;
};

const dateConfig = computed(() => ({
  enableTime: true,
  altInput: true,
  altFormat: 'd M Y, H:i',
  dateFormat: 'Y-m-d H:i',
}));

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

const normalizeNumber = value => {
  if (value === null || typeof value === 'undefined' || value === '') return null;
  const numeric = Number(value);
  return Number.isFinite(numeric) ? numeric : null;
};

const sanitizeDateTime = value => {
  if (typeof value === 'string') {
    const trimmed = value.trim();
    return trimmed === '' ? null : trimmed;
  }

  return null;
};

function prepareSubmission() {
  ['requester_id', 'agent_id', 'assigned_id'].forEach(field => {
    form[field] = normalizeNumber(form[field]);
  });

  const normalizedAssignees = Array.isArray(form.assigned_user_ids)
    ? Array.from(
        new Set(
          form.assigned_user_ids
            .map(normalizeNumber)
            .filter(value => value !== null)
        )
      )
    : [];

  if (form.assigned_id && !normalizedAssignees.includes(form.assigned_id)) {
    normalizedAssignees.push(form.assigned_id);
  }

  form.assigned_user_ids = normalizedAssignees;
  form.attachments = [...attachmentIds.value];
}

function submit() {
  uploadError.value = '';
  prepareSubmission();
  const fallbackStatus = props.ticket.status ?? 'new';
  const currentStatus = typeof form.status === 'string' && form.status.trim() !== '' ? form.status : fallbackStatus;

  form
    .transform(data => ({
      ...data,
      status: currentStatus,
      sla: data.sla ?? null,
      due_at: sanitizeDateTime(form.due_at ?? data.due_at),
      finish_at: sanitizeDateTime(form.finish_at ?? data.finish_at),
    }))
    .put(routeWithFrom('tickets.update', { ticket: props.ticket.id }), {
      preserveScroll: true,
      onSuccess: () => {
        attachmentIds.value = [];
        selectedAttachmentFilter.value = '';
        uploaderRef.value?.reset();
        uploadError.value = '';
      },
    });
}

const formatSla = sla => {
  if (!sla) return '';
  return sla.replace(/_/g, ' ').replace(/\b\w/g, char => char.toUpperCase());
};

const formatSize = size => {
  if (!size) return '';
  const value = Number(size);
  if (Number.isNaN(value)) return '';
  const kb = value / 1024;
  if (kb < 1024) return `${kb.toFixed(0)} KB`;
  const mb = kb / 1024;
  return `${mb.toFixed(1)} MB`;
};
</script>

<style scoped>
.material-icons {
  font-size: inherit;
}

.fade-scale-enter-active,
.fade-scale-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}

.fade-scale-enter-from,
.fade-scale-leave-to {
  opacity: 0;
  transform: scale(0.98);
}

.fade-scale-enter-to,
.fade-scale-leave-from {
  opacity: 1;
  transform: scale(1);
}
</style>
