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

// Ajax call...
var ajax = function(url, data, callback) {
    // Check if "ajax" is possible.
    var x = {};
    if (typeof XMLHttpRequest !== 'undefined') {
        x = new XMLHttpRequest();
    } else {
        showError("Dingen zijn niet ondersteund.. update je browser...");
        return;
    }

    // Construct query.
    var query = [];
    for (var key in data) {
        query.push(encodeURIComponent(key) + '=' + encodeURIComponent(data[key]));
    }

    // Do stuff with the data.
    x.open("POST", url, true);

    x.onreadystatechange = function() {
        if (x.readyState === 4 && x.status === 200) {
            // Success!
            callback(x.responseText)
        } //else {
            // We reached our target server, but it returned an error
            //showError("Er is iets mis gegaan. (Status: " + x.status + ")");
        //}
    };

    x.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

    x.send(query.join("&"));
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
function resizeMap() {
    if ($I("map-size")){
        var mapSize = $I("map-size").clientWidth;
        $I("kaartje").innerHTML = '<img class="info-kaart" alt="Locatie blokhut veghel" src="//maps.googleapis.com/maps/api/staticmap?center=Dorshout+29,Veghel,NL&zoom=14&size='+mapSize+'x300&scale=2&markers=color:blue%7C51.626782,5.522947&key=AIzaSyCgVa8lEEM_4SaJqtlLgl8QtBytdSSrhlM&sensor=false" />';
    }
}
resizeMap();
window.addEventListener("resize", resizeMap);


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

        // Closes the menu button..
        if (menu.classList.contains("hb-menu-open")) {
            menu.classList.toggle("hb-menu-open");
            $I("hb-menu-btn-click").classList.toggle("hb-menu-btn-open");
        }

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

        ajax("../php/contact-form.php", data, function (msg) {
            // Reset form after succes.
            $I(form).reset();
            showSuccess(msg, form);
        });
    }
    // Return false to prevent default form behaviour.
    return false;
};

// Verhuur tab control
$I("verhuur-tabs").addEventListener("click", function (evt) {
    //console.info(evt.target);
    removeClass("tab-active");
    removeClass("tabpanel-active");
    evt.target.classList.add("tab-active");

    $I(evt.target.getAttribute("data-tab")).classList.add("tabpanel-active");
});
$I("verhuur-goto-2").addEventListener("click", function (evt) {
    evt.preventDefault();
    removeClass("tab-active");
    removeClass("tabpanel-active");

    $I("verhuur-stap-2").classList.add("tabpanel-active");
    $I("verhuur-tabs").children[1].classList.add("tab-active");
    //return false;
});
$I("verhuur-goto-3").addEventListener("click", function (evt) {
    evt.preventDefault();
    removeClass("tab-active");
    removeClass("tabpanel-active");

    $I("verhuur-stap-3").classList.add("tabpanel-active");
    $I("verhuur-tabs").children[2].classList.add("tab-active");
    //return false;
});

$I("hb-menu-btn-click").onclick = function () {
    $I("menu-links").classList.toggle("hb-menu-open");
    this.classList.toggle("hb-menu-btn-open");
};

//======== Some cool function to do stuf with the verhuur date time picker...
// Check if the number is in range or not.. and send the correct number back..
function checkMinMax (elem, newVal) {
    var min = elem.getAttribute("min"),
        max = elem.getAttribute("max");

    if (min < newVal && max > newVal) {
        return newVal;
    }
    if (min >= newVal && max > newVal) {
        return min;
    }
    if (min < newVal && max <= newVal) {
        return max;
    }
    return false;
}

