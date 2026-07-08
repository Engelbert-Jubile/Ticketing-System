<template>
  <div class="mx-auto max-w-5xl space-y-6 px-4 py-6 pt-8 lg:px-6 lg:pt-10">
    <header class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Ticket Baru</h1>
        <p class="text-sm text-slate-500 dark:text-slate-300">Lengkapi detail ticket dan pilih PIC utama untuk tugas ini.</p>
      </div>
    </header>

    <section
      v-if="flash.success"
      class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-900/30 dark:text-emerald-200"
    >
      {{ flash.success }}
    </section>
    <section
      v-else-if="flash.error || submitError"
      class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-900/60 dark:bg-rose-900/30 dark:text-rose-200"
    >
      {{ flash.error || submitError }}
    </section>

    <form class="mt-4 space-y-6 rounded-3xl border border-slate-200 bg-white/90 p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900/70" @submit.prevent="submit">
      <section class="grid gap-4 md:grid-cols-2">
        <div>
          <label class="block text-sm font-semibold text-slate-600 dark:text-slate-300">Judul</label>
          <input
            v-model="form.title"
            type="text"
            placeholder="Masukkan judul ticket"
            class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-blue-500 dark:focus:ring-blue-500/40"
          />
          <p v-if="form.errors.title" class="mt-1 text-xs text-red-500">{{ form.errors.title }}</p>
        </div>

        <div>
          <label class="block text-sm font-semibold text-slate-600 dark:text-slate-300">Nomor Surat</label>
          <input
            v-model="form.letter_no"
            type="text"
            placeholder="Opsional"
            class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-blue-500 dark:focus:ring-blue-500/40"
          />
          <p v-if="form.errors.letter_no" class="mt-1 text-xs text-red-500">{{ form.errors.letter_no }}</p>
        </div>

        <div class="md:col-span-2">
          <label class="block text-sm font-semibold text-slate-600 dark:text-slate-300">Reason</label>
          <input
            v-model="form.reason"
            type="text"
            placeholder="Ringkas alasan dibuatnya ticket"
            class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-blue-500 dark:focus:ring-blue-500/40"
          />
          <p v-if="form.errors.reason" class="mt-1 text-xs text-red-500">{{ form.errors.reason }}</p>
        </div>

        <div>
          <label class="block text-sm font-semibold text-slate-600 dark:text-slate-300">Prioritas</label>
          <div class="mt-1 w-full">
            <FancySelect v-model="form.priority" :options="priorityOptions" accent="subtle" />
          </div>
          <p v-if="form.errors.priority" class="mt-1 text-xs text-red-500">{{ form.errors.priority }}</p>
        </div>

        <div>
          <label class="block text-sm font-semibold text-slate-600 dark:text-slate-300">Jenis Ticket</label>
          <div class="mt-1 w-full">
            <FancySelect v-model="form.type" :options="typeOptions" :disabled="true" accent="blue" />
          </div>
          <p v-if="form.errors.type" class="mt-1 text-xs text-red-500">{{ form.errors.type }}</p>
        </div>

        <div>
          <label class="block text-sm font-semibold text-slate-600 dark:text-slate-300">Status</label>
          <div class="relative mt-1 w-full">
            <FancySelect
              v-model="form.status"
              :options="statusOptions"
              :disabled="statusLocked"
              accent="blue"
            />
          </div>
          <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Status awal: New.</p>
          <div class="mt-1 space-y-0.5 text-xs text-slate-500 dark:text-slate-400">
            <p>Status: In Progress dan Confirmation hanya diubah oleh Agent.</p>
            <p>Status: Revision, Done, Cancelled, dan On Hold hanya diubah oleh Requester.</p>
          </div>
          <p v-if="form.errors.status" class="mt-1 text-xs text-red-500">{{ form.errors.status }}</p>
        </div>

        <div>
          <label class="block text-sm font-semibold text-slate-600 dark:text-slate-300">SLA</label>
          <div class="mt-1 w-full">
            <FancySelect v-model="form.sla" :options="slaSelectOptions" accent="subtle" />
          </div>
          <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Pilih SLA yang berlaku atau biarkan kosong bila belum ditentukan.</p>
          <p v-if="form.errors.sla" class="mt-1 text-xs text-red-500">{{ form.errors.sla }}</p>
        </div>

        <div>
          <label class="block text-sm font-semibold text-slate-600 dark:text-slate-300">Mulai</label>
          <DatePickerFlatpickr
            class="mt-1"
            v-model="form.start_at"
            :config="startDateConfig"
            placeholder="Pilih tanggal"
          />
          <p v-if="form.errors.start_at || form.errors.due_date" class="mt-1 text-xs text-red-500">{{ form.errors.start_at || form.errors.due_date }}</p>
        </div>

        <div>
          <label class="block text-sm font-semibold text-slate-600 dark:text-slate-300">Selesai</label>
          <DatePickerFlatpickr
            class="mt-1"
            v-model="form.finish_at"
            :config="finishDateConfig"
            placeholder="Pilih tanggal"
          />
          <p v-if="form.errors.finish_at || form.errors.finish_date" class="mt-1 text-xs text-red-500">{{ form.errors.finish_at || form.errors.finish_date }}</p>
        </div>
      </section>

      <section class="space-y-6">
        <div class="grid gap-4 md:grid-cols-2">
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
                          :key="`requester-option-${option.id}`"
                          type="button"
                          class="flex w-full items-start justify-between gap-3 rounded-xl border border-slate-200 px-3 py-2 text-sm text-left shadow-sm transition hover:border-blue-400 dark:border-slate-700"
                          :class="form.requester_id === option.id ? 'border-blue-400 dark:border-blue-500' : ''"
                          @click="setRequesterSelection(option.id)"
                        >
                          <div class="space-y-0.5">
                            <p class="font-semibold text-slate-700 dark:text-slate-100">{{ option.label }}</p>
                            <p class="text-xs text-slate-400">{{ option.email || '-' }}</p>
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
                {{ requesterLabel || 'Menggunakan akun Anda' }}
              </div>
            </template>
            <p v-if="form.errors.requester_id" class="mt-1 text-xs text-red-500">{{ form.errors.requester_id }}</p>
          </div>

          <div class="md:col-span-2">
            <div class="grid w-full gap-6 items-stretch">
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
                    ref="picTriggerRef"
                    type="button"
                    class="flex w-full items-center justify-between rounded-2xl border border-slate-200 bg-white px-4 py-3 text-left text-sm font-semibold text-slate-700 shadow-sm transition hover:border-emerald-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
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
                      ref="picDropdownRef"
                      v-if="picDropdownOpen"
                      class="absolute left-0 right-0 z-[9999] w-full min-w-full max-w-full rounded-2xl border border-slate-200 bg-white p-4 shadow-2xl box-border dark:border-slate-700 dark:bg-slate-900"
                      :class="picDropdownPlacement === 'top' ? 'bottom-full mb-2' : 'top-full mt-2'"
                      :style="picDropdownStyle"
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
                      <div class="mt-3">
                        <input
                          ref="picSearchInputRef"
                          v-model="picSearchQuery"
                          type="text"
                          placeholder="Cari nama user atau unit"
                          class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-200 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-emerald-500 dark:focus:ring-emerald-500/40"
                        />
                      </div>
                      <div class="mt-3 space-y-2 overflow-y-auto pr-1" :style="{ maxHeight: `${picDropdownListMaxHeight}px` }">
                        <template v-if="activePicUnit || normalizedPicSearch">
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
                                <p class="text-xs text-slate-400">{{ option.email || '-' }} | {{ option.unit || unitPlaceholderLabel }}</p>
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
                          <p v-if="!activePicList.length" class="text-sm text-slate-400">
                            {{ normalizedPicSearch ? 'PIC tidak ditemukan untuk kata kunci tersebut.' : 'Belum ada PIC pada unit ini.' }}
                          </p>
                        </template>
                        <p v-else class="text-sm text-slate-400">Pilih unit terlebih dahulu atau gunakan pencarian untuk melihat PIC.</p>
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
            <div class="mt-3 grid gap-3">
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
          </div>
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
          <h2 class="text-sm font-semibold text-slate-600 dark:text-slate-300">Lampiran</h2>
          <p class="text-xs text-slate-400">Pilih ekstensi terlebih dahulu. Batas ukuran 10MB.</p>
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
            <button
              v-if="attachmentIds.length"
              type="button"
              class="text-blue-600 hover:underline dark:text-blue-400"
              @click="clearAttachments"
            >
              Bersihkan Lampiran
            </button>
          </div>
        </div>

        <p v-if="uploaderDisabled" class="mt-1 text-xs text-amber-600">Pilih jenis file terlebih dahulu sebelum mengunggah.</p>
        <p v-if="form.errors.attachments" class="mt-1 text-xs text-red-500">{{ form.errors.attachments }}</p>
        <p v-if="uploadError" class="mt-2 text-xs text-red-500">{{ uploadError }}</p>
      </section>

      <div class="flex flex-wrap items-center justify-end gap-3">
        <button
          type="button"
          class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-4 py-2 text-sm text-slate-600 transition hover:bg-slate-100 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800"
          @click="resetForm"
        >
          Reset
        </button>
        <button
          type="submit"
          class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 via-indigo-500 to-purple-500 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/30 transition hover:shadow-xl disabled:cursor-not-allowed disabled:opacity-70"
          :disabled="form.processing"
        >
          <span class="material-icons text-base" v-if="form.processing">hourglass_top</span>
          <span v-else class="material-icons text-base">check_circle</span>
          Simpan Ticket
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch, watchEffect } from 'vue';
import resolveRoute from '@/utils/resolveRoute';
import DatePickerFlatpickr from '@/Components/DatePickerFlatpickr.vue';
import FileUploaderPond from '@/Components/FileUploaderPond.vue';
import RichTextQuill from '@/Components/RichTextQuill.vue';
import FancySelect from '@/Components/FancySelect.vue';
import { attachmentFilterOptions } from '@/utils/attachmentFilters';

