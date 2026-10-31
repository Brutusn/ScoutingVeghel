<?php
function adminModules() {
    return [
        'reserveringen' => 'Reserveringen en huurders',
        'kalender' => 'Kalender',
        'zoeken' => 'Zoeken',
        'toevoegen' => 'Verhuringen en huurders toevoegen',
        'maillog' => 'Maillog',
        'mailsjablonen' => 'Verhuurmailsjablonen',
        'contact-sjablonen' => 'Contactmailsjablonen',
        'onderhoud' => 'Onderhoud en privacy',
        'eigen-groepen' => 'Eigen scoutinggroepen',
        'accounts' => 'Groepen en beheerders'
    ];
}
function adminCan($module) {
    return in_array($module, $_SESSION['ADMIN_MODULES'] ?? [], true);
}
function adminRequestModule() {
    // Use the actual entry point, including when legacy PHP files are included by manage.php.
    $path = $_SERVER['SCRIPT_NAME'] ?? '';
    $name = basename($path);
    if ($name === 'manage.php') {
        $module = $_GET['m'] ?? 'inbox';
        return is_string($module)
            ? [
                    'inbox' => 'reserveringen',
                    'search' => 'zoeken',
                    'toevoegen' => 'toevoegen',
                    'agenda' => 'kalender',
                    'kalender' => 'kalender'
                ][$module] ?? 'unknown'
            : 'unknown';
    }
    $pages = [
        'accounts.php' => 'accounts',
        'onderhoud.php' => 'onderhoud',
        'privacy.php' => 'onderhoud',
        'privacy-add.php' => 'toevoegen',
        'eigen-groepen.php' => 'eigen-groepen',
        'maillog.php' => 'maillog',
        'mailsjablonen.php' => 'templates'
    ];
    if (isset($pages[$name])) {
        return $pages[$name];
    }
    if (strpos($path, '/beheer/page/search/') === 0) {
        return 'zoeken';
    }
    if (strpos($path, '/beheer/page/toevoegen/') === 0) {
        return 'toevoegen';
    }
    if (strpos($path, '/beheer/page/') === 0) {
        return in_array($name, ['agenda.php', 'agenda_admin.php', 'agenda_day.php'], true)
            ? 'kalender'
            : 'reserveringen';
    }
    return in_array($name, ['session.php', 'mfa.php', 'logout.php', 'welcome.php'], true)
        ? null
        : 'unknown';
}
function adminRequireModule($module) {
    adminRequireLogin();
    if (!adminCan($module)) {
        adminAccessDenied();
    }
}
function adminAccessDenied() {
    http_response_code(403);
    exit(
        '<p>Je rechtengroep heeft geen toegang tot deze module. Vraag een beheerder om je groepsrechten aan te passen.</p>'
    );
}
function adminRequireRequestModule() {
    $module = adminRequestModule();
    if ($module === 'templates') {
        if (!adminCan('mailsjablonen') && !adminCan('contact-sjablonen')) {
            adminAccessDenied();
        }
    } elseif ($module !== null && !adminCan($module)) {
        adminAccessDenied();
    }
}
