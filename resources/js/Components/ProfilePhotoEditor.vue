<template>
  <dialog ref="dialog" class="photo-dialog" :aria-label="tr('Atur foto profil', 'Adjust profile photo')" @cancel.prevent="close" @click="backdrop">
    <div class="editor-panel">
      <div class="editor-heading"><div><h2>{{ tr('Atur foto profil', 'Adjust profile photo') }}</h2><p>{{ tr('Geser foto dan atur zoom untuk memilih bagian yang dipotong.', 'Drag the photo and adjust zoom to choose your crop.') }}</p></div><button type="button" class="close" :aria-label="tr('Tutup', 'Close')" :disabled="busy" @click="close">×</button></div>
      <div v-if="!image && !error" role="status" class="message">{{ tr('Memuat foto…', 'Loading photo…') }}</div>
      <div v-if="image" ref="viewport" class="crop-area" tabindex="0" role="group" :aria-label="tr('Area potong. Gunakan tombol panah untuk menggeser foto.', 'Crop area. Use arrow keys to move the photo.')" @pointerdown="startDrag" @pointermove="drag" @pointerup="endDrag" @pointercancel="endDrag" @lostpointercapture="endDrag" @keydown="moveWithKeys" @wheel.prevent="wheel">
        <img :src="src" alt="" draggable="false" :style="imageStyle" />
        <div class="crop-guide" aria-hidden="true"></div>
      </div>
      <template v-if="image">
        <div class="zoom-row"><label for="photo-zoom">Zoom</label><output for="photo-zoom">{{ Math.round(zoom * 100) }}%</output></div>
        <input id="photo-zoom" :value="zoom" type="range" min="1" max="4" step="0.01" :disabled="busy" @input="setZoom(Number($event.target.value))" />
        <p class="hint">{{ tr('Area lingkaran menunjukkan tampilan avatar. Foto disimpan berbentuk persegi.', 'The circle previews your avatar. The photo is saved as a square.') }}</p>
      </template>
      <p v-if="error" role="alert" class="error">{{ error }}</p>
      <div class="actions"><button type="button" :disabled="busy || !image" @click="reset">{{ tr('Atur ulang', 'Reset') }}</button><button type="button" :disabled="busy" @click="close">{{ tr('Batal', 'Cancel') }}</button><button type="button" class="apply" :disabled="busy || !image" @click="apply">{{ busy ? tr('Memproses…', 'Processing…') : tr('Gunakan foto', 'Use photo') }}</button></div>
    </div>
  </dialog>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
