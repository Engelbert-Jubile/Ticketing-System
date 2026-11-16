export const MAX_PREVIEW_BYTES = 20 * 1024 * 1024;
export const MAX_ROWS = 500;
export const MAX_COLUMNS = 50;

export function previewKind(name, mime = '') {
  const extension = name.split('.').pop().toLowerCase();
  if (extension === 'pdf' || mime === 'application/pdf') return 'pdf';
  if (['png', 'jpg', 'jpeg', 'gif', 'webp', 'bmp', 'avif', 'svg', 'ico'].includes(extension)) return 'image';
  if (['mp4', 'webm', 'ogv', 'mov'].includes(extension)) return 'video';
  if (['mp3', 'wav', 'ogg', 'm4a', 'aac', 'flac'].includes(extension)) return 'audio';
  if (['xlsx', 'docx', 'pptx'].includes(extension)) return extension;
  if (['txt', 'csv', 'tsv', 'log', 'json', 'xml', 'md', 'html', 'htm', 'css', 'js', 'yaml', 'yml'].includes(extension)) return 'text';
  return 'unsupported';
}

export function attachmentName(disposition = '') {
  const encoded = disposition.match(/filename\*=UTF-8''([^;]+)/i);
  if (encoded) {
    try { return decodeURIComponent(encoded[1]); } catch { /* Try the ASCII filename. */ }
  }
  return disposition.match(/filename="((?:\\.|[^"\\])*)"/i)?.[1]?.replace(/\\(.)/g, '$1')
    || disposition.match(/filename=([^;]+)/i)?.[1]?.trim() || '';
}

export async function readPreviewResponse(response, signal) {
  if (Number(response.headers.get('content-length')) > MAX_PREVIEW_BYTES) throw new Error('too-large');
  const reader = response.body.getReader();
  const chunks = [];
  let length = 0;
  try {
    while (true) {
      if (signal.aborted) throw new DOMException('Aborted', 'AbortError');
      const { done, value } = await reader.read();
      if (done) break;
      length += value.length;
      if (length > MAX_PREVIEW_BYTES) throw new Error('too-large');
      chunks.push(value);
    }
  } finally {
    await reader.cancel().catch(() => {});
    reader.releaseLock();
  }
  return new Blob(chunks);
}

export function columnLabel(index) {
  let label = '';
  for (let value = index + 1; value > 0; value = Math.floor((value - 1) / 26)) {
    label = String.fromCharCode(65 + (value - 1) % 26) + label;
  }
  return label;
}

const elements = (node, name) => Array.from(node.getElementsByTagNameNS('*', name));
const text = (node, name) => elements(node, name).map(item => item.textContent).join('');

export async function openOfficePreview(blob) {
  const { default: JSZip } = await import('jszip');
  const zip = await JSZip.loadAsync(await blob.arrayBuffer());
  let total = 0;
  const cache = new Map();
  const xml = async path => {
    if (cache.has(path)) return cache.get(path);
    const entry = zip.file(path);
    if (!entry) throw new Error('invalid-file');
    // Stop inflated XML before allocating large documents or ZIP bombs.
    const bytes = await new Promise((resolve, reject) => {
      const chunks = [];
      let length = 0;
      const stream = entry.internalStream('uint8array');
      stream.on('data', chunk => {
        length += chunk.length; total += chunk.length;
        if (length > 8 * 1024 * 1024 || total > 32 * 1024 * 1024) {
          stream.pause(); reject(new Error('too-large')); return;
        }
        chunks.push(chunk);
      }).on('error', reject).on('end', () => {
        const result = new Uint8Array(length);
        let offset = 0;
        for (const chunk of chunks) { result.set(chunk, offset); offset += chunk.length; }
        resolve(result);
      }).resume();
    });
    const source = new TextDecoder().decode(bytes);
    if (/<!DOCTYPE|<!ENTITY/i.test(source)) throw new Error('invalid-file');
    const document = new DOMParser().parseFromString(source, 'application/xml');
    if (document.getElementsByTagName('parsererror').length) throw new Error('invalid-file');
    cache.set(path, document);
    return document;
  };
  return { zip, xml };
}

export async function readWorkbook(office) {
  const workbook = await office.xml('xl/workbook.xml');
  const relationships = elements(await office.xml('xl/_rels/workbook.xml.rels'), 'Relationship');
  const strings = office.zip.file('xl/sharedStrings.xml')
    ? elements(await office.xml('xl/sharedStrings.xml'), 'si').map(item => text(item, 't')) : [];
  const sheets = elements(workbook, 'sheet').slice(0, 100).map(sheet => {
    const id = sheet.getAttribute('r:id') || sheet.getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'id');
    const relation = relationships.find(item => item.getAttribute('Id') === id && item.getAttribute('TargetMode') !== 'External');
    const target = relation?.getAttribute('Target') || '';
    const path = new URL(target, 'https://office.invalid/xl/workbook.xml').pathname.slice(1);
    if (!path.startsWith('xl/') || !office.zip.file(path)) throw new Error('invalid-file');
    return { name: sheet.getAttribute('name'), path };
  });
  if (!sheets.length) throw new Error('invalid-file');
  return { sheets, strings };
}

export async function readWorksheet(office, workbook, sheet) {
  const document = await office.xml(sheet.path);
  const allRows = elements(document, 'row');
  let width = 0, truncated = allRows.length > MAX_ROWS;
  const rows = allRows.slice(0, MAX_ROWS).map((row, index) => {
    const values = [];
    for (const cell of elements(row, 'c')) {
      const letters = cell.getAttribute('r')?.match(/^[A-Z]+/i)?.[0] || 'A';
      const column = letters.toUpperCase().split('').reduce((value, char) => value * 26 + char.charCodeAt(0) - 64, 0) - 1;
      if (column >= MAX_COLUMNS) { truncated = true; continue; }
      const type = cell.getAttribute('t');
      const value = text(cell, 'v');
      values[column] = type === 's' ? (workbook.strings[Number(value)] ?? '')
        : type === 'inlineStr' ? text(cell, 't') : type === 'b' ? (value === '1' ? 'TRUE' : 'FALSE') : value;
      width = Math.max(width, column + 1);
    }
    return { number: row.getAttribute('r') || index + 1, values };
  });
  return { rows, width, truncated };
}

export async function readOfficeText(office, kind) {
  const paths = kind === 'docx' ? ['word/document.xml']
    : Object.keys(office.zip.files).filter(path => /^ppt\/slides\/slide\d+\.xml$/.test(path))
      .sort((a, b) => Number(a.match(/slide(\d+)/)[1]) - Number(b.match(/slide(\d+)/)[1])).slice(0, 100);
  if (!paths.length) throw new Error('invalid-file');
  const sections = [];
  for (const path of paths) {
    const document = await office.xml(path);
    sections.push(elements(document, 'p').map(paragraph => text(paragraph, 't')).join('\n'));
  }
  return sections.join('\n\n──────────\n\n').slice(0, 500000);
}
