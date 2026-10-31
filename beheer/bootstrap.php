<?php
require_once __DIR__ . '/security.php';
adminRequireLogin();
require_once __DIR__ . '/../php/database.php';
require_once __DIR__ . '/lib/functions.php';
date_default_timezone_set('Europe/Amsterdam');
$database = siteDatabaseConnection();
$DATETIME_FORMAT = beheerSetting('DATETIME_FORMAT', '%Y-%m-%d %T');
foreach (
    [
        'MIN_AANTAL_PERSONEN',
        'MAX_AANTAL_PERSONEN',
        'MIN_AANTAL_UREN',
        'MAX_AANTAL_UREN',
        'MIN_AANTAL_OVERNACHTINGEN',
        'MAX_AANTAL_OVERNACHTINGEN',
        'KOSTEN_PPPN',
        'KOSTEN_PER_UUR'
    ]
    as $setting
) {
    if (!defined($setting)) {
        define($setting, beheerSetting($setting));
    }
}
