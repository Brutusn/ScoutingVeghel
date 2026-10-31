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
function privacyEscape($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
$db = siteDatabaseConnection();
$error = '';
$notice = '';
$config = privacyConfig();
try {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $action = $_POST['action'] ?? '';
        $token = $_POST['preview'] ?? '';
        if (
            !is_string($action) ||
            !is_string($token) ||
            !hash_equals($_SESSION['PRIVACY_PREVIEW_TOKEN'] ?? '', $token)
        ) {
            throw new InvalidArgumentException('Preview verlopen. Ververs de pagina.');
        }
        if (($_POST['confirm'] ?? '') !== 'UITVOEREN') {
            throw new InvalidArgumentException('Typ UITVOEREN om deze actie te bevestigen.');
        }
        $state = $_SESSION['PRIVACY_PREVIEWS'] ?? [];
        if (($state['created'] ?? 0) < time() - 1800) {
            throw new InvalidArgumentException('Preview verlopen.');
        }
        if ($action === 'placeholder') {
            privacyBackup($db);
            privacyEnsurePlaceholder($db);
            $notice = 'Placeholder ingesteld.';
        } elseif ($action === 'migrate') {
            $count = privacyLock($db, function () use ($db, $state) {
                privacyBackup($db);
                return privacyMigrate($db, $state['migration']);
            });
            $notice = $count . ' huurderkoppelingen gesplitst. Bestaande tokens zijn behouden.';
        } elseif (in_array($action, ['personal', 'booking'], true)) {
            $count = privacyLock($db, function () use ($db, $action, $state) {
                privacyBackup($db);
                return privacyCleanup($db, $action, $state[$action]);
            });
            $notice = $count . ' verhuringen verwerkt.';
        } else {
            throw new InvalidArgumentException('Ongeldige actie.');
        }
    }
} catch (InvalidArgumentException $e) {
    $error = $e->getMessage();
} catch (Throwable $e) {
    http_response_code(500);
    $error =
        'Actie niet voltooid. Bekijk de actuele preview; neem bij een gedeeltelijke fout contact op met de beheerder. Een private herstelkopie is beschikbaar als de actie is gestart.';
    error_log('Privacy maintenance action failed.');
}
$config = privacyConfig();
$plan = privacyPreview($db);
$personal = privacyEligible($db, 'personal');
$booking = privacyEligible($db, 'booking');
$_SESSION['PRIVACY_PREVIEW_TOKEN'] = bin2hex(random_bytes(24));
$_SESSION['PRIVACY_PREVIEWS'] = [
    'created' => time(),
    'migration' => privacyFingerprint($plan),
    'personal' => privacyFingerprint($personal),
    'booking' => privacyFingerprint($booking)
];
function privacyAction($action, $label) {
    ?><form method="post"><input type="hidden" name="action" value="<?= privacyEscape(
    $action
) ?>"><input type="hidden" name="preview" value="<?= privacyEscape($_SESSION['PRIVACY_PREVIEW_TOKEN']) ?>"><label>Bevestiging <input name="confirm" placeholder="Typ UITVOEREN" required autocomplete="off"></label> <button type="submit"><?= privacyEscape($label) ?></button></form><?php
}
?>
<?php echo beheerDocumentStart(
    'Privacy en migratie',
    '',
    'width=device-width,initial-scale=1'
); ?><main class="maintenance-page">
<h1>Privacy en migratie</h1><p>De preview wijzigt niets en toont alleen record-ID’s. Onvolledige koppelingen worden niet automatisch hersteld of verwijderd.</p>
<?php
if ($notice): ?><p role="status"><?= privacyEscape($notice) ?></p><?php endif;
if ($error): ?><p role="alert"><?= privacyEscape($error) ?></p><?php endif;
?>
<h2>Bewaartermijnen</h2><p>Persoonsgegevens: <?= $config['personal_days'] === null
    ? 'niet ingesteld'
    : (int) $config['personal_days'] . ' dagen' ?>. Boekingen: <?= $config['booking_days'] === null
    ? 'niet ingesteld'
    : (int) $config['booking_days'] . ' dagen' ?>. Startmoment: <?= $config['retention_basis'] ===
'end_date_closed'
    ? 'einddatum bij afgesloten status'
    : 'niet ingesteld' ?>. Automatische verwerking: <?= $config['automatic']
    ? 'ingeschakeld'
    : 'uitgeschakeld' ?>.</p>
<h2>Placeholder</h2><p><?= $config['placeholder_id']
    ? 'Ingesteld.'
    : 'Nog niet ingesteld.' ?></p><?php if (!$config['placeholder_id']) {
    privacyAction('placeholder', 'Lege placeholder instellen');
} ?>
<h2>Migratiepreview</h2><table><tr><th>Controle</th><th>Aantal</th></tr><?php foreach (
    [
        'split' => 'Te splitsen huurderkoppelingen',
        'missing_tenant' => 'Ontbrekende huurder',
        'missing_reservation' => 'Ontbrekende reservering',
        'shared_reservation' => 'Gedeelde reservering',
        'historical_group' => 'Historische groepsnaam zonder groepskoppeling',
        'ambiguous_audit' => 'Onduidelijke historische auditkoppeling (blokkeert opschoning)',
        'unused_tenant' => 'Losse externe huurders (alleen beoordelen)',
        'unused_reservation' => 'Losse reserveringen (alleen beoordelen)'
    ]
    as $key => $label
): ?><tr><td><?= privacyEscape($label) ?></td><td><?= count(
    $plan[$key]
) ?></td></tr><?php endforeach; ?></table>
<?php foreach ($plan as $key => $rows): ?><details><summary><?= privacyEscape($key) ?> — <?= count(
     $rows
 ) ?></summary><p><?= privacyEscape(
    implode(', ', array_slice($key === 'split' ? array_column($rows, 'id') : $rows, 0, 200))
) ?></p><?php if (
    count($rows) > 200
): ?><p>Eerste 200 ID’s getoond.</p><?php endif; ?></details><?php endforeach; ?>
<?php if ($plan['split']) {
    privacyAction('migrate', 'Geldige gedeelde externe huurders splitsen');
} ?>
<h2>Persoonsgegevens opschonen</h2><p><?= count(
    $personal
) ?> verhuringen komen in aanmerking. Dit wist contactgegevens, opmerkingen, oude auditdetails, vrije beschrijving en bevestigingslinks; de boeking blijft bestaan.</p><?php if (
     $personal &&
     $config['placeholder_id']
 ) {
     privacyAction('personal', 'Persoonsgegevens verwijderen');
 } ?>
<h2>Boekingen opschonen</h2><p><?= count(
    $booking
) ?> verhuringen komen in aanmerking. Dit verwijdert de boeking en de bijbehorende externe persoonsgegevens.</p><?php if (
     $booking &&
     $config['placeholder_id']
 ) {
     privacyAction('booking', 'Boekingen verwijderen');
 } ?>
<p>Vóór een mutatie wordt buiten de webroot een private herstelkopie bewaard. Die kopieën, mail, exports en back-ups hebben een afzonderlijk bewaarbeleid nodig.</p>
<p><a href="eigen-groepen.php">Eigen groepen beheren</a></p></main></body></html>
