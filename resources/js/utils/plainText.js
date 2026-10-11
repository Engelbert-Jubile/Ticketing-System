// Parse in an inert document and render the result through Vue's escaped text
// interpolation. Stored editor HTML must never be injected into the live DOM.
export function plainText(value) {
  if (!value) return '';
  const doc = new DOMParser().parseFromString(String(value), 'text/html');
  doc.querySelectorAll('script, style, iframe, object, template').forEach(node => node.remove());
  doc.querySelectorAll('br').forEach(node => node.replaceWith('\n'));
  doc.querySelectorAll('p, div, li, h1, h2, h3, h4, blockquote, tr').forEach(node => node.append('\n'));
  return (doc.body.textContent || '').replace(/\u00a0/g, ' ').replace(/\n{3,}/g, '\n\n').trim();
}