const props = defineProps({
  defaults: { type: Object, default: () => ({}) },
  statusOptions: { type: Array, default: () => [] },
  priorityOptions: { type: Array, default: () => [] },
  typeOptions: { type: Array, default: () => [] },
  slaOptions: { type: Array, default: () => [] },
  userOptions: { type: Array, default: () => [] },
  meta: { type: Object, default: () => ({}) },
});

const page = usePage();
const flash = computed(() => page.props.flash || {});
const submitError = ref('');

const form = useForm({
  title: '',
  description: '',
  reason: '',
  letter_no: '',
  priority: props.defaults.priority ?? null,
  type: props.defaults.type ?? 'task',
  status: props.defaults.status ?? 'new',
  sla: props.defaults.sla ?? null,
  start_at: props.defaults.start_at ?? props.defaults.due_date ?? null,
  due_date: props.defaults.due_date ?? null,
  due_at: props.defaults.due_at ?? null,
  finish_date: props.defaults.finish_date ?? null,
  finish_at: props.defaults.finish_at ?? props.defaults.finish_date ?? null,
  requester_id: props.defaults.requester_id ?? null,
  agent_id: null,
  assigned_id: null,
  assigned_user_ids: [],
  attachments: [],
});

const toDateOnly = value => {
  if (!value) return null;
  const raw = String(value).trim();
  if (!raw) return null;
  return raw.slice(0, 10);
};

