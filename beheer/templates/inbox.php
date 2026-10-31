<?php
require_once __DIR__ . '/../lib/document.php';
function inbox($status_id = 0, $item_id = 0) {
    return beheerDocumentStart(
        'Reserveringen | Beheer',
        'admin-workspace',
        'width=device-width, initial-scale=1'
    ) .
        "<header class='workspace-heading'>" .
        '<h1>Reserveringen</h1>' .
        '<p>Bekijk aanvragen en beheer de details van een reservering.</p>' .
        '</header>' .
        "<main class='workspace-grid'>" .
        "<section class='workspace-panel'>" .
        '<h2>Status</h2>' .
        "<iframe src='page/inbox/directory_listing.php' name='directory' title='Status'>" .
        '</iframe>' .
        '</section>' .
        "<section class='workspace-panel'>" .
        '<h2>Aanvragen</h2>' .
        "<iframe src='page/inbox/items_list.php?s={$status_id}' name='items' title='Aanvragen'>" .
        '</iframe>' .
        '</section>' .
        "<section class='workspace-panel'>" .
        '<h2>Reserveringsdetails</h2>' .
        "<iframe src='page/inbox/item.php?i={$item_id}' name='item' title='Reserveringsdetails'>" .
        '</iframe>' .
        '</section>' .
        '</main>' .
        '</body>' .
        '</html>';
}
