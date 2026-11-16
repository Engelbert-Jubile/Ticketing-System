<template>
  <dialog ref="dialog" class="attachment-preview" :class="{ 'attachment-preview--expanded': expanded }" :aria-label="tr('Preview lampiran', 'Attachment preview')" @cancel.prevent="close" @click="backdrop" @close="nativeClosed">
    <div class="attachment-preview__panel">
      <header class="attachment-preview__header">
        <span class="attachment-preview__file-icon material-icons" aria-hidden="true">{{ kind === 'xlsx' ? 'table_chart' : kind === 'image' ? 'image' : 'description' }}</span>
        <div class="attachment-preview__heading">
          <p>{{ tr('Preview lampiran', 'Attachment preview') }}</p>
          <h2 :title="name">{{ name || tr('Memuat lampiran…', 'Loading attachment…') }}</h2>
        </div>
        <a v-if="attachment" :href="attachment.downloadUrl" class="attachment-preview__download" :aria-label="tr('Unduh file asli', 'Download original file')">
          <span class="material-icons" aria-hidden="true">download</span><span>{{ tr('Unduh', 'Download') }}</span>
        </a>
        <button type="button" class="attachment-preview__icon-button attachment-preview__expand" :aria-label="expanded ? tr('Perkecil', 'Restore size') : tr('Perbesar', 'Expand')" :title="expanded ? tr('Perkecil', 'Restore size') : tr('Perbesar', 'Expand')" @click="expanded = !expanded"><span class="material-icons" aria-hidden="true">{{ expanded ? 'close_fullscreen' : 'open_in_full' }}</span></button>
        <button type="button" autofocus class="attachment-preview__icon-button" :aria-label="tr('Tutup preview', 'Close preview')" :title="tr('Tutup preview (Esc)', 'Close preview (Esc)')" @click="close"><span class="material-icons" aria-hidden="true">close</span></button>
      </header>

      <div v-if="kind === 'image' && status === 'ready'" class="attachment-preview__toolbar">
        <span>{{ tr('Gambar', 'Image') }}</span>
        <div><button type="button" :disabled="zoom <= .5" :aria-label="tr('Perkecil gambar', 'Zoom out')" @click="zoom = Math.max(.5, zoom - .25)">−</button><button type="button" :aria-label="tr('Atur ulang zoom', 'Reset zoom')" @click="zoom = 1">{{ Math.round(zoom * 100) }}%</button><button type="button" :disabled="zoom >= 3" :aria-label="tr('Perbesar gambar', 'Zoom in')" @click="zoom = Math.min(3, zoom + .25)">+</button></div>
      </div>
      <div v-if="workbook" class="attachment-preview__sheets" role="tablist" :aria-label="tr('Sheet workbook', 'Workbook sheets')">
        <button v-for="(sheet, index) in workbook.sheets" :id="`attachment-sheet-${index}`" :key="sheet.path" type="button" role="tab" :aria-selected="activeSheet === index" aria-controls="attachment-sheet-content" :tabindex="activeSheet === index ? 0 : -1" @click="selectSheet(index)" @keydown="sheetKey($event, index)"><span class="material-icons" aria-hidden="true">grid_on</span>{{ sheet.name }}</button>
      </div>

      <main class="attachment-preview__content" :aria-busy="status === 'loading'">
        <div v-if="status === 'loading'" class="attachment-preview__message" role="status"><span class="attachment-preview__spinner" aria-hidden="true"></span><h3>{{ tr('Menyiapkan preview…', 'Preparing preview…') }}</h3><p>{{ tr('File akan ditampilkan di sini.', 'Your file will appear here.') }}</p></div>
        <div v-else-if="status === 'error' || status === 'unsupported'" class="attachment-preview__message" :role="status === 'error' ? 'alert' : 'status'">
          <span class="attachment-preview__empty-icon material-icons" aria-hidden="true">{{ status === 'error' ? 'error_outline' : 'insert_drive_file' }}</span>
          <h3>{{ status === 'error' ? tr('Preview belum tersedia', 'Preview unavailable') : tr('Format ini belum mendukung preview', 'Preview is not supported for this format') }}</h3>
          <p>{{ error || tr('Unduh file untuk membukanya dengan aplikasi yang sesuai.', 'Download this file to open it in a compatible application.') }}</p>
          <div class="attachment-preview__message-actions"><button v-if="status === 'error'" type="button" @click="load(attachment)">{{ tr('Coba lagi', 'Try again') }}</button><a :href="attachment?.downloadUrl">{{ tr('Unduh file', 'Download file') }}</a></div>
        </div>
        <div v-else-if="kind === 'image'" class="attachment-preview__image"><img :src="objectUrl" :alt="name" :style="{ width: `${zoom * 100}%` }" @error="renderError" /></div>
        <iframe v-else-if="kind === 'pdf'" :src="objectUrl" :title="name" class="attachment-preview__pdf"></iframe>
        <div v-else-if="kind === 'video' || kind === 'audio'" class="attachment-preview__media"><video v-if="kind === 'video'" :src="objectUrl" controls playsinline preload="metadata" @error="renderError"></video><audio v-else :src="objectUrl" controls preload="metadata" @error="renderError"></audio></div>
        <div v-else-if="kind === 'xlsx'" id="attachment-sheet-content" class="attachment-preview__table" role="tabpanel" :aria-labelledby="`attachment-sheet-${activeSheet}`" tabindex="0">
          <table v-if="table.rows.length"><thead><tr><th scope="col">#</th><th v-for="(_, index) in table.width" :key="index" scope="col">{{ columnLabel(index) }}</th></tr></thead><tbody><tr v-for="row in table.rows" :key="row.number"><th scope="row">{{ row.number }}</th><td v-for="(_, index) in table.width" :key="index">{{ row.values[index] ?? '' }}</td></tr></tbody></table>
          <div v-else class="attachment-preview__message">{{ tr('Sheet ini kosong.', 'This sheet is empty.') }}</div>
        </div>
        <pre v-else class="attachment-preview__text" :class="{ 'attachment-preview__text--document': kind !== 'text' }">{{ documentText || tr('File ini kosong.', 'This file is empty.') }}</pre>
      </main>

      <footer class="attachment-preview__footer"><span>{{ footerText }}</span><span class="attachment-preview__escape">Esc · {{ tr('Tutup', 'Close') }}</span></footer>
    </div>
  </dialog>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';
