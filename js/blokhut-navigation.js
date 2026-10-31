(function () {
  'use strict';
  var button = document.querySelector('.site-menu-toggle');
  var menu = document.getElementById('site-links');
  function setOpen(open) {
    button.setAttribute('aria-expanded', String(open));
    menu.classList.toggle('is-open', open);
    var icon = button.querySelector('i');
    icon.classList.toggle('fa-bars', !open);
    icon.classList.toggle('fa-times', open);
  }
  button.addEventListener('click', function () {
    setOpen(button.getAttribute('aria-expanded') !== 'true');
  });
  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && button.getAttribute('aria-expanded') === 'true') {
      setOpen(false);
      button.focus();
    }
  });
})();
