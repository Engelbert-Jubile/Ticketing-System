<template>
  <div class="mx-auto max-w-5xl space-y-6">
    <Head title="Workflows" />
    <Transition name="fade"><div v-if="notice" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700 dark:border-rose-400/30 dark:bg-rose-400/10 dark:text-rose-200">{{ notice }}</div></Transition>
    <header><Link :href="resolveRoute('workflows.index')" class="text-sm font-semibold text-blue-600">← Kembali</Link><h1 class="mt-2 text-3xl font-bold text-slate-900 dark:text-white">{{ editing ? 'Edit Workflow' : 'Buat Workflow' }}</h1><p class="text-sm text-slate-500">Workflow hanya berlaku untuk Ticket dan Task.</p></header>
    <form class="space-y-6" @submit.prevent="submit">
      <section class="grid gap-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm md:grid-cols-2 dark:border-slate-700 dark:bg-slate-900">
        <label class="field">Nama workflow<input v-model="form.name" required maxlength="120" class="input" /><small v-if="form.errors.name" class="error">{{ form.errors.name }}</small></label>
        <label class="field">Kode<input v-model="form.code" required pattern="[A-Z0-9_-]+" maxlength="50" class="input uppercase" @input="form.code = form.code.toUpperCase().replace(/[^A-Z0-9_-]/g, '')" /><small v-if="form.errors.code" class="error">{{ form.errors.code }}</small></label>
        <label class="field">Tipe<WorkflowSelect v-model="form.entity_type" :options="entityOptions" aria-label="Tipe workflow" /></label>
        <label class="flex items-center gap-3 pt-7"><input v-model="form.is_active" type="checkbox" class="h-5 w-5 rounded border-slate-300 text-blue-600" /><span class="text-sm font-semibold text-slate-700 dark:text-slate-200">Workflow aktif</span></label>
        <label class="field md:col-span-2">Deskripsi<textarea v-model="form.description" rows="3" maxlength="2000" class="input"></textarea></label>
      </section>

      <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
        <div class="flex items-center justify-between"><div><h2 class="text-lg font-bold dark:text-white">Kondisi pemicu</h2><p class="text-sm text-slate-500">Semua kondisi harus terpenuhi.</p></div><button type="button" class="btn-secondary" @click="addCondition">+ Kondisi</button></div>
        <div v-if="!form.trigger_conditions.length" class="mt-4 rounded-xl bg-slate-50 p-4 text-sm text-slate-500 dark:bg-slate-800">Tanpa kondisi: berlaku untuk semua {{ form.entity_type }}.</div>
        <div v-for="(condition, index) in form.trigger_conditions" :key="index" class="mt-3 grid gap-2 md:grid-cols-[1fr_1fr_2fr_auto]">
          <WorkflowSelect v-model="condition.field" :options="conditionFieldOptions" aria-label="Field kondisi" />
          <WorkflowSelect v-model="condition.operator" :options="operatorOptions" aria-label="Operator kondisi" />
          <input v-model="condition.value" required class="input" placeholder="Nilai pemicu" /><button type="button" class="px-3 text-rose-600" @click="form.trigger_conditions.splice(index, 1)">✕</button>
        </div>
      </section>

      <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
        <div class="flex items-center justify-between"><div><h2 class="text-lg font-bold dark:text-white">Tahapan workflow</h2><p class="text-sm text-slate-500">Minimal dua tahap, urut dari awal sampai selesai.</p></div><button type="button" class="btn-secondary" @click="addStage">+ Tahap</button></div>
        <small v-if="form.errors.stages" class="error">{{ form.errors.stages }}</small>
        <div class="mt-5 space-y-4">
          <article v-for="(stage, index) in form.stages" :key="stage.id ?? `new-stage-${index}`" class="relative rounded-2xl border border-slate-200 p-5 dark:border-slate-700">
            <div class="mb-4 flex items-center justify-between"><span class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-600 text-sm font-bold text-white">{{ index + 1 }}</span><div class="flex gap-2"><button type="button" :disabled="index === 0" @click="move(index, -1)">↑</button><button type="button" :disabled="index === form.stages.length - 1" @click="move(index, 1)">↓</button><button v-if="form.stages.length > 2" type="button" class="text-rose-600" @click="form.stages.splice(index, 1)">Hapus</button></div></div>
            <div class="grid gap-4 md:grid-cols-2">
              <label class="field">Nama tahap<input v-model="stage.name" required class="input" /></label>
              <label class="field">Status<WorkflowSelect :model-value="stage.status_key" :options="statusOptions" aria-label="Status tahap" @update:model-value="requestStatusChange(index, $event)" /><small v-if="stageError(index, 'status_key')" class="error">{{ stageError(index, 'status_key') }}</small></label>
              <label class="field">Role PIC<WorkflowSelect v-model="stage.responsible_role" :options="roleOptions" placeholder="Tidak ditentukan" aria-label="Role PIC" /></label>
              <label class="field">PIC spesifik<WorkflowSelect v-model="stage.responsible_user_id" :options="userOptions" placeholder="Tidak ditentukan" aria-label="PIC spesifik" /></label>
              <label class="field">Aksi ke tahap berikutnya<input v-model="stage.action_label" class="input" placeholder="Contoh: Mulai pengerjaan" /></label>
              <label class="field">Instruksi<input v-model="stage.instructions" class="input" placeholder="Catatan untuk penanggung jawab" /></label>
              <label class="flex items-center gap-3 text-sm font-semibold"><input v-model="stage.is_required" type="checkbox" class="h-5 w-5 rounded text-blue-600" /> Tahap wajib diselesaikan</label>
            </div>
          </article>
        </div>
      </section>
      <div class="flex justify-end gap-3"><Link :href="resolveRoute('workflows.index')" class="btn-secondary">Batal</Link><button :disabled="form.processing" class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white disabled:opacity-50">{{ form.processing ? 'Menyimpan...' : 'Simpan Workflow' }}</button></div>
    </form>
    <Teleport to="body"><div v-if="pendingSwap" class="swap-backdrop" @click.self="pendingSwap = null"><div class="swap-modal" role="alertdialog" aria-modal="true" aria-labelledby="swap-title"><span class="material-icons rounded-xl bg-violet-50 p-3 text-violet-600 dark:bg-violet-400/15 dark:text-violet-300">swap_horiz</span><h2 id="swap-title" class="mt-4 text-xl font-bold text-slate-900 dark:text-white">Tukar status tahap?</h2><p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300"><strong>{{ statusLabel(pendingSwap.nextStatus) }}</strong> saat ini digunakan oleh Tahap {{ pendingSwap.conflictIndex + 1 }} ({{ form.stages[pendingSwap.conflictIndex]?.name }}). Tukar status kedua tahap?</p><div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"><p>Tahap {{ pendingSwap.sourceIndex + 1 }}: {{ statusLabel(pendingSwap.previousStatus) }} → {{ statusLabel(pendingSwap.nextStatus) }}</p><p class="mt-1">Tahap {{ pendingSwap.conflictIndex + 1 }}: {{ statusLabel(pendingSwap.nextStatus) }} → {{ statusLabel(pendingSwap.previousStatus) }}</p></div><div class="mt-6 flex justify-end gap-3"><button type="button" class="btn-secondary" @click="pendingSwap = null">Batal</button><button type="button" class="rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-violet-700" @click="confirmStatusSwap">Ya, tukar status</button></div></div></div></Teleport>
  </div>
