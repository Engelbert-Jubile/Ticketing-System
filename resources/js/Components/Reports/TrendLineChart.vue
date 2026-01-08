<template>
  <canvas ref="canvasRef" class="block h-full w-full"></canvas>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'

const props = defineProps({
  labels: { type: Array, default: () => [] },
  datasets: { type: Array, default: () => [] },
})

const canvasRef = ref(null)
const isDark = ref(false)
let chartInstance = null
let ChartModule = null
let themeObserver = null

const detectIsDark = () =>
  document.documentElement.classList.contains('dark') || document.body.classList.contains('dark')

const syncTheme = () => {
  const next = detectIsDark()
  if (isDark.value !== next) {
    isDark.value = next
  }
}

const attachThemeObserver = () => {
  syncTheme()
  themeObserver = new MutationObserver(syncTheme)
  themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })
}

const buildChart = async () => {
  if (!canvasRef.value) return

  if (!ChartModule) {
    const module = await import('chart.js/auto')
    ChartModule = module.default
  }

  if (chartInstance) {
    chartInstance.destroy()
    chartInstance = null
  }

  const context = canvasRef.value.getContext?.('2d')
  if (!context) return

  const palette = isDark.value
    ? {
        text: '#e2e8f0',
        subtext: '#cbd5e1',
        grid: 'rgba(148, 163, 184, 0.25)',
        tooltipBg: 'rgba(15, 23, 42, 0.9)',
        tooltipBorder: 'rgba(148, 163, 184, 0.35)',
      }
    : {
        text: '#0f172a',
        subtext: '#475569',
        grid: 'rgba(148, 163, 184, 0.2)',
        tooltipBg: '#ffffff',
        tooltipBorder: 'rgba(148, 163, 184, 0.35)',
      }

  chartInstance = new ChartModule(context, {
    type: 'line',
    data: {
      labels: props.labels,
      datasets: props.datasets.map(dataset => ({
        tension: dataset.tension ?? 0.38,
        pointRadius: dataset.pointRadius ?? 2.5,
        pointHoverRadius: dataset.pointHoverRadius ?? 4,
        borderWidth: dataset.borderWidth ?? 2,
        fill: dataset.fill ?? true,
        ...dataset,
      })),
    },
    options: {
      maintainAspectRatio: false,
      responsive: true,
      interaction: {
        intersect: false,
        mode: 'index',
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            precision: 0,
            color: palette.text,
            callback: value => value.toLocaleString('id-ID'),
          },
          grid: {
            color: palette.grid,
            drawBorder: false,
          },
        },
        x: {
          ticks: {
            color: palette.text,
            autoSkip: true,
          },
          grid: {
            color: palette.grid,
            drawBorder: false,
          },
        },
      },
      plugins: {
        legend: {
          position: 'bottom',
          labels: {
            usePointStyle: true,
            padding: 16,
            color: palette.text,
          },
        },
        tooltip: {
          backgroundColor: palette.tooltipBg,
          titleColor: palette.text,
          bodyColor: palette.text,
          borderColor: palette.tooltipBorder,
          borderWidth: 1,
          callbacks: {
            label: context => {
              const label = context.dataset?.label ?? ''
              const value = context.parsed?.y ?? 0
              return `${label}: ${value.toLocaleString('id-ID')}`
            },
          },
        },
        title: {
          color: palette.text,
        },
        subtitle: {
          color: palette.subtext,
        },
      },
    },
  })
}

onMounted(() => {
  attachThemeObserver()
  buildChart()
})

watch(
  () => [props.labels, props.datasets, isDark.value],
  async () => {
    await buildChart()
  },
  { deep: true }
)

onBeforeUnmount(() => {
  themeObserver?.disconnect()
  if (chartInstance) {
    chartInstance.destroy()
    chartInstance = null
  }
})
</script>
