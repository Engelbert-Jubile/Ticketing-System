const aliasMap = {
  onprogress: 'in_progress',
  'on-progress': 'in_progress',
  on_progress: 'in_progress',
  'in-progress': 'in_progress',
  inprogress: 'in_progress',
  progress: 'in_progress',
  progressing: 'in_progress',
  pending: 'in_progress',
  completed: 'done',
  complete: 'done',
  success: 'done',
  finished: 'done',
  canceled: 'cancelled',
  cancelled: 'cancelled',
  fail: 'cancelled',
  failed: 'cancelled',
  hold: 'on_hold',
  revision: 'revision',
  rev: 'revision',
  revs: 'revision',
};

const palettes = {
  status: {
    default: {
      bg: '#e5e7eb',
      text: '#374151',
      hoverBg: '#cbd0d9',
      hoverColor: '#0b1220',
      ring: 'rgba(107, 114, 128, 0.45)',
    },
    new: {
      bg: '#e7efff',
      text: '#2563eb',
      hoverBg: '#d5e4ff',
      hoverColor: '#1b4fb6',
      ring: 'rgba(59, 130, 246, 0.42)',
    },
    in_progress: {
      bg: '#ffe8cc',
      text: '#c05621',
      hoverBg: '#ffd3a1',
      hoverColor: '#9c4221',
      ring: 'rgba(251, 146, 60, 0.42)',
    },
    confirmation: {
      bg: '#f3e6ff',
      text: '#a855f7',
      hoverBg: '#e4cdff',
      hoverColor: '#7e22ce',
      ring: 'rgba(168, 85, 247, 0.42)',
    },
    revision: {
      bg: '#e6ebff',
      text: '#4f46e5',
      hoverBg: '#d6ddff',
      hoverColor: '#312e81',
      ring: 'rgba(79, 70, 229, 0.42)',
    },
    done: {
      bg: '#def7ec',
      text: '#0f9f6e',
      hoverBg: '#c8f1df',
      hoverColor: '#0b815a',
      ring: 'rgba(16, 185, 129, 0.42)',
    },
    on_hold: {
      bg: '#fde2ec',
      text: '#e11d48',
      hoverBg: '#fac8dc',
      hoverColor: '#be123c',
      ring: 'rgba(225, 29, 72, 0.42)',
    },
    cancelled: {
      bg: '#ffe1e1',
      text: '#dc2626',
      hoverBg: '#ffcfcf',
      hoverColor: '#b91c1c',
      ring: 'rgba(239, 68, 68, 0.42)',
    },
  },
  priority: {
    default: {
      bg: '#e2e8f0',
      text: '#334155',
      hoverBg: '#cbd5e1',
      hoverColor: '#0f172a',
      ring: 'rgba(148, 163, 184, 0.35)',
    },
    critical: {
      bg: '#ffe5e5',
      text: '#b91c1c',
      hoverBg: '#fecdd3',
      hoverColor: '#991b1b',
      ring: 'rgba(248, 113, 113, 0.25)',
    },
    high: {
      bg: '#fff3d9',
      text: '#b45309',
      hoverBg: '#ffebc1',
      hoverColor: '#92400e',
      ring: 'rgba(234, 179, 8, 0.25)',
    },
    normal: {
      bg: '#e6f0ff',
      text: '#1d4ed8',
      hoverBg: '#dbeafe',
      hoverColor: '#1e3a8a',
      ring: 'rgba(59, 130, 246, 0.25)',
    },
    low: {
      bg: '#e8f5ec',
      text: '#15803d',
      hoverBg: '#d1fae5',
      hoverColor: '#166534',
      ring: 'rgba(16, 185, 129, 0.22)',
    },
  },
  sla: {
    default: {
      bg: '#e2e8f0',
      text: '#334155',
      hoverBg: '#cbd5e1',
      hoverColor: '#0f172a',
      ring: 'rgba(148, 163, 184, 0.35)',
    },
    met: {
      bg: '#bbf7d0',
      text: '#166534',
      hoverBg: '#86efac',
      hoverColor: '#14532d',
      ring: 'rgba(34, 197, 94, 0.4)',
    },
    pending: {
      bg: '#f59e0b',
      text: '#7c2d12',
      hoverBg: '#d97706',
      hoverColor: '#7c2d12',
      ring: 'rgba(245, 158, 11, 0.65)',
    },
    breached: {
      bg: '#f87171',
      text: '#7f1d1d',
      hoverBg: '#ef4444',
      hoverColor: '#7f1d1d',
      ring: 'rgba(248, 113, 113, 0.65)',
    },
  },
};

export function normalizeStatusValue(value) {
  const normalized = (value ?? '').toString().trim().toLowerCase().replace(/[\s-]+/g, '_');
  return (aliasMap[normalized] ?? normalized) || 'default';
}

export function resolveStatusTheme(status, variant = 'status') {
  const palette = palettes[variant] || palettes.status;
  const key = normalizeStatusValue(status);
  const base = palette.default || palettes.status.default;
  const active = palette[key] || palette.default || palettes.status.default;

  return {
    key,
    bg: active.bg ?? base.bg,
    text: active.text ?? base.text,
    hoverBg: active.hoverBg ?? active.bg ?? base.hoverBg ?? base.bg,
    hoverColor: active.hoverColor ?? active.text ?? base.hoverColor ?? base.text,
    ring: active.ring ?? base.ring ?? 'rgba(59, 130, 246, 0.18)',
  };
}

export function statusPillProps(status, { variant = 'status', size = 'md', className = '' } = {}) {
  const theme = resolveStatusTheme(status, variant);
  const style = {
    '--pill-bg': theme.bg,
    '--pill-color': theme.text,
    '--pill-hover-bg': theme.hoverBg,
    '--pill-hover-color': theme.hoverColor,
    '--pill-ring': theme.ring,
  };

  return {
    class: ['status-pill', `status-pill--${size}`, className].filter(Boolean).join(' '),
    style,
    attrs: {
      'data-status': theme.key,
      'data-variant': variant,
    },
  };
}

export function formatStatusLabel(status, label = '') {
  const value = label || status || '';
  return value.replace(/[_-]+/g, ' ').replace(/\b\w/g, char => char.toUpperCase());
}