import { attachmentName, columnLabel, openOfficePreview, previewKind, readOfficeText, readPreviewResponse, readWorkbook, readWorksheet } from '../utils/attachmentPreview';

const props = defineProps({ state: { type: Object, required: true } });
const attachment = computed(() => props.state.attachment);
const tr = (id, en) => attachment.value?.locale?.startsWith('id') ? id : en;
const dialog = ref(null), name = ref(''), kind = ref(''), status = ref('loading'), error = ref('');
const objectUrl = ref(''), documentText = ref(''), zoom = ref(1), expanded = ref(false);
const workbook = ref(null), activeSheet = ref(0), table = ref({ rows: [], width: 0, truncated: false });
let controller, revision = 0, office, trigger, previousOverflow;
const footerText = computed(() => table.value.truncated
  ? tr('Preview dibatasi hingga 500 baris dan 50 kolom. Unduh untuk data lengkap.', 'Preview is limited to 500 rows and 50 columns. Download for all data.')
  : ['xlsx', 'docx', 'pptx'].includes(kind.value) && status.value === 'ready'
    ? tr('Preview isi sederhana; format, grafik, dan formula dapat berbeda. Unduh untuk tampilan asli.', 'Simplified content preview; formatting, charts and formulas may differ. Download for the original.')
    : tr('File asli tetap tersedia melalui Unduh.', 'The original file is available to download.'));

