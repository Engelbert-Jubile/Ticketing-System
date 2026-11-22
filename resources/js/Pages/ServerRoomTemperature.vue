<template>
  <div class="temperature-report">
    <Head :title="labels.title" />
    <header class="report-header">
      <div>
        <Link :href="page.props.portalUrl" class="portal-link">{{ labels.portal }}</Link>
        <h1>{{ labels.title }}</h1>
        <p>{{ labels.subtitle }}</p>
      </div>
      <button
        ref="addButton"
        type="button"
        class="primary-button"
        :aria-expanded="formOpen"
        :disabled="form.processing"
        aria-controls="temperature-check-form"
        @click="setFormOpen(!formOpen)"
      >
        <span class="material-icons" aria-hidden="true">add</span>
        {{ labels.add }}
      </button>
    </header>

    <form
      v-show="formOpen"
      id="temperature-check-form"
      class="report-panel check-form"
      aria-labelledby="check-form-heading"
      @submit.prevent="save"
    >
      <div class="panel-header">
        <h2 id="check-form-heading">{{ labels.add }}</h2>
        <span>{{ defaults.timezone }}</span>
      </div>
      <div class="field-grid">
        <label>
          {{ labels.room }}
          <input ref="roomInput" v-model.trim="form.room" :placeholder="labels.room_placeholder" maxlength="120" required />
        </label>
        <label>
          {{ labels.date }}
          <input v-model="form.date" type="date" :max="defaults.date" required />
        </label>
        <label>
          {{ labels.time }}
          <input v-model="form.time" type="time" required />
        </label>
        <label>
          {{ labels.temperature }}
          <input v-model="form.temperature" type="number" inputmode="decimal" step="0.1" min="-99.9" max="99.9" required />
        </label>
        <label>
          {{ labels.condition }}
          <select v-model="form.condition">
            <option value="normal">{{ labels.normal }}</option>
            <option value="follow_up">{{ labels.follow_up }}</option>
          </select>
        </label>
        <label>
          {{ labels.evidence }}
          <input ref="photoInput" type="file" accept="image/jpeg,image/png,image/webp" aria-describedby="photo-help" required @change="selectPhoto" />
          <span id="photo-help" class="field-help">{{ labels.evidence_help }}</span>
        </label>
      </div>
      <label class="notes-field">
        {{ form.condition === 'follow_up' ? labels.notes_required : labels.notes_optional }}
        <textarea v-model.trim="form.notes" :required="form.condition === 'follow_up'" maxlength="1000" rows="2"></textarea>
      </label>
      <ul v-if="form.hasErrors" class="form-errors" role="alert">
        <li v-for="(message, field) in form.errors" :key="field">{{ message }}</li>
      </ul>
      <div class="form-actions">
        <SaveButton :processing="form.processing">{{ labels.save }}</SaveButton>
        <button type="button" class="secondary-button" :disabled="form.processing" @click="setFormOpen(false)">{{ labels.close }}</button>
      </div>
    </form>

    <dl class="reading-summary">
      <div class="report-panel">
        <dt>{{ labels.today }}</dt>
        <dd>{{ summary.today }}</dd>
      </div>
      <div class="report-panel">
        <dt>{{ labels.latest }}</dt>
        <dd>{{ summary.latest ? temperature(summary.latest.temperature_c) : '—' }}</dd>
        <dd v-if="summary.latest" class="summary-detail">{{ summary.latest.room }}</dd>
      </div>
      <div class="report-panel">
        <dt>{{ labels.follow_up_period }}</dt>
        <dd :class="{ 'follow-up-value': summary.followUp > 0 }">{{ summary.followUp }}</dd>
      </div>
    </dl>

    <section class="report-panel history" aria-labelledby="check-history-heading">
      <div class="panel-header">
        <h2 id="check-history-heading">{{ labels.history }}</h2>
        <form class="date-filter" @submit.prevent="applyFilter">
          <label>
            {{ labels.from }}
            <input v-model="period.from" type="date" required />
          </label>
          <label>
            {{ labels.to }}
            <input v-model="period.to" type="date" :min="period.from" required />
          </label>
          <button class="secondary-button" :disabled="period.processing">{{ labels.filter }}</button>
          <p v-if="period.errors.from || period.errors.to" class="form-errors filter-errors" role="alert">
            {{ period.errors.from || period.errors.to }}
          </p>
        </form>
      </div>

      <div v-if="checks.data.length" class="table-scroll" tabindex="0" role="region" aria-labelledby="check-history-heading">
        <table>
          <thead>
            <tr>
              <th scope="col">{{ labels.date_time }}</th>
              <th scope="col">{{ labels.room }}</th>
              <th scope="col">{{ labels.temperature }}</th>
              <th scope="col">{{ labels.condition }}</th>
              <th scope="col">{{ labels.inspector }}</th>
              <th scope="col">{{ labels.evidence }}</th>
              <th scope="col">{{ labels.notes }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="check in checks.data" :key="check.id">
              <td>
                <strong>{{ dateFormatter.format(new Date(check.date)) }}</strong>
                <span class="cell-detail">{{ check.time }}</span>
              </td>
              <td>{{ check.room }}<span v-if="check.unit" class="cell-detail">{{ check.unit }}</span></td>
              <td class="reading">{{ temperature(check.temperature) }}</td>
              <td>
                <span class="condition" :class="{ 'condition--follow-up': check.needsFollowUp }">
                  {{ check.needsFollowUp ? labels.follow_up : labels.normal }}
                </span>
              </td>
              <td>{{ check.inspector }}</td>
              <td>
                <a
                  :href="check.evidenceUrl"
                  class="evidence"
                  :aria-label="labels.view_evidence + ' · ' + check.room + ' · ' + check.date + ' ' + check.time"
                  @click.exact.prevent="openEvidence(check)"
                >
                  <img v-if="!unavailablePhotos[check.id]" :src="check.evidenceUrl" alt="" loading="lazy" width="44" height="44" @error="unavailablePhotos[check.id] = true" />
                  <span v-else class="material-icons" aria-hidden="true">image</span>
                </a>
              </td>
              <td class="note">{{ check.notes || '—' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <p v-else class="empty-state">{{ labels.empty }}</p>

      <div v-if="checks.total" class="page-footer">
        <p>{{ checks.from ? rangeText : '' }}</p>
        <nav v-if="checks.prev_page_url || checks.next_page_url" :aria-label="labels.history">
          <Link v-if="checks.prev_page_url" :href="checks.prev_page_url" class="secondary-button">{{ labels.previous }}</Link>
          <Link v-if="checks.next_page_url" :href="checks.next_page_url" class="secondary-button">{{ labels.next }}</Link>
        </nav>
      </div>
    </section>

    <dialog ref="photoDialog" class="photo-dialog" aria-labelledby="photo-dialog-heading" @close="selectedEvidence = null">
      <div class="panel-header">
        <h2 id="photo-dialog-heading">{{ labels.evidence }}</h2>
        <form method="dialog"><button class="secondary-button">{{ labels.close }}</button></form>
      </div>
      <template v-if="selectedEvidence">
        <p class="photo-caption">{{ selectedEvidence.room }} · {{ dateFormatter.format(new Date(selectedEvidence.date)) }} · {{ selectedEvidence.time }}</p>
        <img v-if="!unavailablePhotos[selectedEvidence.id]" :src="selectedEvidence.evidenceUrl" :alt="labels.evidence" @error="unavailablePhotos[selectedEvidence.id] = true" />
        <p v-else class="empty-state">{{ labels.evidence_unavailable }}</p>
      </template>
    </dialog>
  </div>
</template>

<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import { computed, nextTick, ref, watch } from 'vue'

const props = defineProps({
  checks: { type: Object, required: true },
  filters: { type: Object, required: true },
  summary: { type: Object, required: true },
  defaults: { type: Object, required: true },
  urls: { type: Object, required: true },
  labels: { type: Object, required: true },
})

const page = usePage()
const dateFormatter = computed(() => new Intl.DateTimeFormat(page.props.locale, { dateStyle: 'medium', timeZone: 'UTC' }))
const numberFormatter = computed(() => new Intl.NumberFormat(page.props.locale, { minimumFractionDigits: 1, maximumFractionDigits: 1 }))
const temperature = value => numberFormatter.value.format(value) + ' °C'
const rangeText = computed(() => props.labels.showing
  .replace('{from}', props.checks.from)
  .replace('{to}', props.checks.to)
  .replace('{total}', props.checks.total))

const formOpen = ref(false)
const addButton = ref(null)
const roomInput = ref(null)
const photoInput = ref(null)
const photoDialog = ref(null)
const selectedEvidence = ref(null)
const unavailablePhotos = ref({})
const form = useForm({
  date: props.defaults.date,
  time: props.defaults.time,
  temperature: '',
  room: '',
  condition: 'normal',
  evidence: null,
  notes: '',
})
const period = useForm({ ...props.filters })

watch(() => props.filters, filters => {
  period.from = filters.from
  period.to = filters.to
})

function applyFilter() {
  period.get(props.urls.index, { preserveState: true, preserveScroll: true })
}

async function setFormOpen(open) {
  formOpen.value = open
  await nextTick()
  if (formOpen.value) roomInput.value?.focus()
  else addButton.value?.focus()
}

function selectPhoto(event) {
  form.evidence = event.target.files[0] ?? null
  form.clearErrors('evidence')
}

function save() {
  form.post(props.urls.store, {
    preserveScroll: true,
    onSuccess: () => {
      form.reset('temperature', 'condition', 'evidence', 'notes')
      form.date = props.defaults.date
      form.time = props.defaults.time
      if (photoInput.value) photoInput.value.value = ''
      setFormOpen(false)
    },
  })
}

function openEvidence(check) {
  selectedEvidence.value = check
  photoDialog.value.showModal()
}
</script>

<style scoped>
.temperature-report {
  --surface: #fff;
  --subtle: #f3f6fb;
  --text: #19263e;
  --muted: #52647c;
  --border: #dbe3ef;
  --accent: #5d359e;
  --warning: #93490c;
  --warning-bg: #fff0dc;
  --normal: #126146;
  --normal-bg: #def4e9;
  --error: #a42140;
  display: grid;
  gap: 1.25rem;
  max-width: 82rem;
  margin-inline: auto;
  padding: clamp(0.5rem, 2vw, 1.5rem) 0 2rem;
  color: var(--text);
}
.dark .temperature-report {
  --surface: #18263b;
  --subtle: #1e3048;
  --text: #f1f5ff;
  --muted: #b9c8dc;
  --border: #394b64;
  --accent: #d5beff;
  --warning: #ffd097;
  --warning-bg: #543518;
  --normal: #a2ecd3;
  --normal-bg: #194437;
  --error: #fda4b5;
}
.report-header, .panel-header, .page-footer {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}
.report-header { align-items: flex-end; }
.portal-link { color: var(--accent); font-size: 0.9rem; font-weight: 650; }
h1 { margin: 0.25rem 0 0; color: var(--text); font-size: clamp(1.85rem, 4vw, 2.8rem); font-weight: 750; letter-spacing: -0.035em; }
.report-header p { margin-top: 0.35rem; color: var(--muted); }
.primary-button, .secondary-button {
  display: inline-flex;
  min-height: 2.65rem;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  padding: 0.5rem 0.85rem;
  border: 1px solid var(--border);
  border-radius: 0.7rem;
  color: var(--text);
  background: var(--surface);
  font-size: 0.875rem;
  font-weight: 650;
  text-decoration: none;
}
.primary-button { border-color: #5d359e; background: #5d359e; color: #fff; }
.primary-button:not(:disabled):hover { background: #49277d; }
.primary-button .material-icons { color: inherit; font-size: 1.15rem; }
.secondary-button:not(:disabled):hover { background: var(--subtle); }
.primary-button:disabled, .secondary-button:disabled { opacity: 0.65; }
.report-panel { border: 1px solid var(--border); border-radius: 1rem; background: var(--surface); }
.panel-header h2 { color: var(--text); font-size: 1.15rem; font-weight: 700; }
.panel-header > span, .field-help, .cell-detail { color: var(--muted); font-size: 0.8125rem; }
.check-form { display: grid; gap: 1rem; padding: 1.25rem; }
.field-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem; }
.field-grid label, .notes-field, .date-filter label { display: grid; align-content: start; gap: 0.35rem; font-size: 0.875rem; font-weight: 650; }
input, select, textarea {
  width: 100%;
  min-width: 0;
  min-height: 2.65rem;
  padding: 0.5rem 0.65rem;
  border: 1px solid var(--border);
  border-radius: 0.65rem;
  background-color: var(--surface);
  color: var(--text);
  font: inherit;
  font-weight: 400;
}
.dark input, .dark select, .dark textarea { color-scheme: dark; }
input::placeholder { color: var(--muted); }
input[type="file"] { padding: 0.4rem; }
input[type="file"]::file-selector-button { margin-inline-end: 0.5rem; color: var(--text); background: var(--subtle); border: 0; border-radius: 0.35rem; padding: 0.3rem 0.5rem; cursor: pointer; }
textarea { resize: vertical; }
.field-help { font-weight: 400; }
.form-actions, .page-footer nav { display: flex; flex-wrap: wrap; gap: 0.65rem; align-items: center; }
.form-errors { color: var(--error); font-size: 0.875rem; }
ul.form-errors { padding-inline-start: 1.25rem; list-style: disc; }
.reading-summary { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.85rem; margin: 0; }
.reading-summary > div { min-width: 0; padding: 1rem 1.15rem; }
.reading-summary dt { color: var(--muted); font-size: 0.875rem; font-weight: 600; }
.reading-summary dd { font-size: 1.75rem; font-weight: 750; font-variant-numeric: tabular-nums; }
.reading-summary .summary-detail { color: var(--muted); font-size: 0.85rem; font-weight: 500; overflow-wrap: anywhere; }
.follow-up-value { color: var(--warning); }
.history { min-width: 0; }
.history > .panel-header, .page-footer { padding: 1rem 1.15rem; }
.date-filter { display: flex; flex-wrap: wrap; gap: 0.6rem; align-items: flex-end; }
.filter-errors { flex-basis: 100%; }
.table-scroll { overflow-x: auto; }
table { width: 100%; min-width: 55rem; border-collapse: collapse; font-size: 0.9rem; }
th, td { padding: 0.75rem 0.9rem; text-align: start; border-top: 1px solid var(--border); }
th { background: var(--subtle); color: var(--muted); font-size: 0.875rem; font-weight: 650; white-space: nowrap; }
td { overflow-wrap: anywhere; }
.cell-detail { display: block; margin-top: 0.15rem; }
.reading { font-weight: 700; font-variant-numeric: tabular-nums; white-space: nowrap; }
.condition { display: inline-block; padding: 0.2rem 0.55rem; border-radius: 0.4rem; color: var(--normal); background: var(--normal-bg); font-size: 0.875rem; font-weight: 650; }
.condition--follow-up { color: var(--warning); background: var(--warning-bg); }
.evidence { display: inline-grid; place-items: center; width: 2.875rem; height: 2.875rem; overflow: hidden; border: 1px solid var(--border); border-radius: 0.55rem; background: var(--subtle); color: var(--accent); }
.evidence img { width: 100%; height: 100%; object-fit: cover; }
.note { min-width: 8rem; max-width: 18rem; white-space: pre-wrap; }
.page-footer, .empty-state { border-top: 1px solid var(--border); color: var(--muted); font-size: 0.875rem; }
.empty-state { padding: 2rem 1rem; text-align: center; }
.photo-dialog { width: min(44rem, calc(100% - 2rem)); max-height: calc(100dvh - 2rem); padding: 1rem; border: 1px solid var(--border); border-radius: 1rem; background: var(--surface); color: var(--text); }
.photo-dialog::backdrop { background: #0e172dcc; }
.photo-caption { margin: 0.6rem 0; color: var(--muted); overflow-wrap: anywhere; }
.photo-dialog img { display: block; max-width: 100%; max-height: 70dvh; margin-inline: auto; object-fit: contain; }
.temperature-report :focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }
@media (max-width: 760px) {
  .field-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 480px) {
  .field-grid { grid-template-columns: minmax(0, 1fr); }
  .reading-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .reading-summary > :last-child { grid-column: 1 / -1; }
}
</style>
