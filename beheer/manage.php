<?php
require_once __DIR__ . '/lib/document.php';

require_once __DIR__ . '/security.php';
require_once __DIR__ . '/../php/database.php';
require_once __DIR__ . '/templates/login.php';
$loginError = '';
require_once __DIR__ . '/mfa-functions.php';
if (isset($_SESSION['ADMIN_PENDING'])) {
    if (time() - ($_SESSION['ADMIN_PENDING_TIME'] ?? 0) > 300) {
        adminClearSession();
        $loginError = 'De inlogpoging is verlopen. Log opnieuw in.';
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mfa_code'])) {
        try {
            $name = $_SESSION['ADMIN_PENDING'];
            $version = $_SESSION['ADMIN_PENDING_VERSION'] ?? -1;
            $versionDatabase = siteDatabaseConnection();
            $check = $versionDatabase->prepare(
                'SELECT auth_version FROM verhuur_administrators WHERE naam=?'
            );
            $check->bind_param('s', $name);
            $check->execute();
            $check->bind_result($currentVersion);
            $found = $check->fetch();
            $check->close();
            if (
                $found &&
                (int) $version === (int) $currentVersion &&
                adminRateAttempt('mfa:' . $name) &&
                adminMfaVerify(siteDatabaseConnection(), $name, $_POST['mfa_code'])
            ) {
                $destination = $_SESSION['ADMIN_PENDING_RETURN'] ?? '/beheer/index.html';
                adminCompleteLogin($name, $version);
                header('Location: ' . $destination, true, 303);
                exit();
            }
            $loginError = 'De code is onjuist, al gebruikt of er zijn te veel pogingen gedaan.';
        } catch (Throwable $error) {
            http_response_code(503);
            $loginError = 'Inloggen is tijdelijk niet beschikbaar.';
        }
    }
    if (isset($_SESSION['ADMIN_PENDING'])) {
        echo beheerDocumentStart(
            'Tweestapsverificatie',
            'admin-login',
            'width=device-width,initial-scale=1'
        ) .
            '<main id="login-form"><h1>Tweestapsverificatie</h1><p>Vul de code uit je authenticator-app of een herstelcode in.</p><p role="alert">' .
            htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8') .
            '</p>' .
            '<form method="post" action="/beheer/manage.php">' .
            '<input name="mfa_code" autocomplete="one-time-code" maxlength="16" required autofocus>' .
            '<button>Inloggen</button>' .
            '</form>' .
            '<a href="/beheer/logout.php">Annuleren</a>' .
            '</main>' .
            '</body>' .
            '</html>';
        exit();
    }
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['gebruikersnaam'], $_POST['wachtwoord'])
) {
    $name = $_POST['gebruikersnaam'];
    $password = $_POST['wachtwoord'];
    if (
        !is_string($name) ||
        !is_string($password) ||
        strlen($name) > 133 ||
        $name === '' ||
        $password === '' ||
        strlen($password) > 72 ||
        strpos($password, "\0") !== false
    ) {
        $loginError = 'Naam of wachtwoord is onjuist.';
    } else {
        $loginStage = 'rate-limit';
        try {
            if (!adminRateAttempt($name)) {
                http_response_code(429);
                header('Retry-After: 900');
                $loginError = 'Te veel inlogpogingen. Probeer het over 15 minuten opnieuw.';
            } else {
                $loginStage = 'database-connect';
                $loginDatabase = siteDatabaseConnection();
                $loginStage = 'credential-check';
                if (adminCheckCredentials($loginDatabase, $name, $password)) {
                    $returnPage = $_POST['beheer_return'] ?? 'reserveringen';
                    if (
                        !is_string($returnPage) ||
                        !in_array(
                            $returnPage,
                            [
                                'reserveringen',
                                'agenda',
                                'kalender',
                                'zoeken',
                                'toevoegen',
                                'onderhoud',
                                'eigen-groepen',
                                'maillog',
                                'mailsjablonen',
                                'accounts',
                                'mfa'
                            ],
                            true
                        )
                    ) {
                        $returnPage = 'reserveringen';
                    }
                    $returnId = $_POST['beheer_return_id'] ?? '';
                    $bookingHash =
                        $returnPage === 'reserveringen' &&
                        is_string($returnId) &&
                        preg_match('/^[1-9][0-9]{0,9}$/D', $returnId)
                            ? '?i=' . $returnId
                            : '';
                    if (isset($_SESSION['ADMIN_PENDING'])) {
                        $_SESSION['ADMIN_PENDING_RETURN'] =
                            '/beheer/index.html#' . $returnPage . $bookingHash;
                        header('Location: /beheer/manage.php', true, 303);
                        exit();
                    }
                    header('Location: /beheer/index.html#' . $returnPage . $bookingHash, true, 303);
                    exit();
                }
                $loginError = 'Naam of wachtwoord is onjuist.';
            }
        } catch (Throwable $error) {
            http_response_code(503);
            $loginError =
                'Inloggen is tijdelijk niet beschikbaar. Neem contact op met de beheerder.';
            error_log(
                'Scouting Veghel: admin login failed at ' .
                    $loginStage .
                    ' (' .
                    get_class($error) .
                    ', code ' .
                    (int) $error->getCode() .
                    ').'
            );
        }
    }
}
if (!adminIsLoggedIn()) {
    $html = login($loginError);
    // Keep the booking destination when a password must be entered again.
    $returnId = $_POST['beheer_return_id'] ?? '';
    if (is_string($returnId) && preg_match('/^[1-9][0-9]{0,9}$/D', $returnId)) {
        $html = str_replace(
            '</form>',
            '<input type="hidden" name="beheer_return" value="reserveringen"><input type="hidden" name="beheer_return_id" value="' .
                $returnId .
                '"></form>',
            $html
        );
    }
    echo $html;
    exit();
}

