// Yes! We use strict :)
"use strict";

/*
 * Google analytics;
 */
(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
})(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

ga('create', 'UA-103964119-1', 'auto');
ga('send', 'pageview');
/*
 * Einde google analytics;
 */


// GLOBALS!
var navElem = $id("navigatie"),
    groupElem = $id("groepen"),
    actiElem = $id("activiteiten"),
    stafElem = $id("staf"),
    contElem = $id("contact"),
    huurElem = $id("verhuur"),
    scrollYpos = window.scrollY,
    isScrolling = false,
    menu = $id("menu-links"),
    menuClick = false,
    isTodayHired,
	setScroll;

// Custom functions.
function $id(elem) {
	return document.getElementById(elem);
}
function $elem(type) {
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
    var x = document.getElementsByClassName(c),
		i;

    for (i = 0; i < x.length; i++) {
        x[i].classList.remove(c);
    }
}

// Ajax call...
const ajax = function(url, data, callback) {
    // Check if "ajax" is possible.
    let x = {};
	let	query = [];

    if (typeof XMLHttpRequest !== 'undefined') {
        x = new XMLHttpRequest();
    } else {
        showError("Dingen zijn niet ondersteund... update je browser...");
        return;
    }

    // Construct query.
    for (let key in data) {
        query.push(encodeURIComponent(key) + '=' + encodeURIComponent(data[key]));
    }

    // Do stuff with the data.
    x.open("POST", url, true);

    x.onreadystatechange = function () {
        if (x.readyState === 4 && x.status === 200) {
            // Success!
            callback(null, x.responseText);
        } else if (x.readyState === 4 && x.status !== 200) {
            // failure
            callback(true);
        }
    };

	x.onerror = function () {
		callback(true);
	};

    x.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

    x.send(query.join("&"));
};
function getSetScrollFunction() {
    if (window.scrollTo) {
        //console.info("window.scrollTo()");
        return function (to) {
			window.scrollTo(0, to);
		};
    }
    if (window.pageYOffset) {
        //console.info("window.pageYOffset");
        return function (to) {
			window.pageYOffset = to;
		};
    }
    if (document.documentElement.scrollTop) {
        //console.info("document.documentElement.scrollTop");
        return function () {
			document.documentElement.scrollTop = to;
		};
    }
    if (document.body.scrollTop) {
        //console.info("document.body.scrollTop");
        return function (to) {
			document.body.scrollTop = to;
		};
    }
}
// Set the function once.. no need to check it everytime.
setScroll = getSetScrollFunction();

function onScrollEvt () {
    function _b (e) {
        return e.getBoundingClientRect();
    }

    var nav = _b(navElem),
        groepen = _b(groupElem),
		staf,
		activiteiten,
		contact,
		huur;


    // Sticky the navigation!
    if (nav.top <= 0 && groepen.top <= nav.height) {
        navElem.classList.add("nav-sticky-top");
    } else {
        //if (box2.top >= box.height) {
        navElem.classList.remove("nav-sticky-top");
    }

    // Only do this when normal isScrolling is happening..
    if (!menuClick) {
        staf = _b(stafElem);
        activiteiten = _b(actiElem);
        contact = _b(contElem);
        huur = _b(huurElem);

        // remove old active class.
        removeClass("menu-active");

        // Auto set navigation on where you are...
        if (nav.height >= groepen.top && nav.height < groepen.bottom) {
            menu.children[0].classList.add("menu-active");
        }
        if (nav.height >= activiteiten.top && nav.height < activiteiten.bottom) {
            menu.children[1].classList.add("menu-active");
        }
        if (nav.height >= staf.top && nav.height < staf.bottom) {
            menu.children[2].classList.add("menu-active");
        }
        if (nav.height >= contact.top && nav.height < contact.bottom) {
            menu.children[3].classList.add("menu-active");
        }
        if (nav.height >= huur.top && nav.height < huur.bottom) {
            menu.children[4].classList.add("menu-active");
        }
    }

    // Set isScrolling back to false
    isScrolling = false;
}
function requestScroll () {
    if (!isScrolling) {
        requestAnimFrame(onScrollEvt);
    }
    isScrolling = true;
}
function scrollUp(toElement){
    var elementOffset = $id(toElement).offsetTop,
	    currentScroll,//document.body.scrollTop;
	    pos,
	    nav = $id("navigatie").clientHeight,
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
    currentScroll = scrollTop();
    if (currentScroll < elementOffset) {
        scrollTo[0] = (elementOffset - nav) - currentScroll;
        scrollTo[1] = elementOffset - nav;
        scrollTo[2] = true;
    } else {
        scrollTo[0] = (currentScroll - (elementOffset - nav)) * -1;
        scrollTo[1] = (elementOffset - nav) * -1;
        scrollTo[2] = false;
    }

    // Start the animation.
    function animation() {
        //setTimeout(function (){},fps);
        if (i < scrollTime) {
            pos = easeOutQuad(i, currentScroll, scrollTo[0], scrollTime);
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
    var elemPres = !!$id("errorMsg"),
        elem = '';
    //console.info(elemPres, elem);
    if (!elemPres){
        elem = document.createElement('div');
    } else {
        elem = $id("errorMsg");
    }
    elem.className = "error-message";
    elem.id = "errorMsg";
    elem.innerHTML = '<i class="fa fa-times-circle"></i> ' + msg;

    if(form) {
        $id(form).appendChild(elem);
    } else {
        console.error(msg);
    }
}
function removeError(){
	var elem = $id("errorMsg");
    if (elem){
        elem.parentNode.removeChild(elem);
    }
}
function showSuccess(msg, form){
    showError("tmp", form);
    $id("errorMsg").innerHTML = msg;
    $id("errorMsg").className = "success-message";
}

// Kaartje
function resizeMap() {
    if ($id("map-size")){
        var mapSize = $id("map-size").clientWidth;
        $id("kaartje").innerHTML = '<img class="info-kaart" alt="Locatie blokhut Scouting Veghel" src="//maps.googleapis.com/maps/api/staticmap?center=Dorshout+29,Veghel,NL&zoom=14&size='+mapSize+'x300&scale=2&markers=color:blue%7C51.626782,5.522947&maptype=terrain&key=AIzaSyCgVa8lEEM_4SaJqtlLgl8QtBytdSSrhlM&sensor=false" />';
    }
}
resizeMap();
window.addEventListener("resize", resizeMap);


// Sticky navigation bar :)
window.onscroll = function () {
    scrollYpos = window.scrollY;
    requestScroll();
};

$id("arrowDown").onclick = function () {
    menuClick = true;
    removeClass("menu-active");
    $id("menu-links").children[0].classList.add("menu-active");
    scrollUp("groepen");
};

$id("sv-logo").onclick = function () {
    removeClass("menu-active");
    scrollUp("landing-page");
    window.location.hash="";
};

menu.onclick = function (evt) {
    var x = evt.target.getAttribute("data-link");
    if (x) {
        menuClick = true;
        removeClass("menu-active");

        // Closes the menu button..
        if (menu.classList.contains("hb-menu-open")) {
            menu.classList.toggle("hb-menu-open");
            $id("hb-menu-btn-click").classList.toggle("hb-menu-btn-open");
        }

        evt.target.classList.add("menu-active");
        scrollUp(x);

        // Finally set the hash.
        location.hash = x;
    }
};



//=====Submits for Forms=========================================




// Contact form submission.
$id("contact-form").onsubmit = function (token) {
    const data = {};
    const re = /[^\s@]+@[^\s@]+\.[^\s@]+/; // Regex for email
    const form = this.id;
    const inputs = this.getElementsByClassName("inp");

    // Fill data object
    for (let input of inputs) {
        data[input.name] = input.value;
    }

    // Enter the captcha token.
    data["g-recaptcha-response"] = token;

    if (!data.whoTo) {
        showError("Aan wie moet de vraag of opmerking verstuurd worden?", form);
    }
    if (!re.test(data.mailadr)){
        showError("Vul een geldig e-mailadres in.", form);
    }
    if (re.test(data.mailadr) && data.whoTo !== ""){
        removeError();

        showSuccess("Vraag of opmerking aan het versturen...", form);

        ajax("../php/contact-form.php", data, (err, msg) => {
            // Reset form after succes.
            $id(form).reset();
            grecaptcha.reset();
            showSuccess(msg, form);
        });
    }

    return false;
};

// Sepperate function for the google captcha thing.
function contactSubmit (token) {
    $id("contact-form").onsubmit(token);
}


// Verhuur form submission.
$id("verhuur-form").onsubmit = function () {
    const data = {};
    const re = /[^\s@]+@[^\s@]+\.[^\s@]+/; // Regex for email
    const form = this.id;
    const inputs = this.getElementsByClassName("inp");

    // Precheck the length
    if (VERHUUR.verhuurtijd() > 14) {
        showError("De duur van de optie mag maximaal 14 overnachtingen zijn. Mocht u langer willen huren, stuur dan een vraag m.b.v. het bovenstaande formulier.", form);

        // Stop it.
        return false;
    }

    // Fill data object
    for (const input of inputs) {
        data[input.name] = input.value;
    }

    // If there is a groepscode do not validate fields
    if (data.groepcode !== "") {
        if (data.people !== "" && data.tArea !== ""){
            showSuccess("Aanvraag voor de optie versturen...", form);

            ajax("../php/verhuur-form.php", data, function (err, msg) {
                // Reset form after succes.
                $id(form).reset();
                showSuccess(msg, form);
            });
        }
    } else {// it is not from SV, so validate the most important data
        if (!data.name || data.name === "" || 
                data.name === null || data.name === undefined) {
            showError("Waarvoor is deze optie op de blokhut?", form);
        }
        else if (!data.contactperson || data.contactperson === "" || 
                data.contactperson === null ||data.contactperson === undefined) {
            showError("Wie wil deze optie op de blokhut nemen?", form);
        }
        else if (!re.test(data.mailadr)){
            showError("Vul een geldig e-mailadres in.", form);
        }
        else if (re.test(data.mailadr)){
            removeError();

            showSuccess("Aanvraag voor de optie versturen...", form);

            ajax("../php/verhuur-form.php", data, function (err, msg) {
                // Reset form after succes.
                $id(form).reset();
                VERHUUR.reset();
                showSuccess(msg, form);
            });
        }
    }
    // Return false to prevent default form behaviour.
    return false;
};


//=====Tab control==============================================


// Verhuur tab control
$id("verhuur-tabs").addEventListener("click", function (evt) {
    //console.info(evt.target);
    removeClass("tab-active");
    removeClass("tabpanel-active");
    evt.target.classList.add("tab-active");

    $id(evt.target.getAttribute("data-tab")).classList.add("tabpanel-active");

    if (evt.target.getAttribute("data-tab") === "verhuur-stap-2") {
        VERHUUR.setEindDays();
    }
});
$id("verhuur-goto-2").addEventListener("click", function (evt) {
    evt.preventDefault();
    removeClass("tab-active");
    removeClass("tabpanel-active");

    $id("verhuur-stap-2").classList.add("tabpanel-active");
    $id("verhuur-tabs").children[1].classList.add("tab-active");

    VERHUUR.setEindDays();
    //return false;
});
$id("verhuur-goto-3").addEventListener("click", function (evt) {
    evt.preventDefault();
    removeClass("tab-active");
    removeClass("tabpanel-active");

    $id("verhuur-stap-3").classList.add("tabpanel-active");
    $id("verhuur-tabs").children[2].classList.add("tab-active");
    //return false;
});

$id("verhuur-confirm-avail").addEventListener("click", function (evt) {
    evt.preventDefault();
    const form = $id("verhuur-form").id;
    const day1 = $id("aankomst-dag").value;
    const month1 = $id("aankomst-maand").value;
    const year1 = $id("aankomst-jaar").value;
    const hour1 = $id("aankomst-uur").value;
    const minute1 = $id("aankomst-minuut").value;
    const day2 = $id("vertrek-dag").value;
    const month2 = $id("vertrek-maand").value;
    const year2 = $id("vertrek-jaar").value;
    const hour2 = $id("vertrek-uur").value;
    const minute2 = $id("vertrek-minuut").value;
    showSuccess("Beschikbaarheid aan het controleren...", form);
    ajax("../php/ReserveringVerification.php", {
      d1: day1, m1: month1, y1: year1, h1: hour1, min1: minute1,
      d2: day2, m2: month2, y2: year2, h2: hour2, min2: minute2
    }, function (err, msg) {
		var tempErr = err;
		if (!err) {
			try {
				var results = JSON.parse(msg);
        if(results.length === 0){
          showSuccess('Er zijn geen reserveringen gevonden tijdens de gewenste periode.', form);
        } else {
          showError('Er zijn reserveringen gevonden tijdens de gewenste periode. <b>Advies: Neem een optie en neem contact op met de beheerder.</b>', form);
        }
			} catch(e) {
				console.warn("Er is iets mis gegaan met het ophalen van de reserveringen.", msg);
				tempErr = true;
			}
		}
		if (tempErr) {
			showError('Er is iets mis gegaan met het ophalen van de reserveringen.', form);
		}
    });

    //Cannot do this, since it causes an overwrite on the onchange events of the correpsonding items. (Labels will no longer react to clicks)
    /*$id("aankomst-dag").onchange = removeError;
    $id("aankomst-maand").onchange = removeError;
    $id("aankomst-jaar").onchange = removeError;
    $id("vertrek-dag").onchange = removeError;
    $id("vertrek-maand").onchange = removeError;
    $id("vertrek-jaar").onchange = removeError;*/
});

$id("hb-menu-btn-click").onclick = function () {
    $id("menu-links").classList.toggle("hb-menu-open");
    this.classList.toggle("hb-menu-btn-open");
};





//======Functions for filling in =================================



//If groepscode is filled in, the contact detaisla re not required
$id("groepcode").oninput = function () {
    //get groepcode input field
    //groepcodeField = document.getElementById("groepcode");
    //get all elems that posisbly need to be required or unrequired
    var elems = document.getElementsByClassName("verhuur-groepcode");
    var requiredValue = true;// just safe init

    //if not empty make the contact details not required
    if (this.value !== "") {
        requiredValue = false;
    }
    //change the actual attribute
    for(var i = 0; i < elems.length; i++) {
        elems[i].required = requiredValue;
        elems[i].disabled = !requiredValue;
    }
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

// TODO: Refactor this thing.
function verhuurDateTime () {
    // Private variables... actually all are..
    var begin = {
            j: $id("aankomst-jaar"),
            m: $id("aankomst-maand"),
            d: $id("aankomst-dag"),
            uu: $id("aankomst-uur"),
            mm: $id("aankomst-minuut")
        },
        einde = {
            j: $id("vertrek-jaar"),
            m: $id("vertrek-maand"),
            d: $id("vertrek-dag"),
            uu: $id("vertrek-uur"),
            mm: $id("vertrek-minuut")
        },
        idElem = {
            "aankomst-jaar":    begin.j,
            "aankomst-maand":   begin.m,
            "aankomst-dag":     begin.d,
            "aankomst-uur":     begin.uu,
            "aankomst-minuut":  begin.mm,

            "vertrek-jaar":    einde.j,
            "vertrek-maand":   einde.m,
            "vertrek-dag":     einde.d,
            "vertrek-uur":     einde.uu,
            "vertrek-minuut":  einde.mm
        },
        $labels = document.getElementsByClassName("verhuur-label"),
        // De checkbox voor elle
        $1dag = $id("EllenIkWil1DagHuren"),
        tabs = $id("verhuur-tabs"),
        nu, dan, returnObj = {};

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
        var e, b, x;

        for (x in einde) {
            e = einde[x];
            b = begin[x];
            if (this.checked) {
                if (e.nodeName === "SELECT") {
                    // It's the selectbox..
                    e.selectedIndex = b.selectedIndex;
                } else if (x === "uu" || x === "mm") {
                    e.value = x === "uu" ? 23 : 59;
                } else {
                    e.value = b.value;
                }
            }
            // Turn on or off disabled and required.
            e.required = !$1dag.checked;
            e.disabled = $1dag.checked;
        }
    }

    function toDouble(input) {
        return input.toString().length === 1 ? "0" + input : input;
    }

    returnObj.setEindDays = () => {
        // First check if we didn't got to this stage before.
        if (tabs.getAttribute("data-first-time")) {
            // stop the presses...
            return;
        } else {
            tabs.setAttribute("data-first-time", true);
        }

        // Parse begin days...
        const dataString = begin.j.value + "-" + toDouble(begin.m.selectedIndex + 1) + "-" + toDouble(begin.d.value) + "T12:00";
        // Parse the actual date..
        const datum = new Date(dataString);

        if (!$1dag.checked) {
            // Fastforward to + 5 days!
            datum.setDate(datum.getDate() + 5);

            // Set Time.
            einde.uu.value = begin.uu.value < 12 ? begin.uu.value + 8 : begin.uu.value;
            einde.mm.value = begin.mm.value;
        } else {
            // Set Time, but with a fixed end time.
            einde.uu.value = 23;
            einde.mm.value = 59;
        }

        // Set eind days
        einde.j.value = datum.getFullYear();
        einde.m.selectedIndex = datum.getMonth();
        einde.d.value = datum.getDate();
    };

    // Returns the verhuurtijd in days.
    returnObj.verhuurtijd = () => {
        const msPerDay = 1000 * 60 * 60 * 24;
        const parsed = (obj) => {
            return new Date(`${obj.j.value}-${toDouble(obj.m.selectedIndex + 1)}-${toDouble(obj.d.value)}T${toDouble(obj.uu.value)}:${toDouble(obj.mm.value)}`);
        }

        const b = parsed(begin);
        const e = parsed(einde);

        return (e - b) / msPerDay;
    }

    // Resetting state te create a new verhuur (if needed).
    returnObj.reset = () => {
        tabs.setAttribute("data-first-time", false);
    };

    // Apply listeners..
    $1dag.onchange = onEllenChange;

    // Clicks for labels
    for (const label of $labels) {
        label.addEventListener("click", labelClick);
    }

    // More things like to listen.
    begin.m.onchange = onMaandChange;
    einde.m.onchange = onMaandChange;

    begin.d.onchange = onNumberChange;
    begin.j.onchange = onNumberChange;
    begin.uu.onchange = onNumberChange;
    begin.mm.onchange = onNumberChange;

    einde.d.onchange = onNumberChange;
    einde.j.onchange = onNumberChange;
    einde.uu.onchange = onNumberChange;
    einde.mm.onchange = onNumberChange;

    // Put default values
    nu = new Date();
    dan = new Date();

    // 5 days ahead!
    dan.setDate(dan.getDate() + 5);

    // For the month..
    begin.m.selectedIndex = nu.getMonth();
    einde.m.selectedIndex = dan.getMonth();

    // The others..
    begin.d.value = nu.getDate();
    einde.d.value = dan.getDate();
    begin.j.value = nu.getFullYear();
    einde.j.value = dan.getFullYear();

    begin.uu.value = einde.uu.value = nu.getHours();
    begin.mm.value = einde.mm.value = nu.getMinutes();

    return returnObj;
}

// Activate functionality:
const VERHUUR = verhuurDateTime();





//=====Reserveringen=============================================





// Other default options...
function isTodayHired (obj) {
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
(function getReserveringen() {
    var currentDate = new Date();
    ajax("../php/Reservering.php", {d: currentDate.getDate(), m: currentDate.getMonth() + 1, y: currentDate.getFullYear()}, function (err, msg) {
		var tempErr = err;
		if (!err) {
			try {
				processHired(JSON.parse(msg));
			} catch(e) {
				console.warn("Er is iets mis gegaan met het ophalen van de reserveringen.", msg);
				tempErr = true;
			}
		}
		if (tempErr) {
			$id("mini-verhuur").innerHTML = '<p class="verhuur-status">Er is iets mis gegaan met het ophalen van de reserveringen.</p>';
		}
    });
})();

// Process the location hash..
(function locationHash() {
    var hash = location.hash.replace("#", ""),
        elem;

    if (hash === "") {
        // No hash.. so stop.
        return;
    }

    elem = $id(hash);

    if (!elem) {
        // Hash is bogus.. return...
        return;
    }

    scrollUp(hash);
})();

function processHired(msg) {
    var container = $id("mini-verhuur"),
        fragment = document.createDocumentFragment(),
        i,
        p, ul, li,
        dateFrom, dateTo,
        time = function (input) {
            var t = new Date(input),
                weekdays = {
                    shorthand: ['zo', 'ma', 'di', 'wo', 'do', 'vr', 'za'],
                    longhand: ['zondag', 'maandag', 'dinsdag', 'woensdag', 'donderdag', 'vrijdag', 'zaterdag']
                },
                months = {
                    shorthand: ['jan', 'feb', 'mrt', 'apr', 'mei', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'dec'],
                    longhand: ['januari', 'februari', 'maart', 'april', 'mei', 'juni', 'juli', 'augustus', 'september', 'oktober', 'november', 'december']
                };

            return weekdays.shorthand[t.getDay()] + ". " + t.getDate() + " " + months.shorthand[t.getMonth()] + "." /*+ " " + t.getFullYear() *//*+ ", " + t.getHours() +  ":00"*/;
        },
        today;

    // Make a list...
    ul = $elem("ul");
    ul.classList.add("hide-mobile");

    // Reverse array loop :)
    for (i = 0; i < msg.length; i++) {
        li = $elem("li");
        dateFrom = time(msg[i].dayFrom);
        dateTo = time(msg[i].dayTo);

        li.textContent = ((dateTo === dateFrom) ? ("Op " + dateFrom) : ("Van " + dateFrom + " tot " + dateTo));

        ul.appendChild(li);
    }

    fragment.appendChild(ul);

    // Nice message... is today rented..
    p = $elem("p");
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

$id("copy-year").textContent = new Date().getFullYear();
