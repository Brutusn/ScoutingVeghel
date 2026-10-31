<?php
require_once __DIR__ . '/lib/document.php';
require_once __DIR__ . '/security.php';
adminRequireLogin();
require_once __DIR__ . '/../php/database.php';
require_once __DIR__ . '/../php/privacy/service.php';
if (!privacyEnabled()) {
    http_response_code(404);
    exit();
}
function privacyHtml($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
date_default_timezone_set('Europe/Amsterdam');
$db = siteDatabaseConnection();
$error = '';
$created = null;
try {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        foreach ($_POST as $value) {
            if (!is_string($value)) {
                throw new InvalidArgumentException('Ongeldige invoer.');
            }
        }
        $groupId = filter_var($_POST['group_id'] ?? '', FILTER_VALIDATE_INT);
        if ($groupId === false || $groupId < 0) {
            throw new InvalidArgumentException('Kies extern of een eigen groep.');
        }
        $start = privacyDate(str_replace('T', ' ', $_POST['start'] ?? '') . ':00');
        $end = privacyDate(str_replace('T', ' ', $_POST['end'] ?? '') . ':00');
        if ($end <= $start) {
            throw new InvalidArgumentException('De einddatum moet na de begindatum liggen.');
        }
        $period = $_POST['period'] ?? '';
        $dates = [[$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')]];
        if ($period !== '') {
            $steps = ['weekly' => '+7 days', 'fortnightly' => '+14 days', 'monthly' => '+1 month'];
            if (!isset($steps[$period])) {
                throw new InvalidArgumentException('Ongeldige herhaling.');
            }
            $until = DateTimeImmutable::createFromFormat('!Y-m-d', $_POST['until'] ?? '');
            if (
                !$until ||
                $until->format('Y-m-d') !== ($_POST['until'] ?? '') ||
                $until < $start->setTime(0, 0)
            ) {
                throw new InvalidArgumentException('Kies de laatste startdatum.');
            }
            for (
                $s = $start->modify($steps[$period]), $e = $end->modify($steps[$period]);
                $s->format('Y-m-d') <= $until->format('Y-m-d');
                $s = $s->modify($steps[$period]), $e = $e->modify($steps[$period])
            ) {
                $dates[] = [$s->format('Y-m-d H:i:s'), $e->format('Y-m-d H:i:s')];
                if (count($dates) > 50) {
                    throw new InvalidArgumentException(
                        'Kies een reeks van maximaal 50 verhuringen.'
                    );
                }
            }
        }
        $created = privacyCreateSeries(
            $db,
            $_POST,
            $dates,
            $_POST['people'] ?? 0,
            $_POST['description'] ?? '',
            $groupId,
            (int) ($_POST['status'] ?? -1)
        );
    }
} catch (InvalidArgumentException $e) {
    $error = $e->getMessage();
} catch (Throwable $e) {
    http_response_code(500);
    $error = 'Opslaan mislukt. Controleer de privacy-preview voor je opnieuw probeert.';
    error_log('Privacy admin creation failed.');
}
$groups = $db
    ->query(
        'SELECT DISTINCT verhuur_huurder.id,verhuur_huurder.naam FROM verhuur_eigen_groep JOIN verhuur_huurder ON verhuur_huurder.id=verhuur_eigen_groep.huurder_id ORDER BY verhuur_huurder.naam'
    )
    ->fetch_all(MYSQLI_ASSOC);
?>
<?php echo beheerDocumentStart(
    'Nieuwe verhuring',
    '',
    'width=device-width,initial-scale=1'
); ?><main class="maintenance-page">
<h1>Nieuwe verhuring</h1><p>Contactgegevens horen bij deze verhuring. Kies voor een eigen groep het permanente groepsrecord.</p>
<?php if ($error): ?><p role="alert"><?= privacyHtml($error) ?></p><?php endif; ?>
<?php if ($created !== null): ?><p role="status"><?= count(
    $created
) ?> verhuring(en) aangemaakt.</p><ul><?php foreach (
     $created
     as $r
 ): ?><li><a href="/beheer/page/inbox/item.php?i=<?= (int) $r['id'] ?>">Verhuring <?= (int) $r[
    'id'
] ?></a></li><?php endforeach; ?></ul><?php endif; ?>
<form method="post"><label>Soort verhuring <select name="group_id"><option value="0">Extern — nieuwe contactgegevens</option><?php foreach (
    $groups
    as $g
):
    if (in_array((int) $g['id'], array_map('intval', privacyConfig()['disabled_groups']), true)) {
        continue;
    } ?><option value="<?= (int) $g['id'] ?>"><?= privacyHtml($g['naam']) ?></option><?php
endforeach; ?></select></label>
<fieldset><legend>Contactgegevens voor externe verhuur</legend><?php foreach (
    [
        'naam' => 'Organisatie / naam',
        'contactpersoon' => 'Contactpersoon',
        'email' => 'E-mailadres',
        'telefoon' => 'Telefoon',
        'adres' => 'Adres',
        'postcode' => 'Postcode',
        'plaats' => 'Plaats'
    ]
    as $key => $label
): ?><p><label><?= privacyHtml($label) ?> <input name="<?= $key ?>" type="<?= $key === 'email'
    ? 'email'
    : 'text' ?>" maxlength="2000"></label></p><?php endforeach; ?></fieldset>
<p><label>Begindatum en tijd <input type="datetime-local" name="start" required></label></p><p><label>Einddatum en tijd <input type="datetime-local" name="end" required></label></p>
<p><label>Aantal personen <input type="number" name="people" min="1" max="10000" required></label></p><p><label>Beschrijving <textarea name="description" required></textarea></label></p>
<p><label>Status <select name="status"><?php foreach (
    $db->query('SELECT status_id,status FROM verhuur_status WHERE status_id<21 ORDER BY status_id')
    as $s
): ?><option value="<?= (int) $s['status_id'] ?>"><?= privacyHtml(
    strip_tags($s['status'])
) ?></option><?php endforeach; ?></select></label></p>
<p><label>Herhalen <select name="period"><option value="">Eenmalig</option><option value="weekly">Wekelijks</option><option value="fortnightly">Tweewekelijks</option><option value="monthly">Maandelijks</option></select></label></p><p><label>Laatste startdatum bij herhaling <input type="date" name="until"></label></p>
<button type="submit">Verhuring aanmaken</button></form></main></body></html>
