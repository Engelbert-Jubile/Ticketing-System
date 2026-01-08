import Chart from 'chart.js/auto';

/* Helpers */
const read = id => {
  const el = document.getElementById(id);
  if (!el) return { el: null, labels: [], values: [], period: '' };
  let labels = [],
    values = [];
  try {
    labels = JSON.parse(el.dataset.labels || '[]');
  } catch {
    labels = [];
  }
  try {
    values = JSON.parse(el.dataset.values || '[]');
  } catch {
    values = [];
  }
  return { el, labels, values: values.map(v => +v || 0), period: el.dataset.period || '' };
};
const readDual = id => {
  const el = document.getElementById(id);
  if (!el) return { el: null, labels: [], done: [], progress: [], period: '' };
  let labels = [],
    done = [],
    progress = [];
  try {
    labels = JSON.parse(el.dataset.labels || '[]');
  } catch {
    labels = [];
  }
  try {
    done = JSON.parse(el.dataset.valuesDone || '[]');
  } catch {
    done = [];
  }
  try {
    progress = JSON.parse(el.dataset.valuesProgress || '[]');
  } catch {
    progress = [];
  }
  return {
    el,
    labels,
    done: done.map(n => +n || 0),
    progress: progress.map(n => +n || 0),
    period: el.dataset.period || ''
  };
};

// --- FIX mojibake "Ã¢â‚¬â€œ/Ã¢â‚¬â€" jadi en-dash "â€“"
const normalizePeriod = (s = '') => s.replace(/Ã¢â‚¬â€œ|Ã¢â‚¬â€|Ã¢â‚¬â€¢|Ã¢â‚¬Â¯/g, '–').trim();

const theme = () => ({
  isDark: document.documentElement.classList.contains('dark'),
  text: document.documentElement.classList.contains('dark') ? '#e5e7eb' : '#111827',
  grid: document.documentElement.classList.contains('dark') ? 'rgba(148,163,184,.25)' : '#e5e7eb'
});
const hexToRGBA = (h, a = 0.28) => {
  h = h.replace('#', '');
  const b =
    h.length === 3
      ? h
          .split('')
          .map(x => x + x)
          .join('')
      : h;
  const r = parseInt(b.slice(0, 2), 16),
    g = parseInt(b.slice(2, 4), 16),
    bl = parseInt(b.slice(4, 6), 16);
  return `rgba(${r},${g},${bl},${a})`;
};
const niceMax = a => {
  const m = Math.max(0, ...(a || []).map(n => +n || 0));
  return m <= 5 ? 5 : Math.ceil((m * 1.1) / 5) * 5;
};
const sum = a => (a || []).reduce((x, y) => x + (+y || 0), 0);
const noData = {
  id: 'noData',
  afterDraw(c) {
    const datasets = Array.isArray(c.data?.datasets) ? c.data.datasets : [];
    const hasData = datasets.some(ds => (ds?.data || []).some(n => +n > 0));
    if (hasData) return;
    const area = c.chartArea;
    if (!area) return;
    const { ctx } = c;
    const { width: w, height: h } = area;
    ctx.save();
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillStyle = '#9ca3af';
    ctx.font = '500 13px system-ui';
    ctx.fillText('No data', w / 2, h / 2);
    ctx.restore();
  }
};

// Mini HTML legend (ikon-only)
function makeMiniLegend(chart) {
  try {
    const root = chart.canvas.closest('.chart-card') || chart.canvas.parentElement;
    if (!root) return;
    root.querySelector('.mini-legend')?.remove();
    const box = document.createElement('div');
    box.className = 'mini-legend';
    const items = [];
    chart.data.datasets.forEach((ds, i) => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'ml-item';
      const color = Array.isArray(ds.borderColor)
        ? ds.borderColor[0]
        : ds.borderColor || ds.backgroundColor || '#9ca3af';
      const bg = Array.isArray(ds.backgroundColor) ? ds.backgroundColor[0] : ds.backgroundColor || color;
      btn.style.setProperty('--ml-color', color);
      btn.style.setProperty('--ml-bg', bg);
      if (ds.label) btn.title = ds.label;
      btn.addEventListener('click', e => {
        e.preventDefault();
        const vis = chart.isDatasetVisible(i);
        chart.setDatasetVisibility(i, !vis);
        chart.update();
        updateStates();
      });
      box.appendChild(btn);
      items.push(btn);
    });
    function updateStates() {
      items.forEach((el, i) => el.classList.toggle('is-off', !chart.isDatasetVisible(i)));
    }
    chart.$miniLegend = { el: box, updateStates };
    root.appendChild(box);
    updateStates();
  } catch (_) {}
}

