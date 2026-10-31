<?php
require_once __DIR__ . '/mail-html.php';
function contactMailTemplate($input, $groupLabel, $forSender = false) {
    return mailHtmlRender($forSender ? 'contact.afzender' : 'contact.groep', [
        'naam' => $input['name'],
        'email' => $input['mailadr'],
        'groep' => $groupLabel,
        'bericht' => $input['tArea']
    ]);
}
