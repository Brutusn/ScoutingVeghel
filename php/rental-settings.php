<?php

//Settings Reservation
define('MIN_AANTAL_PERSONEN', '20');
define('MAX_AANTAL_PERSONEN', '60');
define('MIN_AANTAL_UREN', '0');
define('MAX_AANTAL_UREN', '8');
define('MIN_AANTAL_OVERNACHTINGEN', '0');
define('MAX_AANTAL_OVERNACHTINGEN', '14');
define('KOSTEN_PPPN', '5.00');
define('KOSTEN_PER_UUR', '10');
define('NUMBER_OF_DAYS_OPTION_IS_VALID', '14');
define('BEDRAG_BORG_DAGDEEL', '50');
define('BEDRAG_BORG_NACHT', '75');
define('BEDRAG_BORG_LANGER', '300');

/** Deployment-specific contract details never live in versioned source. */
function rentalContractConfiguration() {
    require_once __DIR__ . '/private-config.php';
    $config = sitePrivateConfiguration('rental-contract.php', 'SV_RENTAL_CONTRACT_CONFIG');
    foreach (
        [
            'HV_VERHUURDER_NAME',
            'HV_VERHUURDER_REP',
            'HV_VERHUURDER_ADDRESS',
            'HV_VERHUURDER_POSTAL',
            'HV_VERHUURDER_PHONE',
            'HV_BEHEERDER',
            'HV_BEHEERDER_PHONE',
            'HV_VERHUURDER_ACCOUNT'
        ]
        as $key
    ) {
        if (!isset($config[$key]) || !is_string($config[$key]) || trim($config[$key]) === '') {
            throw new RuntimeException('Configureer de contractgegevens buiten public_html.');
        }
    }
    return $config;
}
