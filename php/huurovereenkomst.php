<?php

require_once("db_layer.php");
require_once('templateParser.php');
require_once("verhuur_settings.php");
require_once('kostenberekening_nieuw.php');

$HUUROVEREENKOMST_TEMPLATE_URL = 'templates/huurovereenkomst.tpl';

session_start();
date_default_timezone_set('Europe/Paris');

//get verhuringID from provided key
$key = trim(strip_tags($_GET["key"]), " \n");
$verhuring = getVerhuringFromConfirm($key);
$verhuring_id = $verhuring[0];

// if there is a verhuring for this key, continue
if ($verhuring_id != -1) {

	//make template parser initilize
	$template = new templateParser($HUUROVEREENKOMST_TEMPLATE_URL);

	// Load all data into huurovereenkomst.
	$tags = array(
		'verhuurder_naam' => HV_VERHUURDER_NAME,
		'verhuurder_vertegenwoordiger' => HV_VERHUURDER_REP,
		'verhuurder_adres' => HV_VERHUURDER_ADDRESS,
		'verhuurder_plaats' => HV_VERHUURDER_POSTAL,
		'verhuurder_telefoon' => HV_VERHUURDER_PHONE,
		'verhuurder_beheerder' => HV_BEHEERDER,
		'verhuurder_beheerder_telefoon' => HV_BEHEERDER_PHONE,
		'verhuurder_rekeningnummer' => HV_VERHUURDER_ACCOUNT
	);

	//Get data to fill template from DB
	$data_array = getHuurovereenkomstData($verhuring_id);
	if (!empty($data_array)){
		$tags = array_merge($tags, $data_array);
		$tags['verhuring_kenmerk_borg'] = 'WVB/'.$verhuring_id;
		$tags['verhuring_kenmerk_huur'] = 'WVH/'.$verhuring_id;
		$tags['verhuring_borg'] = '<strong>'.getBorg(getDifferenceInDays($tags['verhuring_begin_datum'], $tags['verhuring_eind_datum'])).'</strong>';
		$tags['verhuring_prijs']  = '<strong>' .
			(getKostenByDate($tags['verhuring_begin_datum'], $tags['verhuring_eind_datum'], $tags['verhuring_aantal_personen'])-
			getBorg(getDifferenceInDays($tags['verhuring_begin_datum'], $tags['verhuring_eind_datum']))).'</strong>';
		//parse template and show result
		$template->parseTemplate($tags);
		echo $template->display();
		//echo the print button
		echo "<p class='print_ignore'><input class='button' id='print' value='Print' type='button' onclick='printPage()'></p>";
		echo "<script>function printPage() {window.print();}</script>";
	} else {//Althought here was a verhuring for the key, no data could be retrieved
		echo '[ERROR] Er kon geen data gevonden worden voor de verhuring.';
	}
} else { //ther eis no verhuring for this key
	echo '[ERROR] Er kon geen verhuring gevonden worden.';
}
?>
