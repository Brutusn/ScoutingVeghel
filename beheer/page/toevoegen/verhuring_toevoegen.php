<?php
require_once __DIR__ . '/../../security.php';
adminRequireLogin();
require_once __DIR__ . '/../../../php/privacy/service.php';
if (privacyEnabled()) {
    http_response_code(410);
    exit('Gebruik het nieuwe formulier voor een verhuring.');
}

define('OUTSIDE_PAGE', 1);

require_once __DIR__ . '/../../../php/database.php';
require_once __DIR__ . '/../../lib/functions.php';

$database = siteDatabaseConnection();
require_once __DIR__ . '/../../groups.php';

function rentalSqlDate($day, $month, $year, $hour, $minute) {
    if (
        !checkdate((int) $month, (int) $day, (int) $year) ||
        $hour < 0 ||
        $hour > 23 ||
        $minute < 0 ||
        $minute > 59
    ) {
        throw new InvalidArgumentException('Ongeldige datum.');
    }
    return sprintf('%04d-%02d-%02d %02d:%02d:00', $year, $month, $day, $hour, $minute);
}
function addItem($tenant, $start, $end, $people, $description, $status, $group) {
    global $database;
    $statement = $database->prepare(
        'INSERT INTO verhuur_reservering (beschrijving,begindatum,einddatum,personen,status_id) VALUES (?,?,?,?,?)'
    );
    $statement->bind_param('sssii', $description, $start, $end, $people, $status);
    $statement->execute();
    $reservation = $statement->insert_id;
    $statement->close();
    try {
        $token = bin2hex(random_bytes(32));
        $statement = $database->prepare(
            'INSERT INTO verhuur_verhuring (huurder_id,reservering_id,datum,confirm,groep) VALUES (?,?,NOW(),?,?)'
        );
        $statement->bind_param('iiss', $tenant, $reservation, $token, $group);
        $statement->execute();
        $statement->close();
    } catch (Throwable $error) {
        $database->query('DELETE FROM verhuur_reservering WHERE id=' . (int) $reservation);
        throw $error;
    }
}

$avb = [
    'huurder',
    'groep',
    'VAN_DAG',
    'VAN_MAAND',
    'VAN_JAAR',
    'TOT_DAG',
    'TOT_MAAND',
    'TOT_JAAR',
    'VAN_UUR',
    'VAN_MIN',
    'TOT_UUR',
    'TOT_MIN',
    'personen',
    'beschrijving',
    'status'
];

//array contains all values also included in $avb
function run_checks($a) {
    global $avb;
    $foutmeldingen = '';

    if (!m_isset($avb)) {
        $foutmeldingen .=
            '<p><strong>[Foutmelding]</strong> Niet alle benodigde velden hebben een waarde</p>';
    }
    if ($a['huurder'] == 0) {
        $foutmeldingen .= '<p><strong>[Foutmelding]</strong> De huurder is niet opgegeven</p>';
    }
    if (
        mktime($a['VAN_UUR'], $a['VAN_MIN'], 0, $a['VAN_MAAND'], $a['VAN_DAG'], $a['VAN_JAAR']) >=
        mktime($a['TOT_UUR'], $a['TOT_MIN'], 0, $a['TOT_MAAND'], $a['TOT_DAG'], $a['TOT_JAAR'])
    ) {
        $foutmeldingen .=
            '<p><strong>[Foutmelding]</strong> De begindatum ligt na de einddatum</p>';
    }
    if ($a['status'] == 0) {
        $foutmeldingen .=
            '<p><strong>[Waarschuwing]</strong> De verhuring heeft nog steeds de status "optie".</p>';
    }
    return $foutmeldingen;
}

if (!m_isset($avb)) {
    die('<h2>Verzonden waarden onvolledig/ongeldig</h2>');
}

try {
    $_POST['groep'] = adminGroupForNewRental($database, $_POST['huurder'], $_POST['groep']);
} catch (InvalidArgumentException $error) {
    http_response_code(400);
    exit('<p>Kies een geldige huurder en bijbehorende eigen groep.</p>');
}
my_var_post();

$check = run_checks($_V['POST']);
if ($check != '') {
    die('<h2>Er ging iets mis..</h2>' . $check);
}

if (isset($_POST['repeterend_periode'])) {
    echo '<h2>De volgende data zijn toegevoegd aan de agenda:</h2>';

    $h = $_V['POST']['VAN_UUR'];
    $i = $_V['POST']['VAN_MIN'];
    $m = $_V['POST']['VAN_MAAND'];
    $d = $_V['POST']['VAN_DAG'];
    $j = $_V['POST']['VAN_JAAR'];
    $begindatum = mktime($h, $i, 0, $m, $d, $j);

    $h1 = $_V['POST']['TOT_UUR'];
    $i1 = $_V['POST']['TOT_MIN'];
    $m1 = $_V['POST']['TOT_MAAND'];
    $d1 = $_V['POST']['TOT_DAG'];
    $j1 = $_V['POST']['TOT_JAAR'];
    $einddatum = mktime($h1, $i1, 0, $m1, $d1, $j1);

    if ($begindatum >= $einddatum) {
        die('<h2>Foutmelding</h2><p>De einddatum is niet kleiner dan de begindatum</p>');
    }

    for ($offset = 0; $offset < 7; $offset++) {
        if (date('D', mktime($h, $i, 0, $m, $d + $offset, $j)) == $_V['POST']['repeterend_dag']) {
            $d = $d + $offset;
            break;
        }
    }

    switch ($_V['POST']['repeterend_periode']) {
        case 'w':
            $days = 7;
            $months = 0;
            break;
        case 't':
            $days = 14;
            $months = 0;
            break;
        case 'm':
            $days = 0;
            $months = 1;
            break;
        default:
            $days = 0;
            $months = 6;
            break;
    }

    echo '<table><tr><th>datum</th></tr>';
    for ($counter = 0; $counter < 50; $counter++) {
        $datum_b = mktime($h, $i, 0, $m + $counter * $months, $d + $counter * $days, $j);
        $datum_e = mktime($h1, $i1, 0, $m + $counter * $months, $d + $counter * $days, $j);

        if ($datum_b < $einddatum) {
            addItem(
                $_V['POST']['huurder'],
                date('Y/m/d H:i:00', $datum_b),
                date('Y/m/d H:i:00', $datum_e),
                $_V['POST']['personen'],
                $_V['POST']['beschrijving'],
                $_V['POST']['status'],
                $_POST['groep']
            );
            echo '<tr><td>' . date('Y/m/d', $datum_b) . '</td></tr>';
        } else {
            break;
        }
    }
    echo '</table>';
} else {
    addItem(
        $_V['POST']['huurder'],
        rentalSqlDate(
            $_V['POST']['VAN_DAG'],
            $_V['POST']['VAN_MAAND'],
            $_V['POST']['VAN_JAAR'],
            $_V['POST']['VAN_UUR'],
            $_V['POST']['VAN_MIN']
        ),
        rentalSqlDate(
            $_V['POST']['TOT_DAG'],
            $_V['POST']['TOT_MAAND'],
            $_V['POST']['TOT_JAAR'],
            $_V['POST']['TOT_UUR'],
            $_V['POST']['TOT_MIN']
        ),
        $_V['POST']['personen'],
        $_V['POST']['beschrijving'],
        $_V['POST']['status'],
        $_POST['groep']
    );
    echo '<h2>Item is toegevoegd</h2>';
}
?>

