<template>
  <div class="portal">
    <section class="portal-hero" aria-labelledby="portal-heading">
      <div class="portal-hero__content">
        <p class="portal-hero__label">{{ t('portal.title') }}</p>
        <h1 id="portal-heading">{{ greeting }}</h1>
        <p class="portal-hero__prompt">{{ t('portal.prompt') }}</p>
      </div>
      <div class="portal-hero__art" aria-hidden="true">
        <span class="portal-hero__orbit"></span>
        <span class="portal-hero__orbit portal-hero__orbit--outer"></span>
        <span class="portal-hero__spark"></span>
        <span class="portal-hero__spark portal-hero__spark--small"></span>
      </div>
    </section>

    <nav class="portal-grid" :aria-label="t('portal.title')">
      <Link
        v-for="workspace in workspaces"
        :key="workspace.key"
        :href="resolveRoute(workspace.route, { locale: page.props.locale })"
        class="portal-card"
        :class="`portal-card--${workspace.key}`"
      >
        <div class="portal-card__art" aria-hidden="true">
          <span class="portal-card__ring"></span>
          <span class="portal-card__tile portal-card__tile--back"></span>
          <span class="portal-card__tile portal-card__tile--front">
            <span class="material-icons">{{ workspace.icon }}</span>
          </span>
        </div>
        <div class="portal-card__body">
          <h2>{{ t(`portal.${workspace.key}`) }}</h2>
          <p>{{ t(`portal.${workspace.key}Description`) }}</p>
          <span class="portal-card__action">
            {{ t('portal.open', { module: t(`portal.${workspace.key}`) }) }}
            <span class="material-icons" aria-hidden="true">arrow_forward</span>
          </span>
        </div>
      </Link>
    </nav>
  </div>
</template>

<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import { useI18n } from '../i18n'
import resolveRoute from '../utils/resolveRoute'

const page = usePage()
const { t } = useI18n()
const greeting = computed(() => {
  const user = page.props.auth?.user
  const name = user?.display_name || user?.name || user?.username
  return name ? t('portal.greeting', { name }) : t('portal.greetingWithoutName')
})

const workspaces = [
  { key: 'ticket', route: 'tickets.report', icon: 'confirmation_number' },
  { key: 'task', route: 'tasks.report', icon: 'task_alt' },
  { key: 'project', route: 'projects.report', icon: 'folder' },
]
</script>

<style scoped>
.portal {
  max-width: 72rem;
  margin: 0 auto;
  padding: clamp(0.5rem, 3vw, 3rem) 0 3rem;
}

