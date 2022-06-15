// Yes! We use strict :)
"use strict";

// Custom functions.
function $id(elem) {
    return document.getElementById(elem);
}
function $elem(type) {
    return document.createElement(type);
}

// Ajax call...
const ajax = function (url, data, callback) {
    // Check if "ajax" is possible.
    let x = {};
    let query = [];

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

function showError(msg, form) {
    var elemPres = !!$id("errorMsg"),
        elem = '';
    //console.info(elemPres, elem);
    if (!elemPres) {
        elem = document.createElement('div');
    } else {
        elem = $id("errorMsg");
    }
    elem.className = "error-message";
    elem.id = "errorMsg";
    elem.innerHTML = '<i class="fa fa-times-circle"></i> ' + msg;

    if (form) {
        $id(form).appendChild(elem);
    } else {
        console.error(msg);
    }
}
function removeError() {
    var elem = $id("errorMsg");
    if (elem) {
        elem.parentNode.removeChild(elem);
    }
}
function showSuccess(msg, form) {
    showError("tmp", form);
    $id("errorMsg").innerHTML = msg;
    $id("errorMsg").className = "success-message";
}


function getTimeslots() {
    ajax("./Timeslots.php", {}, function (err, msg) {
        var tempErr = err;
        if (!err) {
            try {
                var slotData = processTimeslots(JSON.parse(msg));
                updateForm(slotData)
            } catch (e) {
                console.warn("Er is iets mis gegaan met het ophalen van de tijdssloten.", msg, e);
                tempErr = true;
            }
        }
        if (tempErr) {
            showError('Er is iets mis gegaan met het ophalen van de tijdsloten.');
        }
    });
}


function processTimeslots(msg) {
    var i, slotid, timeslot, distance, amountAvailable,
        slotData = [];

    // Reverse array loop :)
    for (i = 0; i < msg.length; i++) {
        slotid = msg[i].slotid
        timeslot = msg[i].timeslot;
        distance = msg[i].distance;
        amountAvailable = msg[i].available;

        slotData[i] = { "slotid": slotid, "time": timeslot, "distance": distance, "available": amountAvailable };
    }

    return slotData;
}

function updateForm(slotData) {
    var i, currentDistance = -1.0, formSlotSelector = $id("form-slot");

    formSlotSelector.innerHTML = "";

    for (i = 0; i < slotData.length; i++) {
        // if the current distance group is different add a header in the dropdown
        if(currentDistance != slotData[i].distance) {
            currentDistance = slotData[i].distance;
            var headerFragment = document.createDocumentFragment(), header = $elem("option");
            header.value = -1;
            header.id = "form-slot-header-" + currentDistance;
            header.setAttribute('disabled', true);
            header.className='option-header'
            header.innerHTML = "" + currentDistance + " km tocht start-tijdvakken";
            headerFragment.appendChild(header);
            formSlotSelector.appendChild(headerFragment);
        }

        var fragment = document.createDocumentFragment(), option = $elem("option");
        option.value = slotData[i].slotid;
        option.id = "form-slot-" + i;
        var isUnavailable = slotData[i].available <= 0
        if(isUnavailable) {
            option.setAttribute('disabled', true)
        }
        var walkersAvailable = isUnavailable ? "geen plaatsen beschikbaar" : "nog " + slotData[i].available + " plaatsen beschikbaar";
        option.innerHTML = "" + slotData[i].time + "  -  " + slotData[i].distance + " km" + "   -   " + walkersAvailable;

        fragment.appendChild(option);
        formSlotSelector.appendChild(fragment);
    }
}

$id("registration-form").onsubmit = function () {
    const data = {};
    const re = /[^\s@]+@[^\s@]+\.[^\s@]+/; // Regex for email
    const form = this.id;
    const inputs = this.getElementsByClassName("form-control");
    removeError();

    // Fill data object
    for (const input of inputs) {
        data[input.name] = input.value;
    }

    console.debug(data)

    if (!data.name || data.name === "" ||
        data.name === null || data.name === undefined) {
        showError("Voor wie is deze registratie? Vul een naam in", form);
    }
    else if (!re.test(data.mail)) {
        showError("Vul een geldig e-mailadres in.", form);
    }
    else if (re.test(data.mail)) {
        removeError();

        showSuccess("Aanmelding versturen...", form);

        ajax("./registrationform.php", data, function (err, msg) {
            // Only reset underlying data, but not the form after success.
            //$id(form).reset();
            showSuccess(msg, form);
        });
    }

    getTimeslots();
    // Return false to prevent default form behavior.
    return false;
};

getTimeslots();