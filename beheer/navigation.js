(function () {
  'use strict';
  var frame = document.getElementById('admin-content');
  var routes = {
    reserveringen: { url: 'manage.php?m=inbox', title: 'Reserveringen' },
    kalender: { url: 'manage.php?m=kalender', title: 'Kalender' },
    zoeken: { url: 'manage.php?m=search', title: 'Zoeken' },
    toevoegen: { url: 'manage.php?m=toevoegen', title: 'Toevoegen' },
    maillog: { url: 'maillog.php', title: 'Maillog' },
    mailsjablonen: { url: 'mailsjablonen.php', title: 'Mailsjablonen' },
    accounts: { url: 'accounts.php', title: 'Rechtengroepen' },
    mfa: { url: 'mfa.php', title: 'Tweestapsverificatie' },
    onderhoud: { url: 'onderhoud.php', title: 'Onderhoud' },
    'eigen-groepen': { url: 'eigen-groepen.php', title: 'Eigen groepen' },
    uitloggen: { url: 'logout.php', title: 'Uitloggen' },
  };
  var pending = '';
  function updateAccess() {
    return fetch('session.php', { cache: 'no-store' })
      .then(function (response) {
        if (!response.ok) throw new Error('session');
        return response.json();
      })
      .then(function (state) {
        document.querySelector('nav').hidden = !state.loggedIn;
        document.querySelectorAll('nav [data-route]').forEach(function (link) {
          var module = link.dataset.route;
          link.hidden =
            module !== 'mfa' &&
            module !== 'uitloggen' &&
            !(state.modules || []).includes(module) &&
            !(module === 'mailsjablonen' && (state.modules || []).includes('contact-sjablonen'));
        });
        if (state.loggedIn) {
          var selected = document.querySelector('nav [data-route="' + readRoute().name + '"]');
          if (selected && selected.hidden) {
            var first = document.querySelector('nav [data-route]:not([hidden])');
            history.replaceState(null, '', '#' + (first ? first.dataset.route : 'mfa'));
            render();
          }
        }
      })
      .catch(function () {
        document.querySelector('nav').hidden = true;
      });
  }
  updateAccess();
  function readRoute() {
    var value = location.hash.slice(1).split('?');
    var requested = value[0] === 'agenda' ? 'kalender' : value[0];
    var name = Object.prototype.hasOwnProperty.call(routes, requested)
      ? requested
      : 'reserveringen';
    var query = new URLSearchParams(value[1] || '');
    var year =
      name === 'kalender' && /^\d{4}$/.test(query.get('jaar') || '') ? query.get('jaar') : '';
    var start =
      name === 'kalender' &&
      /^(19\d{2}|20\d{2}|21\d{2}|2200)-(0[1-9]|1[0-2])$/.test(query.get('from') || '')
        ? query.get('from')
        : '';
    var id =
      name === 'reserveringen' && /^[1-9]\d{0,9}$/.test(query.get('i') || '') ? query.get('i') : '';
    return { name: name, year: start ? '' : year, start: start, id: id };
  }
  function hashFor(route) {
    return (
      '#' +
      route.name +
      (route.id
        ? '?i=' + route.id
        : route.start
          ? '?from=' + route.start
          : route.year
            ? '?jaar=' + route.year
            : '')
    );
  }
  function urlFor(route) {
    var url = new URL(routes[route.name].url, location.href);
    if (route.id) url.searchParams.set('i', route.id);
    if (route.start) url.searchParams.set('from', route.start);
    else if (route.year) url.searchParams.set('y', route.year);
    return url;
  }
  function mark(route) {
    document.title = routes[route.name].title + ' | Scouting Veghel beheer';
    document.querySelectorAll('nav a[data-route]').forEach(function (link) {
      if (link.dataset.route === route.name) link.setAttribute('aria-current', 'page');
      else link.removeAttribute('aria-current');
    });
  }
  function render() {
    var route = readRoute();
    var hash = hashFor(route);
    if (location.hash !== hash) history.replaceState(null, '', hash);
    mark(route);
    var url = urlFor(route).href;
    try {
      if (frame.contentWindow.location.href === url || pending === url) return;
      pending = url;
      // Replacing iframe navigation avoids adding a second browser-history entry.
      frame.contentWindow.location.replace(url);
    } catch (error) {
      pending = '';
      frame.src = url;
    }
  }
  document.querySelectorAll('nav a[data-route]').forEach(function (link) {
    link.href = '#' + link.dataset.route;
    link.removeAttribute('target');
    link.addEventListener('click', function (event) {
      if (event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey)
        return;
      event.preventDefault();
      var hash = '#' + link.dataset.route;
      if (location.hash !== hash) history.pushState(null, '', hash);
      render();
    });
  });
  frame.addEventListener('load', function () {
    pending = '';
    updateAccess();
    try {
      var url = new URL(frame.contentWindow.location.href);
      if (url.origin !== location.origin || url.pathname === '/beheer/index.html') return;
      var route = null;
      Object.keys(routes).some(function (name) {
        var expected = new URL(routes[name].url, location.href);
        if (
          url.pathname === expected.pathname &&
          url.searchParams.get('m') === expected.searchParams.get('m')
        ) {
          route = {
            name: name,
            id:
              name === 'reserveringen' && /^[1-9]\d{0,9}$/.test(url.searchParams.get('i') || '')
                ? url.searchParams.get('i')
                : '',
            year:
              name === 'kalender' && /^\d{4}$/.test(url.searchParams.get('y') || '')
                ? url.searchParams.get('y')
                : '',
            start:
              name === 'kalender' &&
              /^(19\d{2}|20\d{2}|21\d{2}|2200)-(0[1-9]|1[0-2])$/.test(
                url.searchParams.get('from') || '',
              )
                ? url.searchParams.get('from')
                : '',
          };
          return true;
        }
        return false;
      });
      var login = frame.contentDocument.querySelector('form[action="/beheer/manage.php?m=inbox"]');
      var expired = frame.contentDocument.querySelector(
        'a[href="/beheer/manage.php"][target="_top"]',
      );
      if (!login && expired) {
        // Authenticate inside the shell while retaining the requested page address.
        frame.contentWindow.location.replace(new URL('manage.php?m=inbox', location.href).href);
        return;
      }
      if (login) route = readRoute();
      if (route) {
        // Also preserve a year chosen inside the agenda workspace.
        if (location.hash !== hashFor(route)) history.replaceState(null, '', hashFor(route));
        mark(route);
        if (login) {
          var input = frame.contentDocument.createElement('input');
          input.type = 'hidden';
          input.name = 'beheer_return';
          input.value = route.name;
          login.appendChild(input);
          if (route.id) {
            var booking = frame.contentDocument.createElement('input');
            booking.type = 'hidden';
            booking.name = 'beheer_return_id';
            booking.value = route.id;
            login.appendChild(booking);
          }
        }
      }
    } catch (error) {
      /* Another browsing context must not interrupt the shell. */
    }
  });
  window.addEventListener('popstate', render);
  window.addEventListener('hashchange', render);
  render();
})();
