(function () {
  'use strict';
  var form = document.getElementById('mail-html-form');
  var editor = document.getElementById('mail-html');
  var preview = document.getElementById('mail-preview');
  var status = document.getElementById('mail-preview-status');
  var timer,
    controller,
    generation = 0;
  var previewObserver;
  function fitEditor() {
    editor.style.height = 'auto';
    editor.style.height = editor.scrollHeight + 2 + 'px';
  }
  function fitPreview() {
    var doc = preview.contentDocument;
    if (!doc || !doc.body) return;
    // Measure content rather than the viewport, so the preview can shrink too.
    var height = Math.ceil(doc.body.getBoundingClientRect().height);
    var style = preview.contentWindow.getComputedStyle(doc.body);
    height += parseFloat(style.marginTop) || 0;
    height += parseFloat(style.marginBottom) || 0;
    preview.style.height = Math.max(100, height + 4) + 'px';
  }
  function observePreview() {
    if (previewObserver) previewObserver.disconnect();
    var doc = preview.contentDocument;
    if (!doc || !doc.body) return;
    fitPreview();
    previewObserver = new ResizeObserver(fitPreview);
    previewObserver.observe(doc.body);
    doc.querySelectorAll('img').forEach(function (img) {
      img.addEventListener('load', fitPreview);
    });
  }
  preview.addEventListener('load', observePreview);
  window.addEventListener('resize', function () {
    fitEditor();
    fitPreview();
  });
  fitEditor();
  observePreview();
  editor.addEventListener('input', function () {
    fitEditor();
    clearTimeout(timer);
    generation += 1;
    var current = generation;
    if (controller) controller.abort();
    status.textContent = 'Voorbeeld wordt bijgewerkt…';
    timer = setTimeout(function () {
      controller = new AbortController();
      var data = new FormData(form);
      data.set('action', 'preview');
      fetch(form.getAttribute('action'), {
        method: 'POST',
        body: data,
        signal: controller.signal,
        cache: 'no-store',
      })
        .then(function (response) {
          return response.json().then(function (result) {
            if (!response.ok || result.error)
              throw new Error(result.error || 'Het voorbeeld kon niet worden bijgewerkt.');
            return result;
          });
        })
        .then(function (result) {
          if (current !== generation) return;
          preview.srcdoc = result.html;
          status.textContent = 'Onderwerp: ' + result.subject + ' · Niet opgeslagen';
        })
        .catch(function (error) {
          if (error.name === 'AbortError' || current !== generation) return;
          status.textContent = error.message + ' Het laatste geldige voorbeeld blijft zichtbaar.';
        });
    }, 300);
  });
})();