const normalizeDateValue = value => {
  const normalized = toDateOnly(value);
  return normalized && /^\d{4}-\d{2}-\d{2}$/.test(normalized) ? normalized : null;
};

const isDateBefore = (left, right) => Boolean(left && right && left < right);

const formatSla = sla => {
  if (!sla) return '';
  return sla.replace(/_/g, ' ').replace(/\b\w/g, char => char.toUpperCase());
};

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
  activeAttachmentFilter.value ? `Hanya ${activeAttachmentFilter.value.label}` : 'Pilih jenis file untuk mengaktifkan unggahan.'
));
const uploaderRef = ref(null);

const userOptions = computed(() => props.userOptions ?? []);
const statusLocked = computed(() => Boolean(props.meta?.lockStatus));
const canSelectRequester = computed(() => Boolean(props.meta?.canSelectRequester));
const requesterLabel = computed(() => props.meta?.requesterLabel ?? '');
const minTicketDate = computed(() => props.meta?.minTicketDate ?? new Date().toISOString().slice(0, 10));
const slaSelectOptions = computed(() => (
  (Array.isArray(props.slaOptions) ? props.slaOptions : []).map(option => ({
    value: option,
    label: formatSla(option),
  }))
));

watchEffect(() => {
  form.type = 'task';
  if (statusLocked.value) {
    form.status = props.defaults?.status ?? 'new';
  }
});
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

