<?php
require_once __DIR__ . '/../php/database.php';
function adminCalendarStatuses() {
    return [
        0 => 'Optie',
        1 => 'Bevestigd',
        2 => 'Goedgekeurd',
        3 => 'Borg betaald',
        4 => 'Betaald',
        5 => 'Bezig',
        6 => 'Nacontrole',
        7 => 'Borg terug te storten',
        8 => 'Afgehandeld',
        12 => 'Onbetaald plaatsgevonden'
    ];
}
function adminCalendarStart($value = '', $year = null, $today = null) {
    $today = $today ?? new DateTimeImmutable('today');
    if ($value === '') {
        if ($year !== null && $year >= 1900 && $year <= 2200) {
            return new DateTimeImmutable($year . '-01-01');
        }
        return $today->modify('first day of this month')->modify('-2 months');
    }
    if (
        !is_string($value) ||
        !preg_match('/^(19\d{2}|20\d{2}|21\d{2}|2200)-(0[1-9]|1[0-2])$/D', $value)
    ) {
        throw new InvalidArgumentException('Kies een geldige startmaand.');
    }
    return new DateTimeImmutable($value . '-01');
}
function adminCalendarReservations($db, $start, $end) {
    $statement = $db->prepare(
        'SELECT r.begindatum,r.einddatum,r.status_id,v.groep,h.id AS tenant_id,h.naam AS tenant_name, EXISTS(SELECT 1 FROM verhuur_eigen_groep WHERE huurder_id=v.huurder_id) AS own_group FROM verhuur_verhuring v JOIN verhuur_reservering r ON r.id=v.reservering_id LEFT JOIN verhuur_huurder h ON h.id=v.huurder_id WHERE r.begindatum<=? AND r.einddatum>=? AND (r.status_id<10 OR r.status_id=12) ORDER BY r.begindatum,v.id'
    );
    $statement->bind_param('ss', $end, $start);
    $statement->execute();
    $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    $statement->close();
    return $rows;
}
function adminCalendarKind($r) {
    if (empty($r['tenant_id']) || trim((string) ($r['tenant_name'] ?? '')) === '') {
        return 'missing';
    }
    $group = trim((string) ($r['groep'] ?? ''));
    return !empty($r['own_group']) || ($group !== '' && strtoupper($group) !== 'NULL')
        ? 'own'
        : 'external';
}
// Arrival and departure both occupy a calendar date, as in the existing calendar.
function adminAgendaDays($reservations, $year, $month) {
    $start = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
    $end = $start->modify('last day of this month');
    $days = [];
    for ($d = $start; $d <= $end; $d = $d->modify('+1 day')) {
        $days[$d->format('Y-m-d')] = [
            'own' => 0,
            'external' => 0,
            'missing' => 0,
            'statuses' => []
        ];
    }
    foreach ($reservations as $r) {
        $status = (int) ($r['status_id'] ?? 0);
        if (!array_key_exists($status, adminCalendarStatuses())) {
            continue;
        }
        $firstText = substr((string) ($r['begindatum'] ?? ''), 0, 10);
        $lastText = substr((string) ($r['einddatum'] ?? ''), 0, 10);
        $first = DateTimeImmutable::createFromFormat('!Y-m-d', $firstText);
        $last = DateTimeImmutable::createFromFormat('!Y-m-d', $lastText);
        if (
            !$first ||
            !$last ||
            $first->format('Y-m-d') !== $firstText ||
            $last->format('Y-m-d') !== $lastText ||
            $first > $last
        ) {
            continue;
        }
        $kind = adminCalendarKind($r);
        for ($d = max($first, $start); $d <= min($last, $end); $d = $d->modify('+1 day')) {
            $key = $d->format('Y-m-d');
            $days[$key][$kind]++;
            $days[$key]['statuses'][$status] = ($days[$key]['statuses'][$status] ?? 0) + 1;
        }
    }
    return $days;
}
function displayMonth($month, $year = 0, $reservations = null, $today = null, $periodStart = null) {
    $year = $year ?: (int) date('Y');
    $today = $today ?? new DateTimeImmutable('today');
    $start = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
    if ($reservations === null) {
        $db = siteDatabaseConnection();
        $reservations = adminCalendarReservations(
            $db,
            $start->format('Y-m-d 00:00:00'),
            $start->modify('last day of this month')->format('Y-m-d 23:59:59')
        );
    }
    $days = adminAgendaDays($reservations, $year, $month);
    $statuses = adminCalendarStatuses();
    $months = [
        '',
        'januari',
        'februari',
        'maart',
        'april',
        'mei',
        'juni',
        'juli',
        'augustus',
        'september',
        'oktober',
        'november',
        'december'
    ];
    $name = $months[$month] . ' ' . $year;
    $class = 'agenda_maand calendar-month';
    if ($start->format('Y-m') < $today->format('Y-m')) {
        $class .= ' calendar-month-past';
    }
    if ($start->format('Y-m') === $today->format('Y-m')) {
        $class .= ' calendar-month-current';
    }
    $html =
        '<table class="' .
        $class .
        '" data-month="' .
        $start->format('Y-m') .
        '"><caption>' .
        $name .
        ($start->format('Y-m') === $today->format('Y-m')
            ? '<span class="calendar-month-badge">Deze maand</span>'
            : '') .
        '</caption><thead><tr>';
    foreach (['Ma', 'Di', 'Wo', 'Do', 'Vr', 'Za', 'Zo'] as $weekday) {
        $html .= '<th scope="col" class="agenda_item dag">' . $weekday . '</th>';
    }
    $html .= '</tr></thead><tbody><tr>';
    $column = (int) $start->format('N') - 1;
    $html .= str_repeat('<td class="agenda_item calendar-empty"></td>', $column);
    foreach ($days as $date => $counts) {
        $number = (int) substr($date, 8, 2);
        $occupied = $counts['own'] + $counts['external'] + $counts['missing'];
        $details = $occupied
            ? $counts['own'] .
                ' eigen, ' .
                $counts['external'] .
                ' extern, ' .
                $counts['missing'] .
                ' info ontbreekt'
            : 'Geen activiteiten';
        ksort($counts['statuses']);
        $statusLabels = [];
        foreach ($counts['statuses'] as $id => $count) {
            $statusLabels[] = $statuses[$id] . ' (' . $count . ')';
        }
        if ($statusLabels) {
            $details .= '; ' . implode(', ', $statusLabels);
        }
        $label = htmlspecialchars($number . ' ' . $name . ': ' . $details, ENT_QUOTES, 'UTF-8');
        $class = 'agenda_item' . ($date === $today->format('Y-m-d') ? ' calendar-today' : '');
        $html .=
            '<td class="' .
            $class .
            '" data-date="' .
            $date .
            '"><a class="calendar-day" href="page/overzichten/agenda_day.php?y=' .
            $year .
            '&amp;m=' .
            $month .
            '&amp;d=' .
            $number .
            '&amp;from=' .
            ($periodStart ?? $start->format('Y-m')) .
            '" title="' .
            $label .
            '" aria-label="' .
            $label .
            '"' .
            ($date === $today->format('Y-m-d') ? ' aria-current="date"' : '') .
            '>';
        if ($occupied) {
            $html .= '<span class="calendar-status-bands" aria-hidden="true">';
            foreach ($counts['statuses'] as $id => $count) {
                $html .= '<span class="calendar-status-' . $id . '"></span>';
            }
            $html .= '</span>';
        }
        $html .= '<span class="calendar-numbers">';
        if ($counts['own']) {
            $html .= '<span class="calendar-number calendar-number-own">' . $number . '</span>';
        }
        if ($counts['external']) {
            $html .=
                '<span class="calendar-number calendar-number-external">' . $number . '</span>';
        }
        if ($counts['missing']) {
            $html .= '<span class="calendar-number calendar-number-missing">' . $number . '</span>';
        }
        if (!$occupied) {
            $html .= '<span class="calendar-number">' . $number . '</span>';
        }
        $html .= '</span></a></td>';
        if (++$column === 7) {
            $html .= '</tr>';
            if ($number < count($days)) {
                $html .= '<tr>';
            }
            $column = 0;
        }
    }
    if ($column) {
        $html .= str_repeat('<td class="agenda_item calendar-empty"></td>', 7 - $column) . '</tr>';
    }
    return $html . '</tbody></table>';
}