(() => {
  if (typeof document === 'undefined') {
    return;
  }
  const t = theme();
  Chart.defaults.color = t.text;
  Chart.defaults.borderColor = t.grid;
  Chart.defaults.plugins.legend.position = 'top';
  Chart.defaults.responsive = true;
  Chart.defaults.maintainAspectRatio = false;
  Chart.defaults.resizeDelay = 120;
  Chart.defaults.font = { family: 'system-ui,-apple-system,"Segoe UI",Roboto,Arial', weight: 600, size: 12 };
  Chart.defaults.devicePixelRatio = Math.max(2, window.devicePixelRatio || 1);
})();
const BRAND_BLUE = '#2563eb',
  BRAND_EMERALD = '#10b981';
const charts = new Map();

const removeMiniLegend = chart => {
  try {
    chart?.$miniLegend?.el?.remove();
  } catch (_) {}
};

const disposeChart = (chart, canvas, removeFromMap = true) => {
  if (!chart) return;
  removeMiniLegend(chart);
  try {
    chart.destroy();
  } catch (_) {}
  if (removeFromMap && canvas) {
    charts.delete(canvas);
  }
};

const cleanupCharts = () => {
  charts.forEach((chart, canvas) => {
    if (!canvas || !canvas.isConnected || !canvas.ownerDocument) {
      disposeChart(chart, canvas);
    }
  });
};

const ro = typeof ResizeObserver !== 'undefined'
  ? new ResizeObserver(entries => {
      cleanupCharts();
      for (const entry of entries) {
        const canvas = entry.target.querySelector('canvas');
        if (!canvas || !canvas.isConnected) continue;
        const chart = charts.get(canvas);
        if (chart && canvas.ownerDocument) {
          chart.resize();
        }
      }
    })
  : {
      observe() {},
      disconnect() {},
    };

