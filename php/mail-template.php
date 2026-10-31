<?php
function siteMailInlineImages() {
    return [
        [
            'path' => __DIR__ . '/../images/scouting-veghel-mail-logo.png',
            'cid' => 'scouting-veghel-logo',
            'name' => 'scouting-veghel-logo.png'
        ]
    ];
}
// $content is trusted template HTML; escape user input before composing it.
function siteMailTemplate($module, $title, $content) {
    $modules = [
        'contact' => [
            'Contact',
            'Deze e-mail is verstuurd vanuit het contactformulier van Scouting Veghel.'
        ],
        'verhuur' => [
            'Verhuur',
            'Deze e-mail is verstuurd vanuit het verhuurprogramma van Scouting Veghel.'
        ]
    ];
    if (!isset($modules[$module])) {
        throw new InvalidArgumentException('Onbekende mailmodule.');
    }
    $title = htmlspecialchars((string) $title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    [$label, $footer] = $modules[$module];
    return '<!doctype html><html lang="nl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head><body data-mail-module="' .
        $module .
        '" style="margin:0;padding:24px;background:#f1f3ed;font-family:Arial,Helvetica,sans-serif;color:#26382c;">' .
        '<table role="presentation" width="100%" cellspacing="0" cellpadding="0"><tr><td align="center">' .
        '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;background:#ffffff;border:1px solid #dfe5da;border-top:6px solid #355d40;border-radius:8px;">' .
        '<tr><td style="padding:32px 36px;">' .
        '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:0 0 28px"><tr>' .
        '<td width="60%" style="vertical-align:middle;padding-right:12px">' .
        '<img src="cid:scouting-veghel-logo" width="200" height="75" alt="Scouting Veghel" style="display:block;width:200px;max-width:100%;height:auto;border:0;">' .
        '</td>' .
        '<td align="right" style="vertical-align:middle;color:#607262;font-size:14px;font-weight:bold;letter-spacing:1px;text-transform:uppercase;">' .
        $label .
        '</td>' .
        '</tr></table>' .
        '<h1 style="margin:0 0 20px;color:#26382c;font-size:25px;line-height:1.25;">' .
        $title .
        '</h1>' .
        $content .
        '</td></tr><tr><td style="padding:18px 36px;border-top:1px solid #dfe5da;color:#607262;font-size:12px;line-height:1.6;">' .
        $footer .
        '</td></tr>' .
        '</table></td></tr></table></body></html>';
}
