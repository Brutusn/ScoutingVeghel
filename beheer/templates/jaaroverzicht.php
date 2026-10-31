<?php
function adminCalendarView($start, $months) {
    $names = [
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
    $last = $start->modify('+14 months');
    $defaultStart = adminCalendarStart();
    $isCurrent = $start->format('Y-m') === $defaultStart->format('Y-m');
    $period =
        $names[(int) $start->format('n')] .
        ' ' .
        $start->format('Y') .
        ' – ' .
        $names[(int) $last->format('n')] .
        ' ' .
        $last->format('Y');
    $html =
        '<header class="calendar-heading"><div><h1>Kalender</h1><p>' .
        $period .
        '</p>' .
        '</div>' .
        '<a class="calendar-reset" href="manage.php?m=kalender">Rond deze maand</a>' .
        '</header>' .
        '<div class="calendar-toolbar">' .
        '<nav aria-label="Kalenderperiode">' .
        '<a href="manage.php?m=kalender&amp;from=' .
        $start->modify('-3 months')->format('Y-m') .
        '">← 3 maanden terug</a><a href="manage.php?m=kalender&amp;from=' .
        $start->modify('+3 months')->format('Y-m') .
        '">3 maanden vooruit →</a>' .
        '</nav>' .
        '<form method="get" action="manage.php">' .
        '<input type="hidden" name="m" value="kalender">' .
        '<label for="calendar-start">Vanaf</label>' .
        '<input type="month" id="calendar-start" name="from" value="' .
        $start->format('Y-m') .
        '" min="1900-01" max="2200-12" required><button type="submit">Bekijken</button></form></div>';
    $html .=
        '<aside class="calendar-legend" aria-label="Kleurenlegenda">' .
        '<div>' .
        '<h2>Kleur van het dagcijfer</h2>' .
        '<ul>' .
        '<li>' .
        '<span class="calendar-number calendar-number-own" aria-hidden="true">7</span> Eigen groep</li>' .
        '<li>' .
        '<span class="calendar-number calendar-number-external" aria-hidden="true">7</span> Externe verhuur</li>' .
        '<li>' .
        '<span class="calendar-number calendar-number-missing" aria-hidden="true">7</span> Info ontbreekt</li>' .
        '</ul>' .
        '</div>' .
        '<div>' .
        '<h2>Achtergrond: verhuurstatus</h2>' .
        '<ul>';
    foreach (adminCalendarStatuses() as $id => $label) {
        $html .=
            '<li><span class="legend-swatch calendar-status-' .
            $id .
            '" aria-hidden="true"></span>' .
            htmlspecialchars($label, ENT_QUOTES, 'UTF-8') .
            '</li>';
    }
    $html .=
        '</ul>' .
        '</div>' .
        '<p>Verschillende statussen op dezelfde dag verschijnen als kleurstroken. Meerdere cijfers betekenen verschillende typen verhuur op dezelfde dag. Rood betekent dat de huurderkoppeling of huurdernaam ontbreekt. Klik op een dag voor de boekingen. Geannuleerde en verwijderde aanvragen tellen niet mee.</p>' .
        '</aside>' .
        '<section class="calendar-section" aria-labelledby="calendar-recent-title">' .
        '<h2 id="calendar-recent-title">' .
        ($isCurrent ? 'Terugblik en deze maand' : 'Geselecteerde periode') .
        '</h2><div class="year-grid calendar-grid calendar-recent-grid">' .
        implode('', array_slice($months, 0, 3)) .
        '</div></section><section class="calendar-section" aria-labelledby="calendar-future-title"><h2 id="calendar-future-title">' .
        ($isCurrent ? 'Komende twaalf maanden' : 'Volgende twaalf maanden') .
        '</h2><div class="year-grid calendar-grid calendar-future-grid">' .
        implode('', array_slice($months, 3, 12)) .
        '</div></section>';
    return '<main class="admin-calendar">' . $html . '</main>';
}