function build() {
  cleanupCharts();
  const t = theme();

  // Tickets by Status
  (function () {
    const d = read('ticketsChart');
    if (!d.el) return;
    const border = (d.labels || []).map(l => {
      const k = (l || '').toLowerCase();
      if (k.includes('new')) return BRAND_BLUE;
      if (k.includes('progress')) return '#f59e0b'; // amber
      if (k.includes('confirmation')) return '#4f46e5'; // indigo
      if (k.includes('revision')) return '#f43f5e'; // rose
      if (k.includes('done') || k.includes('complete')) return BRAND_EMERALD;
      return '#6b7280';
    });
    const bg = border.map(c => hexToRGBA(c, 0.28));
    const chart = new Chart(d.el, {
      type: 'bar',
      data: {
        labels: d.labels,
        datasets: [
          { data: d.values, backgroundColor: bg, borderColor: border, borderWidth: 1.2, hoverBackgroundColor: border }
        ]
      },
      options: {
        indexAxis: 'y',
        plugins: { legend: { display: false } },
        scales: {
          x: {
            beginAtZero: true,
            suggestedMax: niceMax(d.values),
            ticks: { color: t.text, stepSize: 1, precision: 0 },
            grid: { color: t.grid }
          },
          y: { ticks: { color: t.text }, grid: { color: t.grid } }
        }
      },
      plugins: [noData]
    });
    charts.set(d.el, chart);
    const hint = document.getElementById('ticketsHint');
    if (hint) hint.textContent = `Total tickets: ${sum(d.values)}`;
    const card = d.el.closest('.chart-card');
    if (card) {
      ro.observe(card);
    }
  })();

  // Task Report (monthly)
  (function () {
    const d = readDual('tasksReportChart');
    if (!d.el) return;
    const doneC = BRAND_EMERALD,
      progC = '#f59e0b';
    const chart = new Chart(d.el, {
      type: 'bar',
      data: {
        labels: d.labels,
        datasets: [
          {
            label: 'Completed',
            data: d.done,
            backgroundColor: hexToRGBA(doneC, 0.28),
            borderColor: doneC,
            borderWidth: 1.2,
            hoverBackgroundColor: doneC
          },
          {
            label: 'In Progress',
            data: d.progress,
            backgroundColor: hexToRGBA(progC, 0.28),
            borderColor: progC,
            borderWidth: 1.2,
            hoverBackgroundColor: progC
          }
        ]
      },
      options: {
        plugins: { legend: { display: false } },
        scales: {
          y: {
            beginAtZero: true,
            suggestedMax: niceMax([...d.done, ...d.progress]),
            ticks: { color: t.text, stepSize: 1, precision: 0 },
            grid: { color: t.grid }
          },
          x: { ticks: { color: t.text }, grid: { color: t.grid } }
        },
        interaction: { mode: 'index', intersect: false }
      },
      plugins: [noData]
    });
    charts.set(d.el, chart);
    makeMiniLegend(chart);
    const wh = document.getElementById('tasksReportHint');
    if (wh)
      wh.innerHTML = `<span class="k">Completed:</span> <span class="v">${sum(d.done)}</span>\n<span class="k">In Progress:</span> <span class="v">${sum(d.progress)}</span>\n<span class="k">Periode:</span> <span class="v">${normalizePeriod(d.period) || '-'}</span>`;
    const card = d.el.closest('.chart-card');
    if (card) {
      ro.observe(card);
    }
  })();

  // Tasks by Status
  (function () {
    const d = read('tasksStatusChart_top');
    if (!d.el) return;
    const map = l => {
      const k = String(l || '').toLowerCase();
      if (k.includes('new')) return BRAND_BLUE;
      if (k.includes('progress')) return '#f59e0b';
      if (k.includes('confirmation')) return '#4f46e5';
      if (k.includes('revision')) return '#f43f5e';
      if (k.includes('done') || k.includes('complete')) return BRAND_EMERALD;
      return '#6b7280';
    };
    const border = (d.labels || []).map(map),
      bg = border.map(c => hexToRGBA(c, 0.28));
    const chart = new Chart(d.el, {
      type: 'bar',
      data: {
        labels: d.labels,
        datasets: [
          { data: d.values, backgroundColor: bg, borderColor: border, borderWidth: 1.2, hoverBackgroundColor: border }
        ]
      },
      options: {
        indexAxis: 'y',
        plugins: { legend: { display: false } },
        scales: {
          x: {
            beginAtZero: true,
            suggestedMax: niceMax(d.values),
            ticks: { color: t.text, stepSize: 1, precision: 0 },
            grid: { color: t.grid }
          },
          y: { ticks: { color: t.text }, grid: { color: t.grid } }
        }
      },
      plugins: [noData]
    });
    charts.set(d.el, chart);
    const hint = document.getElementById('taskStatusHint_top');
    if (hint) hint.textContent = `Total tasks: ${sum(d.values)}`;
    const card = d.el.closest('.chart-card');
    if (card) {
      ro.observe(card);
    }
  })();

  // Projects by Status (bar)
  (function () {
    const d = read('projectsChart');
    if (!d.el) return;
    const border = (d.labels || []).map(l => {
      const k = (l || '').toLowerCase();
      if (k.includes('done') || k.includes('complete')) return BRAND_EMERALD;
      if (k.includes('progress')) return '#f59e0b';
      if (k.includes('confirmation')) return '#4f46e5';
      if (k.includes('new')) return BRAND_BLUE;
      if (k.includes('revision')) return '#f43f5e';
      return '#6b7280';
    });
    const bg = border.map(c => hexToRGBA(c, 0.28));
    const chart = new Chart(d.el, {
      type: 'bar',
      data: { labels: d.labels, datasets: [{ data: d.values, backgroundColor: bg, borderColor: border, borderWidth: 1.2, hoverBackgroundColor: border }] },
      options: {
        indexAxis: 'y',
        plugins: { legend: { display: false } },
        scales: {
          x: {
            beginAtZero: true,
            suggestedMax: niceMax(d.values),
            ticks: { color: theme().text, stepSize: 1, precision: 0 },
            grid: { color: theme().grid }
          },
          y: { ticks: { color: theme().text }, grid: { color: theme().grid } }
        }
      },
      plugins: [noData]
    });
    charts.set(d.el, chart);
    const hint = document.getElementById('projectsHint');
    if (hint) hint.textContent = `Total projects: ${sum(d.values)}`;
    const card = d.el.closest('.chart-card');
    if (card) {
      ro.observe(card);
    }
  })();

  // Projects Created
  (function () {
    const d = read('projectsCreatedChart');
    if (!d.el) return;
    const border = Array(d.values.length).fill(BRAND_BLUE),
      bg = border.map(c => hexToRGBA(c, 0.28));
    const chart = new Chart(d.el, {
      type: 'bar',
      data: {
        labels: d.labels,
        datasets: [
          { data: d.values, backgroundColor: bg, borderColor: border, borderWidth: 1.2, hoverBackgroundColor: border }
        ]
      },
      options: {
        plugins: { legend: { display: false } },
        scales: {
          y: {
            beginAtZero: true,
            suggestedMax: niceMax(d.values),
            ticks: { color: theme().text, stepSize: 1, precision: 0 },
            grid: { color: theme().grid }
          },
          x: { ticks: { color: theme().text }, grid: { color: theme().grid } }
        },
        interaction: { mode: 'index', intersect: false }
      },
      plugins: [noData]
    });
    charts.set(d.el, chart);
    const ph = document.getElementById('projectsCreatedHint');
    if (ph)
      ph.innerHTML = `<span class="k">Total Created:</span> <span class="v">${sum(d.values)}</span>\n<span class="k">Periode:</span> <span class="v">${normalizePeriod(d.period) || '-'}</span>`;
    const card = d.el.closest('.chart-card');
    if (card) {
      ro.observe(card);
    }
  })();

  // Project Report
  (function () {
    const d = readDual('projectsReportChart');
    if (!d.el) return;
    const doneC = BRAND_EMERALD,
      progC = '#f59e0b';
    const chart = new Chart(d.el, {
      type: 'bar',
      data: {
        labels: d.labels,
        datasets: [
          {
            label: 'Completed',
            data: d.done,
            backgroundColor: hexToRGBA(doneC, 0.28),
            borderColor: doneC,
            borderWidth: 1.2,
            hoverBackgroundColor: doneC
          },
          {
            label: 'In Progress',
            data: d.progress,
            backgroundColor: hexToRGBA(progC, 0.28),
            borderColor: progC,
            borderWidth: 1.2,
            hoverBackgroundColor: progC
          }
        ]
      },
      options: {
        plugins: { legend: { display: false } },
        scales: {
          y: {
            beginAtZero: true,
            suggestedMax: niceMax([...d.done, ...d.progress]),
            ticks: { color: theme().text, stepSize: 1, precision: 0 },
            grid: { color: theme().grid }
          },
          x: { ticks: { color: theme().text }, grid: { color: theme().grid } }
        },
        interaction: { mode: 'index', intersect: false }
      },
      plugins: [noData]
    });
    charts.set(d.el, chart);
    makeMiniLegend(chart);
    const hr = document.getElementById('projectsReportHint');
    if (hr)
      hr.innerHTML = `<span class="k">Completed:</span> <span class="v">${sum(d.done)}</span>\n<span class="k">In Progress:</span> <span class="v">${sum(d.progress)}</span>\n<span class="k">Periode:</span> <span class="v">${normalizePeriod(d.period) || '-'}</span>`;
    const card = d.el.closest('.chart-card');
    if (card) {
      ro.observe(card);
    }
  })();
}

