// Yes! We use strict :)
"use strict";

// Custom functions.
function $I(elem) {
	return document.getElementById(elem);
}
function $el(type) {
    return document.createElement(type);
}
// shim layer with setTimeout fallback
window.requestAnimFrame = (function(){
    return  window.requestAnimationFrame       ||
        window.webkitRequestAnimationFrame ||
        window.mozRequestAnimationFrame    ||
        window.oRequestAnimationFrame      ||
        window.msRequestAnimationFrame     ||
        function( callback ){
            window.setTimeout(callback, 1000 / 60);
        };
})();
function removeClass(c) {
    var x = document.getElementsByClassName(c), i;
    if (x.length === 0) {
        return;
    }
    if (x.length === 1) {
        x[0].classList.remove(c);
        return;
    }
    if (x.length > 1) {
        for (i = 0; i < x.length; i++) {
            x[i].classList.remove(c);
        }
        return;
    }
}
var ajax = {
    x: function() {
        if (typeof XMLHttpRequest !== 'undefined') {
            return new XMLHttpRequest();
        }
        var versions = [
            "MSXML2.XmlHttp.5.0",
            "MSXML2.XmlHttp.4.0",
            "MSXML2.XmlHttp.3.0",
            "MSXML2.XmlHttp.2.0",
            "Microsoft.XmlHttp"
        ];

        var xhr;
        for(var i = 0; i < versions.length; i++) {
            try {
                xhr = new ActiveXObject(versions[i]);
                break;
            } catch (e) {
            }
        }
        return xhr;
    },
    send: function(url, callback, method, data, sync) {
        var x = ajax.x();
        x.open(method, url, sync);
        x.onreadystatechange = function() {
            if (x.readyState > 3) {
                if (x.status === 200) {
                    callback(x.responseText);
                } else {
                    showError("Er is iets mis gegaan. (Status: " + x.status + ")");
                }
            }
        };
        if (method === 'POST') {
            x.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
        }
        x.send(data)
    },
    get: function(url, data, callback) {
        var sync = true;
        var query = [];
        for (var key in data) {
            query.push(encodeURIComponent(key) + '=' + encodeURIComponent(data[key]));
        }
        ajax.send(url + '?' + query.join('&'), callback, 'GET', null, sync)
    },
    post: function(url, data, callback, sync) {
        var query = [];
        for (var key in data) {
            query.push(encodeURIComponent(key) + '=' + encodeURIComponent(data[key]));
        }
        ajax.send(url, callback, 'POST', query.join('&'), sync)
    }
};

