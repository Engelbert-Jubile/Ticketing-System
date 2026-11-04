<template>
  <div class="profile-page mx-auto max-w-6xl space-y-6 px-4 py-8">
    <header><p class="eyebrow">TICKORA / {{ tr('Akun saya', 'My account') }}</p><h1 class="mt-2 text-3xl font-bold">{{ tr('Profil & preferensi', 'Profile & preferences') }}</h1><p class="mt-2 text-sm text-slate-500">{{ tr('Kelola identitas, foto, dan keamanan akun Anda dalam satu tempat.', 'Manage your identity, photo, and account security in one place.') }}</p></header>
    <div v-if="flash.success || flash.error" role="status" class="panel text-sm" :class="flash.error ? 'text-rose-600' : 'text-emerald-600'">{{ flash.success || flash.error }}</div>
    <div class="grid items-start gap-6 lg:grid-cols-[280px,minmax(0,1fr)]">
      <aside class="panel overflow-hidden !p-0">
        <div class="profile-cover h-24"></div>
        <div class="px-6 pb-6">
          <ProfileAvatar :src="avatar" :template="form.avatar_template" :label="fullName" class="-mt-12 h-24 w-24 ring-4 ring-white dark:ring-slate-900" />
          <h2 class="mt-4 break-words text-xl font-bold">{{ fullName }}</h2><p class="mt-1 break-all text-sm text-slate-500">{{ user.email }}</p>
          <span class="role-chip mt-4 inline-flex rounded-full px-3 py-1 text-xs font-semibold">{{ user.role || tr('Anggota', 'Member') }}</span>
          <dl class="mt-6 space-y-4 border-t border-slate-200 pt-5 text-sm dark:border-slate-700"><div><dt class="text-xs text-slate-500">{{ tr('Jabatan', 'Position') }}</dt><dd class="mt-1 break-words">{{ form.position || '—' }}</dd></div><div><dt class="text-xs text-slate-500">Unit</dt><dd class="mt-1">{{ user.unit || '—' }}</dd></div></dl>
          <nav class="mt-6 flex flex-col gap-3 text-sm font-medium"><a href="#personal">{{ tr('Informasi pribadi', 'Personal information') }} ↗</a><a href="#security">{{ tr('Keamanan akun', 'Account security') }} ↗</a></nav>
        </div>
      </aside>
      <div class="space-y-6">
        <form id="personal" class="panel space-y-6" @submit.prevent="submit">
          <div><h2 class="text-lg font-bold">{{ tr('Informasi pribadi', 'Personal information') }}</h2><p class="mt-1 text-sm text-slate-500">{{ tr('Tampilkan versi terbaik dari diri Anda.', 'Put your best self forward.') }}</p></div>
          <div class="rounded-2xl border border-dashed border-slate-300 p-4 dark:border-slate-600">
            <div class="flex flex-wrap items-center gap-4"><ProfileAvatar :src="avatar" :template="form.avatar_template" class="h-16 w-16" /><div><div class="flex flex-wrap gap-2"><button type="button" class="secondary" :disabled="form.processing" @click="fileInput.click()">{{ tr('Pilih foto', 'Choose photo') }}</button><button v-if="avatar" type="button" class="secondary" :disabled="form.processing" @click="removePhoto">{{ tr('Hapus foto', 'Remove photo') }}</button></div><p class="mt-2 text-xs text-slate-500">JPG, PNG, WebP · {{ tr('Maksimal 2 MB, 6000 × 6000 piksel.', 'Up to 2 MB, 6000 × 6000 pixels.') }}</p></div></div>
            <input ref="fileInput" type="file" accept="image/jpeg,image/png,image/webp" class="hidden" :aria-label="tr('Foto profil', 'Profile photo')" @change="choosePhoto" />
            <p v-if="form.errors.photo" role="alert" class="error">{{ form.errors.photo }}</p>
            <label for="avatar-template" class="mt-4 block text-xs font-semibold text-slate-500">{{ tr('Avatar default saat tidak memakai foto', 'Default avatar when no photo is used') }}</label>
            <select id="avatar-template" v-model="form.avatar_template" class="input mt-2" :disabled="form.processing"><option value="male">{{ tr('Laki-laki', 'Male') }}</option><option value="female">{{ tr('Perempuan', 'Female') }}</option></select>
            <p v-if="form.errors.avatar_template" class="error">{{ form.errors.avatar_template }}</p>
          </div>
          <div class="grid gap-5 sm:grid-cols-2">
            <div v-for="field in fields" :key="field.key" :class="field.key === 'bio' ? 'sm:col-span-2' : ''">
              <label :for="field.key" class="mb-2 block text-sm font-medium">{{ field.label }}</label>
              <textarea v-if="field.key === 'bio'" :id="field.key" v-model="form[field.key]" class="input min-h-24" maxlength="1000" :disabled="form.processing"></textarea>
              <input v-else :id="field.key" v-model="form[field.key]" :type="field.key === 'phone_wa' ? 'tel' : 'text'" :autocomplete="field.autocomplete" :required="['username', 'first_name'].includes(field.key)" :maxlength="field.key === 'address' ? 500 : field.key === 'phone_wa' ? 30 : 255" class="input" :disabled="form.processing" :aria-invalid="Boolean(form.errors[field.key])" />
              <p v-if="form.errors[field.key]" class="error">{{ form.errors[field.key] }}</p>
            </div>
            <div class="sm:col-span-2"><label for="profile-email" class="mb-2 block text-sm font-medium">Email</label><input id="profile-email" :value="user.email" type="email" class="input opacity-70" readonly /><p class="mt-2 text-xs text-slate-500">{{ tr('Hubungi administrator untuk mengubah email akun.', 'Contact your administrator to change your account email.') }}</p></div>
          </div>
          <p v-if="form.progress" role="status" class="text-sm text-slate-500">{{ tr('Mengunggah', 'Uploading') }} {{ form.progress.percentage }}%</p>
          <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 pt-5 dark:border-slate-700"><span v-if="form.recentlySuccessful" role="status" class="mr-auto text-sm text-emerald-600">{{ tr('Perubahan tersimpan', 'Changes saved') }}</span><button type="button" class="secondary" :disabled="form.processing" @click="reset">{{ tr('Batalkan perubahan', 'Discard changes') }}</button><button class="primary" :disabled="form.processing">{{ form.processing ? tr('Menyimpan…', 'Saving…') : tr('Simpan perubahan', 'Save changes') }}</button></div>
        </form>
        <form id="security" class="panel space-y-5" @submit.prevent="savePassword">
          <div><h2 class="text-lg font-bold">{{ tr('Keamanan akun', 'Account security') }}</h2><p class="mt-1 text-sm text-slate-500">{{ tr('Perbarui password untuk melindungi akun Anda.', 'Update your password to protect your account.') }}</p></div>
          <div v-for="field in passwordFields" :key="field.key"><label :for="field.key" class="mb-2 block text-sm font-medium">{{ field.label }}</label><div class="relative"><input :id="field.key" v-model="password[field.key]" :type="visible[field.key] ? 'text' : 'password'" :autocomplete="field.key === 'current_password' ? 'current-password' : 'new-password'" required class="input !pr-12" :disabled="password.processing" /><button type="button" class="absolute inset-y-0 right-3 text-slate-500" :aria-label="tr('Tampilkan atau sembunyikan password', 'Show or hide password')" :aria-pressed="Boolean(visible[field.key])" @click="visible[field.key] = !visible[field.key]"><span class="material-icons text-xl">{{ visible[field.key] ? 'visibility_off' : 'visibility' }}</span></button></div><p v-if="password.errors[field.key]" class="error">{{ password.errors[field.key] }}</p></div>
          <p class="text-xs text-slate-500">{{ tr('Gunakan password unik sesuai kebijakan keamanan organisasi.', 'Use a unique password that meets your organization security policy.') }}</p>
          <div class="flex items-center justify-end gap-3"><span v-if="password.recentlySuccessful" role="status" class="text-sm text-emerald-600">{{ tr('Password diperbarui', 'Password updated') }}</span><button class="primary" :disabled="password.processing">{{ password.processing ? tr('Menyimpan…', 'Saving…') : tr('Perbarui password', 'Update password') }}</button></div>
        </form>
      </div>
    </div>
  </div>
