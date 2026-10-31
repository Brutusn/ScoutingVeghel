'use strict';

(() => {
  const picker = document.getElementById('archive-year');
  const sections = [...document.querySelectorAll('[data-year]')];
  const years = sections.map((section) => section.dataset.year);
  const older = document.getElementById('older-year');
  const newer = document.getElementById('newer-year');
  const status = document.getElementById('archive-status');

  function showYear(value) {
    const selected = years.includes(value) || value === 'all' ? value : years[0];
    // Stop playback when switching years.
    document.querySelectorAll('.video-player').forEach((frame) => {
      frame.replaceWith(frame.originalButton);
    });
    sections.forEach((section) => {
      section.hidden = selected !== 'all' && section.dataset.year !== selected;
    });
    picker.value = selected;
    const index = years.indexOf(selected);
    older.disabled = index < 0 || index === years.length - 1;
    newer.disabled = index <= 0;
    status.textContent =
      selected === 'all'
        ? `Alle ${years.length} jaargangen in beeld`
        : `Je bekijkt JOTA ${selected}`;
  }

  function chooseYear(value) {
    showYear(value);
    history.replaceState(null, '', `${location.pathname}${location.search}#jaar-${picker.value}`);
  }

  picker.disabled = false;
  picker.addEventListener('change', () => chooseYear(picker.value));
  older.addEventListener('click', () => chooseYear(years[years.indexOf(picker.value) + 1]));
  newer.addEventListener('click', () => chooseYear(years[years.indexOf(picker.value) - 1]));
  showYear(location.hash.replace('#jaar-', ''));
  window.addEventListener('hashchange', () => {
    if (location.hash.startsWith('#jaar-')) showYear(location.hash.slice(6));
  });

  document.getElementById('archive-years').addEventListener('click', (event) => {
    const button = event.target.closest('[data-video]');
    if (!button) return;
    const frame = document.createElement('iframe');
    frame.className = 'video-player';
    frame.src = `https://www.youtube-nocookie.com/embed/${button.dataset.video}?autoplay=1&rel=0`;
    frame.title = button.getAttribute('aria-label');
    frame.allow = 'autoplay; encrypted-media; picture-in-picture; fullscreen';
    frame.allowFullscreen = true;
    frame.referrerPolicy = 'strict-origin-when-cross-origin';
    frame.originalButton = button;
    button.replaceWith(frame);
    frame.focus();
  });
})();
