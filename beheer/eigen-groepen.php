<?php
require_once __DIR__ . '/lib/document.php';
require_once __DIR__ . '/security.php';
adminRequireLogin();
require_once __DIR__ . '/../php/database.php';
require_once __DIR__ . '/../php/privacy/service.php';
if (!privacyEnabled()) {
    echo beheerDocumentStart('Eigen groepen | Beheer', '', 'width=device-width,initial-scale=1') .
        '<main class="maintenance-page"><h1>Eigen groepen</h1><p>Groepenbeheer is nog niet beschikbaar.</p></main></body></html>';
    exit();
}
function groupEscape($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
$db = siteDatabaseConnection();
$error = '';
$notice = '';
try {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $id = filter_var($_POST['id'] ?? 0, FILTER_VALIDATE_INT);
        if ($id === false || $id < 0) {
            throw new InvalidArgumentException('Ongeldige groep.');
        }
        $action = $_POST['action'] ?? '';
        if ($action === 'save') {
            $code = $_POST['code'] ?? '';
            if ($id && $code === '') {
                $rows = privacyQuery(
                    $db,
                    'SELECT code FROM verhuur_eigen_groep WHERE huurder_id=?',
                    'i',
                    [$id]
                );
                $code = $rows[0]['code'] ?? '';
            }
            privacySaveGroup($db, $id, $_POST['name'] ?? '', $code, $_POST['email'] ?? '');
            $notice = 'Groep opgeslagen.';
        } elseif ($action === 'disable' || $action === 'enable') {
            privacyDisableGroup($db, $id, $action === 'disable');
            $notice = 'Beschikbaarheid gewijzigd; historie blijft behouden.';
        } else {
            throw new InvalidArgumentException('Ongeldige actie.');
        }
    }
} catch (InvalidArgumentException $e) {
    $error = $e->getMessage();
} catch (Throwable $e) {
    http_response_code(500);
    $error = 'Groep opslaan mislukt.';
    error_log('Privacy group update failed.');
}
$groups = $db
    ->query(
        'SELECT DISTINCT verhuur_huurder.id,verhuur_huurder.naam,verhuur_huurder.email FROM verhuur_eigen_groep JOIN verhuur_huurder ON verhuur_huurder.id=verhuur_eigen_groep.huurder_id ORDER BY verhuur_huurder.naam'
    )
    ->fetch_all(MYSQLI_ASSOC);
?>
<?php echo beheerDocumentStart(
    'Eigen groepen',
    '',
    'width=device-width,initial-scale=1'
); ?><main class="maintenance-page"><h1>Eigen groepen</h1><p>Gebruik groepsgegevens en een groepsmailadres. Codes geven toegang tot aanvragen en worden niet getoond. Deactiveren bewaart de historie.</p>
<?php
if ($error): ?><p role="alert"><?= groupEscape($error) ?></p><?php endif;
if ($notice): ?><p role="status"><?= groupEscape($notice) ?></p><?php endif;
?>
<?php
$groups[] = ['id' => 0, 'naam' => '', 'email' => ''];
foreach ($groups as $g):
    $disabled = in_array(
        (int) $g['id'],
        array_map('intval', privacyConfig()['disabled_groups']),
        true
    ); ?>
<section><h2><?= $g['id']
    ? groupEscape($g['naam']) . ($disabled ? ' — inactief' : '')
    : 'Nieuwe eigen groep' ?></h2><form method="post"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int) $g[
    'id'
] ?>"><p><label>Groepsnaam <input name="name" value="<?= groupEscape(
    $g['naam']
) ?>" maxlength="200" required></label></p><p><label>Groepsmailadres <input type="email" name="email" value="<?= groupEscape(
    $g['email']
) ?>" required></label></p><p><label><?= $g['id']
    ? 'Nieuwe code (leeg = bestaande behouden)'
    : 'Toegangscode' ?> <input type="password" name="code" autocomplete="new-password" minlength="16" pattern="[a-zA-Z0-9_-]+" <?= $g[
     'id'
 ]
     ? ''
     : 'required' ?>></label></p><button type="submit">Opslaan</button></form>
<?php if ($g['id']): ?><form method="post"><input type="hidden" name="id" value="<?= (int) $g[
    'id'
] ?>"><input type="hidden" name="action" value="<?= $disabled
    ? 'enable'
    : 'disable' ?>"><button type="submit"><?= $disabled
    ? 'Activeren'
    : 'Deactiveren' ?></button></form><?php endif; ?></section>
<?php
endforeach;
?></main></body></html>
