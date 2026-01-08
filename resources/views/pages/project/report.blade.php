@extends('layouts.app')

@section('title', 'Project Reports (Legacy)')

@section('content')
<div class="legacy-redirect">
  <div class="legacy-redirect__panel">
    <h1>Halaman sudah dipindahkan</h1>
    <p>Report proyek kini tersedia pada antarmuka Inertia yang lebih baru.</p>
    <a class="legacy-redirect__link" href="{{ route('projects.report') }}">Buka versi terbaru</a>
  </div>
</div>
@endsection

@push('styles')
<style>
  .legacy-redirect {
    min-height: calc(100vh - var(--topbar-h, 72px));
    display: grid;
    place-items: center;
    padding: 3rem 1rem;
    background: linear-gradient(135deg, #eef2ff 0%, #e0f2fe 40%, #fdf2f8 100%);
  }

  .legacy-redirect__panel {
    max-width: 420px;
    width: 100%;
    border-radius: 20px;
    padding: 2rem;
    text-align: center;
    background: rgba(255, 255, 255, 0.95);
    box-shadow: 0 24px 48px rgba(37, 99, 235, 0.2);
  }

  .legacy-redirect__panel h1 {
    font-size: 1.6rem;
    margin-bottom: 1rem;
    font-weight: 700;
    color: #1f2937;
  }

  .legacy-redirect__panel p {
    margin-bottom: 1.5rem;
    color: #475569;
  }

  .legacy-redirect__link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.75rem 1.5rem;
    border-radius: 999px;
    font-weight: 600;
    color: #fff;
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    text-decoration: none;
    box-shadow: 0 16px 32px rgba(37, 99, 235, 0.25);
  }

  .legacy-redirect__link:hover {
    box-shadow: 0 20px 36px rgba(37, 99, 235, 0.32);
  }

  .dark .legacy-redirect {
    background: radial-gradient(circle at top, rgba(56, 189, 248, 0.18), transparent 55%),
      radial-gradient(circle at bottom, rgba(192, 132, 252, 0.12), transparent 60%),
      #020617;
  }

  .dark .legacy-redirect__panel {
    background: rgba(15, 23, 42, 0.9);
    box-shadow: 0 24px 48px rgba(15, 23, 42, 0.6);
    color: #e2e8f0;
  }

  .dark .legacy-redirect__panel h1 {
    color: #f8fafc;
  }

  .dark .legacy-redirect__panel p {
    color: #cbd5f5;
  }
