<template>
  <div class="gemini-floating" :class="{ 'is-open': isOpen }">
    <transition name="chat-panel">
      <section v-if="isOpen" class="chat-panel" aria-live="polite">
        <div class="panel-ornament panel-ornament--a"></div>
        <div class="panel-ornament panel-ornament--b"></div>
        <header class="chat-header">
          <div class="chat-meta">
            <p class="chat-title">Tickora AI Assistant</p>
            <p class="chat-subtitle">Partner untuk Tiket, Task, dan Project kamu</p>
            <div class="chat-status">
              <span class="status-dot" aria-hidden="true"></span>
              <div class="status-text">
                <span class="status-state">Online</span>
                <span class="status-caption">Ready to serve</span>
              </div>
            </div>
          </div>
          <button type="button" class="icon-btn" aria-label="Tutup" @click="toggle">
            <span class="material-icons">close</span>
          </button>
        </header>

        <div ref="messagesRef" class="chat-body">
          <div v-for="message in messages" :key="message.id" class="chat-bubble" :class="`chat-bubble--${message.role}`">
            <p>{{ message.text }}</p>
          </div>
          <div v-if="sending" class="chat-bubble chat-bubble--assistant">
            <div class="typing">
              <span></span><span></span><span></span>
            </div>
          </div>
        </div>

        <footer class="chat-footer">
          <form class="chat-form" @submit.prevent="sendMessage">
            <input
              ref="inputRef"
              v-model="draft"
              type="text"
              class="chat-input"
              :placeholder="sending ? 'Menunggu balasan...' : 'Tanyakan apa saja tentang tiket, task, project kamu'"
              :disabled="sending"
            />
            <button type="submit" class="send-btn" :disabled="sending || !draft.trim()">
              <span class="material-icons">send</span>
            </button>
          </form>
          <p v-if="error" class="chat-error">{{ error }}</p>
        </footer>
      </section>
    </transition>

    <button type="button" class="fab" :aria-expanded="String(isOpen)" aria-label="Buka TMS AI" @click="toggle">
      <span class="material-icons" aria-hidden="true">smart_toy</span>
      <span class="fab-label">Tickora</span>
    </button>
  </div>
</template>

<script setup>
import axios from 'axios';
import { computed, nextTick, ref, watch } from 'vue';

const props = defineProps({
  endpoint: {
    type: String,
    required: true,
  },
  snapshot: {
    type: Object,
    default: () => ({}),
  },
});

const isOpen = ref(false);
const draft = ref('');
const sending = ref(false);
const error = ref('');
const messagesRef = ref(null);
const inputRef = ref(null);

let idSeed = 0;
const nextId = () => `msg-${Date.now()}-${idSeed++}`;

const messages = ref([
  {
    id: nextId(),
    role: 'assistant',
    text: 'Halo! Saya Tickora AI dan siap membantu pertanyaan seputar Ticket Management System. Apa yang bisa saya bantu?',
  },
]);

const historyPayload = computed(() =>
  messages.value
    .map(({ role, text }) => ({ role, text }))
    .filter(entry => !!entry.text)
);

const toggle = () => {
  isOpen.value = !isOpen.value;
};

const scrollToBottom = async () => {
  await nextTick();
  if (messagesRef.value) {
    messagesRef.value.scrollTop = messagesRef.value.scrollHeight;
  }
};

const sendMessage = async () => {
  if (!draft.value.trim() || sending.value) {
    return;
  }

  const content = draft.value.trim();
  draft.value = '';
  error.value = '';

  messages.value.push({ id: nextId(), role: 'user', text: content });
  await scrollToBottom();

  sending.value = true;

  try {
    const { data } = await axios.post(props.endpoint, {
      message: content,
      history: historyPayload.value.slice(-6),
    });

    messages.value.push({
      id: nextId(),
      role: 'assistant',
      text: data?.reply || 'Maaf, saya tidak bisa menjawab itu sekarang.',
    });
  } catch (err) {
    error.value = 'Tidak dapat terhubung ke TMS AI. Coba lagi nanti.';
  } finally {
    sending.value = false;
    scrollToBottom();
  }
};

