//Functions coppied from script.origineel.js
// Ajax call...
ajax = function(url, data, callback) {
    // Check if "ajax" is possible.
    var x = {},
		query = [];

    if (typeof XMLHttpRequest !== 'undefined') {
        x = new XMLHttpRequest();
    } else {
        showError("Dingen zijn niet ondersteund... update je browser...");
        return;
    }

    // Construct query.
    for (var key in data) {
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

    x.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

    x.send(query.join("&"));
};


//My functions

/**
* Function that processes the reservations for this month and makrs them on the calendar
*/
function processReservations(reservations) {
  for (var i in reservations) {
    var res = reservations[i];
    var start = new Date(res['dayFrom']);
    var end = new Date(res['dayTo']);
    for (var d = start.getDate(); d <= end.getDate(); d++) {
      if(res['bySV']){
          getId(d).classList.add('bySV');
      } else {
          getId(d).classList.add('reserved');
      }
    }
  }
}

/**
* Get all reservations for the blokhut for a specific month and year
*/
function getReservationsMonth(month, year) {
  getId("msg-calendar").innerHTML = '<p class="verhuur-status">Ophalen van de reserveringen.</p>';
  ajax("../php/ReserveringMonth.php", {m: month + 1, y: year}, function (err, msg) {
  var tempErr = err;
  if (!err) {
    try {
      processReservations(JSON.parse(msg));
      getId("msg-calendar").innerHTML = '<p class="verhuur-status">Reserveringen opgehaald.</p>';
    } catch(e) {
      console.warn("Er is iets mis gegaan met het ophalen van de reserveringen.", msg);
      tempErr = true;
    }
  }
  if (tempErr) {
    getId("msg-calendar").innerHTML = '<p class="verhuur-status">Er is iets mis gegaan met het ophalen van de reserveringen.</p>';
  }
  });
}

//Here starts the downloaded code from http://codepen.io/anon/pen/woLZog
//It is slightly edited by us to faclitate the indication of reservations


var Cal = function(divId) {

  //Store div id
  this.divId = divId;

  // Days of week, starting on Sunday
  this.DaysOfWeek = [
    'Zon',
    'Maa',
    'Din',
    'Woe',
    'Don',
    'Vri',
    'Zat'
  ];

  // Months, stating on January
  this.Months = ['januari', 'februari', 'maart', 'april', 'mei', 'juni', 'juli', 'augustus', 'september', 'oktober', 'november', 'december' ];

  // Set the current month, year
  var d = new Date();

  this.currMonth = d.getMonth();
  this.currYear = d.getFullYear();
  this.currDay = d.getDate();

};

// Goes to next month
Cal.prototype.nextMonth = function() {
  if ( this.currMonth == 11 ) {
    this.currMonth = 0;
    this.currYear = this.currYear + 1;
  }
  else {
    this.currMonth = this.currMonth + 1;
  }
  this.showcurr();
};

// Goes to previous month
Cal.prototype.previousMonth = function() {
  if ( this.currMonth == 0 ) {
    this.currMonth = 11;
    this.currYear = this.currYear - 1;
  }
  else {
    this.currMonth = this.currMonth - 1;
  }
  this.showcurr();
};

// Show current month
Cal.prototype.showcurr = function() {
  this.showMonth(this.currYear, this.currMonth);
};

// Show month (year, month)
Cal.prototype.showMonth = function(y, m) {

  var d = new Date()
  // First day of the week in the selected month
  , firstDayOfMonth = new Date(y, m, 1).getDay()
  // Last day of the selected month
  , lastDateOfMonth =  new Date(y, m+1, 0).getDate()
  // Last day of the previous month
  , lastDayOfLastMonth = m == 0 ? new Date(y-1, 11, 0).getDate() : new Date(y, m, 0).getDate();


  var html = '<table>';

  // Write selected month and year
  html += '<thead><tr>';
  html += '<td colspan="7">' + this.Months[m] + ' ' + y + '</td>';
  html += '</tr></thead>';


  // Write the header of the days of the week
  html += '<tr class="days">';
  for(var i=0; i < this.DaysOfWeek.length;i++) {
    html += '<td>' + this.DaysOfWeek[i] + '</td>';
  }
  html += '</tr>';

  // Write the days
  var i=1;
  do {

    var dow = new Date(y, m, i).getDay();

    // If Sunday, start new row
    if ( dow == 0 ) {
      html += '<tr>';
    }
    // If not Sunday but first day of the month
    // it will write the last days from the previous month
    else if ( i == 1 ) {
      html += '<tr>';
      var k = lastDayOfLastMonth - firstDayOfMonth+1;
      for(var j=0; j < firstDayOfMonth; j++) {
        html += '<td class="not-current">' + k + '</td>';
        k++;
      }
    }

    // Write the current day in the loop
    var chk = new Date();
    var chkY = chk.getFullYear();
    var chkM = chk.getMonth();
    if (chkY == this.currYear && chkM == this.currMonth && i == this.currDay) {
      html += '<td class="today" id="' + i + '">' + i + '</td>';
    } else {
      html += '<td class="normal" id="' + i + '">' + i + '</td>';
    }
    // If Saturday, closes the row
    if ( dow == 6 ) {
      html += '</tr>';
    }
    // If not Saturday, but last day of the selected month
    // it will write the next few days from the next month
    else if ( i == lastDateOfMonth ) {
      var k=1;
      for(dow; dow < 6; dow++) {
        html += '<td class="not-current">' + k + '</td>';
        k++;
      }
    }

    i++;
  }while(i <= lastDateOfMonth);

  // Closes table
  html += '</table>';

  // Write HTML to the div
  document.getElementById(this.divId).innerHTML = html;
};

// On Load of the window
window.onload = function() {

  // Start calendar
  var c = new Cal("divCal");
  c.showcurr();
  var reservations = getReservationsMonth(c.currMonth, c.currYear);

  // Bind next and previous button clicks
  getId('btnNext').onclick = function() {
    c.nextMonth();
    var reservations = getReservationsMonth(c.currMonth, c.currYear);
  };
  getId('btnPrev').onclick = function() {
    c.previousMonth();
    var reservations = getReservationsMonth(c.currMonth, c.currYear);
  };
}

// Get element by id
function getId(id) {
  return document.getElementById(id);
}
