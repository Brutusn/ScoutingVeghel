<?php
header('Cache-Control: no-store');
header('Content-Type: text/html; charset=UTF-8');

require_once __DIR__ . '/bookings.php';
require_once __DIR__ . '/rental-settings.php';
require_once __DIR__ . '/rental-costs.php';

date_default_timezone_set('Europe/Amsterdam');

if (
    !isset($_GET['key']) ||
    !is_string($_GET['key']) ||
    trim($_GET['key']) === '' ||
    strlen($_GET['key']) > 1024
) {
    exit('[ERROR] Er kon geen verhuring gevonden worden.');
}

//get verhuringID from provided key
$key = trim(strip_tags($_GET['key']), " \n");
$verhuring = getVerhuringFromConfirm($key);
$verhuring_id = $verhuring[0];

// if there is a verhuring for this key, continue
if ($verhuring_id != -1) {
    // Load all data into huurovereenkomst.
    $contract = rentalContractConfiguration();
    $tags = [
        'verhuurder_naam' => htmlspecialchars(
            $contract['HV_VERHUURDER_NAME'],
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        ),
        'verhuurder_vertegenwoordiger' => htmlspecialchars(
            $contract['HV_VERHUURDER_REP'],
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        ),
        'verhuurder_adres' => htmlspecialchars(
            $contract['HV_VERHUURDER_ADDRESS'],
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        ),
        'verhuurder_plaats' => htmlspecialchars(
            $contract['HV_VERHUURDER_POSTAL'],
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        ),
        'verhuurder_telefoon' => htmlspecialchars(
            $contract['HV_VERHUURDER_PHONE'],
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        ),
        'verhuurder_beheerder' => htmlspecialchars(
            $contract['HV_BEHEERDER'],
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        ),
        'verhuurder_beheerder_telefoon' => htmlspecialchars(
            $contract['HV_BEHEERDER_PHONE'],
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        ),
        'verhuurder_rekeningnummer' => htmlspecialchars(
            $contract['HV_VERHUURDER_ACCOUNT'],
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        )
    ];

    //Get data to fill template from DB
    $data_array = getHuurovereenkomstData($verhuring_id);
    if (!empty($data_array)) {
        $tags = array_merge(
            $tags,
            array_map(static function ($value) {
                return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            }, $data_array)
        );
        $tags['verhuring_kenmerk_borg'] = 'WVB/' . $verhuring_id;
        $tags['verhuring_kenmerk_huur'] = 'WVH/' . $verhuring_id;
        $tags['verhuring_borg'] =
            '<strong>' .
            getBorg(
                getDifferenceInDays($tags['verhuring_begin_datum'], $tags['verhuring_eind_datum'])
            ) .
            '</strong>';
        $tags['verhuring_prijs'] =
            '<strong>' .
            (getKostenByDate(
                $tags['verhuring_begin_datum'],
                $tags['verhuring_eind_datum'],
                $tags['verhuring_aantal_personen']
            ) -
                getBorg(
                    getDifferenceInDays(
                        $tags['verhuring_begin_datum'],
                        $tags['verhuring_eind_datum']
                    )
                )) .
            '</strong>';

        echo displayHuurovereenkomst(...$tags);
        //echo the print button
        echo "<p class='print_ignore'><input class='button' id='print' value='Print' type='button'></p>";
        echo "<script src='/js/print-agreement.js'></script>";
    } else {
        //Althought here was a verhuring for the key, no data could be retrieved
        echo '[ERROR] Er kon geen data gevonden worden voor de verhuring.';
    }
} else {
    //ther eis no verhuring for this key
    echo '[ERROR] Er kon geen verhuring gevonden worden.';
}

