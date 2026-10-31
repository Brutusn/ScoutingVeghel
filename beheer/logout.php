<?php
require_once __DIR__ . '/security.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    adminClearSession();
    session_destroy();
    setcookie(session_name(), '', [
        'expires' => time() - 3600,
        'path' => '/beheer/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    header('Location: /beheer/index.html', true, 303);
    exit();
}
?><!doctype html><html lang="nl"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Uitloggen</title><link rel="stylesheet" href="/beheer/style.css?v=20261025-live-fix">
<body><h1>Uitloggen</h1><p>Wil je je beheersessie afsluiten?</p><form method="post" action="logout.php" target="_top"><button type="submit">Veilig uitloggen</button></form></html>
