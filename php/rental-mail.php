<?php

require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/mail-template.php';
require_once __DIR__ . '/mail-html.php';

require_once __DIR__ . '/private-config.php';

$MAIL_ADDRESS_VERHUUR = siteMailRecipient('verhuur');
$MAIL_ADDRESS_WEBSITE = smtpConfiguration()['from'];

function verhuurSiteBaseUrl() {
    $host = strtolower(isset($_SERVER['HTTP_HOST']) ? (string) $_SERVER['HTTP_HOST'] : '');
    if ($host === 'localhost:8000' || $host === '127.0.0.1:8000') {
        return 'http://' . $host;
    }
    if ($host === 'test.scoutingveghel.nl') {
        return 'https://' . $host;
    }
    return 'https://www.scoutingveghel.nl';
}

$PUBLIC_SITE_URL = verhuurSiteBaseUrl();
$VERIFY_BASE_URL = $PUBLIC_SITE_URL . '/php/verhuur-confirm.php?key=';
$HUURVOORWAARDEN_URL = $PUBLIC_SITE_URL . '/docs/huurvoorwaarden.pdf';
$HUUROVEREENKOMST_BASE_URL = $PUBLIC_SITE_URL . '/php/huurovereenkomst.php?key=';

function sendRentalTeamBookingEmail(
    $id,
    $name,
    $contact,
    $email,
    $phone,
    $start,
    $end,
    $people,
    $description,
    $ownGroup,
    $overlap,
    $confirmed = false
) {
    global $MAIL_ADDRESS_VERHUUR, $MAIL_ADDRESS_WEBSITE;
    $values = [
        'nummer' => (int) $id,
        'status' => $confirmed ? 'Bevestigd door huurder' : 'Optie – wacht op bevestiging',
        'naam' => $name,
        'contactpersoon' => $contact,
        'email' => $email,
        'telefoon' => $phone,
        'type_verhuur' => $ownGroup ? 'Eigen groep' : 'Extern',
        'aankomst' => (new DateTimeImmutable($start))->format('d-m-Y H:i'),
        'vertrek' => (new DateTimeImmutable($end))->format('d-m-Y H:i'),
        'personen' => $people,
        'bericht' => $description,
        'beheer_url' => verhuurSiteBaseUrl() . '/beheer/index.html#reserveringen?i=' . (int) $id,
        'overlap_waarschuwing' => $overlap
            ? mailHtmlParagraph(
                '<strong>Let op:</strong> er staat al een reservering in deze periode. Controleer de overlap in beheer.'
            )
            : ''
    ];
    $html = mailHtmlRender($confirmed ? 'verhuur.team.bevestigd' : 'verhuur.team.nieuw', $values);
    $result = sendMailWithFrom(
        $MAIL_ADDRESS_VERHUUR,
        mailHtmlSubject($html),
        $html,
        $MAIL_ADDRESS_WEBSITE,
        $email,
        siteMailInlineImages()
    );
    return !empty($result[0]);
}
function sendNewBookingEmail(
    $id,
    $name,
    $contact,
    $email,
    $phone,
    $start,
    $end,
    $people,
    $description,
    $ownGroup,
    $overlap
) {
    return sendRentalTeamBookingEmail(
        $id,
        $name,
        $contact,
        $email,
        $phone,
        $start,
        $end,
        $people,
        $description,
        $ownGroup,
        $overlap
    );
}
function sendConfirmEmailEllen($booking) {
    $group = trim((string) ($booking['groep'] ?? ''));
    $own = !empty($booking['own_group']) || ($group !== '' && strtoupper($group) !== 'NULL');
    return sendRentalTeamBookingEmail(
        $booking['id'],
        $booking['naam'],
        $booking['contactpersoon'],
        $booking['email'],
        $booking['telefoon'],
        $booking['begindatum'],
        $booking['einddatum'],
        $booking['personen'],
        $booking['beschrijving'],
        $own,
        false,
        true
    );
}
function sendConfirmEmail($toMail, $naam, $hashEmail, $activity, $startSTR, $endSTR, $aantalPers) {
    global $MAIL_ADDRESS_VERHUUR, $VERIFY_BASE_URL;
    $html = mailHtmlRender('verhuur.optie', [
        'naam' => $naam,
        'activiteit' => $activity,
        'periode' => $endSTR !== '' ? 'Van ' . $startSTR . ' tot ' . $endSTR : 'Op ' . $startSTR,
        'personen' => $aantalPers,
        'bevestig_url' => $VERIFY_BASE_URL . rawurlencode((string) $hashEmail)
    ]);
    sendMailWithFrom(
        $toMail,
        mailHtmlSubject($html),
        $html,
        $MAIL_ADDRESS_VERHUUR,
        $MAIL_ADDRESS_VERHUUR,
        siteMailInlineImages()
    );
}
function sendDocuments($name, $toMail, $confirm_key) {
    global $MAIL_ADDRESS_VERHUUR, $HUURVOORWAARDEN_URL, $HUUROVEREENKOMST_BASE_URL;
    $html = mailHtmlRender('verhuur.documenten', [
        'naam' => $name,
        'voorwaarden_url' => $HUURVOORWAARDEN_URL,
        'overeenkomst_url' => $HUUROVEREENKOMST_BASE_URL . rawurlencode((string) $confirm_key)
    ]);
    sendMailWithFrom(
        $toMail,
        mailHtmlSubject($html),
        $html,
        $MAIL_ADDRESS_VERHUUR,
        $MAIL_ADDRESS_VERHUUR,
        siteMailInlineImages()
    );
}
