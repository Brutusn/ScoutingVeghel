(function () {
  'use strict';
  var frame = document.getElementById('home-rental-calendar');
  if (!frame) return;
  window.addEventListener('message', function (event) {
    if (event.origin !== location.origin || event.source !== frame.contentWindow) return;
    if (!event.data || event.data.type !== 'scouting-calendar-height') return;
    var height = event.data.height;
    if (typeof height !== 'number' || !Number.isFinite(height) || height < 300 || height > 20000)
      return;
    frame.style.height = Math.ceil(height) + 'px';
  });
  frame.addEventListener('load', function () {
    frame.contentWindow.postMessage({ type: 'scouting-calendar-measure' }, location.origin);
  });
})();