function onScrollEvt () {
    function _b (e) {
        return e.getBoundingClientRect();
    }

    var nav = _b(navElem),
        gro = _b(groupElem);

    // Only do this when normal scrolling is happening..
    if (!menuClick) {
        var sta = _b(stafElem),
            act = _b(actiElem),
            con = _b(contElem),
            huu = _b(huurElem);
    }

    // Sticky the navigation!
    if (nav.top <= 0 && gro.top <= nav.height) {
        navElem.classList.add("nav-sticky-top");
    } else {
        //if (box2.top >= box.height) {
        navElem.classList.remove("nav-sticky-top");
    }

    if (gro.top <= 0) {
        $I("landing-page").style.visibility = "hidden";
    } else {
        $I("landing-page").removeAttribute("style");
    }

    if (!menuClick) {
        // remove old active class.
        removeClass("menu-active");

        // Auto set navigation on where you are...
        if (nav.height >= gro.top && nav.height < gro.bottom) {
            menu.children[0].classList.add("menu-active");
        }
        if (nav.height >= act.top && nav.height < act.bottom) {
            menu.children[1].classList.add("menu-active");
        }
        if (nav.height >= sta.top && nav.height < sta.bottom) {
            menu.children[2].classList.add("menu-active");
        }
        if (nav.height >= con.top && nav.height < con.bottom) {
            menu.children[3].classList.add("menu-active");
        }
        if (nav.height >= huu.top && nav.height < huu.bottom) {
            menu.children[4].classList.add("menu-active");
        }
    }

    // Set scrolling back to false
    scrolling = false;
}
function requestScroll () {
    if (!scrolling) {
        requestAnimFrame(onScrollEvt);
    }
    scrolling = true;
}
function setScroll(to) {
    if (window.scrollTo) {
        //console.info("window.scrollTo()");
        window.scrollTo(0, to);
        return;
    }
    if (window.pageYOffset) {
        //console.info("window.pageYOffset");
        window.pageYOffset = to;
        return;
    }
    if (document.documentElement.scrollTop) {
        //console.info("document.documentElement.scrollTop");
        document.documentElement.scrollTop = to;
        return;
    }
    if (document.body.scrollTop) {
        //console.info("document.body.scrollTop");
        document.body.scrollTop = to;
        return;
    }
    //console.warn("non of the above");
}
function scrollUp(d){
    var s = $I(d).offsetTop,
	    b,//document.body.scrollTop;
	    pos,
	    nav = $I("navigatie").clientHeight,
        scrollTo = [],
        i = 0,
        scrollTime = 50,
        range = 1,
        scrollTop = function () {
            return window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop;
        },
        animateScroll = function () {
            setScroll(pos);
            animation();
        },
        // t: current time, b: begin value, c: change in value thus end - begin, d: duration
        easeOutQuad = function (t, b, c, d) {
            return -c *(t/=d)*(t-2) + b;
        },
        inRange = function (x, y) {
            var rangeSmall = y[1] - range,
                rangeLarge = y[1] + range;

            // Scroll is positive
            if (y[2]) {
                // Normal positive version
                return x >= rangeSmall && x <= rangeLarge;
            } else {
                // Negative version
                x *= -1;
                return x >= rangeLarge && x <= rangeSmall;
            }
        };
    b = scrollTop();
    if (b < s) {
        scrollTo[0] = (s - nav) - b;
        scrollTo[1] = s - nav;
        scrollTo[2] = true;
    } else {
        scrollTo[0] = (b - (s - nav)) * -1;
        scrollTo[1] = (s - nav) * -1;
        scrollTo[2] = false;
    }

    // Start the animation.
    function animation() {
        //setTimeout(function (){},fps);
        if (i < scrollTime) {
            pos = easeOutQuad(i, b, scrollTo[0], scrollTime);
            //console.info("position", pos);
            if (!inRange(pos, scrollTo)) {
                requestAnimFrame(animateScroll);
                i++;
            } else {
                // If it's within range set is to stick to the place we want.
                setScroll(scrollTo[1]);
                menuClick = false;
                return;
            }
        }
    }
    animation();
}

function showError(msg, form) {
    var elemPres = !!$I("errorMsg"),
        elem = '';
    //console.info(elemPres, elem);
    if (!elemPres){
        elem = document.createElement('div');
    } else {
        elem = $I("errorMsg");
    }
    elem.className = "error-message";
    elem.id = "errorMsg";
    elem.innerHTML = '<i class="fa fa-times-circle"></i> ' + msg;

    if(form) {
        $I(form).appendChild(elem);
    } else {
        console.error(msg);
    }
}
function removeError(){
    if ($I("errorMsg")){
        var elem = $I("errorMsg");
        elem.parentNode.removeChild(elem);
    }
}
function showSuccess(msg, form){
    showError("tmp", form);
    $I("errorMsg").innerHTML = msg;
    $I("errorMsg").className = "success-message";
}

// Kaartje
//if ($I("mapSize")){
//	var mapSize = $I("mapSize").clientWidth;
//	$I("mapKaart").innerHTML = '<img class="info-kaart" alt="Locatie blokhut veghel" src="//maps.googleapis.com/maps/api/staticmap?center=Dorshout+29,Veghel,NL&zoom=14&size='+mapSize+'x300&scale=2&markers=color:blue%7C51.626782,5.522947&key=AIzaSyCgVa8lEEM_4SaJqtlLgl8QtBytdSSrhlM&sensor=false" />';
//}

