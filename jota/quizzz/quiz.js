'use strict';

(() => {
  const button = document.getElementById('myBtn');
  function updateButton() {
    button.style.display = window.scrollY > 20 ? 'block' : 'none';
  }
  button.addEventListener('click', () => window.scrollTo(0, 0));
  window.addEventListener('scroll', updateButton, { passive: true });
  updateButton();
})();
