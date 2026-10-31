<?php
require_once __DIR__ . '/../lib/document.php';
function search() {
    return beheerDocumentStart(
        'Zoeken | Beheer',
        'admin-workspace',
        'width=device-width, initial-scale=1'
    ) .
        "<header class='workspace-heading'>" .
        '<h1>Zoeken</h1>' .
        '<p>Zoek op kenmerk, groep of huurder.</p>' .
        '</header>' .
        "<main class='workspace-grid'>" .
        "<section class='workspace-panel'>" .
        '<h2>Zoekfilters</h2>' .
        "<iframe src='page/search/search_form.php' name='search' title='Zoekfilters'>" .
        '</iframe>' .
        '</section>' .
        "<section class='workspace-panel'>" .
        '<h2>Resultaten</h2>' .
        "<iframe src='page/search/results.php' name='results' title='Resultaten'>" .
        '</iframe>' .
        '</section>' .
        "<section class='workspace-panel'>" .
        '<h2>Reserveringsdetails</h2>' .
        "<iframe src='page/inbox/item.php' name='item' title='Reserveringsdetails'>" .
        '</iframe>' .
        '</section>' .
        '</main>' .
        '</body>' .
        '</html>';
}
