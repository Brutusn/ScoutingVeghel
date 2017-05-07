<?php

require_once("../php/kostenberekening.php");
require_once("../php/kostenberekening_nieuw.php");
//TODO NOte that be able to test the two functions, you need to add _new to all functions in _nieuwe file. Otherwise the names would clash. 

require_once("../php/date_layer.php");

session_start();
date_default_timezone_set('Europe/Paris');

//check if all parameters are present in the request
if (isset($_POST["aankomst-dag"]) && isset($_POST["aankomst-maand"]) && isset($_POST["aankomst-jaar"])
&& isset($_POST["aankomst-uur"]) && isset($_POST["aankomst-minuut"])
&& isset($_POST["vertrek-dag"]) && isset($_POST["vertrek-maand"]) && isset($_POST["vertrek-jaar"])
&& isset($_POST["vertrek-uur"]) && isset($_POST["vertrek-minuut"])
) {

  //Store all variables and trim them

  $aankomstdag = trim(strip_tags($_POST["aankomst-dag"]), " \n");
  $aankomstmaand = trim(strip_tags($_POST["aankomst-maand"]), " \n");
  $aankomstjaar = trim(strip_tags($_POST["aankomst-jaar"]), " \n");
  $aankomstuur = trim(strip_tags($_POST["aankomst-uur"]), " \n");
  $aankomstminuut = trim(strip_tags($_POST["aankomst-minuut"]), " \n");
  $vertrekdag = trim(strip_tags($_POST["vertrek-dag"]), " \n");
  $vertrekmaand = trim(strip_tags($_POST["vertrek-maand"]), " \n");
  $vertrekjaar = trim(strip_tags($_POST["vertrek-jaar"]), " \n");
  $vertrekuur = trim(strip_tags($_POST["vertrek-uur"]), " \n");
  $vertrekminuut = trim(strip_tags($_POST["vertrek-minuut"]), " \n");

  // First check if dates are ok and valid, if so build the proper string
  $startSTR = "";
  $endSTR = "";
  $alreadyReserved = False;
  //Convert the month name to month number
  $aankomstmaandNummer = getMonthNumber($aankomstmaand);
  $vertrekmaandNummer = getMonthNumber($vertrekmaand);
  if($aankomstmaandNummer === -1 || $vertrekmaandNummer === -1) {
    incompleteData("aankomsts- en/of vertrekmaand");
  }
  if (!validDate($aankomstjaar, $aankomstmaandNummer, $aankomstdag, $aankomstuur, $aankomstminuut)
  || !validDate($vertrekjaar, $vertrekmaandNummer, $vertrekdag, $vertrekuur, $vertrekminuut)) {
    //Wrong dates, so indicate that
    incompleteData("aankomst- en/of vertrekdatum");
  }

  //Dates valid, so create the actual string
  $start = new DateTime();
  $start->setDate($aankomstjaar, $aankomstmaandNummer, $aankomstdag);
  $start->setTime($aankomstuur, $aankomstminuut);
  $startSTR = $start->format('Y-m-d H:i:s');
  $end = new DateTime();
  $end->setDate($vertrekjaar, $vertrekmaandNummer, $vertrekdag);
  $end->setTime($vertrekuur, $vertrekminuut);
  $endSTR = $end->format('Y-m-d H:i:s');

  // Make sure the start is before the end
  if ($start > $end){
    echo "aankomstdatum is na de vertrekdatum";
  }

  //Now all data is OK, start test
  $diff = getDifferenceInHours($startSTR, $endSTR);
  $diff_new = getDifferenceInHours_new($startSTR, $endSTR);
  echo "Diff in Hours: " . $diff . " ; " . $diff_new;

  echo "<br>";
  $diff = getDifferenceInDays($startSTR, $endSTR);
  $diff_new = getDifferenceInDays_new($startSTR, $endSTR);
  echo "Diff in days: " . $diff . " ; " . $diff_new;

  echo "<br>";
  $price = getKostenByDate($startSTR, $endSTR, 20);
  $price_new = getKostenByDate_new($startSTR, $endSTR, 20);
  echo "Prices are: " . $price . " ; " . $price_new;

  echo "<br>";
  $borg = getBorg($diff);
  $borg_new = getBorg_new($diff_new);
  echo "Borg is: " . $borg . " ; " . $borg_new;

} else {
	echo "not all was set";
}


?>