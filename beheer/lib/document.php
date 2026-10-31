<?php
/** Shared document markup. Deliberately independent of login and database setup. */
function beheerDocumentStart(
    $title,
    $bodyClass = '',
    $viewport = 'width=device-width, initial-scale=1',
    $styles = [],
    $scripts = []
) {
    $escape = static function ($value) {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    };
    $html = '<!doctype html><html lang="nl"><head><meta charset="utf-8">';
    if ($viewport !== '') {
        $html .= '<meta name="viewport" content="' . $escape($viewport) . '">';
    }
    $html .= '<title>' . $escape($title) . '</title>';
    $html .= '<link rel="stylesheet" href="/beheer/style.css?v=20261029-autoheight">';
    foreach ($styles as $style) {
        $html .= '<link rel="stylesheet" href="' . $escape($style) . '">';
    }
    foreach ($scripts as $script) {
        $html .= '<script src="' . $escape($script) . '" defer></script>';
    }
    $html .= '</head>';
    $html .= $bodyClass === '' ? '<body>' : '<body class="' . $escape($bodyClass) . '">';
    return $html;
}
