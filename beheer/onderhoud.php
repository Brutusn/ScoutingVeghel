<?php
require_once __DIR__ . '/lib/document.php';
require_once __DIR__ . '/security.php';
adminRequireLogin();
require_once __DIR__ . '/../php/privacy/service.php';
if (privacyEnabled()) {
    require __DIR__ . '/privacy.php';
    exit();
}
require_once __DIR__ . '/../php/database.php';
require_once __DIR__ . '/maintenance-functions.php';
date_default_timezone_set('Europe/Amsterdam');
$scopes = maintenanceScopes();
$notice = '';
$error = '';
$snapshot = null;
$scope = 'completed';
$cutoff = date('Y-01-01');
$preview = $_SESSION['ADMIN_MAINT_PREVIEW'] ?? null;
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        if (!is_string($action)) {
            throw new InvalidArgumentException('Ongeldige actie.');
        }
        if ($action === 'preview') {
            $scope = $_POST['scope'] ?? '';
            if (!is_string($scope) || !isset($scopes[$scope])) {
                throw new InvalidArgumentException('Kies een opruimoptie.');
            }
            $cutoff = maintenanceCutoff($_POST['cutoff'] ?? '');
            $snapshot = maintenanceSnapshot(siteDatabaseConnection(), $scope, $cutoff);
            $preview = maintenanceSave($snapshot);
            $_SESSION['ADMIN_MAINT_PREVIEW'] = $preview;
        } elseif (in_array($action, ['export', 'review', 'delete'], true)) {
            $snapshot = maintenanceLoad($preview, $_POST['preview_token'] ?? null);
            $scope = $snapshot['scope'];
            $cutoff = $snapshot['cutoff'];
            if ($action === 'export') {
                if (!maintenanceCount($snapshot)) {
                    throw new InvalidArgumentException('De selectie is leeg.');
                }
                $stream = maintenanceCsv($snapshot);
                $preview['exported'] = true;
                $_SESSION['ADMIN_MAINT_PREVIEW'] = $preview;
                // CSV must not pass through the HTML/CSRF output filter.
                while (ob_get_level()) {
                    ob_end_clean();
                }
                header('Content-Type: text/csv; charset=UTF-8');

                $exportName =
                    'onderhoud-' .
                    $scope .
                    ($scope === 'unused_tenants' ? '-zonder-reserveringen' : '-voor-' . $cutoff) .
                    '.csv';
                header('Content-Disposition: attachment; filename="' . $exportName . '"');
                session_write_close();
                fpassthru($stream);
                fclose($stream);
                exit();
            }
            if ($action === 'delete') {
                if (
                    ($_POST['confirm_text'] ?? '') !== 'VERWIJDEREN' ||
                    ($_POST['confirm_saved'] ?? '') !== '1'
                ) {
                    throw new InvalidArgumentException(
                        'Bevestig dat je de CSV hebt bewaard en typ VERWIJDEREN.'
                    );
                }
                $db = siteDatabaseConnection();
                $count = maintenanceDelete($db, $preview, $snapshot);
                $preview['deleted'] = true;
                $_SESSION['ADMIN_MAINT_PREVIEW'] = $preview;
                $notice =
                    $count .
                    ' ' .
                    maintenanceUnit($scope) .
                    ' verwijderd. De CSV blijft jouw eigen archiefkopie.';
                // Log only the cleanup summary, never archived personal details.
                try {
                    $who = $_SESSION['ADMIN_NAME'];
                    $summary = json_encode([
                        'scope' => $scope,
                        'cutoff' => $cutoff,
                        'count' => $count
                    ]);
                    $log = $db->prepare(
                        "INSERT INTO verhuur_mutaties (hoort_bij,naam,actie,datum,`query`) VALUES (0,?,'ONDERHOUD',NOW(),?)"
                    );
                    $log->bind_param('ss', $who, $summary);
                    $log->execute();
                    $log->close();
                } catch (Throwable $logError) {
                    error_log('Scouting Veghel: maintenance summary could not be logged.');
                }
            }
        } else {
            throw new InvalidArgumentException('Ongeldige actie.');
        }
    }
} catch (InvalidArgumentException $problem) {
    $error = $problem->getMessage();
} catch (Throwable $problem) {
    http_response_code(500);
    $error =
        'Onderhoud kon niet volledig worden uitgevoerd. De CSV en de tijdelijke momentopname blijven beschikbaar. Neem contact op met de beheerder voordat je opnieuw opruimt.';
    error_log('Scouting Veghel: maintenance operation failed.');
}
// Never render unvalidated request values.
if (!isset($scopes[$scope])) {
    $scope = 'completed';
}
try {
    $cutoff = maintenanceCutoff($cutoff);
} catch (Throwable $ignored) {
    $cutoff = date('Y-01-01');
}
?>
<?php echo beheerDocumentStart(
    'Onderhoud | Scouting Veghel beheer',
    '',
    'width=device-width, initial-scale=1'
); ?>
<main class="maintenance-page">
<header class="workspace-heading"><h1>Onderhoud</h1><p>Bekijk oude gegevens, bewaar een CSV en ruim daarna gericht op.</p></header>
<?php if ($notice): ?><p class="success" role="status"><?= maintenanceEscape(
    $notice
) ?></p><?php endif; ?>
<?php if ($error): ?><p class="error" role="alert"><?= maintenanceEscape(
    $error
) ?></p><?php endif; ?>
<section class="maintenance-step"><h2>1. Kies wat je wilt opruimen</h2>
<form method="post" action="onderhoud.php"><input type="hidden" name="action" value="preview">
<div class="maintenance-choices">
<?php foreach ($scopes as $key => $option): ?>
<label class="maintenance-choice"><input type="radio" name="scope" value="<?= maintenanceEscape(
    $key
) ?>" <?= $key === $scope ? 'checked' : '' ?> required><span><strong><?= maintenanceEscape(
     $option['title']
 ) ?></strong><span><?= maintenanceEscape($option['description']) ?></span></span></label>
