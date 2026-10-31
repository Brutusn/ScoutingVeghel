<?php
require_once __DIR__ . '/../../lib/document.php';
require_once __DIR__ . '/../../security.php';
adminRequireLogin();
require_once __DIR__ . '/../../../php/database.php';
require_once __DIR__ . '/../../calendar-functions.php';
date_default_timezone_set('Europe/Amsterdam');
function calendarDayEscape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
$year = filter_var($_GET['y'] ?? '', FILTER_VALIDATE_INT);
$month = filter_var($_GET['m'] ?? '', FILTER_VALIDATE_INT);
$day = filter_var($_GET['d'] ?? '', FILTER_VALIDATE_INT);
if (!$year || $year < 1900 || $year > 2201 || !$month || !$day || !checkdate($month, $day, $year)) {
    http_response_code(400);
    exit('Kies een geldige kalenderdag.');
}
$date = sprintf('%04d-%02d-%02d', $year, $month, $day);
$start = $date . ' 00:00:00';
$end = $date . ' 23:59:59';
try {
    $returnFrom = adminCalendarStart($_GET['from'] ?? sprintf('%04d-%02d', $year, $month))->format(
        'Y-m'
    );
} catch (InvalidArgumentException $e) {
    $returnFrom = sprintf('%04d-%02d', $year, $month);
}
$db = siteDatabaseConnection();
$statement = $db->prepare(
    'SELECT v.id,h.naam,h.id AS tenant_id,h.naam AS tenant_name,v.groep,v.datum,r.status_id,s.status,EXISTS(SELECT 1 FROM verhuur_eigen_groep WHERE huurder_id=v.huurder_id) AS own_group FROM verhuur_verhuring v JOIN verhuur_reservering r ON r.id=v.reservering_id LEFT JOIN verhuur_huurder h ON h.id=v.huurder_id LEFT JOIN verhuur_status s ON s.status_id=r.status_id WHERE r.begindatum<=? AND r.einddatum>=? AND (r.status_id<10 OR r.status_id=12) ORDER BY r.begindatum,v.id'
);
$statement->bind_param('ss', $end, $start);
$statement->execute();
$rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
$statement->close();
?>
<?php echo beheerDocumentStart(
    'Kalenderdag | Beheer',
    '',
    'width=device-width,initial-scale=1'
); ?><main class="admin-calendar"><header class="calendar-detail-header"><h1>Verhuringen op <?= calendarDayEscape(
    sprintf('%02d-%02d-%04d', $day, $month, $year)
) ?></h1><a href="../../manage.php?m=kalender&amp;from=<?= calendarDayEscape(
    $returnFrom
) ?>">Terug naar kalender</a></header>
<?php if (
    !$rows
): ?><p class="empty-state">Geen activiteiten op deze dag.</p><?php else: ?><div class="calendar-day-list"><table><thead><tr><th>Huurder</th><th>Type verhuur</th><th>Groep</th><th>Status</th></tr></thead><tbody>
<?php foreach ($rows as $row):

    $group = trim((string) $row['groep']);
    $kind = adminCalendarKind($row);
    $kindLabel = ['own' => 'Eigen groep', 'external' => 'Extern', 'missing' => 'Info ontbreekt'];
    ?>
<tr><td><a href="../inbox/item.php?i=<?= (int) $row['id'] ?>"><?= calendarDayEscape(
    $row['naam'] ?? 'Huurder ontbreekt'
) ?></a></td><td><span class="calendar-number-<?= calendarDayEscape(
    $kind
) ?>"><?= calendarDayEscape($kindLabel[$kind]) ?></span></td><td><?= calendarDayEscape(
    $group === 'NULL' ? '' : $group
) ?></td><td><span class="legend-swatch calendar-status-<?= (int) $row[
    'status_id'
] ?>" aria-hidden="true"></span><?= calendarDayEscape(
    strip_tags($row['status'] ?? 'Onbekend')
) ?></td></tr>
<?php
endforeach; ?></tbody></table></div><?php endif; ?></main></body></html>
