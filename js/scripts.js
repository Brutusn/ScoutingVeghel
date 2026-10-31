'use strict';

// Navigation stays aligned with the existing sections and sticky header.
const navigation = document.getElementById('navigatie');
const menu = document.getElementById('menu-links');
const menuButton = document.getElementById('hb-menu-btn-click');
const navigationStart = document.getElementById('home-navigation-start');
const sectionLinks = [...menu.querySelectorAll('[data-link]')];
let scrollPending = false;

function updateNavigation() {
  navigation.classList.toggle('nav-sticky-top', navigationStart.getBoundingClientRect().top <= 0);
  const offset = navigation.getBoundingClientRect().height + 2;
  for (const link of sectionLinks) {
    const section = document.getElementById(link.dataset.link);
    const box = section.getBoundingClientRect();
    link.classList.toggle('menu-active', box.top <= offset && box.bottom > offset);
  }
  scrollPending = false;
}

function requestNavigationUpdate() {
  if (scrollPending) return;
  scrollPending = true;
  requestAnimationFrame(updateNavigation);
}

function scrollToSection(id) {
  const target = document.getElementById(id);
  if (!target) return;
  window.scrollTo(
    0,
    Math.max(0, target.getBoundingClientRect().top + window.pageYOffset - navigation.offsetHeight),
  );
  requestNavigationUpdate();
}

function setMenuOpen(open) {
  menu.classList.toggle('hb-menu-open', open);
  menuButton.classList.toggle('hb-menu-btn-open', open);
  menuButton.setAttribute('aria-expanded', String(open));
  menuButton.setAttribute('aria-label', open ? 'Menu sluiten' : 'Menu openen');
  if (open && !navigation.classList.contains('nav-sticky-top')) {
    window.scrollTo(
      0,
      Math.max(0, navigationStart.getBoundingClientRect().top + window.pageYOffset),
    );
    requestNavigationUpdate();
  }
}

menuButton.addEventListener('click', () =>
  setMenuOpen(menuButton.getAttribute('aria-expanded') !== 'true'),
);
menu.addEventListener('click', (event) => {
  const item = event.target.closest('[data-link]');
  if (!item) return;
  event.preventDefault();
  setMenuOpen(false);
  history.replaceState(null, '', '#' + item.dataset.link);
  scrollToSection(item.dataset.link);
});
document.getElementById('arrowDown').addEventListener('click', () => scrollToSection('groepen'));
document.getElementById('sv-logo').addEventListener('click', () => {
  scrollToSection('landing-page');
  window.location.hash = '';
});
document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape' && menu.classList.contains('hb-menu-open')) {
    setMenuOpen(false);
    menuButton.focus();
  }
});
document.addEventListener('click', (event) => {
  if (!navigation.contains(event.target)) setMenuOpen(false);
});
window.addEventListener('scroll', requestNavigationUpdate, { passive: true });
window.addEventListener('pageshow', requestNavigationUpdate);
window.addEventListener('resize', () => {
  if (window.innerWidth > 1280) setMenuOpen(false);
  requestNavigationUpdate();
});
if (location.hash) scrollToSection(location.hash.slice(1));
requestNavigationUpdate();
document.getElementById('copy-year').textContent = new Date().getFullYear();

// reCAPTCHA calls this public callback after checking the contact form.
const contactForm = document.getElementById('contact-form');
let contactBusy = false;
contactForm.addEventListener('submit', (event) => event.preventDefault());

function contactMessage(message, success) {
  let box = document.getElementById('contact-result');
  if (!box) {
    box = document.createElement('div');
    box.id = 'contact-result';
    box.setAttribute('role', 'status');
    contactForm.appendChild(box);
  }
  box.className = success ? 'success-message' : 'error-message';
  box.textContent = message;
}

function contactSubmit(token) {
  if (contactBusy) return;
  if (!contactForm.reportValidity()) {
    if (window.grecaptcha) grecaptcha.reset();
    return;
  }
  const button = contactForm.querySelector('button.g-recaptcha');
  contactBusy = true;
  button.disabled = true;
  contactMessage('Je bericht wordt verstuurd...', true);
  const data = new FormData(contactForm);
  data.set('g-recaptcha-response', token);
  const request = new XMLHttpRequest();
  request.open('POST', contactForm.action, true);
  request.timeout = 20000;
  function finish() {
    contactBusy = false;
    button.disabled = false;
    if (window.grecaptcha) grecaptcha.reset();
  }
  request.onload = () => {
    const success = request.status === 200;
    contactMessage(
      request.responseText || 'Versturen is niet gelukt. Probeer het opnieuw.',
      success,
    );
    if (success) contactForm.reset();
    finish();
  };
  request.onerror = request.ontimeout = () => {
    contactMessage(
      'Versturen is niet gelukt. Controleer je verbinding en probeer het opnieuw.',
      false,
    );
    finish();
  };
  request.send(data);
}

// Only the current occupancy summary belongs here; booking lives in calendar.html.
async function loadOccupancy() {
  const container = document.getElementById('mini-verhuur');
  const today = new Date();
  const data = new URLSearchParams({
    d: today.getDate(),
    m: today.getMonth() + 1,
    y: today.getFullYear(),
  });
  const status = document.createElement('p');
  status.className = 'verhuur-status';
  try {
    const response = await fetch('php/Reservering.php', { method: 'POST', body: data });
    if (!response.ok) throw new Error('Reserveringen niet beschikbaar');
    const bookings = await response.json();
    const list = document.createElement('ul');
    list.className = 'hide-mobile';
    function formatDate(value) {
      const date = new Date(value);
      const day = ['zo', 'ma', 'di', 'wo', 'do', 'vr', 'za'][date.getDay()];
      const month = [
        'jan',
        'feb',
        'mrt',
        'apr',
        'mei',
        'jun',
        'jul',
        'aug',
        'sep',
        'okt',
        'nov',
        'dec',
      ][date.getMonth()];
      return `${day}. ${date.getDate()} ${month}.`;
    }
    for (const booking of bookings) {
      const item = document.createElement('li');
      const from = formatDate(booking.dayFrom);
      const to = formatDate(booking.dayTo);
      item.textContent = from === to ? `Op ${from}` : `Van ${from} tot ${to}`;
      list.appendChild(item);
    }
    const current = bookings.find(
      (booking) =>
        today.getTime() >= Date.parse(booking.dayFrom) &&
        today.getTime() <= Date.parse(booking.dayTo) + 86399999,
    );
    status.textContent = current
      ? current.bySV
        ? 'De blokhut wordt gebruikt door Scouting Veghel.'
        : 'De blokhut is op dit moment bezet.'
      : 'De blokhut is op dit moment vrij.';
    container.replaceChildren(list, status);
  } catch (error) {
    status.textContent = 'Er is iets mis gegaan met het ophalen van de reserveringen.';
    container.replaceChildren(status);
    console.warn(error);
  }
}
loadOccupancy();

(function (a, b, c, f, d, e, g) {
  a.GoogleAnalyticsObject = d;
  a[d] =
    a[d] ||
    function () {
      (a[d].q = a[d].q || []).push(arguments);
    };
  a[d].l = 1 * new Date();
  e = b.createElement(c);
  g = b.getElementsByTagName(c)[0];
  e.async = 1;
  e.src = f;
  g.parentNode.insertBefore(e, g);
})(window, document, 'script', 'https://www.google-analytics.com/analytics.js', 'ga');
ga('create', 'UA-103964119-1', 'auto');
ga('send', 'pageview');