/////// SCOUTING VEGHEL CODE!

// Sticky navigation bar :)
var navElem = $I("navigatie"),
    groupElem = $I("groepen"),
    actiElem = $I("activiteiten"),
    stafElem = $I("staf"),
    contElem = $I("contact"),
    huurElem = $I("verhuur"),
    scrollYpos = window.scrollY,
    scrolling = false,
    menu = $I("menu-links"),
    menuClick = false;

window.onscroll = function () {
    scrollYpos = window.scrollY;
    requestScroll();
};

$I("arrowDown").onclick = function () {
    menuClick = true;
    removeClass("menu-active");
    $I("menu-links").children[0].classList.add("menu-active");
    scrollUp("groepen");
};

$I("sv-logo").onclick = function () {
    removeClass("menu-active");
    scrollUp("landing-page");
};

menu.onclick = function (evt) {
    var x = evt.target.getAttribute("data-link");
    if (x) {
        menuClick = true;
        removeClass("menu-active");

        evt.target.classList.add("menu-active");
        scrollUp(x);
    }
};

// Contact form submission.
$I("contact-form").onsubmit = function () {
    var data = {},
        re = /[^\s@]+@[^\s@]+\.[^\s@]+/, // Regex for email
        form = this.id,
        inputs = this.getElementsByClassName("inp");

    // Fill data object
    for (var i = 0; i < inputs.length; i++) {
        data[inputs[i].name] = inputs[i].value;
    }

    if (!data.whoTo) {
        showError("Aan wie moet je vraag/opmerking verstuurd worden?", form);
    }
    if (!re.test(data.mailadr)){
        showError("Vul een geldig email adres in.", form);
    }
    if (re.test(data.mailadr) && data.whoTo !== ""){
        removeError();

        showSuccess("Aanmelding verstsuren...", form);

        ajax.post("../php/contact-form.php", data, function (msg) {
            // Reset form after succes.
            $I(form).reset();
            showSuccess(msg, form);
        });
    }
    // Return false to prevent default form behaviour.
    return false;
};

$I("hb-menu-btn-click").onclick = function () {
    $I("menu-links").classList.toggle("hb-menu-open");
    this.classList.toggle("hb-menu-btn-open");
};

//=======Date Picker code===============================================================================================

/*
 datepickr 3.0 - pick your date not your nose

 https://github.com/joshsalverda/datepickr

 Copyright © 2014 Josh Salverda <josh.salverda@gmail.com>
 This program is free software. It comes without any warranty, to
 the extent permitted by applicable law. You can redistribute it
 and/or modify it under the terms of the Do What The Fuck You Want
 To Public License, Version 2, as published by Sam Hocevar. See
 http://www.wtfpl.net/ for more details.
 */

var hired = [];
/*var hired = [{
    dayFrom: "Thu, 16 Mar 2015",
    dayTo: "Thu, 19 Mar 2015",
    bySV: false
},{
    dayFrom: "Thu, 6 Mar 2015",
    dayTo: "Thu, 10 Mar 2015",
    bySV: false
},{
    dayFrom: "Thu, 28 Mar 2015",
    dayTo: "Thu, 28 Mar 2015",
    bySV: true
},{
    dayFrom: "Thu, 29 Mar 2015",
    dayTo: "Thu, 29 Mar 2015",
    bySV: true
},{
    dayFrom: "Thu, 24 Mar 2015",
    dayTo: "Thu, 27 Mar 2015",
    bySV: false
},{
    dayFrom: "Thu, 30 Mar 2015",
    dayTo: "Thu, 31 Mar 2015",
    bySV: false
}];*/

