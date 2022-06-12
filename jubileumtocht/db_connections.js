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


(function getTimeslots() {
    ajax("./Timeslots.php", {}, function (err, msg) {
        var tempErr = err;
        if (!err) {
            try {
                processTimeslots(JSON.parse(msg));
            } catch (e) {
                console.warn("Er is iets mis gegaan met het ophalen van de tijdssloten.", msg, e);
                tempErr = true;
            }
        }
        if (tempErr) {
            $id("message").innerHTML = '<p class="verhuur-status">Er is iets mis gegaan met het ophalen van de tijdsloten.</p>';
        }
    });
})();

function processTimeslots(msg) {
    var container = $id("timeslots"),
        fragment = document.createDocumentFragment(),
        i,
        ul, li,
        timeslot, distance, amountAvailable;

    // Make a list...
    ul = $elem("ul");

    // Reverse array loop :)
    for (i = 0; i < msg.length; i++) {
        li = $elem("li");
        timeslot = msg[i].timeslot;
        distance = msg[i].distance;
        amountAvailable = msg[i].available

        li.textContent = "Om " + timeslot + " starten met " + distance + " km " + "[nog " + amountAvailable + " beschikbaar" + "]";

        ul.appendChild(li);
    }

    fragment.appendChild(ul);

    container.innerHTML = "";
    container.appendChild(fragment);
}