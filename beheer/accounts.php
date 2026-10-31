<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/account-functions.php';
$accountDb = siteDatabaseConnection();
$notice = '';
$error = '';
$tabs = [
    'groepen' => 'Groepen',
    'nieuw' => 'Nieuwe beheerder',
    'beheerders' => 'Bestaande beheerders'
];
$tab = beheerInput('tab', 'groepen');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = beheerInput('action');
    $tab =
        $action === 'account_create'
            ? 'nieuw'
            : (in_array($action, ['account_group', 'password_reset'], true)
                ? 'beheerders'
                : 'groepen');
}
if (!isset($tabs[$tab])) {
    $tab = 'groepen';
}
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $notice = adminAccountMutation(
            $accountDb,
            beheerInput('action'),
            'beheerInput',
            $_SESSION['ADMIN_NAME']
        );
        // Recheck after a self-change instead of leaving a form available with revoked permissions.
        if (!adminIsLoggedIn()) {
            echo '<p>Je wachtwoord is gewijzigd. <a href="/beheer/index.html" target="_top">Log opnieuw in</a> met je nieuwe wachtwoord.</p>';
            exit();
        }
        if (!adminCan('accounts')) {
            header('Location: /beheer/index.html', true, 303);
            exit();
        }
    }
} catch (InvalidArgumentException $e) {
    $error = $e->getMessage();
} catch (mysqli_sql_exception $e) {
    http_response_code(400);
    $error =
        $e->getCode() === 1062
            ? 'Deze gebruikersnaam of groepsnaam bestaat al. Kies een andere naam.'
            : 'Opslaan is mislukt. Probeer het opnieuw.';
} catch (Throwable $e) {
    http_response_code(503);
    $error = 'Opslaan is tijdelijk niet beschikbaar.';
}
$groups = $accountDb
    ->query('SELECT code,naam FROM beheer_access_groups ORDER BY naam')
    ->fetch_all(MYSQLI_ASSOC);
$permissions = [];
foreach ($accountDb->query('SELECT groep,module FROM beheer_access_permissions') as $row) {
    $permissions[$row['groep']][] = $row['module'];
}
$accounts = $accountDb
    ->query(
        'SELECT naam,rol,mfa_secret IS NOT NULL AS mfa FROM verhuur_administrators ORDER BY naam'
    )
    ->fetch_all(MYSQLI_ASSOC);
function accountGroupOptions($groups, $selected = '') {
    foreach ($groups as $group) {
        echo '<option value="' .
            beheerEscape($group['code']) .
            '" ' .
            ($group['code'] === $selected ? 'selected' : '') .
            '>' .
            beheerEscape($group['naam']) .
            '</option>';
    }
}
function accountModuleChoices($selected, $prefix) {
    echo '<fieldset class="access-modules"><legend>Toegang tot modules</legend>';
    foreach (adminModules() as $code => $label) {
        echo '<label><input type="checkbox" name="modules[]" value="' .
            beheerEscape($code) .
            '" ' .
            (in_array($code, $selected, true) ? 'checked' : '') .
            '> ' .
            beheerEscape($label) .
            '</label>';
    }
    echo '</fieldset>';
}
function accountPasswordFields() {
    ?>
    <label>Nieuw wachtwoord <input type="password" name="password" minlength="12" maxlength="72" autocomplete="new-password" required></label>
    <label>Herhaal wachtwoord <input type="password" name="password_confirm" minlength="12" maxlength="72" autocomplete="new-password" required></label>
<?php
}
beheerHead('Groepen en beheerders');
?>
<main class="maintenance-page access-page">
<header class="workspace-heading"><h1>Groepen en beheerders</h1><p>Stel per groep de moduletoegang in en deel beheerders in. De rechten gelden direct, ook voor bestaande sessies.</p></header>
<?php if ($notice): ?><p class="success" role="status"><?php echo beheerEscape(
    $notice
); ?></p><?php endif; ?>
<?php if ($error): ?><p class="error" role="alert"><?php echo beheerEscape(
    $error
); ?></p><?php endif; ?>
<nav class="access-tabs" aria-label="Groepen en beheerders onderdelen">
<?php foreach ($tabs as $key => $label): ?><a href="accounts.php?tab=<?php echo beheerEscape(
    $key
); ?>" <?php echo $tab === $key ? 'aria-current="page"' : ''; ?>><?php echo beheerEscape(
    $label
); ?></a><?php endforeach; ?>
</nav>
<?php if ($tab === 'groepen'): ?>
<section class="maintenance-step"><h2>Groepen</h2><p>Vink aan welke modules een groep mag gebruiken. Tweestapsverificatie en uitloggen blijven voor ieder account beschikbaar. Er blijft altijd minstens één account met toegang tot deze beheerpagina.</p>
<div class="access-groups"><?php foreach ($groups as $group):
    $code = $group['code']; ?>