// Function returns true as soon as one of the dates inside obj is true.
//var isBetweenDate = function(obj, checkDate, callback) {
//    var dayFrom, dayTo;
//    console.info(Date.parse(checkDate));
//    if (obj) {
//        for (var i = 0; i < obj.length; i++) {
//            dayFrom = Date.parse(obj[i].dayFrom);
//            dayTo = Date.parse(obj[i].dayTo);
//            console.info(dayFrom, dayTo, (checkDate <= dayTo && checkDate >= dayFrom));
//            if ((checkDate <= dayTo && checkDate >= dayFrom)) {
//                callback(obj[i].bySV ? "sv": "other");
//            }
//        }
//        callback(false);
//    }
//};
var isTodayHired = function(obj) {
    var dayFrom, dayTo, checkDate = Date.now();
    console.info(checkDate);
    if (obj) {
        for (var i = 0; i < obj.length; i++) {
            dayFrom = Date.parse(obj[i].dayFrom);
            dayTo = Date.parse(obj[i].dayTo);
            //Because of the removel of the time we need to add 24 hours minus 1 millisecond to make sure the entire day is actually hired.
            dayTo = (dayTo === dayFrom) ? dayTo + 86399999 : dayTo;
            console.info(dayFrom, dayTo, (checkDate <= dayTo && checkDate >= dayFrom));
            if ((checkDate <= dayTo && checkDate >= dayFrom)) {
                return obj[i].bySV ? "sv": "other";
            }
        }
        return false;
    }
};

//
//var datepickr = function (selector, config) {
//    'use strict';
//    var elements,
//        createInstance,
//        instances = [],
//        i;
//
//    var currentDate = new Date();
//    ajax.get("../php/Reservering.php", {m: currentDate.getMonth() + 1, y: currentDate.getFullYear()}, function (msg) {
//        hired = JSON.parse(msg);
//        //buildDays();
//    });
//
//    datepickr.prototype = datepickr.init.prototype;
//
//    createInstance = function (element) {
//        if (element._datepickr) {
//            element._datepickr.destroy();
//        }
//        element._datepickr = new datepickr.init(element, config);
//        return element._datepickr;
//    };
//
//    console.info(selector);
//
//    if (selector.nodeName) {
//        return createInstance(selector);
//    }
//
//    elements = datepickr.prototype.querySelectorAll(selector);
//
//    if (elements.length === 1) {
//        return createInstance(elements[0]);
//    }
//
//    for (i = 0; i < elements.length; i++) {
//        instances.push(createInstance(elements[i]));
//    }
//    return instances;
//};

/**
 * @constructor
 */
