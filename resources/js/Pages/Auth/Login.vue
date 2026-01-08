<template>
  <section class="auth-page">
    <Head title="TMS — Ticket Management System" />

    <div class="auth-container">
      <div class="auth-icon">
        <span class="material-icons">confirmation_number</span>
      </div>

      <div class="auth-heading">
        <span class="auth-heading__primary">TMS</span>
        <span class="auth-heading__secondary">Ticket Management System</span>
      </div>

      <form @submit.prevent="submit" class="auth-card" novalidate>
        <div class="auth-card__field">
          <label for="email">Email</label>
          <input
            id="email"
            v-model="form.email"
            type="email"
            required
            autocomplete="username"
          />
          <p v-if="form.errors.email" class="auth-card__error">{{ form.errors.email }}</p>
        </div>

        <div class="auth-card__field">
          <label for="password">Password</label>
          <input
            id="password"
            v-model="form.password"
            type="password"
            required
            autocomplete="current-password"
          />
          <p v-if="form.errors.password" class="auth-card__error">{{ form.errors.password }}</p>
        </div>

        <button type="submit" :disabled="form.processing" class="auth-card__submit">
          <span class="material-icons">login</span>
          <span>Masuk</span>
        </button>
      </form>

      <p class="auth-link">
        Belum punya akun?
        <Link :href="registerUrl">Daftar di sini</Link>
      </p>
    </div>
  </section>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import resolveRoute from '../../utils/resolveRoute'

const form = useForm({
  email: '',
  password: '',
})

const loginAction = computed(() => resolveRoute('login.store'))
const registerUrl = computed(() => resolveRoute('register'))

const submit = () => {
  form
    .transform(data => ({
      ...data,
      email: data.email.trim().toLowerCase(),
    }))
    .post(loginAction.value, {
      onFinish: () => form.reset('password'),
    })
}
</script>

<style scoped>
.auth-page {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  background: #f1f5f9;
  padding: 3rem 1.5rem;
  color: #0f172a;
}

.auth-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  width: min(100%, 420px);
  gap: 1.75rem;
  text-align: center;
}

.auth-icon {
  display: inline-flex;
  height: 96px;
  width: 96px;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: linear-gradient(135deg, rgba(79, 70, 229, 0.18), rgba(37, 99, 235, 0.25));
  color: #1d4ed8;
  font-size: 2.5rem;
  box-shadow: 0 20px 35px -22px rgba(37, 99, 235, 0.5);
}

.auth-heading {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.auth-heading__primary {
  font-size: clamp(2.25rem, 4vw + 1rem, 3rem);
  font-weight: 700;
  letter-spacing: 0.02em;
}

.auth-heading__secondary {
  font-size: clamp(1rem, 2.6vw + 0.5rem, 1.25rem);
  font-weight: 500;
  color: #1d4ed8;
}

.auth-card {
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  padding: 2.25rem 2rem;
  background: rgba(255, 255, 255, 0.95);
  border-radius: 24px;
  box-shadow: 0 22px 60px -28px rgba(79, 70, 229, 0.45);
  border: 1px solid rgba(99, 102, 241, 0.15);
  backdrop-filter: blur(4px);
}

.auth-card__field {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  text-align: left;
}

.auth-card__field label {
  font-size: 0.95rem;
  font-weight: 600;
  color: #475569;
}

.auth-card__field input {
  height: 48px;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  padding: 0 1rem;
  font-size: 0.95rem;
  color: #0f172a;
  background: #fff;
  box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.05);
  transition: border 0.2s ease, box-shadow 0.2s ease;
}

.auth-card__field input:focus {
  outline: none;
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.18);
}

.auth-card__error {
  font-size: 0.85rem;
  color: #f43f5e;
}

.auth-card__submit {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  height: 52px;
  width: 100%;
  border-radius: 14px;
  border: none;
  background: linear-gradient(135deg, #6366f1, #3b82f6);
  color: #fff;
  font-weight: 600;
  font-size: 1rem;
  letter-spacing: 0.02em;
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease, filter 0.2s ease;
  box-shadow: 0 18px 40px -20px rgba(99, 102, 241, 0.65);
}

.auth-card__submit:disabled {
  opacity: 0.75;
  cursor: not-allowed;
}

.auth-card__submit:hover:not(:disabled) {
  transform: translateY(-2px);
  filter: brightness(1.05);
}

.auth-card__submit .material-icons {
  font-size: 1.1rem;
}

.auth-link {
  font-size: 0.95rem;
  color: #64748b;
}

.auth-link a {
  font-weight: 600;
  color: #6366f1;
  text-decoration: none;
  transition: color 0.2s ease;
}

.auth-link a:hover {
  color: #4f46e5;
}

@media (max-width: 640px) {
  .auth-page {
    padding: 2.5rem 1.25rem;
  }

  .auth-card {
    padding: 2rem 1.75rem;
  }

  .auth-card__field input {
    font-size: 0.9rem;
  }

  .auth-card__submit {
    height: 50px;
    font-size: 0.95rem;
  }
}

@media (max-width: 400px) {
  .auth-card {
    padding: 1.75rem 1.5rem;
  }

  .auth-page {
    padding: 2rem 1rem;
  }
}
</style>