watch(isOpen, val => {
  if (val) {
    nextTick(() => {
      inputRef.value?.focus();
      scrollToBottom();
    });
  }
});

watch(
  () => messages.value.length,
  () => {
    scrollToBottom();
  }
);
</script>

<style scoped>
.gemini-floating {
  position: fixed;
  bottom: 1.5rem;
  right: 1.5rem;
  z-index: 70;
  font-family: inherit;
  --gem-panel-bg: radial-gradient(circle at 25% 15%, rgba(94, 234, 212, 0.12), transparent 28%), radial-gradient(circle at 75% 8%, rgba(99, 102, 241, 0.16), transparent 35%), rgba(255, 255, 255, 0.98);
  --gem-panel-text: #0a1021;
  --gem-panel-shadow: 0 32px 95px -46px rgba(12, 18, 43, 0.55);
  --gem-panel-border: rgba(15, 23, 42, 0.07);
  --gem-chip-label: rgba(15, 23, 42, 0.62);
  --gem-chip-value: #0b1224;
  --gem-bubble-assistant-bg: rgba(15, 23, 42, 0.045);
  --gem-bubble-user-bg: linear-gradient(135deg, #22c55e, #2563eb);
  --gem-typing-dot: rgba(15, 23, 42, 0.4);
  --gem-footer-border: rgba(15, 23, 42, 0.07);
  --gem-input-border: rgba(15, 23, 42, 0.1);
  --gem-input-bg: rgba(246, 248, 252, 0.95);
  --gem-error: #dc2626;
  --gem-fab-bg: linear-gradient(150deg, #2563eb, #8b5cf6 45%, #22d3ee);
  --gem-fab-shadow: 0 18px 32px -18px rgba(37, 99, 235, 0.45);
  --gem-fab-shadow-hover: 0 20px 36px -18px rgba(37, 99, 235, 0.55);
  --gem-subtle-gradient: radial-gradient(circle at 30% 10%, rgba(79, 70, 229, 0.16), transparent 38%), radial-gradient(circle at 70% 0%, rgba(14, 165, 233, 0.18), transparent 30%), linear-gradient(145deg, rgba(255, 255, 255, 0.6), rgba(244, 247, 255, 0.7));
  --gem-divider: rgba(15, 23, 42, 0.05);
  --gem-input-placeholder: rgba(15, 23, 42, 0.5);
  --gem-status-bg: linear-gradient(120deg, rgba(16, 185, 129, 0.14), rgba(14, 165, 233, 0.14));
  --gem-status-border: rgba(37, 99, 235, 0.14);
  --gem-status-text: #0a1021;
  --gem-ornament-opacity: 0.78;
  --gem-bubble-border: rgba(15, 23, 42, 0.05);
  --gem-body-overlay: linear-gradient(180deg, transparent, rgba(0, 0, 0, 0.02));
}

:global(.app-shell--dark .gemini-floating),
:global(.dark .gemini-floating),
:global(.dark-mode .gemini-floating),
:global(body.dark .gemini-floating) {
  --gem-panel-bg: linear-gradient(185deg, #080d18 0%, #0c1121 55%, #080d18 100%);
  --gem-panel-text: #e8ecf5;
  --gem-panel-shadow: 0 36px 110px -44px rgba(0, 0, 0, 0.9);
  --gem-panel-border: rgba(148, 163, 184, 0.16);
  --gem-chip-label: rgba(226, 232, 240, 0.58);
  --gem-chip-value: #f8fafc;
  --gem-bubble-assistant-bg: rgba(255, 255, 255, 0.035);
  --gem-typing-dot: rgba(255, 255, 255, 0.7);
  --gem-footer-border: rgba(148, 163, 184, 0.1);
  --gem-input-border: rgba(148, 163, 184, 0.16);
  --gem-input-bg: rgba(14, 18, 30, 0.82);
  --gem-error: #fca5a5;
  --gem-fab-shadow: 0 18px 34px -18px rgba(99, 102, 241, 0.55);
  --gem-fab-shadow-hover: 0 22px 40px -18px rgba(99, 102, 241, 0.65);
  --gem-subtle-gradient: radial-gradient(circle at 20% 12%, rgba(59, 130, 246, 0.22), transparent 30%), radial-gradient(circle at 82% 6%, rgba(124, 58, 237, 0.25), transparent 28%), linear-gradient(180deg, rgba(7, 10, 20, 0.94), rgba(5, 7, 14, 0.96));
  --gem-divider: rgba(148, 163, 184, 0.12);
  --gem-input-placeholder: rgba(226, 232, 240, 0.65);
  --gem-status-bg: linear-gradient(120deg, rgba(16, 185, 129, 0.18), rgba(14, 165, 233, 0.2));
  --gem-status-border: rgba(59, 130, 246, 0.28);
  --gem-status-text: #f3f6ff;
  --gem-ornament-opacity: 0.14;
  --gem-bubble-border: rgba(255, 255, 255, 0.12);
  --gem-body-overlay: linear-gradient(180deg, transparent, rgba(0, 0, 0, 0.16));
}

.fab {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  border-radius: 999px;
  background: var(--gem-fab-bg);
  color: #fff;
  border: none;
  box-shadow: var(--gem-fab-shadow);
  padding: 0.85rem 1.4rem;
  font-weight: 600;
  cursor: pointer;
  transition: transform 0.1s ease, box-shadow 0.1s ease, filter 0.1s ease, background 0.12s ease;
  position: relative;
  overflow: hidden;
}

.fab:hover {
  transform: translateY(-1px);
  box-shadow: var(--gem-fab-shadow-hover);
  filter: saturate(1.04);
  background: linear-gradient(145deg, #4f46e5, #7c3aed, #22d3ee);
}

.fab-label {
  font-size: 0.95rem;
}

.fab .material-icons {
  color: #ffffff;
  opacity: 0.96;
}

.fab::before,
.fab::after {
  display: none;
}

.gemini-floating.is-open .fab {
  transform: translateY(-1px) scale(0.995);
  box-shadow: var(--gem-fab-shadow-hover);
}

.chat-panel {
  width: clamp(320px, 28vw, 420px);
  height: 520px;
  border-radius: 1.5rem;
  background: var(--gem-panel-bg);
  color: var(--gem-panel-text);
  box-shadow: var(--gem-panel-shadow);
  display: flex;
  flex-direction: column;
  margin-bottom: 1rem;
  backdrop-filter: blur(10px);
  border: 1px solid var(--gem-panel-border);
  position: relative;
  overflow: hidden;
  -webkit-font-smoothing: antialiased;
  isolation: isolate;
  transform-origin: bottom right;
}

.chat-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.25rem 0.75rem;
  gap: 0.5rem;
}

.chat-meta {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.chat-title {
  font-size: 1.05rem;
  font-weight: 700;
  margin-bottom: 0.15rem;
}

.chat-subtitle {
  font-size: 0.8rem;
  color: var(--gem-chip-label);
  letter-spacing: 0.01em;
}

.chat-status {
  display: inline-flex;
  align-items: center;
  gap: 0.55rem;
  padding: 0.4rem 0.7rem;
  background: var(--gem-status-bg);
  color: var(--gem-status-text);
  border-radius: 999px;
  border: 1px solid var(--gem-status-border);
  width: fit-content;
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.12);
}

.status-dot {
  width: 10px;
  height: 10px;
  border-radius: 999px;
  background: linear-gradient(145deg, #22c55e, #0ea5e9);
  box-shadow: 0 0 0 6px rgba(34, 197, 94, 0.14);
  position: relative;
}

.status-dot::after {
  content: "";
  position: absolute;
  inset: -4px;
  border-radius: inherit;
  border: 1px solid rgba(34, 197, 94, 0.35);
  opacity: 0.9;
}

.status-text {
  display: flex;
  flex-direction: column;
  line-height: 1.05;
}

.status-state {
  font-size: 0.82rem;
  font-weight: 700;
}

.status-caption {
  font-size: 0.68rem;
  opacity: 0.75;
}

.panel-ornament {
  position: absolute;
  inset: 0;
  pointer-events: none;
  background: var(--gem-subtle-gradient);
  opacity: var(--gem-ornament-opacity);
  z-index: 0;
}

.panel-ornament--b {
  filter: blur(32px);
  opacity: calc(var(--gem-ornament-opacity) - 0.25);
}

.icon-btn {
  width: 40px;
  height: 40px;
  border-radius: 999px;
  background: var(--gem-bubble-assistant-bg);
  border: none;
  color: inherit;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.chat-body {
  flex: 1 1 auto;
  padding: 1rem 1.1rem;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 0.7rem;
  position: relative;
  background: var(--gem-body-overlay);
  scroll-behavior: smooth;
  mask-image: linear-gradient(to bottom, transparent 0%, black 10%, black 90%, transparent 100%);
}

.chat-header,
.chat-body,
.chat-footer {
  position: relative;
  z-index: 1;
}

.chat-bubble {
  max-width: 88%;
  font-size: 0.92rem;
  line-height: 1.35;
  padding: 0.7rem 0.95rem;
  border-radius: 1rem;
  box-shadow: 0 10px 22px -16px rgba(15, 23, 42, 0.15);
  color: var(--gem-panel-text);
  border: 1px solid var(--gem-bubble-border);
  backdrop-filter: blur(4px);
  position: relative;
}

.chat-bubble--assistant {
  align-self: flex-start;
  background: var(--gem-bubble-assistant-bg);
  color: var(--gem-panel-text);
}

.chat-bubble--user {
  align-self: flex-end;
  background: var(--gem-bubble-user-bg);
  color: #fff;
}

.typing {
  display: inline-flex;
  gap: 0.35rem;
}

.typing span {
  width: 0.4rem;
  height: 0.4rem;
  border-radius: 999px;
  background: var(--gem-typing-dot);
  animation: pulse 1s infinite ease-in-out;
}

.typing span:nth-child(2) {
  animation-delay: 0.15s;
}

.typing span:nth-child(3) {
  animation-delay: 0.3s;
}

@keyframes pulse {
  0%, 100% { transform: translateY(0); opacity: 0.5; }
  50% { transform: translateY(-4px); opacity: 1; }
}

.chat-footer {
  padding: 0.9rem 1rem 1.1rem;
  border-top: 1px solid var(--gem-footer-border);
  background: linear-gradient(180deg, transparent, rgba(0, 0, 0, 0.02));
}

.chat-form {
  display: flex;
  gap: 0.55rem;
}

.chat-input {
  flex: 1 1 auto;
  border: 1px solid var(--gem-input-border);
  border-radius: 999px;
  background: var(--gem-input-bg);
  color: var(--gem-panel-text);
  padding: 0.7rem 1.05rem;
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.6);
  transition: border-color 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
}

.chat-input::placeholder {
  color: var(--gem-input-placeholder);
}

.chat-input:focus {
  outline: none;
  border-color: rgba(37, 99, 235, 0.55);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
  background: rgba(255, 255, 255, 0.96);
}

.send-btn {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  border: none;
  background: linear-gradient(145deg, #2563eb, #7c3aed);
  color: #fff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  box-shadow: 0 15px 25px -16px rgba(79, 70, 229, 0.65);
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.send-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 18px 28px -16px rgba(79, 70, 229, 0.75);
}

.chat-error {
  margin-top: 0.35rem;
  font-size: 0.78rem;
  color: var(--gem-error);
}

.chat-panel-enter-active,
.chat-panel-leave-active {
  transition: opacity 0.16s ease, transform 0.18s ease;
}

.chat-panel-enter-from,
.chat-panel-leave-to {
  opacity: 0;
  transform: translateY(6px);
}

@media (max-width: 640px) {
  .chat-panel {
    width: calc(100vw - 2rem);
    height: 70vh;
  }

  .gemini-floating {
    right: 1rem;
    left: 1rem;
  }

  .fab {
    width: 100%;
    justify-content: center;
  }
}
</style>