const props = defineProps({ src: { type: String, default: '' } })
const emit = defineEmits(['close', 'apply'])
const page = usePage()
const tr = (id, en) => page.props.locale === 'en' ? en : id
const dialog = ref(null), viewport = ref(null), image = ref(null), zoom = ref(1), x = ref(0), y = ref(0), busy = ref(false), error = ref('')
let dragState = null, generation = 0
const scale = computed(() => image.value ? Math.max(300 / image.value.naturalWidth, 300 / image.value.naturalHeight) * zoom.value : 1)
const imageStyle = computed(() => ({
  width: `${image.value.naturalWidth * scale.value / 3}%`, height: `${image.value.naturalHeight * scale.value / 3}%`,
  left: `${50 + x.value / 3}%`, top: `${50 + y.value / 3}%`,
}))
function clampPosition() {
  if (!image.value) return
  const limitX = Math.max(0, (image.value.naturalWidth * scale.value - 300) / 2)
  const limitY = Math.max(0, (image.value.naturalHeight * scale.value - 300) / 2)
  x.value = Math.max(-limitX, Math.min(limitX, x.value)); y.value = Math.max(-limitY, Math.min(limitY, y.value))
}
function setZoom(value) { if (busy.value) return; zoom.value = Math.max(1, Math.min(4, value)); clampPosition() }
function reset() { zoom.value = 1; x.value = 0; y.value = 0; error.value = '' }
function startDrag(event) {
  if (busy.value || (event.pointerType === 'mouse' && event.button !== 0)) return
  event.preventDefault(); viewport.value.focus(); viewport.value.setPointerCapture(event.pointerId)
  dragState = { id: event.pointerId, clientX: event.clientX, clientY: event.clientY, x: x.value, y: y.value }
}
function drag(event) {
  if (!dragState || dragState.id !== event.pointerId || busy.value) return
  const ratio = 300 / viewport.value.getBoundingClientRect().width
  x.value = dragState.x + (event.clientX - dragState.clientX) * ratio; y.value = dragState.y + (event.clientY - dragState.clientY) * ratio; clampPosition()
}
function endDrag() { dragState = null }
function moveWithKeys(event) {
  const directions = { ArrowLeft: [-1, 0], ArrowRight: [1, 0], ArrowUp: [0, -1], ArrowDown: [0, 1] }
  if (!directions[event.key] || busy.value) return
  event.preventDefault(); const step = event.shiftKey ? 20 : 5
  x.value += directions[event.key][0] * step; y.value += directions[event.key][1] * step; clampPosition()
}
function wheel(event) { setZoom(zoom.value + (event.deltaY < 0 ? .05 : -.05)) }
function close() { if (!busy.value) { generation++; dialog.value?.close(); emit('close') } }
function backdrop(event) { if (event.target === dialog.value) { const rect = dialog.value.getBoundingClientRect(); if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) close() } }
async function apply() {
  if (!image.value || busy.value) return
  busy.value = true; error.value = ''
  try {
    const canvas = document.createElement('canvas'); canvas.width = 512; canvas.height = 512
    const context = canvas.getContext('2d'); const side = 300 / scale.value
    context.fillStyle = '#ffffff'; context.fillRect(0, 0, 512, 512)
    context.drawImage(image.value, (image.value.naturalWidth - side) / 2 - x.value / scale.value, (image.value.naturalHeight - side) / 2 - y.value / scale.value, side, side, 0, 0, 512, 512)
    const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', .92))
    if (!blob) throw new Error('encode')
    emit('apply', new File([blob], 'profile-photo.jpg', { type: 'image/jpeg' }))
    busy.value = false; close()
  } catch { error.value = tr('Foto tidak dapat diproses. Silakan pilih foto lain.', 'Unable to process this photo. Please choose another.'); busy.value = false }
}
watch(() => props.src, async src => {
  const current = ++generation; image.value = null; error.value = ''; reset()
  if (!src) { dialog.value?.close(); return }
  await nextTick(); if (current !== generation) return
  if (!dialog.value.open) dialog.value.showModal()
  const loaded = new Image()
  loaded.onload = () => { if (current !== generation) return; if (loaded.naturalWidth > 6000 || loaded.naturalHeight > 6000) { error.value = tr('Ukuran foto maksimal 6000 × 6000 piksel.', 'Maximum image dimensions are 6000 × 6000 pixels.'); return } image.value = loaded }
  loaded.onerror = () => { if (current === generation) error.value = tr('Foto gagal dimuat. Silakan pilih ulang.', 'Unable to load the photo. Please select it again.') }
  loaded.src = src
})
onBeforeUnmount(() => { generation++; dialog.value?.close() })
</script>

<style scoped>
.photo-dialog{width:min(460px,calc(100vw - 32px));max-height:calc(100dvh - 32px);padding:0;border:1px solid #e2e8f0;border-radius:24px;color:#0f172a;background:#fff;box-shadow:0 24px 100px #02061755}.photo-dialog::backdrop{background:#020617aa;backdrop-filter:blur(5px)}.editor-panel{padding:24px}.editor-heading{display:flex;gap:16px;justify-content:space-between;margin-bottom:20px}h2{font-size:20px;font-weight:700}p{margin-top:6px;font-size:13px;color:#64748b}.close{font-size:26px;align-self:flex-start;padding:0 5px}.crop-area{width:min(100%,300px);aspect-ratio:1;margin:auto;position:relative;overflow:hidden;background:#e2e8f0;touch-action:none;cursor:grab;border-radius:12px}.crop-area:active{cursor:grabbing}.crop-area:focus-visible{outline:3px solid var(--user-accent-color,#4338ca);outline-offset:4px}.crop-area img{position:absolute;max-width:none;transform:translate(-50%,-50%);pointer-events:none;user-select:none}.crop-guide{position:absolute;inset:0;border-radius:50%;box-shadow:0 0 0 100px #02061777;border:2px solid #ffffffbb;pointer-events:none}.zoom-row{display:flex;justify-content:space-between;font-size:13px;font-weight:600;margin:20px 0 8px}input[type=range]{width:100%;accent-color:var(--user-accent-color,#4338ca)}.hint{font-size:12px}.actions{display:flex;flex-wrap:wrap;justify-content:flex-end;gap:8px;margin-top:20px}.actions button{border:1px solid #cbd5e1;border-radius:10px;padding:9px 12px;font-size:13px;font-weight:600}.actions .apply{background:var(--user-accent-color,#4338ca);color:white;border-color:transparent}.error{color:#e11d48}.message{padding:40px;text-align:center}button:disabled{opacity:.5;cursor:wait}.dark .photo-dialog{background:#0f172a;color:#f1f5f9;border-color:#334155}.dark p{color:#94a3b8}
</style>