</style>
@endpush
    justify-content: flex-end;
  }

  .report-primary-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.55rem;
    padding: 0.75rem 1.4rem;
    border-radius: 14px;
    border: none;
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 40%, #0f172a 100%);
    color: #fff;
    font-weight: 600;
    font-size: 0.95rem;
    box-shadow: 0 16px 32px rgba(37, 99, 235, 0.28);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }

  .report-primary-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 20px 36px rgba(37, 99, 235, 0.32);
  }

  .report-ghost-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.7rem 1.3rem;
    border-radius: 12px;
    border: 1px solid rgba(15, 23, 42, 0.12);
    font-weight: 500;
    color: #0f172a;
    background: rgba(255, 255, 255, 0.7);
    transition: border-color 0.2s ease, background 0.2s ease;
  }

  .report-ghost-btn:hover {
    border-color: rgba(37, 99, 235, 0.4);
    background: rgba(255, 255, 255, 0.95);
  }

  .dark .report-ghost-btn {
    border-color: rgba(148, 163, 184, 0.28);
    background: rgba(15, 23, 42, 0.7);
    color: #e2e8f0;
  }

  .dark .report-ghost-btn:hover {
    border-color: rgba(96, 165, 250, 0.55);
    background: rgba(15, 23, 42, 0.92);
  }

  .report-alert {
    padding: 1rem 1.4rem;
    border-radius: 14px;
    font-weight: 500;
    backdrop-filter: blur(8px);
  }

  .report-alert.success {
    background: rgba(34, 197, 94, 0.18);
    border: 1px solid rgba(22, 163, 74, 0.35);
    color: #166534;
  }

  .dark .report-alert.success {
    background: rgba(34, 197, 94, 0.12);
    border-color: rgba(22, 163, 74, 0.35);
    color: #bbf7d0;
  }

  .report-filter {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1rem;
    padding: 1.5rem;
    border-radius: var(--report-radius);
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(148, 163, 184, 0.25);
    box-shadow: 0 16px 28px rgba(15, 23, 42, 0.08);
    backdrop-filter: blur(16px);
  }

  .dark .report-filter {
    background: rgba(15, 23, 42, 0.68);
    border-color: rgba(30, 64, 175, 0.35);
  }

  .report-filter .field {
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
  }

  .report-filter .field label {
    font-size: 0.78rem;
    font-weight: 600;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: rgba(15, 23, 42, 0.6);
  }

  .dark .report-filter .field label {
    color: rgba(226, 232, 240, 0.6);
  }

  .field-control {
    position: relative;
    display: flex;
    align-items: center;
  }

  .field-control .icon {
    position: absolute;
    left: 1rem;
    font-size: 0.9rem;
    opacity: 0.5;
  }

  .field-control input,
  .field-control select {
    width: 100%;
    border-radius: 12px;
    border: 1px solid rgba(148, 163, 184, 0.35);
    background: rgba(255, 255, 255, 0.92);
    padding: 0.7rem 1rem 0.7rem 2.6rem;
    font-size: 0.95rem;
    color: #0f172a;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
  }

  .field-control select {
    padding-left: 1rem;
  }

  .field-control input:focus,
  .field-control select:focus {
    outline: none;
    border-color: rgba(59, 130, 246, 0.6);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.18);
  }

  .dark .field-control input,
  .dark .field-control select {
    border-color: rgba(148, 163, 184, 0.25);
    background: rgba(15, 23, 42, 0.75);
    color: #e2e8f0;
  }

  .dark .field-control input:focus,
  .dark .field-control select:focus {
    border-color: rgba(96, 165, 250, 0.55);
    box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.25);
  }

  .report-filter .field.actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: flex-end;
    gap: 0.75rem;
  }

  .report-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 1rem;
  }

  .summary-card {
    padding: 1.1rem 1.35rem;
    border-radius: var(--report-radius);
    background: rgba(255, 255, 255, 0.88);
    border: 1px solid rgba(148, 163, 184, 0.24);
    box-shadow: 0 18px 28px rgba(15, 23, 42, 0.07);
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
  }

  .summary-card span {
    font-size: 0.8rem;
    text-transform: uppercase;
    font-weight: 600;
    letter-spacing: 0.08em;
    color: rgba(15, 23, 42, 0.55);
  }

  .summary-card strong {
    font-size: 1.7rem;
    font-weight: 700;
    color: #0f172a;
  }

  .summary-card.accent {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.95), rgba(129, 140, 248, 0.85));
    border: none;
    color: #f8fafc;
  }

  .summary-card.accent span,
  .summary-card.accent strong {
    color: inherit;
  }

  .summary-card.success {
    background: linear-gradient(135deg, rgba(34, 197, 94, 0.95), rgba(22, 163, 74, 0.85));
    border: none;
    color: #f0fdf4;
  }

  .summary-card.success span,
  .summary-card.success strong {
    color: inherit;
  }

  .summary-card.neutral {
    background: rgba(15, 23, 42, 0.82);
    border: none;
    color: #f1f5f9;
  }

  .summary-card.neutral span,
  .summary-card.neutral strong {
    color: inherit;
  }

  .dark .summary-card {
    background: rgba(15, 23, 42, 0.7);
    border-color: rgba(148, 163, 184, 0.22);
    color: #e2e8f0;
  }

  .report-accordion {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }

  .accordion-card {
    background: rgba(255, 255, 255, 0.92);
    border-radius: var(--report-radius);
    border: 1px solid var(--report-border);
    box-shadow: 0 24px 48px rgba(15, 23, 42, 0.08);
    backdrop-filter: blur(18px);
    overflow: hidden;
    transition: border-color 0.25s ease, box-shadow 0.25s ease, transform 0.25s ease;
  }

  .accordion-card:hover {
    border-color: rgba(59, 130, 246, 0.32);
    box-shadow: 0 28px 56px rgba(37, 99, 235, 0.15);
    transform: translateY(-2px);
  }

  .accordion-card.is-open {
    border-color: rgba(129, 140, 248, 0.45);
    box-shadow: 0 36px 72px rgba(76, 29, 149, 0.2);
  }

  .dark .accordion-card {
    background: rgba(15, 23, 42, 0.78);
    border-color: rgba(96, 165, 250, 0.18);
    box-shadow: 0 24px 48px rgba(2, 6, 23, 0.6);
  }

  .accordion-trigger {
    width: 100%;
    border: none;
    background: transparent;
    padding: 1.6rem 1.8rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    cursor: pointer;
    gap: 1.2rem;
  }

  .trigger-meta {
    display: flex;
    align-items: center;
    gap: 1.1rem;
    text-align: left;
  }

  .trigger-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    border-radius: 14px;
    font-size: 1.4rem;
    color: #fff;
  }

  .gradient-blue {
    background: linear-gradient(135deg, #0ea5e9, #4338ca);
  }

  .gradient-green {
    background: linear-gradient(135deg, #22c55e, #0f766e);
  }

  .trigger-meta h2 {
    font-size: 1.35rem;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 0.25rem;
  }

  .trigger-meta p {
    font-size: 0.9rem;
    color: rgba(15, 23, 42, 0.58);
  }

  .dark .trigger-meta h2 {
    color: #e2e8f0;
  }

  .dark .trigger-meta p {
    color: rgba(148, 163, 184, 0.7);
  }

  .chevron {
    position: relative;
    width: 24px;
    height: 24px;
  }

  .chevron::before {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: 999px;
    border: 2px solid rgba(15, 23, 42, 0.35);
    mask: radial-gradient(circle at center, transparent 45%, #000 46%);
  }

  .chevron::after {
    content: '';
    position: absolute;
    left: 6px;
    top: 8px;
    width: 12px;
    height: 12px;
    border-bottom: 2px solid rgba(15, 23, 42, 0.7);
    border-right: 2px solid rgba(15, 23, 42, 0.7);
    transform: rotate(45deg);
    transition: transform 0.25s ease;
  }

  .accordion-card.is-open .chevron::after {
    transform: rotate(-135deg);
    top: 6px;
  }

  .dark .chevron::before {
    border-color: rgba(148, 163, 184, 0.4);
  }

  .dark .chevron::after {
    border-color: rgba(226, 232, 240, 0.7);
  }

  .accordion-panel {
    max-height: 0;
    opacity: 0;
    overflow: hidden;
    transition: max-height 0.45s ease, opacity 0.3s ease;
  }

  .accordion-card.is-open .accordion-panel {
    max-height: 5600px;
    opacity: 1;
  }

  .panel-metrics {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 0.8rem;
    padding: 0 1.8rem 1.2rem;
  }

  .panel-metrics div {
    padding: 0.9rem 1.1rem;
    border-radius: 14px;
    background: rgba(15, 23, 42, 0.05);
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    font-size: 0.85rem;
    font-weight: 500;
  }

  .panel-metrics span {
    color: rgba(15, 23, 42, 0.6);
    text-transform: uppercase;
    letter-spacing: 0.06em;
  }

  .panel-metrics strong {
    font-size: 1.2rem;
    color: #0f172a;
  }

  .dark .panel-metrics div {
    background: rgba(148, 163, 184, 0.1);
  }

  .dark .panel-metrics span {
    color: rgba(226, 232, 240, 0.6);
  }

  .dark .panel-metrics strong {
    color: #e2e8f0;
  }

  .panel-table {
    padding: 0 1.8rem 1.8rem;
  }

  .table-scroll {
    overflow-x: auto;
    border-radius: 18px;
    border: 1px solid rgba(148, 163, 184, 0.18);
    background: rgba(255, 255, 255, 0.92);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.6);
  }

  .dark .table-scroll {
    background: rgba(15, 23, 42, 0.78);
    border-color: rgba(59, 130, 246, 0.18);
  }

  .table-scroll table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 0.92rem;
  }

  .table-scroll thead th {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.12), rgba(14, 165, 233, 0.08));
    color: #0f172a;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    padding: 0.95rem 1rem;
    border-bottom: 1px solid rgba(148, 163, 184, 0.25);
  }

  .dark .table-scroll thead th {
    background: rgba(37, 99, 235, 0.18);
    color: #e2e8f0;
    border-color: rgba(96, 165, 250, 0.25);
  }

  .table-scroll tbody tr[data-report-row-toggle] {
    cursor: pointer;
    transition: background 0.2s ease;
  }

  .table-scroll tbody tr[data-report-row-toggle]:hover {
    background: rgba(59, 130, 246, 0.08);
  }

  .dark .table-scroll tbody tr[data-report-row-toggle]:hover {
    background: rgba(59, 130, 246, 0.18);
  }

  .table-scroll tbody td {
    padding: 0.9rem 1rem;
    border-bottom: 1px solid rgba(148, 163, 184, 0.15);
    vertical-align: top;
  }

  .table-scroll tbody tr:last-child td {
    border-bottom: none;
  }

  .table-scroll .title {
    font-weight: 600;
    margin-bottom: 0.25rem;
    color: #0f172a;
  }

  .table-scroll .title + span {
    font-size: 0.82rem;
    color: rgba(15, 23, 42, 0.55);
  }

  .dark .table-scroll .title {
    color: #e2e8f0;
  }

  .dark .table-scroll .title + span {
    color: rgba(148, 163, 184, 0.7);
  }

  .chip {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    border-radius: 999px;
    padding: 0.35rem 0.75rem;
    font-size: 0.78rem;
    font-weight: 600;
    background: rgba(59, 130, 246, 0.12);
    color: #1d4ed8;
    border: 1px solid rgba(59, 130, 246, 0.25);
  }

  .chip.blue {
    background: rgba(2, 132, 199, 0.16);
    border-color: rgba(14, 165, 233, 0.3);
    color: #0c4a6e;
  }

  .chip.neutral {
    background: rgba(148, 163, 184, 0.15);
    border-color: rgba(148, 163, 184, 0.28);
    color: #475569;
  }

  .badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.35rem 0.75rem;
    border-radius: 999px;
    font-size: 0.78rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    gap: 0.35rem;
  }

  .mono {
    font-family: 'Fira Code', 'JetBrains Mono', ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;
    font-size: 0.82rem;
    color: rgba(15, 23, 42, 0.7);
  }

  .dark .mono {
    color: rgba(226, 232, 240, 0.7);
  }

  .table-actions {
    display: inline-flex;
    gap: 0.75rem;
  }

  .table-actions a {
    font-size: 0.85rem;
    font-weight: 600;
    color: #2563eb;
    transition: color 0.2s ease;
  }

  .table-actions a:hover {
    color: #1e3a8a;
  }

  .dark .table-actions a {
    color: #93c5fd;
  }

  .table-detail {
    display: none;
    background: rgba(15, 23, 42, 0.04);
  }

  .table-detail.is-open {
    display: table-row;
  }

  .table-detail td {
    padding: 0;
    border: none;
    background: transparent;
  }

  .detail-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.7fr) minmax(0, 1fr);
    gap: 1.2rem;
    padding: 1.5rem;
    background: rgba(255, 255, 255, 0.92);
    border-radius: 0 0 16px 16px;
  }

  .dark .detail-grid {
    background: rgba(15, 23, 42, 0.82);
  }

  .detail-section {
    margin-bottom: 1rem;
  }

  .detail-section h3 {
    font-size: 0.95rem;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 0.5rem;
  }

  .detail-section ul {
    display: grid;
    gap: 0.35rem;
    padding-left: 1.1rem;
    font-size: 0.9rem;
    color: rgba(15, 23, 42, 0.65);
  }

  .attachments {
    list-style: none;
    padding: 0;
  }

  .attachments li a {
    color: #2563eb;
    font-weight: 600;
  }

  .attachments li a:hover {
    text-decoration: underline;
  }

  .detail-prose {
    padding: 1rem;
    border-radius: 14px;
    background: rgba(148, 163, 184, 0.12);
    max-height: 12rem;
    overflow: auto;
  }

  .detail-side {
    display: grid;
    gap: 0.75rem;
  }

  .meta-card {
    padding: 0.9rem 1rem;
    border-radius: 14px;
    background: rgba(59, 130, 246, 0.1);
    border: 1px solid rgba(59, 130, 246, 0.22);
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
  }

  .meta-card span {
    font-size: 0.78rem;
    text-transform: uppercase;
    font-weight: 600;
    color: rgba(15, 23, 42, 0.55);
  }

  .meta-card strong {
    font-size: 1rem;
    color: #0f172a;
  }

  .dark .meta-card {
    background: rgba(59, 130, 246, 0.18);
    border-color: rgba(59, 130, 246, 0.3);
  }

  .dark .meta-card span {
    color: rgba(226, 232, 240, 0.58);
  }

  .dark .meta-card strong {
    color: #e2e8f0;
  }

  .empty {
    text-align: center;
    padding: 2rem 0;
    font-weight: 500;
    color: rgba(15, 23, 42, 0.55);
  }

  .dark .empty {
    color: rgba(226, 232, 240, 0.6);
  }

  @media (max-width: 1024px) {
    .detail-grid {
      grid-template-columns: 1fr;
    }

    .report-hero {
      padding: 2rem;
    }
  }

  @media (max-width: 768px) {
    .report-surface {
      padding: 2rem 1rem 3rem;
    }

    .report-filter {
      padding: 1.2rem;
    }

    .report-primary-btn,
    .report-ghost-btn {
      width: 100%;
    }

    .report-filter .field.actions {
      justify-content: stretch;
    }
  }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  flatpickr('.flatpickr-field', {
    dateFormat: 'd/m/Y',
    allowInput: true
  });

  const accordionCards = document.querySelectorAll('.accordion-card');
  accordionCards.forEach((card, index) => {
    const trigger = card.querySelector('[data-report-section-toggle]');
    const panel = card.querySelector('.accordion-panel');
    if (!trigger || !panel) return;

    const setExpanded = (expanded) => {
      card.classList.toggle('is-open', expanded);
      trigger.setAttribute('aria-expanded', expanded);
      if (expanded) {
        panel.style.maxHeight = panel.scrollHeight + 'px';
      } else {
        panel.style.maxHeight = '0px';
      }
    };

    trigger.addEventListener('click', () => {
      const isOpen = card.classList.contains('is-open');
      accordionCards.forEach(other => {
        if (other !== card) {
          const otherTrigger = other.querySelector('[data-report-section-toggle]');
          const otherPanel = other.querySelector('.accordion-panel');
          if (otherTrigger && otherPanel) {
            other.classList.remove('is-open');
            otherTrigger.setAttribute('aria-expanded', 'false');
            otherPanel.style.maxHeight = '0px';
          }
        }
      });
      setExpanded(!isOpen);
    });

    trigger.addEventListener('keydown', (event) => {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        trigger.click();
      }
    });

    if (index === 0) {
      setExpanded(true);
    }
  });

  const detailRows = document.querySelectorAll('[data-report-row-toggle]');
  detailRows.forEach((row) => {
    row.addEventListener('click', () => toggleDetail(row));
    row.addEventListener('keydown', (event) => {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        toggleDetail(row);
      }
    });
  });

  function toggleDetail(trigger) {
    const key = trigger.getAttribute('data-report-row-toggle');
    if (!key) return;

    const detail = document.querySelector('[data-report-row="' + key + '"]');
    if (!detail) return;

    const tbody = trigger.closest('tbody');
    if (tbody) {
      tbody.querySelectorAll('.table-detail.is-open').forEach((openRow) => {
        const openKey = openRow.getAttribute('data-report-row');
        if (openKey !== key) {
          openRow.classList.remove('is-open');
          const opener = tbody.querySelector('[data-report-row-toggle="' + openKey + '"]');
          if (opener) {
            opener.setAttribute('aria-expanded', 'false');
          }
        }
      });
    }

    const isOpen = detail.classList.contains('is-open');
    detail.classList.toggle('is-open', !isOpen);
    trigger.setAttribute('aria-expanded', (!isOpen).toString());
  }
});
</script>
@endpush
