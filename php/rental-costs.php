<?php
require_once __DIR__ . '/rental-settings.php';

function rentalDateDifference($begin, $end) {
    $start = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $begin);
    $finish = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $end);
    if (
        !$start ||
        !$finish ||
        $start->format('Y-m-d H:i:s') !== $begin ||
        $finish->format('Y-m-d H:i:s') !== $end ||
        $finish < $start
    ) {
        throw new InvalidArgumentException('Ongeldige huurperiode.');
    }
    return $start->diff($finish);
}
function getDifferenceInHours($begin, $end) {
    $difference = rentalDateDifference($begin, $end);
    return $difference->days * 24 + $difference->h + ($difference->i > 0 ? 1 : 0);
}
function getDifferenceInDays($begin, $end) {
    return rentalDateDifference($begin, $end)->days;
}
function getKostenByDate($begin, $end, $people) {
    return getKosten(
        getDifferenceInDays($begin, $end),
        getDifferenceInHours($begin, $end),
        $people
    );
}
function getBorg($days) {
    if ($days == 0) {
        return BEDRAG_BORG_DAGDEEL;
    }
    return $days == 1 ? BEDRAG_BORG_NACHT : BEDRAG_BORG_LANGER;
}
function getKosten($days, $hours, $people) {
    if ($hours > 8 && $days == 0) {
        $days = 1;
    }
    return ($days == 0 ? $hours * KOSTEN_PER_UUR : $days * $people * KOSTEN_PPPN) + getBorg($days);
}
