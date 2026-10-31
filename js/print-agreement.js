'use strict';
var printButton = document.getElementById('print');
if (printButton)
  printButton.addEventListener('click', function () {
    window.print();
  });
