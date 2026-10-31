<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/mfa-functions.php';
$notice = '';
$error = '';
$recoveryCodes = [];
$name = $_SESSION['ADMIN_NAME'];
$stmt = $database->prepare('SELECT mfa_secret FROM verhuur_administrators WHERE naam=?');
$stmt->bind_param('s', $name);
$stmt->execute();
$stmt->bind_result($active);
$stmt->fetch();
$stmt->close();
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!adminRateAttempt('mfa:' . $name)) {
            throw new InvalidArgumentException(
                'Te veel pogingen. Probeer het over 15 minuten opnieuw.'
            );
        }
        $action = beheerInput('action');
        if ($action === 'start' && !$active) {
            $_SESSION['MFA_SETUP'] = adminMfaSecret();
            $_SESSION['MFA_SETUP_TIME'] = time();
        } elseif ($action === 'enable' && !$active) {
            $secret = $_SESSION['MFA_SETUP'] ?? '';
            if (!$secret || time() - ($_SESSION['MFA_SETUP_TIME'] ?? 0) > 600) {
                throw new InvalidArgumentException('Start het instellen opnieuw.');
            }
            $step = adminMfaStep($secret, beheerInput('code'));
            if ($step === false) {
                throw new InvalidArgumentException('De app-code is onjuist.');
            }
            for ($i = 0; $i < 8; $i++) {
                $recoveryCodes[] = bin2hex(random_bytes(8));
            }
            $hashes = json_encode(
                array_map(function ($code) {
                    return hash('sha256', $code);
                }, $recoveryCodes)
            );
            $stmt = $database->prepare(
                'UPDATE verhuur_administrators SET mfa_secret=?,mfa_last=?,mfa_recovery=? WHERE naam=? AND mfa_secret IS NULL'
            );
            $stmt->bind_param('siss', $secret, $step, $hashes, $name);
            $stmt->execute();
            $ok = $stmt->affected_rows === 1;
            $stmt->close();
            if (!$ok) {
                throw new RuntimeException('Setup conflict');
            }
            unset($_SESSION['MFA_SETUP'], $_SESSION['MFA_SETUP_TIME']);
            $active = $secret;
            $notice =
                'Tweestapsverificatie staat aan. Bewaar onderstaande herstelcodes veilig; ze worden één keer getoond.';
        } elseif ($action === 'disable' && $active) {
            if (!adminMfaVerify($database, $name, beheerInput('code'))) {
                throw new InvalidArgumentException('De code is onjuist of al gebruikt.');
            }
            $stmt = $database->prepare(
                'UPDATE verhuur_administrators SET mfa_secret=NULL,mfa_last=-1,mfa_recovery=NULL WHERE naam=?'
            );
            $stmt->bind_param('s', $name);
            $stmt->execute();
            $stmt->close();
            $active = null;
            $notice = 'Tweestapsverificatie is uitgezet.';
        } else {
            throw new InvalidArgumentException('Ongeldige actie.');
        }
    }
} catch (InvalidArgumentException $e) {
    $error = $e->getMessage();
} catch (Throwable $e) {
    http_response_code(503);
    $error = 'De wijziging kon niet worden opgeslagen.';
}
beheerHead('Tweestapsverificatie');
?>
<main class="maintenance-page"><h1>Tweestapsverificatie</h1><p>Beveilig je eigen account met een authenticator-app. Naast je wachtwoord gebruik je dan een code die elke 30 seconden verandert.</p>
<?php if ($notice): ?><p role="status"><?= beheerEscape($notice) ?></p><?php endif; ?>
<?php if ($error): ?><p class="error" role="alert"><?= beheerEscape($error) ?></p><?php endif; ?>
<?php if (
    $recoveryCodes
): ?><h2>Herstelcodes</h2><p>Elke code werkt één keer. Bewaar ze buiten deze website.</p><pre><?= beheerEscape(
    implode("\n", $recoveryCodes)
) ?></pre><?php endif; ?>
<?php if (
    $active
): ?><p><strong>Tweestapsverificatie staat aan.</strong></p><form method="post"><input type="hidden" name="action" value="disable"><label>App-code of herstelcode <input name="code" autocomplete="one-time-code" required maxlength="16"></label><button>Uitzetten</button></form>
<?php elseif (
    !empty($_SESSION['MFA_SETUP'])
): ?><p>Voeg in je authenticator-app handmatig een account toe: <strong>Scouting Veghel (<?= beheerEscape(
    $name
) ?>)</strong>. Kies een tijdgebonden code (TOTP), 6 cijfers, 30 seconden, SHA-1.</p><p>Geheime sleutel: <code><?= beheerEscape(
    $_SESSION['MFA_SETUP']
) ?></code></p><p>De sleutel is 10 minuten geldig voor het instellen. Deel deze niet.</p><form method="post"><input type="hidden" name="action" value="enable"><label>Code uit de app <input name="code" inputmode="numeric" pattern="[0-9]{6}" autocomplete="one-time-code" required></label><button>Controleren en activeren</button></form>
<?php else: ?><form method="post"><input type="hidden" name="action" value="start"><button>Tweestapsverificatie instellen</button></form><?php endif; ?></main>
<?php beheerFoot();
