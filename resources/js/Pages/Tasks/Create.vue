<template>
  <div class="mx-auto max-w-6xl space-y-6 px-4 py-8 pb-28 lg:px-6">
    <header class="space-y-1">
      <h1 class="text-3xl font-semibold text-slate-900 dark:text-slate-100">{{ $t("Buat Task Baru") }}</h1>
      <p class="text-sm text-slate-500 dark:text-slate-300">{{ $t("Lengkapi informasi task, tentukan penanggung jawab, dan tambahkan lampiran jika diperlukan.") }}</p>
    </header>
    <DraftNotice :draft="draft" />

    <form class="space-y-6" @submit.prevent="handleSubmit">
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

      <section v-show="showStep('info')" class="rounded-3xl border border-slate-200 bg-white/90 p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
        <h2 class="section-title">{{ $t("Informasi Utama") }}</h2>
        <div class="grid gap-4 md:grid-cols-2">
          <div>
            <label class="field-label" for="title">{{ $t("Judul Task") }}</label>
            <input
              id="title"
              v-model="form.title"
              type="text"
              class="field-input"
              :placeholder="$t(&quot;Contoh: Implementasi modul autentikasi&quot;)"
            />
            <p v-if="form.errors.title" class="field-error">{{ form.errors.title }}</p>
          </div>
          <div>
            <label class="field-label">Status</label>
            <FancySelect v-model="form.status" :options="displayStatusOptions" :disabled="statusLocked" />
            <p v-if="form.errors.status" class="field-error">{{ form.errors.status }}</p>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
              {{ $t("Status awal diset ke") }} {{ statusDefaultLabel }}.
            </p>
            <ul v-if="statusGuide" class="mt-1 list-disc space-y-0.5 pl-5 text-xs text-slate-500 dark:text-slate-400">
              <li>{{ statusGuide.agent }}</li>
              <li>{{ statusGuide.requester }}</li>
              <li>{{ statusGuide.admin }}</li>
            </ul>
          </div>
          <div>
            <label class="field-label">{{ $t("Prioritas") }}</label>
            <FancySelect v-model="form.priority" :options="priorityOptions" />
            <p v-if="form.errors.priority" class="field-error">{{ form.errors.priority }}</p>
          </div>
        </div>

        <div class="mt-4 relative z-0">
          <label class="field-label">{{ $t("Deskripsi") }}</label>
          <div class="rounded-2xl border border-slate-200/80 dark:border-slate-700/60">
            <RichTextQuill v-model="form.description" />
          </div>
          <p v-if="form.errors.description" class="field-error">{{ form.errors.description }}</p>
        </div>
      </section>

      <section v-show="showStep('assignment')" class="rounded-3xl border border-slate-200 bg-white/90 p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
        <h2 class="section-title">{{ $t("Penugasan & Timeline") }}</h2>
        <div class="grid gap-4 md:grid-cols-2">
          <div class="md:col-span-2">
            <div class="grid gap-6 lg:grid-cols-2">
              <div ref="assigneeCardRef" class="relative rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-100">Agent</p>
                    <p class="text-xs text-slate-400">{{ $t("Pilih assignee lintas unit, lalu tandai yang utama.") }}</p>
                  </div>
                  <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">
                    {{ assigneeSelections.length ? assigneeSelections.length + ' agent dipilih' : $t("Belum ada pilihan") }}
                  </span>
                </div>
                <div class="relative mt-3 w-full">
                  <button
                    type="button"
                    class="flex w-full items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3 text-left text-sm font-semibold text-slate-700 shadow-sm transition hover:border-blue-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                    :class="assigneeDropdownOpen ? 'rounded-b-none border-blue-400' : ''"
                    @click="toggleAssigneeDropdown"
                  >
                    <div class="flex flex-1 flex-wrap gap-1.5">
                      <span
                        v-for="person in selectedAssigneeDetails"
                        :key="`chip-assignee-${person.id}`"
                        class="inline-flex items-center gap-1 rounded-lg border border-blue-200/70 bg-white px-2 py-1 text-xs font-medium text-blue-700 dark:border-blue-500/40 dark:bg-slate-900/70 dark:text-blue-200"
                      >
                        {{ person.label }}
                        <button type="button" class="text-slate-400 transition hover:text-rose-500" @click.stop="removeAssigneeSelection(person.id)">
                          <span class="material-icons text-[13px]">close</span>
                        </button>
                      </span>
                      <span v-if="!selectedAssigneeDetails.length" class="text-xs font-normal text-slate-400">{{ $t("Belum ada agent dipilih") }}</span>
                    </div>
                    <span class="material-icons text-base text-slate-400 transition duration-200" :class="assigneeDropdownOpen ? 'rotate-180 text-blue-500' : ''">expand_more</span>
                  </button>
                  <transition name="fade-scale">
                    <div
                      v-if="assigneeDropdownOpen"
                      class="absolute left-0 right-0 top-full z-[9999] mt-2 w-full rounded-2xl rounded-t-none border border-slate-200 bg-white p-3 shadow-2xl md:p-4 dark:border-slate-700 dark:bg-slate-900"
                      :class="assigneeDropdownOpen ? 'border-blue-400' : ''"
                    >
                      <div class="flex flex-wrap gap-2 border-b border-slate-100 pb-3 dark:border-slate-800">
                        <button
                          v-for="unit in allUnits"
                          :key="`assignee-unit-${unit}`"
                          type="button"
                          class="rounded-full border px-3 py-1 text-xs font-semibold transition"
                          :class="activeAssigneeUnit === unit ? 'border-blue-500 bg-blue-50 text-blue-700 dark:border-blue-400 dark:bg-blue-500/10 dark:text-blue-200' : 'border-slate-200 text-slate-500 hover:border-blue-300 hover:text-blue-600 dark:border-slate-700 dark:text-slate-300'"
                          @click="activeAssigneeUnit = unit"
                        >
                          {{ unit }}
                        </button>
                      </div>
                      <div class="mt-3 max-h-64 space-y-2 overflow-y-auto pr-1">
                        <label
                          v-for="option in activeAssigneeList"
                          :key="`assignee-option-${option.id}`"
                          class="flex items-start justify-between gap-3 rounded-xl border border-slate-200 px-3 py-2 text-sm shadow-sm transition hover:border-blue-400 dark:border-slate-700"
                        >
                          <div class="flex items-start gap-3">
                            <input
                              type="checkbox"
                              class="mt-1 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                              :checked="isAssigneeSelected(option.id)"
                              @change="toggleAssigneeSelection(option.id)"
                            />
                            <div>
                              <p class="font-semibold text-slate-700 dark:text-slate-100">{{ option.label }}</p>
                              <p class="text-xs text-slate-400">{{ option.email || '—' }} · {{ option.unit || unitPlaceholderLabel }}</p>
                            </div>
                          </div>
                          <button
                            type="button"
                            class="rounded-full px-2 py-1 text-[11px] font-semibold uppercase tracking-wide transition"
                            :class="assigneePrimary === normalizeSelectionId(option.id) ? 'bg-blue-50 text-blue-600 dark:bg-blue-500/20 dark:text-blue-100' : 'text-slate-400 hover:text-blue-500'"
                            @click.stop="setAssigneePrimary(option.id)"
                          >
                            {{ assigneePrimary === normalizeSelectionId(option.id) ? $t("Utama") : $t("Jadikan Utama") }}
                          </button>
                        </label>
                        <p v-if="!activeAssigneeList.length" class="text-sm text-slate-400">{{ $t("Belum ada agent pada unit ini.") }}</p>
                      </div>
                    </div>
                  </transition>
                </div>
                <p class="mt-2 text-xs text-slate-400">
                  {{ assigneeSelections.length ? assigneeSelections.length + ' agent terhubung' : $t("Belum ada agent terpilih") }}
                </p>
                <p v-if="form.errors.assignee_id" class="mt-2 text-xs text-red-500">{{ form.errors.assignee_id }}</p>
              </div>

              <div ref="additionalCardRef" class="relative rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-100">PIC</p>
                    <p class="text-xs text-slate-400">{{ $t("Tambahkan pendukung tugas dari unit manapun.") }}</p>
                  </div>
                  <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">
                    {{ additionalSelections.length ? additionalSelections.length + ' PIC dipilih' : $t("Belum ada pilihan") }}
                  </span>
                </div>
                <div class="relative mt-3 w-full">
                  <button
                    type="button"
                    class="flex w-full items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3 text-left text-sm font-semibold text-slate-700 shadow-sm transition hover:border-emerald-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                    :class="additionalDropdownOpen ? 'rounded-b-none border-emerald-400' : ''"
                    @click="toggleAdditionalDropdown"
                  >
                    <div class="flex flex-1 flex-wrap gap-1.5">
                      <span
                        v-for="person in selectedAdditionalDetails"
                        :key="`chip-additional-${person.id}`"
                        class="inline-flex items-center gap-1 rounded-lg border border-emerald-200/70 bg-white px-2 py-1 text-xs font-medium text-emerald-700 dark:border-emerald-500/40 dark:bg-slate-900/70 dark:text-emerald-200"
                      >
                        {{ person.label }}
                        <button type="button" class="text-slate-400 transition hover:text-rose-500" @click.stop="removeAdditionalSelection(person.id)">
                          <span class="material-icons text-[13px]">close</span>
                        </button>
                      </span>
                      <span v-if="!selectedAdditionalDetails.length" class="text-xs font-normal text-slate-400">{{ $t("Belum ada PIC") }}</span>
                    </div>
                    <span class="material-icons text-base text-slate-400 transition duration-200" :class="additionalDropdownOpen ? 'rotate-180 text-emerald-500' : ''">expand_more</span>
                  </button>
                  <transition name="fade-scale">
                    <div
                      v-if="additionalDropdownOpen"
                      class="absolute left-0 right-0 top-full z-[9999] mt-2 w-full rounded-2xl rounded-t-none border border-slate-200 bg-white p-3 shadow-2xl md:p-4 dark:border-slate-700 dark:bg-slate-900"
                      :class="additionalDropdownOpen ? 'border-emerald-400' : ''"
                    >
                      <div class="flex flex-wrap gap-2 border-b border-slate-100 pb-3 dark:border-slate-800">
                        <button
                          v-for="unit in allUnits"
                          :key="`additional-unit-${unit}`"
                          type="button"
                          class="rounded-full border px-3 py-1 text-xs font-semibold transition"
                          :class="activeAdditionalUnit === unit ? 'border-emerald-500 bg-emerald-50 text-emerald-700 dark:border-emerald-400 dark:bg-emerald-500/10 dark:text-emerald-200' : 'border-slate-200 text-slate-500 hover:border-emerald-300 hover:text-emerald-600 dark:border-slate-700 dark:text-slate-300'"
                          @click="activeAdditionalUnit = unit"
                        >
                          {{ unit }}
                        </button>
                      </div>
                      <div class="mt-3 max-h-64 space-y-2 overflow-y-auto pr-1">
                        <label
                          v-for="option in activeAdditionalList"
                          :key="`additional-option-${option.id}`"
                          class="flex items-start justify-between gap-3 rounded-xl border border-slate-200 px-3 py-2 text-sm shadow-sm transition hover:border-emerald-400 dark:border-slate-700"
                        >
                          <div class="flex items-start gap-3">
                            <input
                              type="checkbox"
                              class="mt-1 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                              :checked="isAdditionalSelected(option.id)"
                              @change="toggleAdditionalSelection(option.id)"
                            />
                            <div>
                              <p class="font-semibold text-slate-700 dark:text-slate-100">{{ option.label }}</p>
                              <p class="text-xs text-slate-400">{{ option.email || '—' }} · {{ option.unit || unitPlaceholderLabel }}</p>
                            </div>
                          </div>
                          <button
                            type="button"
                            class="rounded-full px-2 py-1 text-[11px] font-semibold uppercase tracking-wide text-slate-400 transition hover:text-emerald-500"
                            @click.stop="setAdditionalPrimary(option.id)"
                          >
                            {{ $t("Jadikan Utama") }}
                          </button>
                        </label>
                        <p v-if="!activeAdditionalList.length" class="text-sm text-slate-400">{{ $t("Belum ada PIC pada unit ini.") }}</p>
                      </div>
                    </div>
                  </transition>
                </div>
                <p class="mt-2 text-xs text-slate-400">
                  {{ additionalSelections.length ? additionalSelections.length + ' PIC terhubung' : $t("Belum ada PIC") }}
                </p>
              </div>
            </div>
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
                        <p v-if="!activeRequesterList.length" class="text-sm text-slate-400">{{ $t("Belum ada requester pada unit ini.") }}</p>
                      </template>
                      <p v-else class="text-sm text-slate-400">{{ $t("Pilih unit untuk melihat requester.") }}</p>
                    </div>
                  </div>
                </transition>
              </div>
            </template>
            <template v-else>
              <div class="field-input bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-300">
                {{ requesterLabel || $t("Requester mengikuti pengguna yang sedang login.") }}
              </div>
            </template>
            <p v-if="form.errors.requester_id" class="field-error">{{ form.errors.requester_id }}</p>
          </div>
        </div>

        <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-700 dark:bg-slate-900/60">
          <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-100">{{ $t("Ringkasan Penugasan") }}</h3>
          <div class="mt-3 grid gap-3 sm:grid-cols-2">
            <div class="rounded-xl border border-blue-200/60 bg-white p-3 dark:border-blue-500/30 dark:bg-slate-900">
              <div class="mb-2 flex items-center gap-2 text-sm font-semibold text-blue-700 dark:text-blue-200">
                <span class="material-icons text-base">verified_user</span>
                <span>Agent ({{ selectedAssigneeDetails.length }})</span>
              </div>
              <div class="flex flex-wrap gap-1">
                <span
                  v-for="person in selectedAssigneeDetails"
                  :key="`summary-main-${person.id}`"
                  class="text-xs rounded-md border border-blue-200 bg-white px-2 py-0.5 text-blue-600 dark:border-blue-400/40 dark:bg-slate-900 dark:text-blue-100"
                >
                  {{ person.label }}
                  <span v-if="assigneePrimary === normalizeSelectionId(person.id)" class="font-semibold text-blue-500"> {{ $t("· Utama") }}</span>
                </span>
                <span v-if="!selectedAssigneeDetails.length" class="text-xs text-slate-400">{{ $t("Belum ada data") }}</span>
              </div>
            </div>
            <div class="rounded-xl border border-emerald-200/60 bg-white p-3 dark:border-emerald-500/30 dark:bg-slate-900">
              <div class="mb-2 flex items-center gap-2 text-sm font-semibold text-emerald-700 dark:text-emerald-200">
                <span class="material-icons text-base">groups</span>
                <span>PIC ({{ selectedAdditionalDetails.length }})</span>
              </div>
              <div class="flex flex-wrap gap-1">
                <span
                  v-for="person in selectedAdditionalDetails"
                  :key="`summary-additional-${person.id}`"
                  class="text-xs rounded-md border border-emerald-200 bg-white px-2 py-0.5 text-emerald-600 dark:border-emerald-400/40 dark:bg-slate-900 dark:text-emerald-100"
                >
                  {{ person.label }}
                </span>
                <span v-if="!selectedAdditionalDetails.length" class="text-xs text-slate-400">{{ $t("Belum ada data") }}</span>
              </div>
            </div>
          </div>
          <p v-if="form.errors.assignees" class="field-error">{{ form.errors.assignees }}</p>
        </div>

        <div class="mt-4 grid gap-4 md:grid-cols-2">
          <div>
            <label class="field-label">{{ $t("Mulai") }}</label>
            <DatePickerFlatpickr v-model="form.start_at" :config="dateConfig" :placeholder="$t(&quot;Pilih tanggal & waktu&quot;)" />
            <p v-if="form.errors.start_at || form.errors.start_date" class="field-error">{{ form.errors.start_at || form.errors.start_date }}</p>
          </div>
          <div>
            <label class="field-label"> {{ $t("Selesai") }}</label>
            <DatePickerFlatpickr v-model="form.end_at" :config="dateConfig" :placeholder="$t(&quot;Pilih tanggal & waktu&quot;)" />
            <p v-if="form.errors.end_at || form.errors.end_date || form.errors.due_at" class="field-error">{{ form.errors.end_at || form.errors.end_date || form.errors.due_at }}</p>
          </div>
        </div>


      </section>

      <section v-show="showStep('attachments')" class="rounded-3xl border border-slate-200 bg-white/90 p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
        <h2 class="section-title">{{ $t("Lampiran") }}</h2>
        <div class="mt-3 space-y-2">
          <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $t("Jenis File") }}</label>
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
              <span>{{ attachmentIds.length ? attachmentIds.length + ' file siap diunggah' : $t("Belum ada file siap diunggah") }}</span>
              <button
                v-if="attachmentIds.length"
                type="button"
                class="text-blue-600 hover:underline dark:text-blue-400"
                @click="clearAttachments"
              >
                {{ $t("Bersihkan Lampiran") }}
              </button>
            </div>
          </div>
        </div>
        <p v-if="uploaderDisabled" class="field-error text-amber-600">{{ $t("Pilih jenis file terlebih dahulu sebelum mengunggah.") }}</p>
        <p v-if="form.errors.attachments" class="field-error">{{ form.errors.attachments }}</p>
        <p v-if="uploadError" class="field-error">{{ uploadError }}</p>
      </section>

      <div class="flex flex-wrap items-center justify-end gap-3">
        <button
          type="button"
          class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-4 py-2 text-sm text-slate-600 transition hover:bg-slate-100 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800"
          @click="resetForm"
        >
          Reset
        </button>
        <SaveButton v-if="isLastStep" type="submit" :processing="form.processing">
          {{ primaryButtonLabel }}
        </SaveButton>
        <button v-else
          type="submit"
          class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-500 via-teal-500 to-sky-500 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-500/30 transition hover:shadow-xl disabled:cursor-not-allowed disabled:opacity-70"
          :disabled="form.processing"
        >
          <span class="material-icons text-base" v-if="form.processing">hourglass_top</span>
          <span class="material-icons text-base" v-else>{{ primaryButtonIcon }}</span>
          {{ primaryButtonLabel }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { translateUi as uiText } from '../../i18n'

import { useFormDraft } from '@/composables/useFormDraft';
import DraftNotice from '@/Components/DraftNotice.vue';
import { computed, onBeforeUnmount, onMounted, ref, watch, watchEffect } from 'vue';
import { useForm } from '@inertiajs/vue3';
import resolveRoute from '@/utils/resolveRoute';
import DatePickerFlatpickr from '@/Components/DatePickerFlatpickr.vue';
import FileUploaderPond from '@/Components/FileUploaderPond.vue';
import RichTextQuill from '@/Components/RichTextQuill.vue';
import FancySelect from '@/Components/FancySelect.vue';
import { attachmentFilterOptions } from '@/utils/attachmentFilters';

const props = defineProps({
  statusOptions: { type: Array, default: () => [] },
  priorityOptions: { type: Array, default: () => [] },
  userOptions: { type: Array, default: () => [] },
  defaults: { type: Object, default: () => ({}) },
  meta: { type: Object, default: () => ({}) },
});

const steps = [
  { key: 'info', label: uiText("Informasi Utama") },
  { key: 'assignment', label: uiText("Penugasan & Timeline") },
  { key: 'attachments', label: uiText("Lampiran") },
];

const stepFieldGroups = {
  info: ['title', 'status', 'priority', 'description'],
  assignment: [
    'assignee_id',
    'assignees',
    'assigned_to',
    'requester_id',
    'due_at',
    'due_date',
    'start_at',
    'end_at',
    'start_date',
    'end_date',
  ],
  attachments: ['attachments'],
};

const fieldStepLookup = Object.entries(stepFieldGroups).reduce((acc, [stepKey, fields]) => {
  fields.forEach(field => {
    acc[field] = stepKey;
  });
  return acc;
}, {});

const form = useForm({
  title: '',
  description: '',
  status: props.defaults.status ?? 'new',
  priority: props.defaults.priority ?? 'normal',
  assignees: [],
  assignee_id: null,
  assigned_to: [],
  requester_id: props.defaults.requester_id ?? null,
  start_at: null,
  end_at: null,
  due_at: null,
  start_date: null,
  end_date: null,
  attachments: [],
});
const draft = useFormDraft(form, 'task', ["title","description","priority","start_at","end_at"], true);


const statusGuide = computed(() => props.meta?.statusGuide ?? null);
const statusDefaultLabel = computed(() => props.meta?.statusDefault ?? uiText("New"));
const allowedStatusValues = computed(() => (Array.isArray(props.meta?.allowedStatuses) ? props.meta.allowedStatuses : []));
const statusLocked = computed(() => Boolean(props.meta?.lockStatus) || allowedStatusValues.value.length <= 1);
watchEffect(() => {
  if (statusLocked.value) {
    form.status = props.defaults.status ?? 'new';
  }
});
const canSelectRequester = computed(() => Boolean(props.meta?.canSelectRequester));
const requesterLabel = computed(() => props.meta?.requesterLabel ?? '');
const displayStatusOptions = computed(() => {
  const baseOptions = props.statusOptions ?? [];
  if (!allowedStatusValues.value.length) {
    return baseOptions;
  }
  const allowedSet = new Set([
    ...allowedStatusValues.value,
    form.status ?? null,
  ].filter(Boolean));
  const filtered = baseOptions.filter(option => allowedSet.has(option.value));

  return filtered.length ? filtered : baseOptions;
});
const userOptions = computed(() => props.userOptions ?? []);
const unitPlaceholderLabel = uiText("Unit Tidak Tercatat");
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
const usersByUnit = computed(() => {
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
const userLookup = computed(() => {
  const map = new Map();
  userOptions.value.forEach(option => {
    map.set(String(option.id), option);
  });

  return map;
});
const requesterCardRef = ref(null);
const requesterTriggerRef = ref(null);
const requesterDropdownRef = ref(null);
const requesterDropdownOpen = ref(false);
const activeRequesterUnit = ref(null);
const requesterOptions = computed(() => (userOptions.value ?? []).map(option => ({
  ...option,
  label: formatRequesterLabel(option),
})));
const selectedRequester = computed(() => requesterOptions.value.find(
  option => normalizeSelectionId(option.id) === normalizeSelectionId(form.requester_id)
) ?? null);
const selectedRequesterLabel = computed(() => selectedRequester.value?.label ?? uiText("Pilih requester"));
const activeRequesterList = computed(() => {
  if (!activeRequesterUnit.value) return [];
  return requesterOptions.value.filter(option => {
    const unit = typeof option.unit === 'string' && option.unit.trim() !== '' ? option.unit.trim() : unitPlaceholderLabel;
    return unit === activeRequesterUnit.value;
  });
});
const normalizeSelectionId = value => {
  if (typeof value === 'number' && Number.isFinite(value)) {
    return value;
  }
  if (typeof value === 'string' && value.trim() !== '' && !Number.isNaN(Number(value))) {
    return Number(value);
  }

  return null;
};
const assigneeCardRef = ref(null);
const additionalCardRef = ref(null);
const assigneeDropdownOpen = ref(false);
const additionalDropdownOpen = ref(false);
const activeAssigneeUnit = ref('');
const activeAdditionalUnit = ref('');
const assigneeSelections = ref([]);
const additionalSelections = ref([]);
const assigneePrimary = ref(normalizeSelectionId(form.assignee_id) ?? null);
const activeAssigneeList = computed(() => {
  if (!activeAssigneeUnit.value) return [];
  return usersByUnit.value[activeAssigneeUnit.value] ?? [];
});
const activeAdditionalList = computed(() => {
  if (!activeAdditionalUnit.value) return [];
  return usersByUnit.value[activeAdditionalUnit.value] ?? [];
});
const selectedAssigneeDetails = computed(() => {
  const lookup = userLookup.value;

  return assigneeSelections.value
    .map(id => lookup.get(String(normalizeSelectionId(id))))
    .filter(Boolean);
});
const selectedAdditionalDetails = computed(() => {
  const lookup = userLookup.value;

  return additionalSelections.value
    .map(id => lookup.get(String(normalizeSelectionId(id))))
    .filter(Boolean);
});
const isAssigneeSelected = id => assigneeSelections.value
  .map(normalizeSelectionId)
  .includes(normalizeSelectionId(id));
const isAdditionalSelected = id => additionalSelections.value
  .map(normalizeSelectionId)
  .includes(normalizeSelectionId(id));

const uploadError = ref('');
const attachmentIds = ref([]);
const attachmentFilterChoices = attachmentFilterOptions;
const selectedAttachmentFilter = ref('');
const activeAttachmentFilter = computed(
  () => attachmentFilterChoices.find(option => option.value === selectedAttachmentFilter.value) ?? null
);
const attachmentMimeTypes = computed(() => activeAttachmentFilter.value?.mimeTypes ?? []);
const attachmentAllowedExtensions = computed(() => activeAttachmentFilter.value?.extensions ?? []);
const uploaderDisabled = computed(() => !activeAttachmentFilter.value);
const attachmentFilterHint = computed(() => (
  activeAttachmentFilter.value ? (uiText("Hanya ") + (activeAttachmentFilter.value.label)) : uiText("Pilih jenis file untuk mengaktifkan unggahan.")
));
const uploaderRef = ref(null);
const currentStep = ref(0);
const currentStepKey = computed(() => steps[currentStep.value].key);
const isLastStep = computed(() => currentStep.value === steps.length - 1);
const primaryButtonLabel = computed(() => (isLastStep.value ? uiText("Simpan Task") : uiText('Lanjut')));
const primaryButtonIcon = computed(() => (isLastStep.value ? 'check_circle' : 'arrow_forward'));

const dateConfig = computed(() => ({
  enableTime: true,
  altInput: true,
  altFormat: 'd M Y, H:i',
  dateFormat: 'Y-m-d H:i',
}));

const toDatePart = value => {
  if (!value) return null;
  const raw = String(value).trim();
  if (!raw) return null;
  return raw.slice(0, 10);
};

function showStep(key) {
  return currentStepKey.value === key;
}

function goToStep(index) {
  currentStep.value = Math.min(Math.max(index, 0), steps.length - 1);
}

function nextStep() {
  if (!isLastStep.value) {
    goToStep(currentStep.value + 1);
  }
}

function toggleRequesterDropdown() {
  requesterDropdownOpen.value = !requesterDropdownOpen.value;
  if (requesterDropdownOpen.value) {
    assigneeDropdownOpen.value = false;
    additionalDropdownOpen.value = false;
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

function sanitizeSelectionList(list) {
  return list
    .map(normalizeSelectionId)
    .filter(id => id !== null && id !== '');
}

function toggleAssigneeSelection(rawId) {
  const id = normalizeSelectionId(rawId);
  if (id === null) return;
  const set = new Set(assigneeSelections.value.map(normalizeSelectionId));
  if (set.has(id)) {
    set.delete(id);
  } else {
    set.add(id);
  }
  assigneeSelections.value = Array.from(set);
}

function removeAssigneeSelection(rawId) {
  const id = normalizeSelectionId(rawId);
  if (id === null) return;
  assigneeSelections.value = assigneeSelections.value.filter(value => normalizeSelectionId(value) !== id);
  if (normalizeSelectionId(assigneePrimary.value) === id) {
    assigneePrimary.value = assigneeSelections.value[0] ?? null;
  }
}

function setAssigneePrimary(rawId) {
  const id = normalizeSelectionId(rawId);
  if (id === null) return;
  if (!isAssigneeSelected(id)) {
    assigneeSelections.value = [...assigneeSelections.value, id];
  }
  assigneePrimary.value = id;
}

function toggleAssigneeDropdown() {
  assigneeDropdownOpen.value = !assigneeDropdownOpen.value;
  if (assigneeDropdownOpen.value) {
    additionalDropdownOpen.value = false;
    requesterDropdownOpen.value = false;
  }
}

function toggleAdditionalSelection(rawId) {
  const id = normalizeSelectionId(rawId);
  if (id === null) return;
  const set = new Set(additionalSelections.value.map(normalizeSelectionId));
  if (set.has(id)) {
    set.delete(id);
  } else {
    set.add(id);
  }
  additionalSelections.value = Array.from(set);
}

function removeAdditionalSelection(rawId) {
  const id = normalizeSelectionId(rawId);
  if (id === null) return;
  additionalSelections.value = additionalSelections.value.filter(value => normalizeSelectionId(value) !== id);
}

function setAdditionalPrimary(rawId) {
  const id = normalizeSelectionId(rawId);
  if (id === null) return;
  if (!isAdditionalSelected(id)) {
    additionalSelections.value = [...additionalSelections.value, id];
  }
  setAssigneePrimary(id);
}

function toggleAdditionalDropdown() {
  additionalDropdownOpen.value = !additionalDropdownOpen.value;
  if (additionalDropdownOpen.value) {
    assigneeDropdownOpen.value = false;
    requesterDropdownOpen.value = false;
  }
}

function handleOutsideClick(event) {
  const target = event.target;
  if (assigneeDropdownOpen.value && assigneeCardRef.value && !assigneeCardRef.value.contains(target)) {
    assigneeDropdownOpen.value = false;
  }
  if (additionalDropdownOpen.value && additionalCardRef.value && !additionalCardRef.value.contains(target)) {
    additionalDropdownOpen.value = false;
  }
  if (requesterDropdownOpen.value && requesterCardRef.value && !requesterCardRef.value.contains(target)) {
    requesterDropdownOpen.value = false;
  }
}

function handleEscapeKey(event) {
  if (event.key !== 'Escape') return;
  assigneeDropdownOpen.value = false;
  additionalDropdownOpen.value = false;
  requesterDropdownOpen.value = false;
}

function initializeAssigneeSelections() {
  const existing = Array.isArray(form.assignees)
    ? form.assignees.map(normalizeSelectionId).filter(id => id !== null && id !== '')
    : [];
  const primary = normalizeSelectionId(form.assignee_id) ?? existing[0] ?? null;
  assigneePrimary.value = primary;
  assigneeSelections.value = primary ? [primary] : [];
  additionalSelections.value = existing.filter(id => id !== primary);
  updateAssigneePayload();
}

watch(allUnits, units => {
  if (!units.length) {
    activeAssigneeUnit.value = '';
    activeAdditionalUnit.value = '';
    activeRequesterUnit.value = null;
    return;
  }
  if (!units.includes(activeAssigneeUnit.value)) {
    activeAssigneeUnit.value = '';
  }
  if (!units.includes(activeAdditionalUnit.value)) {
    activeAdditionalUnit.value = '';
  }
  if (activeRequesterUnit.value && !units.includes(activeRequesterUnit.value)) {
    activeRequesterUnit.value = null;
  }
}, { immediate: true });

const userUnit = id => {
  const user = userLookup.value.get(String(id));
  return typeof user?.unit === 'string' ? user.unit.trim() : '';
};

watch(activeAssigneeUnit, unit => {
  const normalized = (unit || '').trim();
  if (!normalized) {
    assigneeSelections.value = [];
    assigneePrimary.value = null;
    updateAssigneePayload();
    return;
  }
  const filtered = assigneeSelections.value.filter(id => userUnit(id) === normalized);
  if (filtered.length !== assigneeSelections.value.length) {
    assigneeSelections.value = filtered;
  }
  if (assigneePrimary.value && userUnit(assigneePrimary.value) !== normalized) {
    assigneePrimary.value = filtered[0] ?? null;
  }
  updateAssigneePayload();
});

watch(activeAdditionalUnit, unit => {
  const normalized = (unit || '').trim();
  if (!normalized) {
    additionalSelections.value = [];
    updateAssigneePayload();
    return;
  }
  const filtered = additionalSelections.value.filter(id => userUnit(id) === normalized);
  if (filtered.length !== additionalSelections.value.length) {
    additionalSelections.value = filtered;
    updateAssigneePayload();
  }
});

watch(assigneeSelections, value => {
  const normalized = sanitizeSelectionList(value);

  if (normalized.length !== value.length || normalized.some((id, idx) => id !== value[idx])) {
    assigneeSelections.value = normalized;
    return;
  }

  if (!normalized.length) {
    assigneePrimary.value = null;
  } else if (!normalized.includes(normalizeSelectionId(assigneePrimary.value))) {
    assigneePrimary.value = normalized[0];
    return;
  }

  updateAssigneePayload();
}, { deep: true });

watch(additionalSelections, value => {
  const normalized = sanitizeSelectionList(value);
  if (normalized.length !== value.length || normalized.some((id, idx) => id !== value[idx])) {
    additionalSelections.value = normalized;
    return;
  }

  updateAssigneePayload();
}, { deep: true });

watch(assigneePrimary, value => {
  const normalized = normalizeSelectionId(value);
  const currentSet = assigneeSelections.value.map(normalizeSelectionId);
  if (normalized && !currentSet.includes(normalized)) {
    assigneeSelections.value = [...assigneeSelections.value, normalized];
    return;
  }
  form.assignee_id = normalized ?? null;
  updateAssigneePayload();
}, { immediate: true });

function updateAssigneePayload() {
  const primary = normalizeSelectionId(assigneePrimary.value);
  const merged = [
    ...assigneeSelections.value,
    ...additionalSelections.value,
  ].map(normalizeSelectionId).filter(id => id !== null && id !== '');

  const ordered = [];
  const seen = new Set();

  if (primary) {
    ordered.push(primary);
    seen.add(primary);
  }

  merged.forEach(id => {
    if (id && !seen.has(id)) {
      seen.add(id);
      ordered.push(id);
    }
  });

  form.assignees = ordered;
  syncAssignedArray();
}

function syncAssignedArray() {
  const ids = new Set([
    ...(Array.isArray(form.assignees) ? form.assignees : []),
    form.assignee_id ?? undefined,
  ].filter(Boolean));
  form.assigned_to = Array.from(ids);
}

function resolveErrorStep(errors) {
  if (!errors) return null;
  for (const key of Object.keys(errors)) {
    const baseKey = key.split('.')[0];
    const stepKey = fieldStepLookup[baseKey];
    if (stepKey) {
      return stepKey;
    }
  }
  return null;
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
    uploadError.value = uiText("Gagal mengunggah file.");
  }
}

function clearAttachments() {
  if (!attachmentIds.value.length) {
    return;
  }
  attachmentIds.value = [];
  form.attachments = [];
  uploaderRef.value?.reset();
  uploadError.value = '';
}

watch(selectedAttachmentFilter, () => {
  attachmentIds.value = [];
  form.attachments = [];
  uploaderRef.value?.reset();
  uploadError.value = '';
});

onMounted(() => {
  window.addEventListener('click', handleOutsideClick, true);
  window.addEventListener('keydown', handleEscapeKey, true);
  initializeAssigneeSelections();
});

onBeforeUnmount(() => {
  window.removeEventListener('click', handleOutsideClick, true);
  window.removeEventListener('keydown', handleEscapeKey, true);
});

function resetForm() {
  draft.clear();
  form.reset();
  form.status = props.defaults.status ?? 'new';
  form.priority = props.defaults.priority ?? 'normal';
  form.assignees = [];
  form.assignee_id = null;
  form.assigned_to = [];
  form.start_at = null;
  form.end_at = null;
  form.due_at = null;
  form.start_date = null;
  form.end_date = null;
  form.requester_id = props.defaults.requester_id ?? null;
  attachmentIds.value = [];
  uploadError.value = '';
  selectedAttachmentFilter.value = '';
  uploaderRef.value?.reset();
  currentStep.value = 0;
  assigneeSelections.value = [];
  assigneePrimary.value = null;
  assigneeDropdownOpen.value = false;
  additionalSelections.value = [];
  additionalDropdownOpen.value = false;
  activeAssigneeUnit.value = '';
  activeAdditionalUnit.value = '';
  requesterDropdownOpen.value = false;
  activeRequesterUnit.value = null;
}

function handleSubmit() {
  if (!isLastStep.value) {
    nextStep();
    return;
  }

  submitForm();
}

function submitForm() {
  uploadError.value = '';
  syncAssignedArray();
  form.start_date = toDatePart(form.start_at);
  form.end_date = toDatePart(form.end_at);
  form.due_at = form.end_at || null;
  form.transform(data => ({
    ...data,
    title: (data.title ?? '').toString().trim(),
    status: (data.status ?? '').toString().trim() || (props.defaults.status ?? 'new'),
  })).post(resolveRoute('tasks.store'), {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      resetForm();
    },
    onError: errors => {
      const stepKey = resolveErrorStep(errors);
      if (stepKey) {
        const idx = steps.findIndex(step => step.key === stepKey);
        if (idx !== -1) {
          currentStep.value = idx;
        }
      }
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
  return option?.email || uiText("Email tidak tersedia");
}

function formatRequesterLabel(option) {
  const title = formatRequesterTitle(option);
  const email = option?.email ? ` — ${option.email}` : '';

  return `${title}${email}`.trim();
}
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


.wizard {
  display: flex;
  justify-content: center;
  margin-bottom: 1rem;
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
  color: #2563eb;
}

.wizard__indicator {
  width: 34px;
  height: 34px;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: rgba(148, 163, 184, 0.3);
  color: #2563eb;
  font-weight: 600;
  transition: all 0.2s ease;
}

.wizard__label {
  font-size: 0.75rem;
  font-weight: 600;
  white-space: nowrap;
  text-align: center;
}

.wizard__divider {
  flex: 1;
  height: 2px;
  min-width: 28px;
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
</style>
