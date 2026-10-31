<?php
require_once __DIR__ . '/../lib/document.php';
function toevoegen() {
    require_once __DIR__ . '/../../php/privacy/service.php';
    if (privacyEnabled()) {
        return beheerDocumentStart('Toevoegen', '', 'width=device-width,initial-scale=1') .
            "<iframe src='/beheer/page/toevoegen/verhuring.php' title='Nieuwe verhuring' style='width:100%;height:95vh;border:0'></iframe></body></html>";
    }

    return beheerDocumentStart(
        'Toevoegen | Beheer',
        'admin-workspace',
        'width=device-width, initial-scale=1'
    ) .
        "<header class='workspace-heading'>" .
        '<h1>Toevoegen</h1>' .
        '<p>Voeg een huurder toe en plan een nieuwe verhuring.</p>' .
        '</header>' .
        "<main class='workspace-grid'>" .
        "<section class='workspace-panel'>" .
        '<h2>Wat wil je toevoegen?</h2>' .
        "<iframe src='page/toevoegen/options.php' name='search' title='Wat wil je toevoegen?'>" .
        '</iframe>' .
        '</section>' .
        "<section class='workspace-panel'>" .
        '<h2>Huurder</h2>' .
        "<iframe src='page/toevoegen/gebruikers.php' name='results' title='Huurder'>" .
        '</iframe>' .
        '</section>' .
        "<section class='workspace-panel'>" .
        '<h2>Nieuwe verhuring</h2>' .
        "<iframe src='page/toevoegen/verhuring.php' name='item' title='Nieuwe verhuring'>" .
        '</iframe>' .
        '</section>' .
        '</main>' .
        '</body>' .
        '</html>';
}
