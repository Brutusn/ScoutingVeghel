(function () {
  var button = document.querySelector('.site-menu-toggle'),
    menu = document.getElementById('site-links');
  button.addEventListener('click', function () {
    var open = button.getAttribute('aria-expanded') !== 'true';
    button.setAttribute('aria-expanded', String(open));
    menu.classList.toggle('is-open', open);
    var icon = button.querySelector('i');
    icon.classList.toggle('fa-bars', !open);
    icon.classList.toggle('fa-times', open);
  });
  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && button.getAttribute('aria-expanded') === 'true') {
      button.setAttribute('aria-expanded', 'false');
      menu.classList.remove('is-open');
      button.focus();
    }
  });
})();
(function () {
  var nav = document.querySelector('.site-nav');
  function fitNav() {
    document.body.style.setProperty('--site-nav-height', nav.offsetHeight + 'px');
  }
  fitNav();
  if (window.ResizeObserver) {
    new ResizeObserver(fitNav).observe(nav);
  } else {
    window.addEventListener('resize', fitNav);
    document.querySelector('.site-menu-toggle').addEventListener('click', fitNav);
  }
})();
