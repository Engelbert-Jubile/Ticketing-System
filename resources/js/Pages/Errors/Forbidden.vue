<script setup>
defineOptions({
  layout: page => page,
});

const props = defineProps({
  title: { type: String, default: '403 - Unauthorized' },
  code: { type: String, default: '403' },
  message: {
    type: String,
    default: 'Anda tidak memiliki hak akses untuk halaman ini. Silakan hubungi admin jika ini keliru.',
  },
  buttonLabel: { type: String, default: 'Kembali' },
  buttonUrl: { type: String, default: '/' },
  illustration: { type: String, default: '/images/access-denied-403.svg' },
});
</script>

<template>
  <div class="error-shell">
    <main class="wrap">
      <section class="card" role="dialog" aria-labelledby="error-title" aria-describedby="error-message">
        <div class="content">
          <div class="art" :style="{ backgroundImage: `url(${props.illustration})` }" aria-hidden="true" />
          <p class="helper">Akses dibatasi</p>
          <h1 id="error-code" class="code">{{ props.code }}</h1>
          <p id="error-title" class="title">{{ props.title }}</p>
          <p id="error-message" class="subtitle">{{ props.message }}</p>
          <a class="btn" :href="props.buttonUrl">
            <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24">
              <path fill="currentColor" d="M9.53 5.53a.75.75 0 0 0-1.06-1.06l-5 5a.75.75 0 0 0 0 1.06l5 5a.75.75 0 0 0 1.06-1.06L5.81 11H20a.75.75 0 0 0 0-1.5H5.81z" />
            </svg>
            {{ props.buttonLabel }}
          </a>
        </div>
      </section>
    </main>
  </div>
</template>

<style scoped>
:global(body) {
  margin: 0;
}

.error-shell {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: clamp(32px, 6vw, 72px) 20px;
  background: radial-gradient(circle at 0% -20%, rgba(59, 130, 246, 0.18), transparent 46%),
    radial-gradient(circle at 110% 10%, rgba(124, 58, 237, 0.16), transparent 48%),
    linear-gradient(135deg, #eef2ff, #f8fafc);
  color: #0f172a;
  font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, Ubuntu, Arial, sans-serif;
}

.wrap {
  width: min(880px, 100%);
}

.card {
  position: relative;
  background: rgba(255, 255, 255, 0.92);
  border-radius: 28px;
  padding: clamp(40px, 6vw, 72px) clamp(28px, 6vw, 64px);
  box-shadow: 0 38px 80px -48px rgba(30, 64, 175, 0.45);
  text-align: center;
  overflow: hidden;
  border: 1px solid rgba(99, 102, 241, 0.15);
  backdrop-filter: blur(6px);
}

.card::before,
.card::after {
  content: '';
  position: absolute;
  border-radius: 999px;
  background: rgba(79, 70, 229, 0.12);
  z-index: 0;
}

.card::before {
  width: 220px;
  height: 220px;
  top: -70px;
  right: -80px;
}

.card::after {
  width: 160px;
  height: 160px;
  bottom: -60px;
  left: -50px;
}

.content {
  position: relative;
  z-index: 1;
  display: grid;
  gap: 24px;
  justify-items: center;
}

.art {
  width: clamp(220px, 35vw, 280px);
  height: clamp(180px, 28vw, 260px);
  margin: 0 auto;
  background-position: center;
  background-size: contain;
  background-repeat: no-repeat;
}

.helper {
  margin: 0;
  font-size: 15px;
  color: rgba(99, 102, 241, 0.8);
  font-weight: 600;
  letter-spacing: 0.4px;
}

.code {
  margin: 0;
  font-size: clamp(64px, 10vw, 96px);
  line-height: 1;
  font-weight: 800;
  letter-spacing: 6px;
  color: #1e3a8a;
}

.title {
  margin: 0;
  font-size: clamp(22px, 3vw, 30px);
  font-weight: 700;
}

.subtitle {
  margin: 0;
  font-size: clamp(16px, 2.5vw, 18px);
  color: #627093;
  max-width: 520px;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 14px 26px;
  border-radius: 999px;
  text-decoration: none;
  background: linear-gradient(135deg, #6366f1, #4338ca);
  color: #fff;
  font-weight: 600;
  font-size: 16px;
  box-shadow: 0 20px 40px -22px rgba(79, 70, 229, 0.75);
  transition: transform 0.18s ease, box-shadow 0.18s ease;
}

.btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 24px 48px -24px rgba(79, 70, 229, 0.8);
}

.btn:focus-visible {
  outline: 2px solid rgba(79, 70, 229, 0.6);
  outline-offset: 4px;
}

@media (max-width: 600px) {
  .card {
    padding: 32px 20px;
    border-radius: 24px;
  }

  .subtitle {
    font-size: 15px;
  }

  .btn {
    width: 100%;
  }
}
</style>
