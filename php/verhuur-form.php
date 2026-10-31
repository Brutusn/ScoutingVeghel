<?php
require_once __DIR__ . '/rental-settings.php';
require_once __DIR__ . '/bookings.php';
require_once __DIR__ . '/dates.php';
require_once __DIR__ . '/rental-mail.php';
date_default_timezone_set('Europe/Amsterdam');

function rentalFormText($field) {
    $value = $_POST[$field] ?? null;
    return is_string($value) ? trim(strip_tags($value)) : null;
}
function rentalFormDate($prefix) {
    $parts = [];
    foreach (['jaar', 'maand', 'dag', 'uur', 'minuut'] as $part) {
        $parts[] = rentalFormText($prefix . '-' . $part);
    }
    $parts[1] = getMonthNumber($parts[1]);
    if (!validDate(...$parts)) {
        reservationStatus(
            'Controleer je gegevens',
            'Niet alle velden zijn correct ingevuld. Specifiek: aankomst- en/of vertrekdatum.'
        );
    }
    return new DateTime(sprintf('%04d-%02d-%02d %02d:%02d:00', ...$parts));
}

$groupCode = rentalFormText('groepcode');
$people = rentalFormText('people');
$description = rentalFormText('tArea');
if ($groupCode === null || $people === null || $description === null || $description === '') {
    reservationStatus(
        'Aanvraag niet verstuurd',
        'Niet alle velden zijn ingevuld. Ga terug naar het formulier en controleer je invoer.'
    );
}
$fields = [
    'naam' => 'name',
    'contactpersoon' => 'contactperson',
    'email' => 'mailadr',
    'telefoon' => 'phone',
    'adres' => 'adress',
    'postcode' => 'postcode',
    'plaats' => 'city'
];
$contact = [];
foreach ($fields as $key => $field) {
    $contact[$key] = rentalFormText($field) ?? '';
}
$contact['ip'] = $_SERVER['REMOTE_ADDR'] ?? '';
if (
    $groupCode === '' &&
    (in_array('', array_intersect_key($contact, $fields), true) ||
        !filter_var($contact['email'], FILTER_VALIDATE_EMAIL))
) {
    reservationStatus(
        'Aanvraag niet verstuurd',
        'Niet alle velden zijn ingevuld. Ga terug naar het formulier en controleer je invoer.'
    );
}
$start = rentalFormDate('aankomst');
$end = rentalFormDate('vertrek');
if ($start >= $end) {
    reservationStatus(
        'Controleer je gegevens',
        'Niet alle velden zijn correct ingevuld. Specifiek: aankomstdatum is na de vertrekdatum.'
    );
}
if ($end > (clone $start)->modify('+' . MAX_AANTAL_OVERNACHTINGEN . ' days')) {
    reservationStatus(
        'Periode te lang',
        'De duur van een optie mag maximaal ' .
            MAX_AANTAL_OVERNACHTINGEN .
            ' overnachtingen zijn. Voor een langere huurperiode kun je contact met ons opnemen.'
    );
}
if (
    !ctype_digit($people) ||
    (int) $people < MIN_AANTAL_PERSONEN ||
    (int) $people > MAX_AANTAL_PERSONEN
) {
    reservationStatus(
        'Aantal personen controleren',
        'Het aantal personen moet tussen ' .
            MIN_AANTAL_PERSONEN .
            ' en ' .
            MAX_AANTAL_PERSONEN .
            ' liggen. Neem contact met ons op voor een grotere groep.'
    );
}
$startText = $start->format('Y-m-d H:i:s');
$endText = $end->format('Y-m-d H:i:s');
try {
    $overlap = alreadyReserved($start, $end);
    $groupId = 0;
    if ($groupCode !== '') {
        $groupId = getHuurderIDFromCode($groupCode);
        if ($groupId < 1) {
            reservationStatus(
                'Controleer je gegevens',
                'Niet alle velden zijn correct ingevuld. Specifiek: de groepscode is incorrect.'
            );
        }
        [$contact['naam'], $contact['email']] = getInfoFromHid($groupId);
        $contact['contactpersoon'] = $contact['naam'];
    }
    if (privacyEnabled()) {
        $database = siteDatabaseConnection();
        try {
            $booking = privacyCreate(
                $database,
                $contact,
                $startText,
                $endText,
                (int) $people,
                $description,
                $groupId,
                0
            );
            $id = (int) $booking['id'];
            $token = $booking['confirm'];
        } finally {
            $database->close();
        }
    } else {
        $tenant =
            $groupId ?:
            getHuurder(
                $contact['naam'],
                $contact['contactpersoon'],
                $contact['email'],
                $contact['telefoon'],
                $contact['adres'],
                $contact['postcode'],
                $contact['plaats']
            );
        if ($tenant < 1) {
            throw new RuntimeException('Huurder ontbreekt');
        }
        $reservation = getReservering($description, $startText, $endText, (int) $people);
        if ($reservation < 1) {
            throw new RuntimeException('Reservering ontbreekt');
        }
        $id = createVerhuring($tenant, $reservation, $groupId ? $contact['naam'] : 'NULL');
        $token = getConfirm($tenant, $reservation);
        if ($id < 1 || $token === 'error') {
            throw new RuntimeException('Verhuring ontbreekt');
        }
    }
} catch (Throwable $error) {
    error_log('Scouting Veghel: booking creation failed.');
    reservationStatus(
        'Aanvraag tijdelijk niet verwerkt',
        'Er ging iets mis bij het verwerken van je aanvraag. Probeer het later opnieuw of neem contact met ons op.'
    );
}
// The booking is already saved. A mail failure must not invite a duplicate.
try {
    if (
        !sendNewBookingEmail(
            $id,
            $contact['naam'],
            $contact['contactpersoon'],
            $contact['email'],
            $contact['telefoon'],
            $startText,
            $endText,
            $people,
            $description,
            $groupId > 0,
            $overlap
        )
    ) {
        error_log('New booking notification could not be sent.');
    }
} catch (Throwable $error) {
    error_log('New booking notification could not be sent.');
}
try {
    sendConfirmEmail(
        $contact['email'],
        $contact['contactpersoon'],
        $token,
        $contact['naam'],
        $startText,
        $endText,
        $people
    );
} catch (Throwable $error) {
    error_log('Booking mail could not be sent.');
}
if ($overlap) {
    reservationStatus(
        'Aanvraag ontvangen',
        'Er staat al een optie in deze periode. Je aanvraag is wel geregistreerd. Neem contact met ons op; de bevestigingsinstructies zijn naar je e-mailadres gestuurd.',
        'warning'
    );
}
reservationStatus(
    'Aanvraag ontvangen',
    'Je ontvangt een e-mail met een link om je optie te bevestigen. Controleer ook je map met ongewenste e-mail.',
    'success'
);

