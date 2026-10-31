<?php
// Validate every request before connecting to the mail server.
header('Content-Type: text/plain; charset=UTF-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
function contactFail($status, $message) {
    http_response_code($status);
    echo $message;
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    contactFail(405, 'Gebruik het contactformulier om een bericht te versturen.');
}
if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 32768) {
    contactFail(413, 'Je bericht is te groot. Gebruik maximaal 5000 tekens.');
}
$hosts = ['scoutingveghel.nl', 'www.scoutingveghel.nl', 'test.scoutingveghel.nl'];
if (!empty($_SERVER['HTTP_ORIGIN'])) {
    $origin = parse_url($_SERVER['HTTP_ORIGIN']);
    if (
        !is_array($origin) ||
        ($origin['scheme'] ?? '') !== 'https' ||
        !in_array(strtolower($origin['host'] ?? ''), $hosts, true) ||
        isset($origin['port'])
    ) {
        contactFail(403, 'Verstuur je bericht via de website van Scouting Veghel.');
    }
}
$limits = [
    'name' => 132,
    'mailadr' => 254,
    'whoTo' => 16,
    'tArea' => 20000,
    'g-recaptcha-response' => 4096
];
$input = [];
foreach ($limits as $field => $limit) {
    if (!isset($_POST[$field]) || !is_string($_POST[$field]) || strlen($_POST[$field]) > $limit) {
        contactFail(400, 'Controleer of alle velden correct zijn ingevuld.');
    }
    $input[$field] = trim($_POST[$field]);
    if ($input[$field] === '' || !preg_match('//u', $input[$field])) {
        contactFail(400, 'Controleer of alle velden correct zijn ingevuld.');
    }
}
if (
    isset($_POST['contact_website']) &&
    (!is_string($_POST['contact_website']) || $_POST['contact_website'] !== '')
) {
    contactFail(400, 'Je bericht kon niet worden verwerkt.');
}
if (
    preg_match('/[\x00-\x1f\x7f]/', $input['name'] . $input['mailadr']) ||
    !filter_var($input['mailadr'], FILTER_VALIDATE_EMAIL) ||
    preg_match_all('/./us', $input['name']) > 33 ||
    preg_match_all('/./us', $input['tArea']) > 5000 ||
    strpos($input['tArea'], "\0") !== false
) {
    contactFail(400, 'Controleer je naam, e-mailadres en bericht (maximaal 5000 tekens).');
}
$recipientLabels = [
    'gebe' => 'Groepsbegeleiding',
    'verhuur' => 'Verhuur',
    'bestuur' => 'Bestuur',
    'sponsor' => 'Sponsoring',
    'site' => 'Website'
];
if (!isset($recipientLabels[$input['whoTo']])) {
    contactFail(400, 'Kies voor wie je bericht bedoeld is.');
}
require_once __DIR__ . '/private-config.php';
try {
    $recipient = siteMailRecipient($input['whoTo']);
} catch (Throwable $error) {
    contactFail(503, 'Het formulier is tijdelijk niet beschikbaar. Probeer het later opnieuw.');
}
// Shared, locked state outside the public website. Never trust forwarded IP headers.
$ratePath = sys_get_temp_dir() . '/sv-contact-' . hash('sha256', __DIR__) . '.json';
$rateFile = @fopen($ratePath, 'c+');
if (!$rateFile || !flock($rateFile, LOCK_EX)) {
    if ($rateFile) {
        fclose($rateFile);
    }
    contactFail(503, 'Het formulier is tijdelijk niet beschikbaar. Probeer het later opnieuw.');
}
@chmod($ratePath, 0600);
$now = time();
$rates = json_decode(stream_get_contents($rateFile), true);
if (!is_array($rates)) {
    $rates = [];
}
foreach ($rates as $key => $entry) {
    if (!is_array($entry) || ($entry['until'] ?? 0) <= $now) {
        unset($rates[$key]);
    }
}
$key = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
$entry = $rates[$key] ?? ['until' => $now + 600, 'count' => 0];
if ($entry['count'] >= 5 || (!isset($rates[$key]) && count($rates) >= 5000)) {
    flock($rateFile, LOCK_UN);
    fclose($rateFile);
    header('Retry-After: ' . max(1, $entry['until'] - $now));
    contactFail(
        429,
        'Er zijn te veel verzendpogingen gedaan. Probeer het over tien minuten opnieuw.'
    );
}
$entry['count']++;
$rates[$key] = $entry;
$encoded = json_encode($rates);
rewind($rateFile);
$written =
    ftruncate($rateFile, 0) &&
    fwrite($rateFile, $encoded) === strlen($encoded) &&
    fflush($rateFile);