function displayHuurovereenkomst(
    $verhuurder_naam,
    $verhuurder_vertegenwoordiger,
    $verhuurder_adres,
    $verhuurder_plaats,
    $verhuurder_telefoon,
    $verhuurder_beheerder,
    $verhuurder_beheerder_telefoon,
    $verhuurder_rekeningnummer,
    $huurder_naam,
    $huurder_telefoon,
    $huurder_adres,
    $huurder_postcode,
    $huurder_plaats,
    $verhuring_begin_datum,
    $verhuring_eind_datum,
    $verhuring_aantal_personen,
    $verhuring_borg_limiet,
    $verhuring_huurprijs_limiet,
    $datum,
    $verhuring_kenmerk_borg,
    $verhuring_kenmerk_huur,
    $verhuring_borg,
    $verhuring_prijs
) {
    return "
<!DOCTYPE html
	PUBLIC '-//W3C//DTD XHTML 1.0 Strict//EN'
	'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'>
<html>
<head>
   <title>
	   Scouting Veghel huurcontract
   </title>
   <style type='text/css'>
	   body{
		   width: 18cm;
	   }
	   table{
		   border: 1px solid black;
		   width: 100%;
		   border-collapse: collapse;
		   margin: 10px 0px 0px 0px;
	   }
	   th{
		   text-align: left;
		   font-weight: bold;
		   text-decoration: underline;
	   }
	   td{
		   border: 1px solid black;
		   width: 50%;
	   }
   </style>
</head>
<body>
<h1>Huurovereenkomst 'Hopman Joosten Blokhut'</h1>
<h2>Dorshout 29, 5462 GM Veghel</h2>

<table>
   <tr>
	   <th colspan='2'>Verhuurder:</th>
   </tr>
   <tr>
	   <td>Naam:</td>
	   <td>{$verhuurder_naam}</td>
   </tr>
   <tr>
	   <td>Vertegenwoordigd door:</td>
	   <td>{$verhuurder_vertegenwoordiger}</td>
   </tr>
   <tr>
	   <td>Adres:</td>
	   <td>{$verhuurder_adres}</td>
   </tr>
   <tr>
	   <td>Plaats:</td>
	   <td>{$verhuurder_plaats}</td>
   </tr>
   <tr>
	   <td>Telefoon</td>
	   <td>{$verhuurder_telefoon}</td>
   </tr>
</table>

<table>
   <tr>
	   <th colspan='2'>Huurder:</th>
   </tr>
   <tr>
	   <td>Naam:</td>
	   <td>{$huurder_naam}</td>
   </tr>
   <tr>
	   <td>Adres:</td>
	   <td>{$huurder_adres}</td>
   </tr>
   <tr>
	   <td>Plaats:</td>
	   <td>{$huurder_postcode} {$huurder_plaats}</td>
   </tr>
   <tr>
	   <td>Telefoon</td>
	   <td>{$huurder_telefoon}</td>
   </tr>
</table>

<p>
   Bovengenoemde spreken aansluitend op de algemene huurvoorwaarden het volgende af:
</p>

<h3>Afspraken met betrekking tot huurperiode</h3>
<table>
   <tr>
	   <td>Aanvang huurperiode</td>
	   <td>{$verhuring_begin_datum}</td>
   </tr>
   <tr>
	   <td>Einde huurperiode</td>
	   <td>{$verhuring_eind_datum}</td>
   </tr>
</table>

<p>
   Aan het verblijf in de blokhut wordt deelgenomen door in totaal {$verhuring_aantal_personen} personen.
</p>

<p>
   Voor de overdracht van de sleutel voor aanvang van de huurperiode kunt U een afspraak maken met de beheerder, de heer {$verhuurder_beheerder}, telefoon {$verhuurder_beheerder_telefoon}. Tevens kunt U met hem een afspraak maken over de sleutel overdracht na afloop van de huurperiode.
</p>

<h3>Huursom en waarborg</h3>
<p>
   De waarborgsom bedraagt {$verhuring_borg} Euro en moet voor {$verhuring_borg_limiet} overgemaakt zijn op de giro rekening {$verhuurder_rekeningnummer}, onder vermelding van kenmerk <strong>{$verhuring_kenmerk_borg}</strong>.
</p>
<p>
   <strong>Noot:</strong> als de waarborg op genoemde datum niet door Scouting Veghel is ontvangen, komt de reservering te vervallen en wordt de blokhut weer voor huur door anderen vrij gegeven.
</p>
<p>
   De totale huursom bedraagt {$verhuring_prijs} Euro en moet voor {$verhuring_huurprijs_limiet} overgemaakt zijn op de girorekening van Scouting Veghel, onder vermelding van kenmerk <strong>{$verhuring_kenmerk_huur}</strong>
</p>
<p>
   Deze overeenkomst wordt uitgevoerd in het kader van de algemene huurvoorwaarden. De huurder verklaart alle veiligheidsmaatregelen in acht te nemen en akkoord te gaan met alle huurvoorwaarden, zoals vermeld in de bijlagen.
</p>
<table>
   <tr>
	   <td>Huurder</td>
	   <td>Verhuurder</td>
   </tr>
   <tr>
	   <td style='height: 120px;'>" .
        "</td>
	   <td>" .
        "</td>
   </tr>
   <tr>
	   <td>Plaats, datum</td>
	   <td>Veghel,</td>
   </tr>
</table>
<span>
   U wordt verzocht deze overeenkomst getekend te tonen aan de beheerder bij aanvang van de verhuurperiode.
</span>
</body>
</html>
";
}
?>
