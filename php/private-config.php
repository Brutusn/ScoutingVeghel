<?php
/** Read deployment settings from outside the public document root. */
function sitePrivateConfiguration($name, $environmentVariable) {
    $path = getenv($environmentVariable) ?: dirname(__DIR__, 2) . '/private/' . $name;
    $file = realpath($path);
    if (!$file) {
        return [];
    }
    $webroot = realpath(dirname(__DIR__));
    if (
        !is_file($file) ||
        $file === $webroot ||
        strpos($file, $webroot . DIRECTORY_SEPARATOR) === 0
    ) {
        throw new RuntimeException('Bewaar privéconfiguratie buiten public_html.');
    }
    $settings = (static function ($file) {
        return require $file;
    })($file);
    if (!is_array($settings)) {
        throw new RuntimeException('Ongeldige privéconfiguratie.');
    }
    return $settings;
}

/** Fixed route names prevent a visitor from choosing an arbitrary recipient. */
function siteMailRecipient($destination) {
    $variables = [
        'gebe' => 'SV_MAIL_GEBE',
        'verhuur' => 'SV_MAIL_RENTAL',
        'bestuur' => 'SV_MAIL_BOARD',
        'sponsor' => 'SV_MAIL_SPONSOR',
        'site' => 'SV_MAIL_WEBSITE'
    ];
    if (!isset($variables[$destination])) {
        throw new InvalidArgumentException('Onbekende mailbestemming.');
    }
    if (getenv('SV_ENV') === 'local') {
        // Local mail is captured; real deployment recipients are never loaded.
        return [
            'gebe' => 'groepsbegeleiding@example.invalid',
            'verhuur' => 'verhuur@example.invalid',
            'bestuur' => 'bestuur@example.invalid',
            'sponsor' => 'bestuur@example.invalid',
            'site' => 'website@example.invalid'
        ][$destination];
    }
    $address = getenv($variables[$destination]);
    if ($address === false || $address === '') {
        $settings = sitePrivateConfiguration('recipients.php', 'SV_RECIPIENTS_CONFIG');
        $address = $settings[$destination] ?? null;
        if ($destination === 'sponsor' && $address === null) {
            return siteMailRecipient('bestuur');
        }
    }
    if (
        !is_string($address) ||
        !filter_var($address, FILTER_VALIDATE_EMAIL) ||
        preg_match('/[\r\n]/', $address)
    ) {
        throw new RuntimeException('Configureer de mailontvangers buiten public_html.');
    }
    return $address;
}
