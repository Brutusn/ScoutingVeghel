'use strict';var ajax_running=0,ajax_error=!1,ajax=function(b,d,h){var e={},c=[];if("undefined"!==typeof XMLHttpRequest){var e=new XMLHttpRequest,a;for(a in d)c.push(encodeURIComponent(a)+"="+encodeURIComponent(d[a]));e.open("POST",b,!0);e.onreadystatechange=function(){4===e.readyState&&200===e.status?h(null,e.responseText):4===e.readyState&&200!==e.status&&h(!0)};e.onerror=function(){h(!0)};e.setRequestHeader("Content-type","application/x-www-form-urlencoded");e.send(c.join("&"))}else showError("Dingen zijn niet ondersteund... update je browser...")};
function processReservations(b,d,h){for(var e in b){var c=b[e],a=new Date(c.dayFrom),k=new Date(c.dayTo),l="reserved";c.bySV&&(l="bySV");var c=a.getDate(),g=k.getDate();if(a.getMonth()!==k.getMonth())if(k.getMonth()===d)for(c=1,a=a.getDate();31>=a;a++){var f=getId("prev."+a+"."+(d+1)+"-"+h);null!==f&&f.classList.add(l)}else if(a.getMonth()===d)for(g=31,a=1;a<=k.getDate();a++)f=getId("next."+a+"."+(d+1)+"-"+h),null!==f&&f.classList.add(l);else if(a.getMonth()!==d&&k.getMonth()!==d){c=1;g=31;for(a=
a.getDate();31>=a;a++)f=getId("prev."+a+"."+(d+1)+"-"+h),null!==f&&f.classList.add(l);for(a=1;a<=k.getDate();a++)f=getId("next."+a+"."+(d+1)+"-"+h),null!==f&&f.classList.add(l)}for(a=c;a<=g;a++)f=getId(a+"."+(d+1)+"-"+h),null!==f&&f.classList.add(l)}}
function getReservationsMonth(b,d){0<ajax_running?(ajax_error=!0,getId("msg-calendar").innerHTML="<p></p>",getId("error-calendar").innerHTML='<p><button id="error-msg" class="verhuur-error">U klikt te snel, klik hier om te verversen.</button></p>',getId("error-msg").onclick=function(){ajax_running=0;ajax_error=!1;getId("error-calendar").innerHTML="<p></p>";getReservationsMonth(b,d)}):(ajax_running+=1,!1===ajax_error&&(getId("msg-calendar").innerHTML='<p class="verhuur-status">Ophalen van de reserveringen.</p>'),
ajax("../php/ReserveringMonth.php",{m:b+1,y:d},function(h,e){var c=h;if(!h)try{!1===ajax_error&&(processReservations(JSON.parse(e),b,d),getId("msg-calendar").innerHTML='<p class="verhuur-status">Reserveringen opgehaald.</p>')}catch(a){console.warn("Er is iets mis gegaan met het ophalen van de reserveringen.",e),c=!0}c&&(getId("msg-calendar").innerHTML='<p class="verhuur-status">Er is iets mis gegaan met het ophalen van de reserveringen.</p>');--ajax_running}))}
var Cal=function(b){this.divId=b;this.DaysOfWeek="Ma Di Wo Do Vr Za Zo".split(" ");this.Months="januari februari maart april mei juni juli augustus september oktober november december".split(" ");b=new Date;this.currMonth=b.getMonth();this.currYear=b.getFullYear();this.currDay=b.getDate()};Cal.prototype.nextMonth=function(){11===this.currMonth?(this.currMonth=0,this.currYear+=1):this.currMonth+=1;this.showcurr()};
Cal.prototype.previousMonth=function(){0===this.currMonth?(this.currMonth=11,--this.currYear):--this.currMonth;this.showcurr()};Cal.prototype.thisMonth=function(){var b=new Date;this.currMonth=b.getMonth();this.currYear=b.getFullYear();this.showcurr()};Cal.prototype.showcurr=function(){this.showMonth(this.currYear,this.currMonth)};
Cal.prototype.showMonth=function(b,d){(new Date(b,d,1)).getDay();var h=(new Date(b,d+1,0)).getDate(),e=0===d?(new Date(b-1,11,0)).getDate():(new Date(b,d,0)).getDate(),c;c="<table><thead><tr>"+('<td colspan="7">'+this.Months[d]+" "+b+"</td>");c+='</tr></thead><tr class="days">';for(var a=0;a<this.DaysOfWeek.length;a++)c+='<td class="days">'+this.DaysOfWeek[a]+"</td>";c+="</tr>";a=1;do{var k=(new Date(b,d,a)).getDay();if(1===k)c+="<tr>";else if(1===a){c+="<tr>";var l=k;0===k&&(l=7);for(var g=e-l+2,
f=1;f<l;f++)c+='<td class="not-current" id="prev.'+g+"."+(this.currMonth+1)+"-"+this.currYear+'">'+g+"</td>",g++}g=new Date;l=g.getFullYear();g=g.getMonth();c=l===this.currYear&&g===this.currMonth&&a===this.currDay?c+('<td class="today" id="'+a+"."+(this.currMonth+1)+"-"+this.currYear+'">'+a+"</td>"):c+('<td class="normal" id="'+a+"."+(this.currMonth+1)+"-"+this.currYear+'">'+a+"</td>");if(0===k)c+="</tr>";else if(a===h)for(g=1;6>=k;k++)c+='<td class="not-current" id="next.'+g+"."+(this.currMonth+
1)+"-"+this.currYear+'">'+g+"</td>",g++;a++}while(a<=h);c+="</table>";document.getElementById(this.divId).innerHTML=c};
window.onload=function(){var b=new Cal("divCal");b.showcurr();getReservationsMonth(b.currMonth,b.currYear);getId("btnNext").onclick=function(){b.nextMonth();getReservationsMonth(b.currMonth,b.currYear)};getId("btnPrev").onclick=function(){b.previousMonth();getReservationsMonth(b.currMonth,b.currYear)};getId("btnToday").onclick=function(){b.thisMonth();getReservationsMonth(b.currMonth,b.currYear)}};function getId(b){return document.getElementById(b)};