//datepickr.init = function (element, instanceConfig) {
//    'use strict';
//    var self = this,
//        defaultConfig = {
//            dateFormat: 'd - m - Y',
//            altFormat: null,
//            altInput: null,
//            minDate: null,
//            maxDate: null,
//            shorthandCurrentMonth: false,
//            asCalender: false
//        },
//        calendarContainer = document.createElement('div'),
//        navigationCurrentMonth = document.createElement('span'),
//        calendar = document.createElement('table'),
//        calendarBody = document.createElement('tbody'),
//        wrapperElement,
//        currentDate = new Date(),
//        wrap,
//        date,
//        formatDate,
//        monthToStr,
//        isSpecificDay,
//        buildWeekdays,
//        buildDays,
//        updateNavigationCurrentMonth,
//        buildMonthNavigation,
//        handleYearChange,
//        documentClick,
//        calendarClick,
//        buildCalendar,
//        getOpenEvent,
//        bind,
//        open,
//        close,
//        destroy,
//        init;
//
//    calendarContainer.className = 'datepickr-calendar';
//    navigationCurrentMonth.className = 'datepickr-current-month';
//    instanceConfig = instanceConfig || {};
//
//    wrap = function (autoOpen) {
//        console.info("here");
//        wrapperElement = document.createElement('div');
//        wrapperElement.className = 'datepickr-wrapper';
//        self.element.parentNode.insertBefore(wrapperElement, self.element);
//        if (autoOpen) {
//            wrapperElement.classList.add("open");
//        }
//        wrapperElement.appendChild(self.element);
//    };
//
//    date = {
//        current: {
//            year: function () {
//                return currentDate.getFullYear();
//            },
//            month: {
//                integer: function () {
//                    return currentDate.getMonth();
//                },
//                string: function (shorthand) {
//                    var month = currentDate.getMonth();
//                    return monthToStr(month, shorthand);
//                }
//            },
//            day: function () {
//                return currentDate.getDate();
//            }
//        },
//        month: {
//            string: function () {
//                return monthToStr(self.currentMonthView, self.config.shorthandCurrentMonth);
//            },
//            numDays: function () {
//                // checks to see if february is a leap year otherwise return the respective # of days
//                return self.currentMonthView === 1 && (((self.currentYearView % 4 === 0) && (self.currentYearView % 100 !== 0)) || (self.currentYearView % 400 === 0)) ? 29 : self.l10n.daysInMonth[self.currentMonthView];
//            }
//        }
//    };
//
//    formatDate = function (dateFormat, milliseconds) {
//        var formattedDate = '',
//            dateObj = new Date(milliseconds),
//            formats = {
//                d: function () {
//                    var day = formats.j();
//                    return (day < 10) ? '0' + day : day;
//                },
//                D: function () {
//                    return self.l10n.weekdays.shorthand[formats.w()];
//                },
//                j: function () {
//                    return dateObj.getDate();
//                },
//                l: function () {
//                    return self.l10n.weekdays.longhand[formats.w()];
//                },
//                w: function () {
//                    return dateObj.getDay();
//                },
//                F: function () {
//                    return monthToStr(formats.n() - 1, false);
//                },
//                m: function () {
//                    var month = formats.n();
//                    return (month < 10) ? '0' + month : month;
//                },
//                M: function () {
//                    return monthToStr(formats.n() - 1, true);
//                },
//                n: function () {
//                    return dateObj.getMonth() + 1;
//                },
//                U: function () {
//                    return dateObj.getTime() / 1000;
//                },
//                y: function () {
//                    return String(formats.Y()).substring(2);
//                },
//                Y: function () {
//                    return dateObj.getFullYear();
//                }
//            },
//            formatPieces = dateFormat.split('');
//
//        self.forEach(formatPieces, function (formatPiece, index) {
//            if (formats[formatPiece] && formatPieces[index - 1] !== '\\') {
//                formattedDate += formats[formatPiece]();
//            } else {
//                if (formatPiece !== '\\') {
//                    formattedDate += formatPiece;
//                }
//            }
//        });
//
//        return formattedDate;
//    };
//
//    monthToStr = function (date, shorthand) {
//        if (shorthand === true) {
//            return self.l10n.months.shorthand[date];
//        }
//
//        return self.l10n.months.longhand[date];
//    };
//
//    isSpecificDay = function (day, month, year, comparison) {
//        return day === comparison && self.currentMonthView === month && self.currentYearView === year;
//    };
//
//    buildWeekdays = function () {
//        var weekdayContainer = document.createElement('thead'),
//            firstDayOfWeek = self.l10n.firstDayOfWeek,
//            weekdays = self.l10n.weekdays.shorthand;
//
//        if (firstDayOfWeek > 0 && firstDayOfWeek < weekdays.length) {
//            weekdays = [].concat(weekdays.splice(firstDayOfWeek, weekdays.length), weekdays.splice(0, firstDayOfWeek));
//        }
//
//        weekdayContainer.innerHTML = '<tr><th>' + weekdays.join('</th><th>') + '</th></tr>';
//        calendar.appendChild(weekdayContainer);
//    };
//
//    buildDays = function () {
//        var firstOfMonth = new Date(self.currentYearView, self.currentMonthView, 1).getDay(),
//            numDays = date.month.numDays(),
//            calendarFragment = document.createDocumentFragment(),
//            row = document.createElement('tr'),
//            dayCount,
//            dayNumber,
//            today = '',
//            selected = '',
//            disabled = '',
//            currentTimestamp;
//
//        // Offset the first day by the specified amount
//        firstOfMonth -= self.l10n.firstDayOfWeek;
//        if (firstOfMonth < 0) {
//            firstOfMonth += 7;
//        }
//
//        dayCount = firstOfMonth;
//        calendarBody.innerHTML = '';
//
//        // Add spacer to line up the first day of the month correctly
//        if (firstOfMonth > 0) {
//            row.innerHTML += '<td colspan="' + firstOfMonth + '">&nbsp;</td>';
//        }
//
//        // Start at 1 since there is no 0th day
//        for (dayNumber = 1; dayNumber <= numDays; dayNumber++) {
//            // if we have reached the end of a week, wrap to the next line
//            if (dayCount === 7) {
//                calendarFragment.appendChild(row);
//                row = document.createElement('tr');
//                dayCount = 0;
//            }
//
//
//
//            //console.info(new Date(self.currentYearView, self.currentMonthView, dayNumber));
//
//            var d = isBetweenDate(hired, new Date(self.currentYearView, self.currentMonthView, dayNumber));
//
//            if (d) {
//                var title;
//                if (d === "datepickr-sv") {
//                    title = 'title="Blokhut is vrij, er zijn wel groepen van Scouting Veghel"';
//                } else {
//                    title = 'title="Blokhut is reeds verhuurd op deze datum"';
//                }
//            } else { //Colin: added this, because otherwise the title did not reset for non occupied days
//                title = 'title="Blokhut is vrij"';
//            }
//
//            today = isSpecificDay(date.current.day(), date.current.month.integer(), date.current.year(), dayNumber) ? ' today' : '';
//            if (self.selectedDate) {
//                selected = isSpecificDay(self.selectedDate.day, self.selectedDate.month, self.selectedDate.year, dayNumber) ? ' selected' : '';
//            }
//
//            if (self.config.minDate || self.config.maxDate) {
//                currentTimestamp = new Date(self.currentYearView, self.currentMonthView, dayNumber).getTime();
//                disabled = '';
//
//                if (self.config.minDate && currentTimestamp < self.config.minDate) {
//                    disabled = ' disabled';
//                }
//
//                if (self.config.maxDate && currentTimestamp > self.config.maxDate) {
//                    disabled = ' disabled';
//                }
//            }
//
//            row.innerHTML += '<td ' + title + ' class="' + d + today + selected + disabled + '"><span class="datepickr-day">' + dayNumber + '</span></td>';
//            dayCount++;
//        }
//
//        calendarFragment.appendChild(row);
//        calendarBody.appendChild(calendarFragment);
//    };
//
//    updateNavigationCurrentMonth = function () {
//        navigationCurrentMonth.innerHTML = date.month.string() + ' ' + self.currentYearView;
//    };
//
//    buildMonthNavigation = function () {
//        var months = document.createElement('div'),
//            monthNavigation;
//
//        monthNavigation  = '<span class="datepickr-prev-month">&lt;</span>';
//        monthNavigation += '<span class="datepickr-next-month">&gt;</span>';
//
//        months.className = 'datepickr-months';
//        months.innerHTML = monthNavigation;
//
//        months.appendChild(navigationCurrentMonth);
//        updateNavigationCurrentMonth();
//        calendarContainer.appendChild(months);
//    };
//
//    handleYearChange = function () {
//        if (self.currentMonthView < 0) {
//            self.currentYearView--;
//            self.currentMonthView = 11;
//        }
//
//        if (self.currentMonthView > 11) {
//            self.currentYearView++;
//            self.currentMonthView = 0;
//        }
//    };
//
//    documentClick = function (event) {
//        var parent;
//        if (event.target !== self.element && event.target !== wrapperElement) {
//            parent = event.target.parentNode;
//            if (parent !== wrapperElement) {
//                while (parent !== wrapperElement) {
//                    parent = parent.parentNode;
//                    if (parent === null) {
//                        close();
//                        break;
//                    }
//                }
//            }
//        }
//    };
//
//    calendarClick = function (event) {
//        var target = event.target,
//            targetClass = target.className,
//            currentTimestamp;
//
//        if (targetClass) {
//            if (targetClass === 'datepickr-prev-month' || targetClass === 'datepickr-next-month') {
//                if (targetClass === 'datepickr-prev-month') {
//                    self.currentMonthView--;
//                } else {
//                    self.currentMonthView++;
//                }
//
//                handleYearChange();
//                updateNavigationCurrentMonth();
//                buildDays();
//            } else if (targetClass === 'datepickr-day' && !self.hasClass(target.parentNode, 'disabled') && !self.config.asCalender) {
//                self.selectedDate = {
//                    day: parseInt(target.innerHTML, 10),
//                    month: self.currentMonthView,
//                    year: self.currentYearView
//                };
//
//                currentTimestamp = new Date(self.currentYearView, self.currentMonthView, self.selectedDate.day).getTime();
//
//                if (self.config.altInput) {
//                    if (self.config.altFormat) {
//                        self.config.altInput.value = formatDate(self.config.altFormat, currentTimestamp);
//                    } else {
//                        // I don't know why someone would want to do this... but just in case?
//                        self.config.altInput.value = formatDate(self.config.dateFormat, currentTimestamp);
//                    }
//                }
//
//                self.element.value = formatDate(self.config.dateFormat, currentTimestamp);
//
//                close();
//                buildDays();
//            }
//        }
//    };
//
//    buildCalendar = function () {
//        buildMonthNavigation();
//        buildWeekdays();
//        buildDays();
//
//        calendar.appendChild(calendarBody);
//        calendarContainer.appendChild(calendar);
//
//        wrapperElement.appendChild(calendarContainer);
//    };
//
//    getOpenEvent = function () {
//        if (self.element.nodeName === 'INPUT') {
//            return 'focus';
//        }
//        return 'click';
//    };
//
//    bind = function () {
//        if (!self.config.asCalender) {
//            self.addEventListener(self.element, getOpenEvent(), open, false);
//        }
//        self.addEventListener(calendarContainer, 'click', calendarClick, false);
//    };
//
//    open = function () {
//        if (!self.config.asCalender) {
//            self.addEventListener(document, 'click', documentClick, false);
//            self.addClass(wrapperElement, 'open');
//        }
//    };
//
//    close = function () {
//        if (!self.config.asCalender) {
//            self.removeEventListener(document, 'click', documentClick, false);
//            self.removeClass(wrapperElement, 'open');
//        }
//    };
//
//    destroy = function () {
//        var parent,
//            element;
//
//        self.removeEventListener(document, 'click', documentClick, false);
//        self.removeEventListener(self.element, getOpenEvent(), open, false);
//
//        parent = self.element.parentNode;
//        parent.removeChild(calendarContainer);
//        element = parent.removeChild(self.element);
//        parent.parentNode.replaceChild(element, parent);
//    };
//
//    init = function () {
//        var config,
//            parsedDate;
//
//        self.config = {};
//        self.destroy = destroy;
//
//        for (config in defaultConfig) {
//            self.config[config] = instanceConfig[config] || defaultConfig[config];
//        }
//
//        self.element = element;
//
//        if (self.element.value) {
//            parsedDate = Date.parse(self.element.value);
//        }
//
//        if (parsedDate && !isNaN(parsedDate)) {
//            parsedDate = new Date(parsedDate);
//            self.selectedDate = {
//                day: parsedDate.getDate(),
//                month: parsedDate.getMonth(),
//                year: parsedDate.getFullYear()
//            };
//            self.currentYearView = self.selectedDate.year;
//            self.currentMonthView = self.selectedDate.month;
//            self.currentDayView = self.selectedDate.day;
//        } else {
//            self.selectedDate = null;
//            self.currentYearView = date.current.year();
//            self.currentMonthView = date.current.month.integer();
//            self.currentDayView = date.current.day();
//        }
//
//        wrap(self.config.asCalender);
//        buildCalendar();
//        //if (!self.config.asCalender){
//        //    bind();
//        //}
//        bind();
//    };
//
//    init();
//
//    return self;
//};
//
//datepickr.init.prototype = {
//    hasClass: function (element, className) { return element.classList.contains(className); },
//    addClass: function (element, className) { element.classList.add(className); },
//    removeClass: function (element, className) { element.classList.remove(className); },
//    forEach: function (items, callback) { [].forEach.call(items, callback); },
//    querySelectorAll: document.querySelectorAll.bind(document),
//    isArray: Array.isArray,
//    addEventListener: function (element, type, listener, useCapture) {
//        element.addEventListener(type, listener, useCapture);
//    },
//    removeEventListener: function (element, type, listener, useCapture) {
//        element.removeEventListener(type, listener, useCapture);
//    },
//    l10n: {
//        weekdays: {
//            shorthand: ['Zo', 'Ma', 'Di', 'Wo', 'Do', 'Vr', 'Za'],
//            longhand: ['Zondag', 'Maandag', 'Dinsdag', 'Woensdag', 'Donderdag', 'Vrijdag', 'Zaterdag']
//        },
//        months: {
//            shorthand: ['Jan', 'Feb', 'Mrt', 'Apr', 'Mei', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dec'],
//            longhand: ['Januari', 'Februari', 'Maart', 'April', 'Mei', 'Juni', 'Juli', 'Augustus', 'September', 'Oktober', 'November', 'December']
//        },
//        daysInMonth: [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31],
//        firstDayOfWeek: 1
//    }
//};

