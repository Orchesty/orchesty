// Opens a stylised audit report in a new browser tab and triggers the
// system print dialog. The user can then "Save as PDF" — no server-side
// PDF service required.
//
// The opened tab is a self-contained HTML document: it loads Tailwind from
// the CDN (matching the Demo template) and triggers window.print() once
// the styles are applied. The same renderer output (auditReportRenderer)
// drops straight into <body> here.

import { escapeHtml } from './auditReportRenderer'

export const printReport = (innerHtml: string, title = 'Trace Audit Report'): void => {
  // NB: do NOT pass `noopener` here — modern browsers return `null` for the
  // window reference when `noopener` is set, which would silently break
  // `document.write`. We explicitly need the handle to write the printable
  // document into the new tab, so we omit it. The new tab loads only our
  // own content + the Tailwind CDN, so opener access is not a meaningful
  // attack surface.
  const win = window.open('about:blank', '_blank')
  if (!win) {
    console.warn('printReport: pop-up blocked — allow pop-ups to export PDF.')
    return
  }
  // Defensive: drop opener after the write so the new tab cannot reach back.
  try {
    win.opener = null
  } catch {
    // some browsers throw when assigning to opener — non-fatal.
  }
  win.document.open()
  win.document.write(buildPrintableDoc(innerHtml, title))
  win.document.close()
}

const buildPrintableDoc = (inner: string, title: string): string => `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>${escapeHtml(title)}</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    // Force class-based dark mode and never apply the "dark" class so the
    // printable artefact stays light — the rendered report carries dark:*
    // variants for the in-app modal, which must not leak into print.
    tailwind.config = { darkMode: 'class' };
  </script>
  <style>
    @page { size: A4; margin: 16mm; }
    html, body {
      background: #ffffff;
      color: #111827;
      color-scheme: light;
    }
    body {
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }
    pre { white-space: pre-wrap; word-break: break-word; }
    /* Hide collapsible chrome on paper — show all checkpoints. */
    @media print {
      details { break-inside: avoid; }
      details > summary { list-style: none; }
      details:not([open]) > *:not(summary) { display: block !important; }
    }
  </style>
</head>
<body class="bg-white text-gray-900 text-sm">
  <div class="max-w-4xl mx-auto px-10 py-8">${inner}</div>
  <script>
    (function () {
      var fired = false;
      function go() {
        if (fired) return;
        fired = true;
        // Open every <details> so the printout shows full audit context.
        document.querySelectorAll('details').forEach(function (d) { d.open = true; });
        window.focus();
        // Small delay lets Tailwind CDN finish styling before print preview.
        setTimeout(function () { window.print(); }, 200);
      }
      if (document.readyState === 'complete') {
        setTimeout(go, 50);
      } else {
        window.addEventListener('load', function () { setTimeout(go, 50); });
      }
    })();
  </script>
</body>
</html>`
