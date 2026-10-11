import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { translateUi as t } from '../i18n'

export function useFormDraft(form, namespace, fields, enabled = true) {
  const page = usePage()
  const key = `tickora:draft:v1:${page.props.auth?.user?.id}:${namespace}`
  const available = ref(false)
  const message = ref('')
  const snapshot = () => Object.fromEntries(fields.map(field => [field, form[field]]))
  let baseline = JSON.stringify(snapshot())
  let candidate = null
  let timer = null
  let removeBefore = null
  let suspended = false
  let saveFailed = false
  const save = () => {
    if (!enabled || suspended || available.value) return
    const data = snapshot()
    if (JSON.stringify(data) === baseline) return
    try {
      localStorage.setItem(key, JSON.stringify({ version: 1, savedAt: Date.now(), data }))
      saveFailed = false
      message.value = t('Draft tersimpan di perangkat ini (24 jam).')
    } catch (_) { saveFailed = true; message.value = t('Draft tidak dapat disimpan. Jangan tutup halaman sebelum menyimpan formulir.') }
  }
  const clear = () => {
    clearTimeout(timer)
    suspended = true
    candidate = null
    available.value = false
    try { localStorage.removeItem(key) } catch (_) {}
    message.value = ''
    saveFailed = false
    nextTick(() => { baseline = JSON.stringify(snapshot()); suspended = false })
  }
  const restore = () => {
    if (!candidate) return
    for (const field of fields) {
      if (Object.prototype.hasOwnProperty.call(candidate.data, field)) form[field] = candidate.data[field]
    }
    available.value = false
    candidate = null
    message.value = t('Draft dipulihkan. Periksa kembali tanggal, pilih penugasan dan unggah lampiran.')
  }
  const beforeUnload = event => {
    save()
    if (saveFailed && JSON.stringify(snapshot()) !== baseline) {
      event.preventDefault()
      event.returnValue = ''
    }
  }
  watch(snapshot, () => { clearTimeout(timer); timer = setTimeout(save, 700) }, { deep: true })
  onMounted(() => {
    if (!enabled) return
    try {
      const stored = JSON.parse(localStorage.getItem(key) || 'null')
      if (stored?.version === 1 && stored.data && Date.now() - stored.savedAt < 86400000) {
        candidate = stored
        available.value = true
      } else localStorage.removeItem(key)
    } catch (_) { message.value = t('Penyimpanan draft tidak tersedia.') }
    window.addEventListener('beforeunload', beforeUnload)
    removeBefore = router.on('before', save)
  })
  onBeforeUnmount(() => {
    clearTimeout(timer)
    save()
    window.removeEventListener('beforeunload', beforeUnload)
    removeBefore?.()
  })
  return { available, message, restore, clear }
}
