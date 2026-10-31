(function () {
  'use strict';
  var $jscomp = { scope: {} };
  $jscomp.defineProperty =
    'function' == typeof Object.defineProperties
      ? Object.defineProperty
      : function (a, b, c) {
          if (c.get || c.set) throw new TypeError('ES3 does not support getters and setters.');
          a != Array.prototype && a != Object.prototype && (a[b] = c.value);
        };
  $jscomp.getGlobal = function (a) {
    return 'undefined' != typeof window && window === a
      ? a
      : 'undefined' != typeof global && null != global
        ? global
        : a;
  };
  $jscomp.global = $jscomp.getGlobal(window);
  $jscomp.SYMBOL_PREFIX = 'jscomp_symbol_';
  $jscomp.initSymbol = function () {
    $jscomp.initSymbol = function () {};
    $jscomp.global.Symbol || ($jscomp.global.Symbol = $jscomp.Symbol);
  };
  $jscomp.symbolCounter_ = 0;
  $jscomp.Symbol = function (a) {
    return $jscomp.SYMBOL_PREFIX + (a || '') + $jscomp.symbolCounter_++;
  };
  $jscomp.initSymbolIterator = function () {
    $jscomp.initSymbol();
    var a = $jscomp.global.Symbol.iterator;
    a || (a = $jscomp.global.Symbol.iterator = $jscomp.global.Symbol('iterator'));
    'function' != typeof Array.prototype[a] &&
      $jscomp.defineProperty(Array.prototype, a, {
        configurable: !0,
        writable: !0,
        value: function () {
          return $jscomp.arrayIterator(this);
        },
      });
    $jscomp.initSymbolIterator = function () {};
  };
  $jscomp.arrayIterator = function (a) {
    var b = 0;
    return $jscomp.iteratorPrototype(function () {
      return b < a.length ? { done: !1, value: a[b++] } : { done: !0 };
    });
  };
  $jscomp.iteratorPrototype = function (a) {
    $jscomp.initSymbolIterator();
    a = { next: a };
    a[$jscomp.global.Symbol.iterator] = function () {
      return this;
    };
    return a;
  };
  $jscomp.makeIterator = function (a) {
    $jscomp.initSymbolIterator();
    var b = a[Symbol.iterator];
    return b ? b.call(a) : $jscomp.arrayIterator(a);
  };

  function $id(id) {
    return document.getElementById(id);
  }
  function removeClass(name) {
    Array.from(document.getElementsByClassName(name)).forEach(function (el) {
      el.classList.remove(name);
    });
  }
  function showError(a, b) {
    var c;
    c = $id('errorMsg') ? $id('errorMsg') : document.createElement('div');
    c.className = 'error-message';
    c.id = 'errorMsg';
    c.innerHTML = '<i class="fa fa-times-circle"></i> ' + a;
    b ? $id(b).appendChild(c) : console.error(a);
  }
  function removeError() {
    var a = $id('errorMsg');
    a && a.parentNode.removeChild(a);
  }
  function showSuccess(a, b) {
    showError('tmp', b);
    $id('errorMsg').innerHTML = a;
    $id('errorMsg').className = 'success-message';
  }
  $id('verhuur-form').onsubmit = function () {
    var fields = this.querySelectorAll('input,select,textarea');
    for (var i = 0; i < fields.length; i++) {
      if (!fields[i].disabled && !fields[i].checkValidity()) {
        var panel = fields[i].closest('.tabpanel');
        if (panel) {
          removeClass('tabpanel-active');
          removeClass('tab-active');
          panel.classList.add('tabpanel-active');
          document.querySelector('[data-tab="' + panel.id + '"]').classList.add('tab-active');
        }
        fields[i].reportValidity();
        return false;
      }
    }
    var a = {},
      b = /[^\s@]+@[^\s@]+\.[^\s@]+/,
      c = this.id,
      f = this.getElementsByClassName('inp');
    if (14 < VERHUUR.verhuurtijd())
      return (
        showError(
          'De duur van de optie mag maximaal 14 overnachtingen zijn. Mocht u langer willen huren, stuur dan een vraag m.b.v. het bovenstaande formulier.',
          c,
        ),
        !1
      );
    for (var f = $jscomp.makeIterator(f), d = f.next(); !d.done; d = f.next())
      ((d = d.value), (a[d.name] = d.value));
    '' !== a.groepcode
      ? '' !== a.people &&
        '' !== a.tArea &&
        (showSuccess('Aanvraag voor de optie versturen...', c),
        ajax('../php/verhuur-form.php', a, function (a, b) {
          if (a) {
            showError('Versturen mislukt. Probeer het later opnieuw.', c);
            return;
          }
          VERHUUR.reset();
          showSuccess(b, c);
        }))
      : a.name && '' !== a.name && null !== a.name && void 0 !== a.name
        ? a.contactperson &&
          '' !== a.contactperson &&
          null !== a.contactperson &&
          void 0 !== a.contactperson
          ? b.test(a.mailadr)
            ? b.test(a.mailadr) &&
              (removeError(),
              showSuccess('Aanvraag voor de optie versturen...', c),
              ajax('../php/verhuur-form.php', a, function (a, b) {
                if (a) {
                  showError('Versturen mislukt. Probeer het later opnieuw.', c);
                  return;
                }
                VERHUUR.reset();
                showSuccess(b, c);
              }))
            : showError('Vul een geldig e-mailadres in.', c)
          : showError('Wie wil deze optie op de blokhut nemen?', c)
        : showError('Waarvoor is deze optie op de blokhut?', c);
    return !1;
  };
  $id('verhuur-tabs').addEventListener('click', function (a) {
    if (!a.target.getAttribute('data-tab')) return;
    removeClass('tab-active');
    removeClass('tabpanel-active');
    a.target.classList.add('tab-active');
    $id(a.target.getAttribute('data-tab')).classList.add('tabpanel-active');
    'verhuur-stap-2' === a.target.getAttribute('data-tab') && VERHUUR.setEindDays();
  });
  $id('verhuur-goto-2').addEventListener('click', function (a) {
    a.preventDefault();
    removeClass('tab-active');
    removeClass('tabpanel-active');
    $id('verhuur-stap-2').classList.add('tabpanel-active');
    $id('verhuur-tabs').children[1].classList.add('tab-active');
    VERHUUR.setEindDays();
  });
  $id('verhuur-goto-3').addEventListener('click', function (a) {
    a.preventDefault();
    removeClass('tab-active');
    removeClass('tabpanel-active');
    $id('verhuur-stap-3').classList.add('tabpanel-active');
    $id('verhuur-tabs').children[2].classList.add('tab-active');
  });
  $id('verhuur-confirm-avail').addEventListener('click', function (a) {
    a.preventDefault();
    var b = $id('verhuur-form').id;
    a = $id('aankomst-dag').value;
    var c = $id('aankomst-maand').value,
      f = $id('aankomst-jaar').value,
      d = $id('aankomst-uur').value,
      e = $id('aankomst-minuut').value,
      g = $id('vertrek-dag').value,
      k = $id('vertrek-maand').value,
      h = $id('vertrek-jaar').value,
      n = $id('vertrek-uur').value,
      l = $id('vertrek-minuut').value;
    showSuccess('Beschikbaarheid aan het controleren...', b);
    ajax(
      '../php/ReserveringVerification.php',
      { d1: a, m1: c, y1: f, h1: d, min1: e, d2: g, m2: k, y2: h, h2: n, min2: l },
      function (a, c) {
        var d = a;
        if (!a)
          try {
            0 === JSON.parse(c).length
              ? showSuccess('Er zijn geen reserveringen gevonden tijdens de gewenste periode.', b)
              : showError(
                  'Er zijn reserveringen gevonden tijdens de gewenste periode. <b>Advies: Neem een optie en neem contact op met de beheerder.</b>',
                  b,
                );
          } catch (r) {
            (console.warn('Er is iets mis gegaan met het ophalen van de reserveringen.', c),
              (d = !0));
          }
        d && showError('Er is iets mis gegaan met het ophalen van de reserveringen.', b);
      },
    );
  });
  $id('groepcode').oninput = function () {
    var a = document.getElementsByClassName('verhuur-groepcode'),
      b = !0;
    '' !== this.value && (b = !1);
    for (var c = 0; c < a.length; c++) ((a[c].required = b), (a[c].disabled = !b));
  };
  function checkMinMax(a, b) {
    var c = a.getAttribute('min');
    a = a.getAttribute('max');
    return c < b && a > b ? b : c >= b && a > b ? c : c < b && a <= b ? a : !1;
  }
  function verhuurDateTime() {
    function a(a) {
      'number' === typeof a &&
        ((a = checkMinMax(this, +this.value + a)), !1 !== a && (this.value = a));
    }
    function b(a) {
      'number' === typeof a &&
        ((this.selectedIndex += a), -1 === this.selectedIndex && (this.selectedIndex = 0));
    }
    function c() {
      g[this.getAttribute('for')].onchange(this.getAttribute('data-plus-it') ? 1 : -1);
    }
    function f(a) {
      return 1 === a.toString().length ? '0' + a : a;
    }
    var d = {
        j: $id('aankomst-jaar'),
        m: $id('aankomst-maand'),
        d: $id('aankomst-dag'),
        uu: $id('aankomst-uur'),
        mm: $id('aankomst-minuut'),
      },
      e = {
        j: $id('vertrek-jaar'),
        m: $id('vertrek-maand'),
        d: $id('vertrek-dag'),
        uu: $id('vertrek-uur'),
        mm: $id('vertrek-minuut'),
      },
      g = {
        'aankomst-jaar': d.j,
        'aankomst-maand': d.m,
        'aankomst-dag': d.d,
        'aankomst-uur': d.uu,
        'aankomst-minuut': d.mm,
        'vertrek-jaar': e.j,
        'vertrek-maand': e.m,
        'vertrek-dag': e.d,
        'vertrek-uur': e.uu,
        'vertrek-minuut': e.mm,
      },
      k = document.getElementsByClassName('verhuur-label'),
      h = $id('EllenIkWil1DagHuren'),
      n = $id('verhuur-tabs'),
      l,
      m,
      q = {
        setEindDays: function () {
          if (!n.getAttribute('data-first-time')) {
            n.setAttribute('data-first-time', !0);
            var a = d.j.value + '-' + f(d.m.selectedIndex + 1) + '-' + f(d.d.value) + 'T12:00',
              a = new Date(a);
            h.checked
              ? ((e.uu.value = 23), (e.mm.value = 59))
              : (a.setDate(a.getDate() + 5),
                (e.uu.value = 12 > d.uu.value ? d.uu.value + 8 : d.uu.value),
                (e.mm.value = d.mm.value));
            e.j.value = a.getFullYear();
            e.m.selectedIndex = a.getMonth();
            e.d.value = a.getDate();
          }
        },
        verhuurtijd: function () {
          var a = function (a) {
              return new Date(
                a.j.value +
                  '-' +
                  f(a.m.selectedIndex + 1) +
                  '-' +
                  f(a.d.value) +
                  'T' +
                  f(a.uu.value) +
                  ':' +
                  f(a.mm.value),
              );
            },
            b = a(d);
          return (a(e) - b) / 864e5;
        },
        reset: function () {
          n.setAttribute('data-first-time', !1);
          h.checked = !1;
          for (var a in e) {
            var b = e[a];
            b.required = !h.checked;
            b.disabled = h.checked;
          }
          l = new Date();
          m = new Date();
          m.setDate(m.getDate() + 5);
          d.m.selectedIndex = l.getMonth();
          e.m.selectedIndex = m.getMonth();
          d.d.value = l.getDate();
          e.d.value = m.getDate();
          d.j.value = l.getFullYear();
          e.j.value = m.getFullYear();
          d.uu.value = e.uu.value = l.getHours();
          d.mm.value = e.mm.value = l.getMinutes();
        },
      };
    h.onchange = function () {
      var a, b, c;
      for (c in e)
        ((a = e[c]),
          (b = d[c]),
          this.checked &&
            ('SELECT' === a.nodeName
              ? (a.selectedIndex = b.selectedIndex)
              : (a.value = 'uu' === c || 'mm' === c ? ('uu' === c ? 23 : 59) : b.value)),
          (a.required = !h.checked),
          (a.disabled = h.checked));
    };
    for (var k = $jscomp.makeIterator(k), p = k.next(); !p.done; p = k.next())
      p.value.addEventListener('click', c);
    d.m.onchange = b;
    e.m.onchange = b;
    d.d.onchange = a;
    d.j.onchange = a;
    d.uu.onchange = a;
    d.mm.onchange = a;
    e.d.onchange = a;
    e.j.onchange = a;
    e.uu.onchange = a;
    e.mm.onchange = a;
    q.reset();
    return q;
  }
  var VERHUUR = verhuurDateTime();
})();
