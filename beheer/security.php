<?php
require_once __DIR__ . '/access-functions.php';
// Shared security for every administration entry point (PHP 7.3+).
// Public administration must use TLS. Loopback remains available for local tests.
$adminHost = strtolower($_SERVER['HTTP_HOST'] ?? '');
$adminLocal = in_array(
    parse_url('http://' . $adminHost, PHP_URL_HOST),
    ['localhost', '127.0.0.1', '[::1]'],
    true
);
if (!$adminLocal && (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off')) {
    if (
        in_array(
            $adminHost,
            ['scoutingveghel.nl', 'www.scoutingveghel.nl', 'test.scoutingveghel.nl'],
            true
        ) &&
        in_array($_SERVER['REQUEST_METHOD'] ?? '', ['GET', 'HEAD'], true)
    ) {
        header('Location: https://' . $adminHost . '/beheer/index.html', true, 302);
    } else {
        http_response_code(403);
        echo 'Gebruik HTTPS om in te loggen op beheer.';
    }
    exit();
}
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    session_name('SVADMIN');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/beheer/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_start();
}
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header("Content-Security-Policy: frame-ancestors 'self'; base-uri 'self'; form-action 'self'");
header('Referrer-Policy: same-origin');

function adminCsrfToken() {
    if (empty($_SESSION['ADMIN_CSRF'])) {
        $_SESSION['ADMIN_CSRF'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['ADMIN_CSRF'];
}
function adminClearSession() {
    $_SESSION = [];
    session_regenerate_id(true);
}
function adminIsLoggedIn() {
    if (($_SESSION['ADMIN_LOGGED_IN'] ?? false) !== true) {
        return false;
    }
    $now = time();
    if (
        empty($_SESSION['ADMIN_STARTED']) ||
        empty($_SESSION['ADMIN_LAST']) ||
        $now - $_SESSION['ADMIN_LAST'] > 1800 ||
        $now - $_SESSION['ADMIN_STARTED'] > 28800
    ) {
        adminClearSession();
        return false;
    }
    // Refresh permissions on every request so revocation applies to open sessions too.
    require_once __DIR__ . '/../php/database.php';
    $roleDatabase = siteDatabaseConnection();
    $stmt = $roleDatabase->prepare(
        'SELECT a.rol,a.auth_version FROM verhuur_administrators a JOIN beheer_access_groups g ON g.code=a.rol WHERE a.naam = ?'
    );
    $stmt->bind_param('s', $_SESSION['ADMIN_NAME']);
    $stmt->execute();
    $stmt->bind_result($role, $version);
    $found = $stmt->fetch();
    $stmt->close();
    if (!$found || (int) $version !== (int) ($_SESSION['ADMIN_AUTH_VERSION'] ?? -1)) {
        adminClearSession();
        return false;
    }
    $_SESSION['ADMIN_ROLE'] = $role;
    $stmt = $roleDatabase->prepare('SELECT module FROM beheer_access_permissions WHERE groep=?');
    $stmt->bind_param('s', $role);
    $stmt->execute();
    $stmt->bind_result($module);
    $modules = [];
    while ($stmt->fetch()) {
        $modules[] = $module;
    }
    $stmt->close();
    $_SESSION['ADMIN_MODULES'] = $modules;
    $_SESSION['ADMIN_LAST'] = $now;
    return true;
}
function adminRequireLogin() {
    if (!adminIsLoggedIn()) {
        http_response_code(401);
        exit(
            '<p>Je bent niet ingelogd of je sessie is verlopen. <a href="/beheer/manage.php" target="_top">Opnieuw inloggen</a></p>'
        );
    }
    adminRequireRequestModule();
}
function adminCompleteLogin($name, $version) {
    adminClearSession();
    $_SESSION['ADMIN_NAME'] = $name;
    $_SESSION['ADMIN_AUTH_VERSION'] = (int) $version;
    $_SESSION['ADMIN_LOGGED_IN'] = true;
    $_SESSION['ADMIN_STARTED'] = $_SESSION['ADMIN_LAST'] = time();
    adminCsrfToken();
}
function adminRateAttempt($name) {
    $path =
        getenv('SV_ENV') === 'local'
            ? dirname(__DIR__, 2) . '/local/run/admin-rate.json'
            : sys_get_temp_dir() . '/sv-admin-' . hash('sha256', __DIR__) . '.json';
    $file = @fopen($path, 'c+');
    if (!$file || !flock($file, LOCK_EX)) {
        if ($file) {
            fclose($file);
        }
        throw new RuntimeException('Login limiter unavailable');
    }
    @chmod($path, 0600);
    $now = time();
    $state = json_decode(stream_get_contents($file), true);
    if (!is_array($state)) {
        $state = [];
    }
    foreach ($state as $key => $entry) {
        if (($entry['until'] ?? 0) <= $now) {
            unset($state[$key]);
        }
    }
    $keys = [
        hash('sha256', 'ip:' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown')),
        hash('sha256', 'user:' . strtolower($name))
    ];
    $allowed = count($state) < 10000;
    foreach ($keys as $key) {
        if (($state[$key]['count'] ?? 0) >= 10) {
            $allowed = false;
        }
    }
    if ($allowed) {
        foreach ($keys as $key) {
            if (!isset($state[$key])) {
                $state[$key] = ['count' => 0, 'until' => $now + 900];
            }
            $state[$key]['count']++;
        }
        $data = json_encode($state);
        rewind($file);
        $ok = ftruncate($file, 0) && fwrite($file, $data) === strlen($data) && fflush($file);
    } else {
        $ok = true;
    }
    flock($file, LOCK_UN);
    fclose($file);
    if (!$ok) {
        throw new RuntimeException('Cannot save login limiter');
    }
    return $allowed;
}
function adminCheckCredentials($database, $name, $password) {
    $stmt = $database->prepare(
        'SELECT a.naam,a.pass,a.pass_hash,a.auth_version FROM verhuur_administrators a JOIN beheer_access_groups g ON g.code=a.rol WHERE a.naam = ? LIMIT 1'
    );
    if (!$stmt) {
        throw new RuntimeException('Apply the admin password migration first');
    }
    $stmt->bind_param('s', $name);
    $stmt->execute();
    $stmt->bind_result($storedName, $legacy, $hash, $version);
    $found = $stmt->fetch();
    $stmt->close();
    // Work factor is also paid for unknown accounts; never reveal which names exist.
    $dummy = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
    if (!$found) {
        password_verify($password, $dummy);
        return false;
    }
    if (!empty($hash)) {
        if (!password_verify($password, $hash)) {
            return false;
        }
    } else {
        password_verify($password, $dummy);
        // Preserve existing accounts, upgrading only after a correct legacy login.
        if (!is_string($legacy) || $legacy === '' || !hash_equals($legacy, $password)) {
            return false;
        }
    }
    if (empty($hash) || password_needs_rehash($hash, PASSWORD_DEFAULT)) {
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        $update = $database->prepare(
            "UPDATE verhuur_administrators SET pass_hash = ?, pass = '' WHERE naam = ? AND (pass_hash <=> ?)"
        );
        $update->bind_param('sss', $newHash, $storedName, $hash);
        if (!$update->execute() || $update->affected_rows !== 1) {
            throw new RuntimeException('Password upgrade failed');
        }
        $update->close();
    }
    $mfa = $database->prepare('SELECT mfa_secret FROM verhuur_administrators WHERE naam = ?');
    $mfa->bind_param('s', $storedName);
    $mfa->execute();
    $mfa->bind_result($secret);
    $mfa->fetch();
    $mfa->close();
    adminClearSession();
    if ($secret) {
        $_SESSION['ADMIN_PENDING'] = $storedName;
        $_SESSION['ADMIN_PENDING_TIME'] = time();
        $_SESSION['ADMIN_PENDING_VERSION'] = (int) $version;
    } else {
        adminCompleteLogin($storedName, $version);
    }
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $update = $database->prepare(
        'UPDATE verhuur_administrators SET ip = ?, activity = NOW() WHERE naam = ?'
    );
    $update->bind_param('ss', $ip, $storedName);
    $update->execute();
    $update->close();
    return true;
}

// Validate every POST before any page-specific mutation. Do not pass the token to legacy POST loops.
adminCsrfToken();
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $token = $_POST['_admin_csrf'] ?? null;
    if (!is_string($token) || !hash_equals(adminCsrfToken(), $token)) {
        http_response_code(403);
        exit('<p>Dit formulier is verlopen. Ververs de pagina en probeer opnieuw.</p>');
    }
    unset($_POST['_admin_csrf']);
}
// Existing templates generate HTML strings: add the same CSRF token to every local POST form.
ob_start(function ($html) {
    return preg_replace_callback(
        '/<form\b[^>]*>/i',
        function ($match) {
            if (!preg_match('/\bmethod\s*=\s*[\x22\x27]?post\b/i', $match[0])) {
                return $match[0];
            }
            return $match[0] .
                '<input type="hidden" name="_admin_csrf" value="' .
                htmlspecialchars(adminCsrfToken(), ENT_QUOTES, 'UTF-8') .
                '">';
        },
        $html
    );
});
