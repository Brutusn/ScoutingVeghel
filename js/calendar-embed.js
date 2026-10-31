(function () {
  'use strict';
  if (window.parent === window) {
    location.replace('index.html#verhuur');
    return;
  }
  if (new URLSearchParams(location.search).get('embed') !== '1') return;
  document.documentElement.classList.add('calendar-embedded');
  document.addEventListener('DOMContentLoaded', function () {
    var content = document.getElementById('calendar-content');
    function measure() {
      window.parent.postMessage(
        {
          type: 'scouting-calendar-height',
          height: Math.max(300, Math.ceil(content.getBoundingClientRect().height) + 8),
        },
        location.origin,
      );
    }
    if (window.ResizeObserver) new ResizeObserver(measure).observe(content);
    window.addEventListener('resize', measure);
    window.addEventListener('load', measure);
    window.addEventListener('message', function (event) {
      if (
        event.origin === location.origin &&
        event.source === window.parent &&
        event.data &&
        event.data.type === 'scouting-calendar-measure'
      )
        measure();
    });
    document.querySelectorAll('a[href="index.html#contact"]').forEach(function (link) {
      link.target = '_top';
    });
    measure();
  });
})();