function destroy() {
  ro.disconnect?.();
  charts.forEach((chart, canvas) => disposeChart(chart, canvas, false));
  charts.clear();
}

function waitForFonts() {
  if (typeof document === 'undefined' || !document.fonts) {
    return Promise.resolve();
  }
  return document.fonts.ready.catch(() => {});
}

let themeObserver = null;

export function initDashboardCharts() {
  if (typeof document === 'undefined') {
    return Promise.resolve();
  }
  return waitForFonts().then(() => {
    destroy();
    build();
    if (!themeObserver && typeof MutationObserver !== 'undefined') {
      themeObserver = new MutationObserver(() => {
        destroy();
        build();
      });
      themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
    }
  });
}

export function destroyDashboardCharts() {
  destroy();
  if (themeObserver) {
    themeObserver.disconnect();
    themeObserver = null;
  }
}

if (typeof window !== 'undefined' && typeof document !== 'undefined') {
  window.initDashboardCharts = initDashboardCharts;
  window.destroyDashboardCharts = destroyDashboardCharts;
  if (document.getElementById('ticketsChart')) {
    initDashboardCharts();
  }

  if (!window.__dashboardChartsCleanupRegistered) {
    window.__dashboardChartsCleanupRegistered = true;
    const navCleanup = () => destroyDashboardCharts();
    window.addEventListener('beforeunload', navCleanup);
    document.addEventListener('inertia:before', navCleanup);
    document.addEventListener('turbo:before-cache', navCleanup);
    document.addEventListener('livewire:navigating', navCleanup);
  }
}

