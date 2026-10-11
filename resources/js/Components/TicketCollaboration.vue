<template>
  <section class="space-y-5 rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-900">
    <h2 class="text-lg font-semibold">{{ $t("Aktivitas dan kolaborasi") }}</h2>
    <p v-if="error" role="alert" class="text-sm text-rose-600">{{ error }} <button type="button" class="underline" @click="load()">{{ $t("Coba lagi") }}</button></p>
    <p v-if="!state && !error" role="status">{{ $t("Memuat aktivitas…") }}</p>
    <template v-if="state">
      <div>
        <h3 class="font-semibold">{{ $t("Checklist penyelesaian") }}</h3>
        <p v-if="!state.checklist.length" class="mt-2 text-sm text-slate-500">{{ $t("Belum ada checklist.") }}</p>
        <label v-for="item in state.checklist" :key="item.id" class="mt-2 flex items-center gap-3 text-sm">
          <input type="checkbox" :checked="Boolean(item.done)" :disabled="!state.can_internal || busy" @change="toggleItem(item, $event.target.checked)" />
          <span :class="{ 'line-through text-slate-500': item.done }">{{ item.title }}</span>
        </label>
        <form v-if="state.can_internal" @submit.prevent="addItem" class="mt-3 flex gap-2"><input v-model="checkTitle" :aria-label="$t(&quot;Checklist baru&quot;)" required maxlength="255" :placeholder="$t(&quot;Tambahkan langkah penyelesaian&quot;)" class="min-w-0 flex-1 rounded-lg dark:bg-slate-800" /><button :disabled="busy" class="rounded-lg border px-3 py-2">{{ $t("Tambah") }}</button></form>
      </div>
      <form @submit.prevent="send" class="space-y-3 border-t border-slate-200 pt-4 dark:border-slate-700">
        <label class="block text-sm font-semibold">{{ $t("Pesan") }}<textarea v-model="body" required maxlength="5000" rows="3" class="mt-2 w-full rounded-xl dark:bg-slate-800" :placeholder="$t(&quot;Tulis pembaruan. Gunakan @username untuk menyebut peserta ticket.&quot;)" /></label>
        <label v-if="state.can_internal" class="flex items-center gap-2 text-sm"><input v-model="internal" type="checkbox" />{{ $t("Catatan internal untuk agent/admin") }}</label>
        <button :disabled="busy || !body.trim()" class="rounded-lg bg-blue-600 px-4 py-2 text-white disabled:opacity-50">{{ busy ? $t('Menyimpan…') : $t('Kirim pesan') }}</button>
      </form>
      <div class="space-y-3">
        <p v-if="!state.messages.data.length" class="text-sm text-slate-500">{{ $t("Belum ada percakapan.") }}</p>
        <article v-for="message in state.messages.data" :key="message.id" class="rounded-xl bg-slate-50 p-4 dark:bg-slate-800">
          <p class="text-sm font-semibold">{{ message.first_name }} {{ message.last_name }} <span v-if="message.internal" class="ml-2 text-amber-700 dark:text-amber-300">Internal</span></p>
          <p class="mt-2 whitespace-pre-wrap break-words text-sm">{{ message.body }}</p><time class="mt-2 block text-xs text-slate-500">{{ message.created_at }}</time>
        </article>
        <div class="flex justify-between"><button v-if="state.messages.prev_page_url" class="text-sm text-blue-600" @click="load(state.messages.prev_page_url)">{{ $t("Lebih baru") }}</button><button v-if="state.messages.next_page_url" class="text-sm text-blue-600" @click="load(state.messages.next_page_url)">{{ $t("Lebih lama") }}</button></div>
      </div>
      <form v-if="state.can_feedback" @submit.prevent="sendFeedback" class="space-y-3 border-t border-slate-200 pt-4 dark:border-slate-700">
        <h3 class="font-semibold">{{ $t("Bagaimana hasil penyelesaiannya?") }}</h3>
        <label class="block text-sm">{{ $t("Penilaian") }}<select v-model.number="feedback.rating" class="ml-3 rounded-lg dark:bg-slate-800"><option v-for="n in 5" :key="n" :value="n">{{ n }} / 5</option></select></label>
        <textarea v-model="feedback.comment" :aria-label="$t(&quot;Komentar penilaian&quot;)" maxlength="2000" :placeholder="$t(&quot;Masukan untuk tim&quot;)" class="w-full rounded-lg dark:bg-slate-800" />
        <label class="flex items-center gap-2 text-sm"><input v-model="feedback.reopen_requested" type="checkbox" />{{ $t("Saya membutuhkan tindak lanjut kembali") }}</label>
        <button :disabled="busy" class="rounded-lg bg-blue-600 px-4 py-2 text-white">{{ $t("Simpan penilaian") }}</button>
      </form>
      <p v-else-if="state.feedback" class="text-sm">{{ $t("Penilaian requester:") }} {{ state.feedback.rating }}/5 · {{ state.feedback.comment }}<span v-if="state.feedback.reopen_requested" class="block font-semibold text-amber-700 dark:text-amber-300">{{ $t("Requester meminta tindak lanjut kembali.") }}</span></p>
      <p role="status" class="text-sm text-emerald-700 dark:text-emerald-300">{{ success }}</p>
    </template>
  </section>
</template>
<script setup>
import { translateUi as uiText } from '../i18n'

import axios from 'axios'
import { onMounted, reactive, ref } from 'vue'
import resolveRoute from '@/utils/resolveRoute'
const props = defineProps({ ticketId: { type: Number, required: true } })
const state = ref(null), error = ref(''), success = ref(''), busy = ref(false), body = ref(''), internal = ref(false), checkTitle = ref('')
const feedback = reactive({ rating: 5, comment: '', reopen_requested: false })
const url = name => resolveRoute(name, { ticket: props.ticketId })
async function load(href) { try { error.value = ''; state.value = (await axios.get(href || url('tickets.collaboration'))).data; if (state.value.feedback) Object.assign(feedback, { rating: state.value.feedback.rating, comment: state.value.feedback.comment || '', reopen_requested: Boolean(state.value.feedback.reopen_requested) }) } catch (_) { error.value = uiText("Aktivitas belum dapat dimuat.") } }
async function save(name, data, after) { if (busy.value) return; busy.value = true; error.value = ''; success.value = ''; try { await axios.post(url(name), data); after?.(); await load(); success.value = uiText("Perubahan tersimpan.") } catch (e) { error.value = Object.values(e.response?.data?.errors || {}).flat().join(' ') || uiText("Perubahan gagal disimpan. Coba kembali.") } finally { busy.value = false } }
const send = () => save('tickets.messages.store', { body: body.value, internal: internal.value }, () => body.value = '')
const addItem = () => save('tickets.checklist.store', { title: checkTitle.value }, () => checkTitle.value = '')
const toggleItem = (item, done) => save('tickets.checklist.store', { id: item.id, done })
const sendFeedback = () => save('tickets.feedback.store', { ...feedback })
onMounted(() => load())
</script>