function reservationStatus($title, $message, $kind = 'error') {
    $isAjax =
        isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    if (!$isAjax && isset($_SERVER['HTTP_SEC_FETCH_MODE'], $_SERVER['HTTP_SEC_FETCH_DEST'])) {
        $isAjax =
            strtolower($_SERVER['HTTP_SEC_FETCH_MODE']) === 'cors' &&
            $_SERVER['HTTP_SEC_FETCH_DEST'] === 'empty';
    }
    if ($isAjax) {
        echo $message;
        exit();
    }

    $safeTitle = htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeMessage = htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeKind = in_array($kind, ['success', 'warning'], true) ? $kind : 'error';
    $role = $safeKind === 'success' ? 'status' : 'alert';

    header('Content-Type: text/html; charset=UTF-8');
    echo '<!doctype html><html lang="nl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">' .
        '<title>' .
        $safeTitle .
        ' | Scouting Veghel verhuur</title>' .
        '<link rel="stylesheet" href="../css/style.min.css"><link rel="stylesheet" href="../css/status.css"></head>' .
        '<body class="booking-status-page"><main class="booking-status booking-status--' .
        $safeKind .
        '">' .
        '<p class="booking-status__eyebrow">Scouting Veghel · Verhuur</p>' .
        '<h1>' .
        $safeTitle .
        '</h1><p class="booking-status__message" role="' .
        $role .
        '">' .
        $safeMessage .
        '</p>' .
        '<nav class="booking-status__actions" aria-label="Vervolgacties">' .
        '<a class="booking-status__primary" href="../calendar.html#boeken">Terug naar verhuurkalender</a>' .
        '<a class="booking-status__secondary" href="../index.html">Naar de website</a>' .
        '</nav></main></body></html>';
    exit();
}
