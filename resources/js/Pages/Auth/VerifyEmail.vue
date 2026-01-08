<template>
  <div class="mx-auto w-full max-w-md rounded-3xl bg-white/90 p-8 shadow-xl backdrop-blur dark:bg-slate-900/90">
    <Head title="Verifikasi Email" />
    <div class="space-y-6 text-center">
      <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-blue-100 text-blue-600">
        <span class="material-icons text-3xl">mark_email_unread</span>
      </div>
      <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Verifikasi Email Diperlukan</h1>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
          <span v-if="successMessage">Tautan verifikasi sudah dikirim</span>
          <span v-else>Belum menerima tautan verifikasi?</span>
          <span v-if="email"> ke email ({{ email }})</span>. Silakan cek kotak masuk atau folder spam.
        </p>
      </div>
      <form @submit.prevent="resend" class="space-y-4">
        <button
          type="submit"
          :disabled="sending"
          class="flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-600/25 transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-75"
        >
          <span class="material-icons text-base">refresh</span>
          <span>Kirim Ulang Email Verifikasi</span>
        </button>
      </form>
      <p v-if="successMessage" class="text-sm font-semibold text-green-600">{{ successMessage }}</p>
      <p v-if="errorMessage" class="text-sm font-semibold text-red-500">{{ errorMessage }}</p>
      <p v-if="checking" class="text-xs font-medium text-slate-500 dark:text-slate-400">
        Menunggu verifikasi&hellip; halaman ini akan otomatis lanjut setelah kamu klik link di email.
      </p>
      <form @submit.prevent="logout" class="pt-2">
        <button
          type="submit"
          :disabled="loggingOut"
          class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900/5 px-5 py-2.5 text-sm font-semibold text-slate-700 ring-1 ring-slate-200 transition hover:bg-slate-900/10 disabled:cursor-not-allowed disabled:opacity-75 dark:bg-white/10 dark:text-slate-100 dark:ring-white/10 dark:hover:bg-white/15"
        >
          <span class="material-icons text-base">logout</span>
          <span>Logout</span>
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import MinimalAuthLayout from '../../Layouts/MinimalAuthLayout.vue';
import resolveRoute from '../../utils/resolveRoute';

defineOptions({ layout: MinimalAuthLayout });

const page = usePage();
const email = computed(() => page.props.value?.auth?.user?.email ?? '');
const status = computed(() => page.props.value?.status ?? null);
const flashSuccess = computed(() => page.props.value?.flash?.success ?? null);
const flashError = computed(() => page.props.value?.flash?.error ?? null);
const successMessage = computed(() => {
  if (status.value === 'verification-link-sent') return 'Tautan verifikasi berhasil dikirim.';
  if (typeof flashSuccess.value === 'string' && flashSuccess.value.trim() !== '') return flashSuccess.value;
  return null;
});
const errorMessage = computed(() => {
  if (typeof flashError.value === 'string' && flashError.value.trim() !== '') return flashError.value;
  return null;
});

const resendForm = useForm({});
const logoutForm = useForm({});
const sending = ref(false);
const loggingOut = ref(false);
const checking = ref(true);
const checkingInFlight = ref(false);
const resendEndpoint = computed(() => resolveRoute('verification.send'));
const logoutEndpoint = computed(() => resolveRoute('logout'));
const statusEndpoint = computed(() => resolveRoute('verification.status'));

const resend = () => {
  if (sending.value) return;
  sending.value = true;
  resendForm.post(resendEndpoint.value, {
    preserveScroll: true,
    onFinish: () => {
      sending.value = false;
    },
  });
};

const logout = () => {
  if (loggingOut.value) return;
  loggingOut.value = true;
  logoutForm.post(logoutEndpoint.value, {
    onFinish: () => {
      loggingOut.value = false;
    },
  });
};

const checkVerification = async () => {
  if (checkingInFlight.value) return;
  checkingInFlight.value = true;
  try {
    const response = await fetch(statusEndpoint.value, {
      method: 'GET',
      headers: { Accept: 'application/json' },
      credentials: 'same-origin',
    });

    if (!response.ok) return;
    const data = await response.json();
    if (data?.verified) {
      router.visit(resolveRoute('dashboard'), { replace: true });
    }
  } catch (_) {
  } finally {
    checkingInFlight.value = false;
  }
};

let checkIntervalId = null;
onMounted(() => {
  checkVerification();
  checkIntervalId = window.setInterval(checkVerification, 2500);
});

onBeforeUnmount(() => {
  if (checkIntervalId) window.clearInterval(checkIntervalId);
});
</script>
