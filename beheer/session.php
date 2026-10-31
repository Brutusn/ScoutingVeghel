<?php
require_once __DIR__ . '/security.php';
header('Content-Type: application/json');
try {
    $logged = adminIsLoggedIn();
    echo json_encode([
        'loggedIn' => $logged,
        'role' => $logged ? $_SESSION['ADMIN_ROLE'] : null,
        'modules' => $logged ? $_SESSION['ADMIN_MODULES'] : []
    ]);
} catch (Throwable $error) {
    http_response_code(503);
    echo '{"loggedIn":false}';
}