<?php endforeach; ?></div>
<div class="maintenance-cutoff"><label for="maintenance-cutoff">Gegevens van vóór</label><input type="date" name="cutoff" id="maintenance-cutoff" value="<?= maintenanceEscape(
    $cutoff
) ?>" max="<?= date('Y-m-d') ?>" required><button type="submit">Bekijk selectie</button></div>
<p class="maintenance-help">Bij reserveringen geldt de einddatum; bij wijzigingslogs de registratiedatum. Toekomstige en lopende reserveringen worden niet geselecteerd. Eigen groepen en accounts blijven behouden. Huurders worden alleen bij de twee huurdersopties verwijderd. Bij oude externe huurders moeten alle aanvragen afgesloten zijn, met aanvraag- en einddatum vóór de grens. Bij huurders zonder reserveringen is de ouderdom onbekend en geldt de datumgrens niet.</p>
</form></section>
<?php if ($snapshot && $preview):
    $count = maintenanceCount($snapshot); ?>
<section class="maintenance-step"><h2>2. Controleer en download</h2>
<p><strong><?= maintenanceEscape($scopes[$snapshot['scope']]['title']) ?></strong> · <?= $snapshot[
    'scope'
] === 'unused_tenants'
    ? 'ouderdom onbekend'
    : 'vóór ' .
        maintenanceEscape($snapshot['cutoff']) ?> · <strong><?= $count ?> <?= maintenanceUnit(
     $snapshot['scope']
 ) ?></strong></p>
<?php if (maintenanceTenantScope($snapshot['scope'])): ?><p class="maintenance-help"><?= count(
    $snapshot['rentals']
) ?> bijbehorende verhuringen en <?= count(
     $snapshot['notes']
 ) ?> opmerkingen gaan mee in de CSV en worden ook verwijderd. Wijzigingslogs blijven apart opruimbaar.</p><?php elseif (
    $snapshot['scope'] !== 'logs'
): ?><p class="maintenance-help"><?= count(
    $snapshot['notes']
) ?> bijbehorende opmerkingen worden ook opgenomen in de export en verwijderd. Huurdergegevens en wijzigingslogs blijven staan.</p><?php endif; ?>
<?php if ($count): ?>
<div class="maintenance-table"><table><thead><tr><th>Kenmerk</th><th><?= $snapshot['scope'] ===
'logs'
    ? 'Actie'
    : 'Huurder / groep' ?></th><th><?= maintenanceTenantScope($snapshot['scope'])
    ? 'Laatste einddatum'
    : 'Datum' ?></th></tr></thead><tbody>