.portal-hero {
  position: relative;
  isolation: isolate;
  overflow: hidden;
  padding: clamp(2rem, 5.5vw, 4.75rem) clamp(1.5rem, 5vw, 4.25rem);
  padding-bottom: clamp(4.75rem, 9vw, 7.25rem);
  border-radius: clamp(1.5rem, 3vw, 2.5rem);
  color: #fff;
  background: radial-gradient(ellipse at 95% 20%, #145f7b, transparent 65%),
    linear-gradient(115deg, #0e1c3a, #15375c);
}

.portal-hero__content { position: relative; z-index: 1; }

.portal-hero__label {
  margin-bottom: 1rem;
  color: #a1e7e3;
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.14em;
  text-transform: uppercase;
}

.portal-hero h1 {
  max-width: 20ch;
  overflow-wrap: anywhere;
  font-size: clamp(2.125rem, 5vw, 4.25rem);
  font-weight: 750;
  letter-spacing: -0.045em;
  line-height: 1.06;
}

.portal-hero__prompt {
  margin-top: 1.1rem;
  color: #d5e8fc;
  font-size: clamp(0.95rem, 1.6vw, 1.125rem);
}

.portal-hero__art {
  position: absolute;
  inset: 0 0 0 46%;
  background: radial-gradient(ellipse at 78% 42%, #4fe1d744, transparent 59%);
  pointer-events: none;
}

.portal-hero__orbit {
  position: absolute;
  top: 48%;
  left: 66%;
  width: 90%;
  aspect-ratio: 1.75;
  border: 1px solid #96ede473;
  border-radius: 50%;
  transform: translate(-50%, -50%) rotate(-28deg);
}

.portal-hero__orbit--outer { width: 125%; border-color: #96ede43d; }

.portal-hero__spark {
  position: absolute;
  top: 27%;
  right: 18%;
  width: 0.7rem;
  aspect-ratio: 1;
  border-radius: 50%;
  background: #a2fbf0;
  box-shadow: 0 0 1.75rem #82fff8;
}

.portal-hero__spark--small { top: 68%; right: 63%; width: 0.38rem; }

.portal-grid {
  position: relative;
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: clamp(0.85rem, 2vw, 1.4rem);
  margin: -2.5rem clamp(0.75rem, 3vw, 2rem) 0;
}

.portal-card {
  --ink: #1d47a4;
  --accent: #4077f3;
  --wash: #e8f0ff;
  --delay: 0ms;
  display: flex;
  min-width: 0;
  flex-direction: column;
  overflow: hidden;
  border: 1px solid #dce5f0;
  border-radius: 1.5rem;
  background: #fff;
  color: #14233d;
  box-shadow: 0 14px 38px -26px #243d64aa;
  text-decoration: none;
  animation: portal-arrive 460ms ease-out var(--delay) backwards;
  transition: transform 230ms ease, border-color 230ms ease, box-shadow 230ms ease;
}

.portal-card--task {
  --ink: #087064;
  --accent: #148d7d;
  --wash: #dcf7ed;
  --delay: 75ms;
}

.portal-card--project {
  --ink: #a04419;
  --accent: #cd662a;
  --wash: #fff0e2;
  --delay: 150ms;
}

.portal-card:is(:hover, :focus-visible) {
  transform: translateY(-0.4rem);
  border-color: var(--accent);
  box-shadow: 0 20px 42px -25px var(--accent);
}

.portal-card:focus-visible { outline: 3px solid var(--accent); outline-offset: 4px; }
.portal-card:is(:active, [data-loading]) { transform: translateY(-0.1rem) scale(0.98); }

.portal-card__art {
  display: grid;
  place-items: center;
  position: relative;
  overflow: hidden;
  min-height: clamp(7rem, 14vw, 10rem);
  background: radial-gradient(ellipse at 50% 105%, color-mix(in srgb, var(--accent) 34%, var(--wash)), transparent 74%), var(--wash);
}

.portal-card__ring {
  position: absolute;
  width: 13rem;
  aspect-ratio: 1;
  border: 1px solid color-mix(in srgb, var(--accent) 23%, transparent);
  border-radius: 50%;
  box-shadow: 0 0 0 1.5rem color-mix(in srgb, var(--accent) 7%, transparent);
  transition: transform 350ms ease;
}

.portal-card__tile {
  display: grid;
  place-items: center;
  grid-area: 1 / 1;
  position: relative;
  width: clamp(3.5rem, 7vw, 4.75rem);
  aspect-ratio: 1;
  border-radius: 1.25rem;
  transition: transform 350ms cubic-bezier(0.22, 1, 0.36, 1);
}

.portal-card__tile--back {
  background: color-mix(in srgb, var(--accent) 24%, var(--wash));
  transform: translate(20%, -10%) rotate(16deg);
}

.portal-card__tile--front {
  color: #fff;
  background: linear-gradient(145deg, var(--accent), var(--ink));
  box-shadow: 0 9px 22px -8px var(--accent);
  transform: translateX(-10%) rotate(-8deg);
}

.portal-card__tile .material-icons { font-size: clamp(1.8rem, 4vw, 2.45rem); }
.portal-card:is(:hover, :focus-visible) .portal-card__ring { transform: scale(1.18); }
.portal-card:is(:hover, :focus-visible) .portal-card__tile--back { transform: translate(36%, -16%) rotate(24deg); }
.portal-card:is(:hover, :focus-visible) .portal-card__tile--front { transform: translate(-14%, -4%) rotate(1deg) scale(1.08); }

.portal-card__body {
  display: flex;
  flex: 1;
  flex-direction: column;
  padding: clamp(1rem, 3vw, 1.65rem);
}

.portal-card h2 { font-size: clamp(1.3rem, 2vw, 1.65rem); font-weight: 750; letter-spacing: -0.025em; }
.portal-card p { margin: 0.35rem 0 1.25rem; color: #4a5c73; font-size: 0.94rem; line-height: 1.55; }

.portal-card__action {
  display: flex;
  align-items: center;
  gap: 0.55rem;
  margin-top: auto;
  color: var(--ink);
  font-size: 0.9rem;
  font-weight: 700;
}

.portal-card__action .material-icons { font-size: 1.05rem; transition: transform 230ms ease; }
.portal-card:is(:hover, :focus-visible) .portal-card__action .material-icons { transform: translateX(0.25rem); }

.dark .portal-card { background: #18263b; color: #f2f7ff; border-color: #3c4d67; }
.dark .portal-card p { color: #c2cfe0; }
.dark .portal-card__action { color: var(--wash); }
.dark .portal-card__art {
  background: radial-gradient(ellipse at 50% 105%, color-mix(in srgb, var(--accent) 50%, #172438), transparent 74%), #1b2b43;
}

@keyframes portal-arrive {
  from { opacity: 0; transform: translateY(0.75rem); }
  to { opacity: 1; transform: translateY(0); }
}

@media (max-width: 639px) {
  .portal-grid { grid-template-columns: 1fr; }
  .portal-hero__art { left: 18%; opacity: 0.65; }
  .portal-card { flex-direction: row; }
  .portal-card__art { flex: 0 0 28%; }
  .portal-card__ring { width: 7rem; }
}

@media (prefers-reduced-motion: reduce) {
  .portal-card,
  .portal-card * { animation: none; transition: none; }
  .portal-card:is(:hover, :focus-visible, :active, [data-loading]) { transform: none; }
  .portal-card:is(:hover, :focus-visible) .portal-card__ring,
  .portal-card:is(:hover, :focus-visible) .portal-card__action .material-icons { transform: none; }
  .portal-card:is(:hover, :focus-visible) .portal-card__tile--back { transform: translate(20%, -10%) rotate(16deg); }
  .portal-card:is(:hover, :focus-visible) .portal-card__tile--front { transform: translateX(-10%) rotate(-8deg); }
}
</style>