const requesterCardRef = ref(null);
const picCardRef = ref(null);
const requesterTriggerRef = ref(null);
const picTriggerRef = ref(null);
const requesterDropdownRef = ref(null);
const picDropdownRef = ref(null);
const picSearchInputRef = ref(null);
const requesterDropdownOpen = ref(false);
const picDropdownOpen = ref(false);
const activeRequesterUnit = ref(null);
const activePicUnit = ref('');
const picSelections = ref([]);
const picPrimary = ref(null);
const picSearchQuery = ref('');
const picDropdownPlacement = ref('bottom');
const picDropdownStyle = ref({});
const picDropdownListMaxHeight = ref(320);

const requesterOptions = computed(() => userOptions.value.map(option => ({
  ...option,
  label: formatUserOptionLabel(option),
})));
const selectedRequester = computed(() => requesterOptions.value.find(option => option.id === form.requester_id) || null);

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

const activeRequesterList = computed(() => usersByUnit.value[activeRequesterUnit.value] ?? []);
const normalizedPicSearch = computed(() => picSearchQuery.value.trim().toLowerCase());
const activePicList = computed(() => {
  const query = normalizedPicSearch.value;
  if (query) {
    return userOptions.value.filter(option => {
      const name = String(option?.label ?? '').toLowerCase();
      const unit = String(option?.unit ?? unitPlaceholderLabel).toLowerCase();
      return name.includes(query) || unit.includes(query);
    });
  }
  return activePicUnit.value ? (usersByUnit.value[activePicUnit.value] ?? []) : [];
});

const selectedPicDetails = computed(() => {
  const lookup = userLookup.value;
  return picSelections.value
    .map(id => lookup.get(String(normalizeSelectionId(id))))
    .filter(Boolean);
});

const isPicSelected = id => picSelections.value
  .map(normalizeSelectionId)
  .includes(normalizeSelectionId(id));

const syncAssignedUsers = () => {
  form.assigned_user_ids = Array.from(new Set(
    picSelections.value.map(normalizeSelectionId)
  )).filter(value => value !== null && value !== '');
};

watch(allUnits, units => {
  if (!units.length) {
    activePicUnit.value = '';
    activeRequesterUnit.value = null;
    return;
  }
  if (activePicUnit.value && !units.includes(activePicUnit.value)) {
    activePicUnit.value = units[0];
  }
  if (activeRequesterUnit.value && !units.includes(activeRequesterUnit.value)) {
    activeRequesterUnit.value = null;
  }
}, { immediate: true });

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

watch(picPrimary, value => {
  const normalized = normalizeSelectionId(value);
  if (normalized && !picSelections.value.map(normalizeSelectionId).includes(normalized)) {
    return;
  }
  form.assigned_id = normalized ?? null;
}, { immediate: true });

watch(() => form.start_at, value => {
  const normalized = normalizeDateValue(value);
  if (normalized !== value) {
    form.start_at = normalized;
    return;
  }
  form.due_date = normalized;
  if (normalized && form.finish_at && isDateBefore(form.finish_at, normalized)) {
    form.finish_at = null;
    form.finish_date = null;
  }
}, { immediate: true });

watch(() => form.finish_at, value => {
  const normalized = normalizeDateValue(value);
  if (normalized !== value) {
    form.finish_at = normalized;
    return;
  }
  form.finish_date = normalized;
}, { immediate: true });

watch(requesterDropdownOpen, open => {
  if (open) {
    nextTick(() => syncDropdownWidth(requesterTriggerRef, requesterDropdownRef));
  }
});