function verhuurDateTime () {
    // Private variables... actually all are..
    var $begin = {
            j: $I("aankomst-jaar"),
            m: $I("aankomst-maand"),
            d: $I("aankomst-dag"),
            uu: $I("aankomst-uur"),
            mm: $I("aankomst-minuut")
        },
        $einde = {
            j: $I("vertrek-jaar"),
            m: $I("vertrek-maand"),
            d: $I("vertrek-dag"),
            uu: $I("vertrek-uur"),
            mm: $I("vertrek-minuut")
        },
        idElem = {
            "aankomst-jaar":    $begin.j,
            "aankomst-maand":   $begin.m,
            "aankomst-dag":     $begin.d,
            "aankomst-uur":     $begin.uu,
            "aankomst-minuut":  $begin.mm,

            "vertrek-jaar":    $einde.j,
            "vertrek-maand":   $einde.m,
            "vertrek-dag":     $einde.d,
            "vertrek-uur":     $einde.uu,
            "vertrek-minuut":  $einde.mm
        },
        $labels = document.getElementsByClassName("verhuur-label"),
        // De checkbox voor elle
        $1dag = $I("EllenIkWil1DagHuren");
    
    function onNumberChange (val) {
        if (typeof val !== "number") {
            return;
        }
        var newVal = +this.value + val,
            result = checkMinMax(this, newVal);

        if (result !== false) {
            this.value = result;
        }
    }
    function onMaandChange (val) {
        if (typeof val !== "number") {
            return;
        }
        this.selectedIndex += val;

        if (this.selectedIndex === -1) {
            this.selectedIndex = 0;
        }
    }
    function labelClick () {
        // Activate the correct number change event :)
        idElem[this.getAttribute("for")].onchange(this.getAttribute("data-plus-it") ? 1 : -1);
    }

    function onEllenChange () {
        // De 1 dag huren optie..
        for (var x in $einde) {
            var e = $einde[x], b = $begin[x];
            if (this.checked) {
                if (e.nodeName !== "SELECT") {
                    e.value = b.value;
                } else {
                    // It's the selectbox..
                    e.selectedIndex = b.selectedIndex;
                }
            }
            // Turn on or off disabled and required.
            e.required = !$1dag.checked;
            e.disabled = $1dag.checked;
        }
    }

    // Apply listeners..
    $1dag.onchange = onEllenChange;

    // Clicks for labels
    for (var i = 0; i < $labels.length; i++) {
        $labels[i].addEventListener("click", labelClick);
    }

    // More things like to listen.
    $begin.m.onchange = onMaandChange;
    $einde.m.onchange = onMaandChange;

    $begin.d.onchange = onNumberChange;
    $begin.j.onchange = onNumberChange;
    $begin.uu.onchange = onNumberChange;
    $begin.mm.onchange = onNumberChange;

    $einde.d.onchange = onNumberChange;
    $einde.j.onchange = onNumberChange;
    $einde.uu.onchange = onNumberChange;
    $einde.mm.onchange = onNumberChange;
    
    // Put default values
    var nu = new Date();
    var dan = new Date();

    dan.setDate(dan.getDate() + 5);

    // For the month..
    $begin.m.selectedIndex = nu.getMonth();
    $einde.m.selectedIndex = dan.getMonth();
    
    // The others..
    $begin.d.value = nu.getDate();
    $einde.d.value = dan.getDate();
    $begin.j.value = nu.getFullYear();
    $einde.j.value = dan.getFullYear();

    $begin.uu.value = $einde.uu.value = nu.getHours();
    $begin.mm.value = $einde.mm.value = nu.getMinutes();
    
}

// Activate functionality:
verhuurDateTime();

// Other default options...

var isTodayHired = function(obj) {
    var dayFrom, dayTo, checkDate = Date.now();
    //console.info(checkDate);
    if (obj) {
        for (var i = 0; i < obj.length; i++) {
            dayFrom = Date.parse(obj[i].dayFrom);
            dayTo = Date.parse(obj[i].dayTo);
            //Because of the removel of the time we need to add 24 hours minus 1 millisecond to make sure the entire day is actually hired.
            dayTo = (dayTo === dayFrom) ? dayTo + 86399999 : dayTo;
            //console.info(dayFrom, dayTo, (checkDate <= dayTo && checkDate >= dayFrom));
            if ((checkDate <= dayTo && checkDate >= dayFrom)) {
                return obj[i].bySV ? "sv": "other";
            }
        }
        return false;
    }
};

// Get all the reserveringen..
(function () {
    var currentDate = new Date();
    ajax("../php/Reservering.php", {d: currentDate.getDate(), m: currentDate.getMonth() + 1, y: currentDate.getFullYear()}, function (msg) {
        //console.info(JSON.parse(msg));
        try {
            processHired(JSON.parse(msg));
        } catch(e) {
            console.warn("Er is iets mis gegaan met het ophalen van de reserveringen.", msg);
        }
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