function release() {
  revision++;
  controller?.abort();
  if (objectUrl.value) URL.revokeObjectURL(objectUrl.value);
  objectUrl.value = ''; office = null; workbook.value = null;
  documentText.value = ''; table.value = { rows: [], width: 0, truncated: false };
}
function close() { props.state.attachment = null; }
// Native close events are queued; an older event must not close a newly opened file.
function nativeClosed() { if (!dialog.value?.open) close(); }
function backdrop(event) {
  if (event.target !== dialog.value) return;
  const bounds = dialog.value.getBoundingClientRect();
  if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) close();
}
function showError(cause) {
  status.value = 'error';
  error.value = cause?.message === 'too-large'
    ? tr('File terlalu besar untuk preview. Batas preview 20 MB; unduh file untuk melihat seluruh isinya.', 'This file is too large to preview. The preview limit is 20 MB; download the file to view its full contents.')
    : cause?.message === '403' ? tr('Anda tidak memiliki akses ke lampiran ini.', 'You do not have access to this attachment.')
      : cause?.message === '404' ? tr('File lampiran tidak ditemukan.', 'This attachment could not be found.')
        : tr('File tidak dapat ditampilkan. Silakan coba lagi atau unduh file aslinya.', 'Unable to display this file. Try again or download the original file.');
}
function renderError() { showError(); }
async function load(item) {
  if (!item) return;
  release();
  const current = revision;
  controller = new AbortController();
  const { signal } = controller;
  status.value = 'loading'; error.value = ''; kind.value = ''; name.value = ''; zoom.value = 1; activeSheet.value = 0;
  try {
    const response = await fetch(item.viewUrl, { signal, credentials: 'same-origin', headers: { Accept: '*/*' } });
    if (!response.ok) throw new Error(String(response.status));
    if (response.redirected) throw new Error('session-expired');
    name.value = attachmentName(response.headers.get('content-disposition') || '') || tr('Lampiran', 'Attachment');
    const mime = (response.headers.get('content-type') || '').split(';')[0];
    kind.value = previewKind(name.value, mime);
    if (kind.value === 'unsupported') { await response.body?.cancel(); status.value = 'unsupported'; return; }
    const blob = await readPreviewResponse(response, signal);
    if (current !== revision) return;
    if (['xlsx', 'docx', 'pptx'].includes(kind.value)) {
      const loadedOffice = await openOfficePreview(blob);
      if (current !== revision) return;
      office = loadedOffice;
      if (kind.value === 'xlsx') {
        const loadedWorkbook = await readWorkbook(office);
        if (current !== revision) return;
        workbook.value = loadedWorkbook;
        const loadedTable = await readWorksheet(office, loadedWorkbook, loadedWorkbook.sheets[0]);
        if (current !== revision) return;
        table.value = loadedTable;
      } else {
        const content = await readOfficeText(office, kind.value);
        if (current !== revision) return;
        documentText.value = content;
      }
    } else if (kind.value === 'text') {
      const content = await blob.text();
      if (current !== revision) return;
      documentText.value = content;
    } else {
      let safeMime = mime;
      if (kind.value === 'pdf') {
        if (!(await blob.slice(0, 1024).text()).includes('%PDF-')) throw new Error('invalid-file');
        safeMime = 'application/pdf';
      }
      if (current !== revision) return;
      objectUrl.value = URL.createObjectURL(new Blob([blob], { type: safeMime }));
    }
    if (current === revision) status.value = 'ready';
  } catch (cause) {
    if (current === revision && cause.name !== 'AbortError') { controller.abort(); showError(cause); }
  }
}
async function selectSheet(index) {
  if (!workbook.value || !office) return;
  const current = ++revision;
  activeSheet.value = index; status.value = 'loading';
  try {
    const result = await readWorksheet(office, workbook.value, workbook.value.sheets[index]);
    if (current !== revision) return;
    table.value = result; status.value = 'ready';
  } catch (cause) { if (current === revision) showError(cause); }
}
function sheetKey(event, index) {
  const count = workbook.value.sheets.length;
  const target = event.key === 'ArrowRight' ? (index + 1) % count : event.key === 'ArrowLeft' ? (index + count - 1) % count : event.key === 'Home' ? 0 : event.key === 'End' ? count - 1 : null;
  if (target === null) return;
  event.preventDefault(); selectSheet(target);
  document.getElementById(`attachment-sheet-${target}`)?.focus();
}
watch(attachment, async item => {
  if (!item) {
    release(); dialog.value?.close();
    if (previousOverflow !== undefined) document.documentElement.style.overflow = previousOverflow;
    previousOverflow = undefined;
    if (trigger?.isConnected) trigger.focus({ preventScroll: true });
    trigger = null;
    return;
  }
  trigger = item.trigger;
  await nextTick();
  if (item !== attachment.value) return;
  if (!dialog.value.open) {
    previousOverflow = document.documentElement.style.overflow;
    document.documentElement.style.overflow = 'hidden';
    expanded.value = false;
    dialog.value.showModal();
  }
  void load(item);
}, { immediate: true });
onBeforeUnmount(() => { release(); dialog.value?.close(); if (previousOverflow !== undefined) document.documentElement.style.overflow = previousOverflow; });
</script>
