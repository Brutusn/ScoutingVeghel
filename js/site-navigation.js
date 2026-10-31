(function () {
  'use strict';
  const button = document.querySelector('.site-menu-toggle');
  const menu = document.getElementById('site-links');
  const navigation = document.querySelector('.site-nav');
  if (!button || !menu) return;

  function setOpen(open) {
    button.setAttribute('aria-expanded', String(open));
    menu.classList.toggle('is-open', open);
    const icon = button.querySelector('i');
    icon.classList.toggle('fa-bars', !open);
    icon.classList.toggle('fa-times', open);
  }

  button.addEventListener('click', () => setOpen(button.getAttribute('aria-expanded') !== 'true'));
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && button.getAttribute('aria-expanded') === 'true') {
      setOpen(false);
      button.focus();
    }
  });

  // The calendar uses the measured height to keep its content below the menu.
  if (!navigation) return;
  function fitNavigation() {
    document.body.style.setProperty('--site-nav-height', navigation.offsetHeight + 'px');
  }
  fitNavigation();
  if (window.ResizeObserver) {
    new ResizeObserver(fitNavigation).observe(navigation);
  } else {
    window.addEventListener('resize', fitNavigation);
    button.addEventListener('click', fitNavigation);
  }
})();