</template>
<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppLayout from '../../Layouts/AppLayout.vue'
import WorkflowSelect from '../../Components/WorkflowSelect.vue'
import resolveRoute from '../../utils/resolveRoute'
defineOptions({ layout: AppLayout })
const props = defineProps({ workflow: { type: Object, default: null }, roles: { type: Array, default: () => [] }, users: { type: Array, default: () => [] }, statusOptions: { type: Array, default: () => [] }, canSwapStatuses: { type: Boolean, default: false } })
const editing = Boolean(props.workflow)
const entityOptions = [{ value: 'ticket', label: 'Ticket' }, { value: 'task', label: 'Task' }]
const conditionFieldOptions = [{ value: 'priority', label: 'Priority' }, { value: 'status', label: 'Status' }, { value: 'type', label: 'Type' }]
const operatorOptions = [{ value: 'equals', label: 'Sama dengan' }, { value: 'not_equals', label: 'Tidak sama' }, { value: 'contains', label: 'Mengandung' }]
const roleOptions = [{ value: '', label: 'Tidak ditentukan' }, ...props.roles.map(role => ({ value: role, label: role }))]
const userOptions = [{ value: null, label: 'Tidak ditentukan' }, ...props.users.map(user => ({ value: user.id, label: user.name }))]
const blankStage = (name = '', status = 'new') => ({ id: null, name, status_key: status, responsible_role: '', responsible_user_id: null, is_required: true, action_label: '', instructions: '' })
const form = useForm({ name: props.workflow?.name || '', code: props.workflow?.code || '', entity_type: props.workflow?.entity_type || 'ticket', description: props.workflow?.description || '', is_active: props.workflow?.is_active ?? true, trigger_conditions: props.workflow?.trigger_conditions?.map(item => ({ ...item })) || [], stages: props.workflow?.stages?.map(item => ({ id: item.id, name: item.name, status_key: item.status_key, responsible_role: item.responsible_role || '', responsible_user_id: item.responsible_user_id, is_required: item.is_required ?? true, action_label: item.action_label || '', instructions: item.instructions || '' })) || [blankStage('Baru', 'new'), blankStage('Selesai', 'done')] })
const page = usePage()
const notice = ref(page.props.flash?.error || null)
const addCondition = () => form.trigger_conditions.push({ field: 'priority', operator: 'equals', value: '' })
const addStage = () => form.stages.push(blankStage())
const move = (index, direction) => { const target = index + direction; if (target < 0 || target >= form.stages.length) return; [form.stages[index], form.stages[target]] = [form.stages[target], form.stages[index]] }
const stageError = (index, field) => form.errors[`stages.${index}.${field}`]
const pendingSwap = ref(null)
const statusLabel = value => props.statusOptions.find(option => String(option.value) === String(value))?.label || String(value).replaceAll('_', ' ').replace(/\b\w/g, char => char.toUpperCase())
const requestStatusChange = (sourceIndex, nextStatus) => {
  const source = form.stages[sourceIndex]
  if (! source || source.status_key === nextStatus) return
  const conflictIndex = form.stages.findIndex((stage, index) => index !== sourceIndex && stage.status_key === nextStatus)
  if (conflictIndex < 0) {
    source.status_key = nextStatus
    return
  }
  if (! props.canSwapStatuses) {
    notice.value = `${statusLabel(nextStatus)} sedang digunakan oleh Tahap ${conflictIndex + 1}. Pertukaran status hanya dapat dilakukan oleh Super Admin.`
    return
  }
  pendingSwap.value = {
    sourceIndex,
    conflictIndex,
    previousStatus: source.status_key,
    nextStatus,
  }
}
const confirmStatusSwap = () => {
  if (! pendingSwap.value) return
  const { sourceIndex, conflictIndex, previousStatus, nextStatus } = pendingSwap.value
  const source = form.stages[sourceIndex]
  const target = form.stages[conflictIndex]
  if (! source || ! target) return
  source.status_key = nextStatus
  target.status_key = previousStatus
  form.clearErrors('stages', `stages.${sourceIndex}.status_key`, `stages.${conflictIndex}.status_key`)
  notice.value = null
  pendingSwap.value = null
}
const submit = () => {
  notice.value = null
  const options = {
    preserveScroll: true,
    onError: errors => { notice.value = Object.values(errors)[0] || 'Workflow gagal disimpan. Periksa kembali setiap tahap.' },
  }
  form.transform(data => ({
    ...data,
    stages: data.stages.map(stage => ({ ...stage, id: stage.id ? Number(stage.id) : null, status_key: String(stage.status_key || '').toLowerCase() })),
  }))
  editing
    ? form.put(resolveRoute('workflows.update', { workflow: props.workflow.slug }), options)
    : form.post(resolveRoute('workflows.store'), options)
}
</script>
<style scoped>
.field { display: flex; flex-direction: column; gap: .4rem; font-size: .8rem; font-weight: 600; color: #475569; }
.input { width: 100%; border: 1px solid #cbd5e1; border-radius: .75rem; background: transparent; padding: .65rem .8rem; font-size: .875rem; font-weight: 400; color: inherit; }
.dark .input { border-color: #475569; color: #e2e8f0; }
.error { color: #e11d48; font-weight: 500; }
.btn-secondary { display: inline-flex; align-items: center; justify-content: center; border: 1px solid #cbd5e1; border-radius: .75rem; padding: .6rem 1rem; font-size: .875rem; font-weight: 600; color: #475569; }
.swap-backdrop { position: fixed; inset: 0; z-index: 2147483000; display: flex; align-items: center; justify-content: center; background: rgb(15 23 42 / .65); padding: 1rem; backdrop-filter: blur(3px); }
.swap-modal { width: 100%; max-width: 30rem; border: 1px solid #e2e8f0; border-radius: 1rem; background: white; padding: 1.5rem; box-shadow: 0 24px 70px rgb(15 23 42 / .35); }.dark .swap-modal { border-color: #334155; background: #0f172a; }
</style>