</template>
<script setup>
import { computed, onBeforeUnmount, reactive, ref, watch } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import ProfileAvatar from '@/Components/ProfileAvatar.vue'
const props = defineProps({ user: { type: Object, required: true }, meta: { type: Object, default: () => ({}) } })
const page = usePage()
const tr = (id, en) => page.props.locale === 'en' ? en : id
const flash = computed(() => page.props.flash || {})
const values = () => Object.fromEntries(['username', 'first_name', 'last_name', 'position', 'phone_wa', 'bio', 'address', 'avatar_template'].map(key => [key, props.user[key] || (key === 'avatar_template' ? 'male' : '')]))
const form = useForm({ ...values(), photo: null, remove_photo: false })
const password = useForm({ current_password: '', password: '', password_confirmation: '' })
const visible = reactive({})
const fileInput = ref(null)
const preview = ref(null)
const avatar = computed(() => preview.value || (!form.remove_photo ? props.user.photo_url : null))
const fullName = computed(() => `${form.first_name} ${form.last_name}`.trim() || form.username)
const fields = computed(() => [
  { key: 'first_name', label: tr('Nama depan', 'First name'), autocomplete: 'given-name' },
  { key: 'last_name', label: tr('Nama belakang', 'Last name'), autocomplete: 'family-name' },
  { key: 'username', label: 'Username', autocomplete: 'username' },
  { key: 'position', label: tr('Jabatan', 'Position'), autocomplete: 'organization-title' },
  { key: 'phone_wa', label: tr('Nomor telepon / WhatsApp', 'Phone / WhatsApp'), autocomplete: 'tel' },
  { key: 'address', label: tr('Alamat', 'Address'), autocomplete: 'street-address' },
  { key: 'bio', label: tr('Tentang saya', 'About me') },
])
const passwordFields = computed(() => [
  { key: 'current_password', label: tr('Password saat ini', 'Current password') },
  { key: 'password', label: tr('Password baru', 'New password') },
  { key: 'password_confirmation', label: tr('Konfirmasi password baru', 'Confirm new password') },
])
function clearPreview() { if (preview.value) URL.revokeObjectURL(preview.value); preview.value = null; if (fileInput.value) fileInput.value.value = '' }
function choosePhoto(event) {
  const file = event.target.files?.[0]
  if (!file) return
  form.clearErrors('photo')
  if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 2 * 1024 * 1024) {
    form.setError('photo', tr('Pilih JPG, PNG, atau WebP maksimal 2 MB.', 'Choose a JPG, PNG, or WebP up to 2 MB.')); event.target.value = ''; return
  }
  clearPreview(); form.photo = file; form.remove_photo = false; preview.value = URL.createObjectURL(file)
}
function removePhoto() { clearPreview(); form.photo = null; form.remove_photo = true; form.clearErrors('photo') }
function reset() { clearPreview(); form.defaults({ ...values(), photo: null, remove_photo: false }); form.reset(); form.clearErrors() }
function submit() { form.transform(data => ({ ...data, _method: props.meta.method || 'put' })).post(props.meta.updateRoute || route('account.update-profile'), { forceFormData: true, preserveScroll: true, onSuccess: reset }) }
function savePassword() { password.put(route('account.password.update'), { preserveScroll: true, onSuccess: () => { password.reset(); Object.keys(visible).forEach(key => { visible[key] = false }) } }) }
watch(() => props.user, reset)
onBeforeUnmount(clearPreview)
</script>
<style scoped>
.profile-page{color:#0f172a;--accent:var(--user-accent-color,#4338ca)}.dark .profile-page{color:#f1f5f9}
.panel{border:1px solid #e2e8f0;border-radius:1.5rem;background:#fff;padding:1.5rem;box-shadow:0 8px 32px -24px #33415533;scroll-margin-top:100px}.dark .panel{background:#0f172a;border-color:#334155}
.eyebrow{font-size:.65rem;font-weight:700;letter-spacing:.16em;color:var(--accent);text-transform:uppercase}.profile-cover{background:linear-gradient(120deg,color-mix(in srgb,var(--accent) 65%,white),var(--accent),color-mix(in srgb,var(--accent) 70%,black))}
.role-chip{color:var(--accent);background:color-mix(in srgb,var(--accent) 12%,transparent)}.dark .role-chip{color:color-mix(in srgb,var(--accent) 40%,white)}
.input{width:100%;border:1px solid #cbd5e1;border-radius:.8rem;background:transparent;padding:.75rem .9rem;font-size:.875rem;transition:border-color .15s,box-shadow .15s}.input:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px color-mix(in srgb,var(--accent) 15%,transparent)}.dark .input{border-color:#475569;color:#e2e8f0;color-scheme:dark}
.primary,.secondary{display:inline-flex;align-items:center;justify-content:center;gap:.5rem;border-radius:.75rem;padding:.7rem 1rem;font-size:.8rem;font-weight:600}.primary{background:var(--accent);color:#fff}.secondary{border:1px solid #cbd5e1}.primary:hover,.secondary:hover{filter:brightness(.93)}button:disabled{opacity:.5;cursor:wait}.error{margin-top:.5rem;color:#e11d48;font-size:.75rem}
</style>
