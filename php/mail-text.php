<?php
function mailTextDefaults() {
    return [
        'verhuur.optie.onderwerp' => 'Aanvraag huren blokhut Scouting Veghel',
        'verhuur.optie.titel' => 'Bevestig je optie op de blokhut',
        'verhuur.optie.intro' =>
            'We hebben je aanvraag ontvangen. Bevestig de optie via de knop hieronder.',
        'verhuur.optie.vervallen' =>
            'Heb je deze aanvraag niet gedaan of wil je niet bevestigen? Dan hoef je niets te doen. De optie vervalt na 14 dagen.',
        'verhuur.documenten.onderwerp' => 'Je reservering is bevestigd | Scouting Veghel',
        'verhuur.documenten.titel' => 'Je reservering is bevestigd',
        'verhuur.documenten.intro' =>
            'Je optie is bevestigd. Lees de huurovereenkomst goed door en onderteken deze.',
        'verhuur.team.nieuw.titel' => 'Nieuwe reserveringsaanvraag',
        'verhuur.team.bevestigd.titel' => 'Reservering bevestigd door huurder',
        'verhuur.team.nieuw.intro' =>
            'Er is een nieuwe reserveringsaanvraag via de website aangemaakt. De aanvraag staat als optie geregistreerd; de huurder moet deze nog per e-mail bevestigen.',
        'verhuur.team.bevestigd.intro' =>
            'De huurder heeft de reserveringsaanvraag via de bevestigingslink bevestigd. De reservering heeft nu de status Bevestigd. Bekijk de aanvraag in beheer voor de verdere afhandeling.',
        'contact.afzender.titel' => 'Je bericht is verstuurd',
        'contact.groep.titel' => 'Nieuw contactbericht',
        'contact.afzender.antwoord' => 'We reageren zo spoedig mogelijk.',
        'contact.groep.antwoord' =>
            'Gebruik Beantwoorden in je mailprogramma om rechtstreeks te reageren op de afzender.'
    ];
}
function mailTextOverrides($database = null) {
    static $values;
    if ($values !== null) {
        return $values;
    }
    $values = [];
    $owned = $database === null;
    try {
        require_once __DIR__ . '/database.php';
        if ($owned) {
            $database = siteDatabaseConnection();
        }
        $stmt = $database->prepare('SELECT naam,inhoud FROM beheer_mail_templates');
        $stmt->execute();
        $stmt->bind_result($key, $text);
        while ($stmt->fetch()) {
            $values[$key] = $text;
        }
        $stmt->close();
    } catch (Throwable $error) {
        error_log('Scouting Veghel: mail templates unavailable; using defaults.');
    } finally {
        if ($owned && $database) {
            $database->close();
        }
    }
    return $values;
}
function mailText($key) {
    $defaults = mailTextDefaults();
    if (!isset($defaults[$key])) {
        throw new InvalidArgumentException('Onbekend sjabloonveld');
    }
    return mailTextOverrides()[$key] ?? $defaults[$key];
}
function mailTextHtml($key) {
    return nl2br(htmlspecialchars(mailText($key), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
}