$adminBootStage = 'instellingen';
try {
    require_once __DIR__ . '/bootstrap.php';
    $adminBootStage = 'templates';
    require_once __DIR__ . '/templates/includes.php';
    require_once __DIR__ . '/calendar-functions.php';
    $adminBootStage = 'pagina';
    $module = beheerInput('m', 'inbox');
    switch ($module) {
        case 'inbox':
            echo inbox(beheerId('s', 0), beheerId('i', 0));
            break;
        case 'search':
            echo search();
            break;
        case 'toevoegen':
            echo toevoegen();
            break;
        case 'agenda': // Preserve old bookmarks and direct links.
        case 'kalender':
            try {
                $start = adminCalendarStart(
                    beheerInput('from', ''),
                    isset($_GET['y']) ? beheerId('y') : null
                );
            } catch (InvalidArgumentException $error) {
                http_response_code(400);
                beheerHead('Kalender');
                echo '<p>Kies een geldige startmaand.</p>';
                beheerFoot();
                break;
            }
            $rows = adminCalendarReservations(
                $database,
                $start->format('Y-m-d 00:00:00'),
                $start
                    ->modify('+14 months')
                    ->modify('last day of this month')
                    ->format('Y-m-d 23:59:59')
            );
            $months = [];
            for ($i = 0; $i < 15; $i++) {
                $month = $start->modify('+' . $i . ' months');
                $months[] = displayMonth(
                    (int) $month->format('n'),
                    (int) $month->format('Y'),
                    $rows,
                    null,
                    $start->format('Y-m')
                );
            }
            beheerHead('Kalender');
            echo adminCalendarView($start, $months);
            beheerFoot();
            break;
        default:
            http_response_code(404);
            beheerHead('Onbekende pagina');
            echo '<p>Deze beheerpagina bestaat niet.</p>';
            beheerFoot();
    }
} catch (Throwable $error) {
    http_response_code(503);
    $reference = 'BEHEER-' . strtoupper($adminBootStage) . '-' . (int) $error->getCode();
    error_log('Scouting Veghel: ' . $reference . ' (' . get_class($error) . ').');
    echo beheerDocumentStart(
        'Beheer tijdelijk niet beschikbaar',
        '',
        'width=device-width,initial-scale=1'
    ) .
        '<main class="empty-state">' .
        '<h1>Beheer kon niet worden geladen</h1>' .
        '<p>Het laden van de beheerpagina is mislukt. Geef onderstaande foutcode door zodat dit gericht kan worden hersteld.</p>' .
        '<p>' .
        '<strong>' .
        htmlspecialchars($reference, ENT_QUOTES, 'UTF-8') .
        '</strong></p><p><a href="/beheer/index.html">Opnieuw proberen</a> · <a href="/beheer/logout.php">Uitloggen</a></p></main></body></html>';
}