<details class="access-card" id="group-<?php echo beheerEscape(
    $code
); ?>" open><summary><?php echo beheerEscape($group['naam']); ?> · <?php echo count(
     $permissions[$code] ?? []
 ); ?> modules · Bewerken</summary>
<form method="post" action="accounts.php"><input type="hidden" name="action" value="group_save"><input type="hidden" name="groep" value="<?php echo beheerEscape(
    $code
); ?>">
<label>Groepsnaam <input name="groepsnaam" value="<?php echo beheerEscape(
    $group['naam']
); ?>" maxlength="100" required></label>
<?php accountModuleChoices(
    $permissions[$code] ?? [],
    $code
); ?><button>Groep opslaan</button></form>
<form method="post" action="accounts.php"><input type="hidden" name="action" value="group_delete"><input type="hidden" name="groep" value="<?php echo beheerEscape(
    $code
); ?>"><button class="button-secondary">Lege groep verwijderen</button></form></details>
<?php
endforeach; ?></div>
<details class="access-card" id="new-group"><summary>Nieuwe groep aanmaken</summary><form method="post" action="accounts.php"><input type="hidden" name="action" value="group_create"><label>Groepsnaam <input name="groepsnaam" maxlength="100" required></label><?php accountModuleChoices(
    [],
    'new'
); ?><button>Groep aanmaken</button></form></details>
</section>
<?php elseif ($tab === 'nieuw'): ?>
<section class="maintenance-step"><h2>Nieuwe beheerder</h2><form method="post" action="accounts.php" id="new-account"><input type="hidden" name="action" value="account_create">
<label>Gebruikersnaam <input name="naam" minlength="3" maxlength="100" autocomplete="off" pattern="[a-zA-Z0-9][a-zA-Z0-9._@\-]{2,99}" required></label>
<label>Rechtengroep <select name="groep" required><option value="">Kies een rechtengroep</option><?php accountGroupOptions(
    $groups
); ?></select></label>
<?php accountPasswordFields(); ?><p>Gebruik een uniek wachtwoord van 12 tot 72 bytes. Deel het veilig met de nieuwe beheerder.</p><button>Beheerder aanmaken</button></form></section>
<?php elseif ($tab === 'beheerders'): ?>
<section class="maintenance-step"><h2>Bestaande beheerders</h2>
<?php foreach (
    $accounts
    as $account
): ?><article class="access-card" data-account="<?php echo beheerEscape(
    $account['naam']
); ?>"><h3><?php
echo beheerEscape($account['naam']);
echo $account['naam'] === $_SESSION['ADMIN_NAME'] ? ' (jij)' : '';
?> · MFA <?php echo $account['mfa'] ? 'aan' : 'uit'; ?></h3>
<form method="post" action="accounts.php"><input type="hidden" name="action" value="account_group"><input type="hidden" name="naam" value="<?php echo beheerEscape(
    $account['naam']
); ?>"><label>Rechtengroep <select name="groep"><?php accountGroupOptions(
    $groups,
    $account['rol']
); ?></select></label><button>Groep wijzigen</button></form>
<h4>Wachtwoord resetten</h4><form method="post" action="accounts.php"><input type="hidden" name="action" value="password_reset"><input type="hidden" name="naam" value="<?php echo beheerEscape(
    $account['naam']
); ?>"><?php accountPasswordFields(); ?><p>Alle bestaande sessies van dit account worden afgesloten. MFA blijft behouden.</p><button>Nieuw wachtwoord opslaan</button></form></article><?php endforeach; ?>
</section><?php endif; ?></main><?php beheerFoot();