// Get all the reserveringen..
(function () {
    var currentDate = new Date();
    ajax.get("../php/Reservering.php", {d: currentDate.getDate(), m: currentDate.getMonth() + 1, y: currentDate.getFullYear()}, function (msg) {
        console.info(JSON.parse(msg));
        processHired(JSON.parse(msg));
    });
})();

function processHired(msg) {
    var container = $I("mini-verhuur"),
        fragment = document.createDocumentFragment(),
        i,
        p, ul, li,
        dateFrom, dateTo,
        time = function (input) {
            var t = new Date(input),
                weekdays = {
                    shorthand: ['Zo', 'Ma', 'Di', 'Wo', 'Do', 'Vr', 'Za'],
                    longhand: ['Zondag', 'Maandag', 'Dinsdag', 'Woensdag', 'Donderdag', 'Vrijdag', 'Zaterdag']
                },
                months = {
                    shorthand: ['Jan', 'Feb', 'Mrt', 'Apr', 'Mei', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dec'],
                    longhand: ['Januari', 'Februari', 'Maart', 'April', 'Mei', 'Juni', 'Juli', 'Augustus', 'September', 'Oktober', 'November', 'December']
                };

            return weekdays.shorthand[t.getDay()] + " " + t.getDate() + " " + months.shorthand[t.getMonth()] /*+ " " + t.getFullYear() *//*+ ", " + t.getHours() +  ":00"*/;
        },
        today;

    // Make a list...
    ul = $el("ul");
    ul.classList.add("hide-mobile");

    // Reverse array loop :)
    for (i = 0; i < msg.length; i++) {
        li = $el("li");
        dateFrom = time(msg[i].dayFrom);
        dateTo = time(msg[i].dayTo);

        li.textContent = ((dateTo === dateFrom) ? ("Op: " + dateFrom) : ("Van: " + dateFrom + " tot: " + dateTo));

        ul.appendChild(li);
    }

    fragment.appendChild(ul);

    // Nice message... is today rented..
    p = $el("p");
    p.classList.add("verhuur-status");
    fragment.appendChild(p);

    // Function that checks if it's today...
    today = isTodayHired(msg);
    if (today) {
        if (today === "sv") {
            p.textContent = "De blokhut wordt gebruikt door Scouting Veghel.";
        } else {
            p.textContent = "De blokhut is op dit moment bezet.";
        }
    } else {
        p.textContent = "De blokhut is op dit moment vrij.";
    }

    container.innerHTML = "";
    container.appendChild(fragment);
}