flock($rateFile, LOCK_UN);
fclose($rateFile);
if (!$written) {
    contactFail(503, 'Het formulier is tijdelijk niet beschikbaar. Probeer het later opnieuw.');
}

// POST verification data and fail closed on network errors or malformed responses.
$secret = getenv('RECAPTCHA_SECRET');
if (!is_string($secret) || $secret === '') {
    try {
        $contactConfig = sitePrivateConfiguration('contact.php', 'SV_CONTACT_CONFIG');
    } catch (Throwable $error) {
        contactFail(503, 'Het formulier is tijdelijk niet beschikbaar. Probeer het later opnieuw.');
    }
    $secret = is_array($contactConfig) ? $contactConfig['recaptcha_secret'] ?? '' : '';
}
if (!is_string($secret) || $secret === '') {
    contactFail(503, 'Het formulier is tijdelijk niet beschikbaar. Probeer het later opnieuw.');
}
$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
        'content' => http_build_query([
            'secret' => $secret,
            'response' => $input['g-recaptcha-response']
        ]),
        'timeout' => 10,
        'follow_location' => 0
    ]
]);
$verified = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);
if ($verified === false) {
    contactFail(503, 'De spamcontrole is tijdelijk niet bereikbaar. Probeer het opnieuw.');
}
$captcha = json_decode($verified, true);
if (
    !is_array($captcha) ||
    ($captcha['success'] ?? false) !== true ||
    !in_array(strtolower($captcha['hostname'] ?? ''), $hosts, true)
) {
    contactFail(400, 'De spamcontrole is niet gelukt. Probeer het opnieuw.');
}
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/contact-mail-template.php';
$groupLabel = $recipientLabels[$input['whoTo']];

$inlineImages = siteMailInlineImages();
$groupMessage = contactMailTemplate($input, $groupLabel);
try {
    $result = sendMail(
        $recipient,
        mailHtmlSubject($groupMessage),
        $groupMessage,
        $input['mailadr'],
        $inlineImages
    );
    if (empty($result[0])) {
        contactFail(503, 'Verzenden is niet gelukt. Probeer het later opnieuw.');
    }
} catch (Throwable $error) {
    contactFail(503, 'Verzenden is niet gelukt. Probeer het later opnieuw.');
}
// Only acknowledge delivery to the sender after the group mail has succeeded.
$confirmation = contactMailTemplate($input, $groupLabel, true);
$confirmationSent = false;
try {
    $result = sendMail(
        $input['mailadr'],
        mailHtmlSubject($confirmation),
        $confirmation,
        $recipient,
        $inlineImages
    );
    $confirmationSent = !empty($result[0]);
} catch (Throwable $error) {
    // A failed confirmation must not invite resending a message already sent to the group.
}
echo 'Hallo ' . $input['name'] . ', je bericht is verstuurd naar ' . $groupLabel . '. ';
if ($confirmationSent) {
    echo 'Een bevestiging met je ingevulde gegevens is naar je e-mailadres gestuurd. We reageren zo spoedig mogelijk.';
} else {
    echo 'De bevestigingsmail kon niet worden verstuurd. Je bericht is wel bij ons ontvangen; je hoeft het niet opnieuw te versturen.';
}
