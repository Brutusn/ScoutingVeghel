//Functions coppied from script.origineel.js
var ajax_running = 0;
var ajax_error = false;
// Ajax call...
const ajax = function(url, data, callback) {
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
function processReservations(reservations, month, year) {
  for (var i in reservations) {
    var res = reservations[i];
    var start = new Date(res['dayFrom']);
    var end = new Date(res['dayTo']);
    var res_class = 'reserved';
    //set the class of this reservation
    if(res['bySV']){
      res_class = 'bySV';
    }
    //check whether it is in the same month or that it overflows
    var start_mark = start.getDate();
    var end_mark = end.getDate();
    if (start.getMonth() === end.getMonth()) {
      //do nothing as dates are already set and this is the normal case
    } else {//this is the case where reservations start ot end in another month
      if (end.getMonth() === month){//starts in a previous month
        //so set starting mark as first of the month
        start_mark = 1;

        //actually mark the dates from previous month
        for (var d = start.getDate(); d <= 31; d++) {
              var elem = getId('prev.'+d+'.'+(month+1)+'-'+year);
              if (elem !== null){
                elem.classList.add(res_class);
              }
        }//end for marking
      } else if (start.getMonth() === month) {//ends in a next month
        //so set the end mark at the end of the month
        //(31 is safe, since there are no other ids if there are less days than 31 days in a month)
        end_mark = 31;
        //actually mark the dates from previous month
        for (var d = 1; d <= end.getDate(); d++) {
          var elem = getId('next.'+d+'.'+(month+1)+'-'+year);
          if (elem !== null){
            elem.classList.add(res_class);
          }
        }//end for marking
      } else if (start.getMonth() !== month && end.getMonth() !== month) {//cover th entire month with a reservation
        start_mark = 1;
        end_mark = 31;
        //actually mark the dates from previous month
        for (var d = start.getDate(); d <= 31; d++) {
              var elem = getId('prev.'+d+'.'+(month+1)+'-'+year);
              if (elem !== null){
                elem.classList.add(res_class);
              }
        }//end for marking
        //actually mark the dates from previous month
        for (var d = 1; d <= end.getDate(); d++) {
          var elem = getId('next.'+d+'.'+(month+1)+'-'+year);
          if (elem !== null){
            elem.classList.add(res_class);
          }
        }//end for marking
      }
    }//end else months equal

    //actually mark the dates
    for (var d = start_mark; d <= end_mark; d++) {
      var elem =   getId(d+'.'+(month+1)+'-'+year);
      if (elem !== null){
        elem.classList.add(res_class);
      }
    }//end for marking
  }//end for reservations
}

/**
* Get all reservations for the blokhut for a specific month and year
*/
function getReservationsMonth(month, year) {
  if(ajax_running > 0){
    ajax_error = true;
    getId("msg-calendar").innerHTML = '<p></p>';
    getId("error-calendar").innerHTML = '<p><button id="error-msg" class="verhuur-error">U klikt te snel, klik hier om te verversen.</button></p>';
    getId("error-msg").onclick = function() {
      ajax_running = 0;
      ajax_error = false;
      getId("error-calendar").innerHTML = "<p></p>";
      getReservationsMonth(month, year);
    };
  } else {
    ajax_running += 1;
    if (ajax_error === false) {
      getId("msg-calendar").innerHTML = '<p class="verhuur-status">Ophalen van de reserveringen.</p>';
    }
    ajax("../php/ReserveringMonth.php", {m: month + 1, y: year}, function (err, msg) {
      var tempErr = err;
      if (!err) {
        try {
          if (ajax_error === false) {
            processReservations(JSON.parse(msg), month, year);
            getId("msg-calendar").innerHTML = '<p class="verhuur-status">Reserveringen opgehaald.</p>';
          }
        } catch(e) {
          console.warn("Er is iets mis gegaan met het ophalen van de reserveringen.", msg);
          tempErr = true;
        }
      }
      if (tempErr) {
        getId("msg-calendar").innerHTML = '<p class="verhuur-status">Er is iets mis gegaan met het ophalen van de reserveringen.</p>';
      }
      ajax_running -= 1;
    });
  }
}

//Here starts the downloaded code from http://codepen.io/anon/pen/woLZog
//It is slightly edited by us to faclitate the indication of reservations


var Cal = function(divId) {

  //Store div id
  this.divId = divId;

  // Days of week, starting on Sunday
  this.DaysOfWeek = [
    'Ma',
    'Di',
    'Wo',
    'Do',
    'Vr',
    'Za',
    'Zo'
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
  if ( this.currMonth === 11 ) {
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
  if ( this.currMonth === 0 ) {
    this.currMonth = 11;
    this.currYear = this.currYear - 1;
  }
  else {
    this.currMonth = this.currMonth - 1;
  }
  this.showcurr();
};

Cal.prototype.thisMonth = function() {
  var d = new Date();
  this.currMonth = d.getMonth();
  this.currYear = d.getFullYear();
  
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
  , lastDayOfLastMonth = m === 0 ? new Date(y-1, 11, 0).getDate() : new Date(y, m, 0).getDate();


  var html = '<table>';

  // Write selected month and year
  html += '<thead><tr>';
  html += '<td colspan="7">' + this.Months[m] + ' ' + y + '</td>';
  html += '</tr></thead>';


  // Write the header of the days of the week
  html += '<tr class="days">';
  for(var i=0; i < this.DaysOfWeek.length;i++) {
    html += '<td class="days">' + this.DaysOfWeek[i] + '</td>';
  }
  html += '</tr>';

  // Write the days
  var i=1;
  do {

    var dow = new Date(y, m, i).getDay();

    // If Monday, start new row
    if ( dow === 1 ) {
      html += '<tr>';
    }
    // If not Monday but first day of the month
    // it will write the last days from the previous month
    else if ( i === 1) {
      html += '<tr>';
      var startDay = dow;
      //In case the first day is on a Sunday we need all days before it
      if(dow === 0) {
          startDay = 7;
      }
      var k = lastDayOfLastMonth - startDay + 2;
      for(var j=1; j < startDay; j++) {
        html += '<td class="not-current" id="prev.' + k +'.'+(this.currMonth+1)+'-'+this.currYear + '">' + k + '</td>';
        k++;
      }
    }

    // Write the current day in the loop
    var chk = new Date();
    var chkY = chk.getFullYear();
    var chkM = chk.getMonth();
    if (chkY === this.currYear && chkM === this.currMonth && i === this.currDay) {
      html += '<td class="today" id="' + i+'.'+(this.currMonth+1)+'-'+this.currYear + '">' + i + '</td>';
    } else {
      html += '<td class="normal" id="' + i+'.'+(this.currMonth+1)+'-'+this.currYear + '">' + i + '</td>';
    }
    // If Sunday, closes the row
    if ( dow === 0 ) {
      html += '</tr>';
    }
    // If not Saturday, but last day of the selected month
    // it will write the next few days from the next month
    else if ( i === lastDateOfMonth ) {
      var k=1;
      for(var l; dow <= 6; dow++) {
        html += '<td class="not-current" id="next.' + k+'.'+(this.currMonth+1)+'-'+this.currYear + '">' + k + '</td>';
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
  getReservationsMonth(c.currMonth, c.currYear);

  // Bind next and previous button clicks
  getId('btnNext').onclick = function() {
    c.nextMonth();
    getReservationsMonth(c.currMonth, c.currYear);
  };
  getId('btnPrev').onclick = function() {
    c.previousMonth();
    getReservationsMonth(c.currMonth, c.currYear);
  };
  getId('btnToday').onclick = function() {
    c.thisMonth();
    getReservationsMonth(c.currMonth, c.currYear);
  };
};

// Get element by id
function getId(id) {
  return document.getElementById(id);
}
