<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit();
}
// Standalone test: synthetic settings only; no database or network access.
$webroot = is_dir(__DIR__ . '/../public_html') ? __DIR__ . '/../public_html' : dirname(__DIR__);
require_once $webroot . '/php/private-config.php';
require_once $webroot . '/php/rental-settings.php';
function configurationCheck($ok, $message) {
    if (!$ok) {
        throw new RuntimeException($message);
    }
}
function configurationRejected($callback) {
    try {
        $callback();
    } catch (RuntimeException $error) {
        return;
    }
    throw new RuntimeException('Unsafe configuration accepted');
}
$directory = sys_get_temp_dir() . '/sv-config-' . bin2hex(random_bytes(8));
mkdir($directory, 0700);
$file = $directory . '/recipients.php';
try {
    putenv('SV_ENV=configuration-test');
    foreach (
        ['SV_MAIL_GEBE', 'SV_MAIL_RENTAL', 'SV_MAIL_BOARD', 'SV_MAIL_SPONSOR', 'SV_MAIL_WEBSITE']
        as $name
    ) {
        putenv($name);
    }
    putenv('SV_RECIPIENTS_CONFIG=' . $file);
    file_put_contents(
        $file,
        "<?php return ['verhuur'=>'rental@example.invalid', 'bestuur'=>'board@example.invalid'];"
    );
    configurationCheck(
        siteMailRecipient('verhuur') === 'rental@example.invalid',
        'Private recipient not used'
    );
    configurationCheck(
        siteMailRecipient('sponsor') === 'board@example.invalid',
        'Sponsor fallback failed'
    );
    putenv('SV_MAIL_RENTAL=override@example.invalid');
    configurationCheck(
        siteMailRecipient('verhuur') === 'override@example.invalid',
        'Environment override failed'
    );
    putenv("SV_MAIL_RENTAL=bad@example.invalid\r\nBcc: other@example.invalid");
    configurationRejected(function () {
        siteMailRecipient('verhuur');
    });
    putenv('SV_MAIL_RENTAL');
    try {
        siteMailRecipient('visitor@example.invalid');
        throw new RuntimeException('Unknown route accepted');
    } catch (InvalidArgumentException $error) {
    }
    putenv('SV_RECIPIENTS_CONFIG=' . $webroot . '/php/private-config.php');
    configurationRejected(function () {
        siteMailRecipient('verhuur');
    });
    putenv('SV_RECIPIENTS_CONFIG=' . $directory . '/missing.php');
    configurationRejected(function () {
        siteMailRecipient('verhuur');
    });
    putenv('SV_ENV=local');
    putenv('SV_MAIL_RENTAL=must-not-be-used@example.invalid');
    configurationCheck(
        siteMailRecipient('verhuur') === 'verhuur@example.invalid',
        'Local mail isolation failed'
    );
    putenv('SV_RENTAL_CONTRACT_CONFIG=' . $file);
    file_put_contents($file, '<?php return [];');
    configurationRejected(function () {
        rentalContractConfiguration();
    });
    $settings = array_fill_keys(
        [
            'HV_VERHUURDER_NAME',
            'HV_VERHUURDER_REP',
            'HV_VERHUURDER_ADDRESS',
            'HV_VERHUURDER_POSTAL',
            'HV_VERHUURDER_PHONE',
            'HV_BEHEERDER',
            'HV_BEHEERDER_PHONE',
            'HV_VERHUURDER_ACCOUNT'
        ],
        'Synthetic contract fixture'
    );
    file_put_contents($file, '<?php return ' . var_export($settings, true) . ';');
    configurationCheck(
        rentalContractConfiguration() === $settings,
        'Private contract fields not preserved'
    );
    echo "Private recipients, environment override, sponsor fallback, injection rejection, webroot rejection, missing config, local isolation and contract settings PASS\n";
} finally {
    @unlink($file);
    @rmdir($directory);
}
