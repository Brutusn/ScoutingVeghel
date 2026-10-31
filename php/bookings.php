<?php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/privacy/service.php';

/** Execute the existing rental procedures and release all result sets. */
function rentalProcedure($name, $parameters = []) {
    $allowed = [
        'GetHuurder',
        'CreateHuurder',
        'HuurderIDGroepscode',
        'GetHuurderFromID',
        'InsertReservering',
        'GetReservations',
        'ConfirmReservering',
        'ReserveringConfirmable',
        'GetVerhuringFromConfirm',
        'InsertVerhuring',
        'GetConfirm',
        'GetHuurovereenkomstData'
    ];
    if (!in_array($name, $allowed, true)) {
        throw new InvalidArgumentException('Onbekende verhuurprocedure');
    }
    $database = siteDatabaseConnection();
    try {
        $statement = $database->prepare(
            'CALL ' . $name . '(' . implode(',', array_fill(0, count($parameters), '?')) . ')'
        );
        try {
            if ($parameters) {
                $statement->bind_param(str_repeat('s', count($parameters)), ...$parameters);
            }
            $statement->execute();
            $rows = [];
            $first = true;
            do {
                $result = $statement->get_result();
                if ($result) {
                    if ($first) {
                        $rows = $result->fetch_all(MYSQLI_NUM);
                        $first = false;
                    }
                    $result->free();
                }
            } while ($statement->more_results() && $statement->next_result());
            return $rows;
        } finally {
            $statement->close();
        }
    } finally {
        $database->close();
    }
}
function getHuurder($name, $contact, $email, $phone, $address, $postal, $city) {
    $rows = rentalProcedure('GetHuurder', [
        $name,
        $contact,
        $email,
        $phone,
        $address,
        $postal,
        $city
    ]);
    if (isset($rows[0][0]) && (int) $rows[0][0] !== -1) {
        return (int) $rows[0][0];
    }
    $rows = rentalProcedure('CreateHuurder', [
        $name,
        $contact,
        $email,
        $phone,
        $postal,
        $city,
        $address,
        $_SERVER['REMOTE_ADDR'] ?? ''
    ]);
    return (int) ($rows[0][0] ?? -1);
}
function getHuurderIDFromCode($code) {
    $rows = rentalProcedure('HuurderIDGroepscode', [$code]);
    return (int) ($rows[0][0] ?? -1);
}
function getInfoFromHid($id) {
    if ((int) $id === -1) {
        return ['', ''];
    }
    $rows = rentalProcedure('GetHuurderFromID', [$id]);
    return $rows[0] ?? ['', ''];
}
function getReservering($description, $start, $end, $people) {
    $rows = rentalProcedure('InsertReservering', [$description, $start, $end, $people]);
    return (int) ($rows[0][0] ?? -1);
}
function getReservations(DateTime $start, DateTime $end) {
    $rows = rentalProcedure('GetReservations', [
        $start->format('Y-m-d H:i:s'),
        $end->format('Y-m-d H:i:s')
    ]);
    $bookings = [];
    foreach ($rows as $row) {
        $bookings[] = [
            'dayFrom' => (new DateTime($row[0]))->format('Y-m-d'),
            'dayTo' => (new DateTime($row[1]))->format('Y-m-d'),
            'bySV' => $row[2] != null
        ];
    }
    return $bookings;
}
function reserveringConfirmed($id) {
    rentalProcedure('ConfirmReservering', [$id]);
}
function isReserveringConfirmable($id) {
    return count(rentalProcedure('ReserveringConfirmable', [$id])) > 0;
}
function alreadyReserved(DateTime $start, DateTime $end) {
    return count(getReservations($start, $end)) > 0;
}
function getVerhuringFromConfirm($key) {
    if (privacyEnabled()) {
        $database = siteDatabaseConnection();
        try {
            $rows = privacyToken($database, $key);
            return count($rows) === 1
                ? [
                    (int) $rows[0]['id'],
                    (int) $rows[0]['huurder_id'],
                    (int) $rows[0]['reservering_id']
                ]
                : [-1, -1, -1];
        } finally {
            $database->close();
        }
    }
    $rows = rentalProcedure('GetVerhuringFromConfirm', [$key]);
    return isset($rows[0]) ? array_map('intval', $rows[0]) : [-1, -1, -1];
}
function createVerhuring($tenant, $reservation, $group) {
    $rows = rentalProcedure('InsertVerhuring', [$tenant, $reservation, $group]);
    return (int) ($rows[0][0] ?? -1);
}
function getConfirm($tenant, $reservation) {
    $rows = rentalProcedure('GetConfirm', [$tenant, $reservation]);
    return $rows[0][0] ?? 'error';
}
function getHuurovereenkomstData($id) {
    $rows = rentalProcedure('GetHuurovereenkomstData', [$id]);
    if (!$rows) {
        return [];
    }
    $keys = [
        'huurder_naam',
        'huurder_telefoon',
        'huurder_adres',
        'huurder_postcode',
        'huurder_plaats',
        'verhuring_begin_datum',
        'verhuring_eind_datum',
        'verhuring_aantal_personen',
        'verhuring_borg_limiet',
        'verhuring_huurprijs_limiet',
        'datum'
    ];
    return array_combine($keys, $rows[0]);
}
function getBookingNotificationData($id) {
    $database = siteDatabaseConnection();
    try {
        $statement = $database->prepare(
            'SELECT v.id,v.groep,h.naam,h.contactpersoon,h.email,h.telefoon,r.begindatum,r.einddatum,r.personen,r.beschrijving,EXISTS(SELECT 1 FROM verhuur_eigen_groep WHERE huurder_id=v.huurder_id) AS own_group FROM verhuur_verhuring v JOIN verhuur_huurder h ON h.id=v.huurder_id JOIN verhuur_reservering r ON r.id=v.reservering_id WHERE v.id=?'
        );
        $statement->bind_param('i', $id);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();
        if (!$row) {
            throw new RuntimeException('Reserveringsgegevens ontbreken.');
        }
        return $row;
    } finally {
        $database->close();
    }
}