watch(picDropdownOpen, open => {
  if (open) {
    nextTick(() => positionPicDropdown(true));
  }
});

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

function removePicSelection(rawId) {
  const id = normalizeSelectionId(rawId);
  if (id === null) return;
  picSelections.value = picSelections.value.filter(value => normalizeSelectionId(value) !== id);
  if (normalizeSelectionId(picPrimary.value) === id) {
    picPrimary.value = picSelections.value[0] ?? null;
  }
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
const syncDropdownWidth = (triggerRef, dropdownRef) => {
  const trigger = triggerRef?.value;
  const dropdown = dropdownRef?.value;
  if (!trigger || !dropdown) return;
  const { width } = trigger.getBoundingClientRect();
  dropdown.style.width = `${width}px`;
  dropdown.style.minWidth = `${width}px`;
  dropdown.style.maxWidth = `${width}px`;
};

function positionPicDropdown(focusSearch = false) {
  syncDropdownWidth(picTriggerRef, picDropdownRef);
  const trigger = picTriggerRef?.value;
  if (!trigger) return;
  const rect = trigger.getBoundingClientRect();
  const viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
  const spaceBelow = Math.max(viewportHeight - rect.bottom - 16, 160);
  const spaceAbove = Math.max(rect.top - 16, 160);
  const openUpward = spaceBelow < 340 && spaceAbove > spaceBelow;
  picDropdownPlacement.value = openUpward ? 'top' : 'bottom';
  picDropdownStyle.value = {
    width: `${rect.width}px`,
    minWidth: `${rect.width}px`,
    maxWidth: `${rect.width}px`,
  };
  const availableSpace = openUpward ? spaceAbove : spaceBelow;
  picDropdownListMaxHeight.value = Math.max(Math.min(availableSpace - 120, 360), 160);
  if (focusSearch) {
    picSearchInputRef.value?.focus();
  }
}

function togglePicDropdown() {
  picDropdownOpen.value = !picDropdownOpen.value;
  if (picDropdownOpen.value) {
    requesterDropdownOpen.value = false;
    picSearchQuery.value = '';
    if (!activePicUnit.value && allUnits.value.length) {
      activePicUnit.value = allUnits.value[0];
    }
    nextTick(() => positionPicDropdown(true));
  }
}

function toggleRequesterDropdown() {
  requesterDropdownOpen.value = !requesterDropdownOpen.value;
  if (requesterDropdownOpen.value) {
    picDropdownOpen.value = false;
    if (!activeRequesterUnit.value && allUnits.value.length) {
      activeRequesterUnit.value = allUnits.value[0];
    }
    nextTick(() => syncDropdownWidth(requesterTriggerRef, requesterDropdownRef));
  }
}

function handleOutsideClick(event) {
  const target = event.target;
  if (picDropdownOpen.value && picCardRef.value && !picCardRef.value.contains(target)) {
    picDropdownOpen.value = false;
  }
  if (requesterDropdownOpen.value && requesterCardRef.value && !requesterCardRef.value.contains(target)) {
    requesterDropdownOpen.value = false;
  }
}

const handleResize = () => {
  if (picDropdownOpen.value) {
    positionPicDropdown();
  }
  if (requesterDropdownOpen.value) {
    syncDropdownWidth(requesterTriggerRef, requesterDropdownRef);
  }
};

onMounted(() => {
  window.addEventListener('click', handleOutsideClick, true);
  window.addEventListener('resize', handleResize);
  window.addEventListener('scroll', handleResize, true);
});

onBeforeUnmount(() => {
  window.removeEventListener('click', handleOutsideClick, true);
  window.removeEventListener('resize', handleResize);
  window.removeEventListener('scroll', handleResize, true);
});

const baseDateConfig = computed(() => ({
  enableTime: false,
  altInput: true,
  altFormat: 'd M Y',
  dateFormat: 'Y-m-d',
  minDate: minTicketDate.value,
}));

const startDateConfig = computed(() => ({
  ...baseDateConfig.value,
}));

const finishDateConfig = computed(() => ({
  ...baseDateConfig.value,
  minDate: form.start_at || minTicketDate.value,
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

function resetForm() {
  form.reset();
  form.priority = props.defaults.priority ?? null;
  form.type = 'task';
  form.status = props.defaults.status ?? 'new';
  form.start_at = props.defaults.start_at ?? props.defaults.due_date ?? null;
  form.due_date = props.defaults.due_date ?? null;
  form.due_at = props.defaults.due_at ?? null;
  form.finish_date = props.defaults.finish_date ?? null;
  form.finish_at = props.defaults.finish_at ?? props.defaults.finish_date ?? null;
  form.agent_id = null;
  form.assigned_id = null;
  form.assigned_user_ids = [];
  attachmentIds.value = [];
  uploadError.value = '';
  submitError.value = '';
  selectedAttachmentFilter.value = '';
  uploaderRef.value?.reset();
  picSelections.value = [];
  picPrimary.value = null;
  picSearchQuery.value = '';
  requesterDropdownOpen.value = false;
  picDropdownOpen.value = false;
  activePicUnit.value = '';
  activeRequesterUnit.value = '';
  if (typeof form.clearErrors === 'function') {
    form.clearErrors();
  }
}

const normalizeNumber = value => {
  if (value === null || typeof value === 'undefined' || value === '') return null;
  const numeric = Number(value);
  return Number.isFinite(numeric) ? numeric : null;
};

function validateForm() {
  if (typeof form.clearErrors === 'function') {
    form.clearErrors('priority', 'due_date', 'finish_date', 'start_at', 'finish_at');
  }

  const errors = {};
  const startDate = normalizeDateValue(form.start_at);
  const finishDate = normalizeDateValue(form.finish_at);

  if (!form.priority) {
    errors.priority = 'Pilih prioritas ticket.';
  }
  if (startDate && isDateBefore(startDate, minTicketDate.value)) {
    errors.due_date = 'Tanggal mulai tidak boleh sebelum hari ini.';
  }
  if (finishDate && isDateBefore(finishDate, minTicketDate.value)) {
    errors.finish_date = 'Tanggal selesai tidak boleh sebelum hari ini.';
  }
  if (startDate && finishDate && isDateBefore(finishDate, startDate)) {
    errors.finish_date = 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.';
  }

  if (Object.keys(errors).length) {
    if (typeof form.setError === 'function') {
      form.setError(errors);
    }
    submitError.value = 'Gagal membuat ticket. Periksa kembali input yang berwarna merah.';
    return false;
  }

  return true;
}

function prepareSubmission() {
  ['requester_id', 'agent_id', 'assigned_id'].forEach(field => {
    form[field] = normalizeNumber(form[field]);
  });

  form.type = 'task';
  form.agent_id = null;
  form.start_at = normalizeDateValue(form.start_at);
  form.finish_at = normalizeDateValue(form.finish_at);
  form.due_date = form.start_at || null;
  form.finish_date = form.finish_at || null;
  form.due_at = form.start_at || null;
  form.finish_at = form.finish_date || null;

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
  submitError.value = '';
  prepareSubmission();
  if (!validateForm()) {
    return;
  }
  form.post(resolveRoute('tickets.store'), {
    forceFormData: true,
    preserveScroll: false,
    preserveState: false,
    onError: () => {
      submitError.value = 'Gagal membuat ticket. Periksa kembali input yang berwarna merah.';
      try {
        window.scrollTo({ top: 0, behavior: 'smooth' });
      } catch (error) {
      }
    },
    onSuccess: () => {
      resetForm();
      try {
        window.scrollTo({ top: 0, behavior: 'smooth' });
      } catch (error) {
      }
    },
  });
}

const formatUserOptionLabel = option => {
  if (!option) return '';
  const parts = [];
  if (option.unit) {
    parts.push(`[${option.unit}]`);
  }
  if (option.label) {
    parts.push(option.label);
  }
  if (option.agent_label && option.agent_label !== '-') {
    parts.push(`(${option.agent_label})`);
  }
  const base = parts.join(' ').replace(/\s+/g, ' ').trim();
  if (!base) {
    return option.email ?? '';
  }
  return option.email ? `${base} | ${option.email}` : base;
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
