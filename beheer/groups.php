<?php
// Own groups are the tenants linked through verhuur_eigen_groep; never expose their access codes.
function adminOwnGroups($database) {
    $result = $database->query(
        'SELECT DISTINCT h.id, h.naam FROM verhuur_eigen_groep g JOIN verhuur_huurder h ON h.id = g.huurder_id ORDER BY h.naam'
    );
    $groups = [];
    foreach ($result as $row) {
        $groups[(int) $row['id']] = $row['naam'];
    }
    return $groups;
}
function adminOwnGroupOptions($database, $selected = '') {
    $selected = (string) $selected;
    $options =
        '<option value=""' .
        ($selected === '' ? ' selected' : '') .
        '>Geen eigen groep (extern)</option>';
    $found = $selected === '';
    foreach (adminOwnGroups($database) as $id => $name) {
        $escaped = htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $active = $selected === $name;
        $found = $found || $active;
        $options .=
            '<option value="' .
            $escaped .
            '" data-huurder-id="' .
            $id .
            '"' .
            ($active ? ' selected' : '') .
            '>' .
            $escaped .
            '</option>';
    }
    // Retain historical values until the administrator explicitly replaces them.
    if (!$found) {
        $escaped = htmlspecialchars($selected, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $options .=
            '<option value="' .
            $escaped .
            '" selected>' .
            $escaped .
            ' (bestaande waarde)</option>';
    }
    return $options;
}
function adminGroupForNewRental($database, $tenantId, $requested) {
    if (!is_scalar($tenantId) || !is_string($requested)) {
        throw new InvalidArgumentException('Ongeldige groepskeuze.');
    }
    $groups = adminOwnGroups($database);
    if (isset($groups[(int) $tenantId])) {
        return $groups[(int) $tenantId];
    }
    if (trim($requested) !== '') {
        throw new InvalidArgumentException('Kies de huurder die bij deze eigen groep hoort.');
    }
    return '';
}