<?php if ($snapshot['scope'] === 'logs'):
    foreach (array_slice($snapshot['logs'], 0, 20) as $row): ?>
<tr><td><?= $row['id'] ?></td><td><?= maintenanceEscape(
    $row['actie']
) ?></td><td><?= maintenanceEscape($row['datum']) ?></td></tr>
<?php endforeach;
elseif (maintenanceTenantScope($snapshot['scope'])):
    foreach (array_slice($snapshot['tenants'], 0, 20) as $row): ?>
<tr><td><?= $row['id'] ?></td><td><?= maintenanceEscape(
    $row['naam']
) ?></td><td><?= maintenanceEscape(
    maintenanceLastUse($snapshot, $row['id']) ?: 'Geen gekoppelde reserveringen'
) ?></td></tr>
<?php endforeach;
else:
    $reservations = array_column($snapshot['reservations'], null, 'id');
    $tenants = array_column($snapshot['tenants'], null, 'id');
    foreach (array_slice($snapshot['rentals'], 0, 20) as $row): ?>
<tr><td><?= $row['id'] ?></td><td><?= maintenanceEscape(
    $row['groep'] ?: $tenants[$row['huurder_id']]['naam'] ?? 'Onbekende huurder'
) ?></td><td><?= maintenanceEscape($reservations[$row['reservering_id']]['einddatum']) ?></td></tr>
<?php endforeach;
endif; ?></tbody></table></div>
<?php if (
    $count > 20
): ?><p class="maintenance-help">De eerste 20 records worden getoond. De CSV bevat de volledige selectie.</p><?php endif; ?>
<div class="maintenance-actions">
<form method="post" action="onderhoud.php"><input type="hidden" name="action" value="export"><input type="hidden" name="preview_token" value="<?= maintenanceEscape(
    $preview['token']
) ?>"><button type="submit">Download CSV</button></form>
<?php if (
    empty($preview['deleted'])
): ?><form method="post" action="onderhoud.php"><input type="hidden" name="action" value="review"><input type="hidden" name="preview_token" value="<?= maintenanceEscape(
    $preview['token']
) ?>"><button class="button-secondary" type="submit">CSV gedownload? Ga verder</button></form><?php endif; ?>
</div><p class="maintenance-help">Bewaar de CSV op een veilige plek. Dit is een gegevensoverzicht, geen volledige databaseback-up. De selectie verloopt na 30 minuten.</p>
<?php else: ?><p>Geen gegevens gevonden voor deze selectie.</p><?php endif; ?>
</section>
<?php if ($count && !empty($preview['exported']) && empty($preview['deleted'])): ?>
<section class="maintenance-step maintenance-delete"><h2>3. Bevestig het opruimen</h2><p>Je verwijdert <strong><?= $count ?> <?= maintenanceUnit(
     $snapshot['scope']
 ) ?></strong> uit de database. Dit is niet via deze pagina terug te draaien.</p>
<form method="post" action="onderhoud.php"><input type="hidden" name="action" value="delete"><input type="hidden" name="preview_token" value="<?= maintenanceEscape(
    $preview['token']
) ?>">
<label class="maintenance-confirm"><input type="checkbox" name="confirm_saved" value="1" required> Ik heb de CSV gedownload en veilig bewaard.</label>
<label for="confirm-delete">Typ VERWIJDEREN om te bevestigen</label><input type="text" id="confirm-delete" name="confirm_text" autocomplete="off" pattern="VERWIJDEREN" required>
<button class="button-danger" type="submit">Verwijder deze selectie</button></form></section>
<?php endif;
endif; ?>
</main></body></html>
