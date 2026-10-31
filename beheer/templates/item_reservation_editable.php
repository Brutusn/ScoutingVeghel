<?php
require_once __DIR__ . '/../groups.php';
function item_reservation_editable(
    $huurder_id,
    $contactpersoon,
    $naam,
    $adres,
    $postcode,
    $plaats,
    $email,
    $telefoon,
    $reservering_id,
    $begindatum,
    $einddatum,
    $display_begindatum,
    $display_einddatum,
    $personen,
    $beschrijving,
    $id,
    $datum,
    $display_datum,
    $groep,
    $status,
    $confirm
) {
    global $database;
    $groupOptions = adminOwnGroupOptions($database, $groep);
    $groupConversion = '';
    $missingTenantNotice =
        $naam === null
            ? "<p role='alert'>Het oorspronkelijke huurderrecord ontbreekt. Je kunt deze verhuring hieronder aan een bestaande eigen groep koppelen. De overige reserveringsgegevens blijven behouden.</p>"
            : '';
    $groupField = "<select name='groep' aria-label='Op naam van eigen groep'>{$groupOptions}</select>";
    require_once __DIR__ . '/../../php/privacy/service.php';
    if (privacyEnabled()) {
        $safeGroup = htmlspecialchars((string) $groep, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $groups = adminOwnGroups($database);
        $current = isset($groups[(int) $huurder_id])
            ? htmlspecialchars($groups[(int) $huurder_id], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            : ($safeGroup !== '' && strtoupper($safeGroup) !== 'NULL'
                ? $safeGroup . ' (historische vermelding)'
                : 'Externe verhuur');
        $groupField =
            "<input type='hidden' name='groep' value='{$safeGroup}'>" . "<span>{$current}</span>";
        if ((int) $huurder_id !== (int) privacyConfig()['placeholder_id']) {
            $choices = "<option value=''>Kies een eigen groep</option>";
            foreach ($groups as $groupId => $groupName) {
                if (
                    (int) $groupId !== (int) $huurder_id &&
                    in_array(
                        (int) $groupId,
                        array_map('intval', privacyConfig()['disabled_groups']),
                        true
                    )
                ) {
                    continue;
                }
                $label = htmlspecialchars($groupName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $choices .=
                    "<option value='" .
                    (int) $groupId .
                    "'" .
                    ((int) $groupId === (int) $huurder_id ? ' selected' : '') .
                    ">{$label}</option>";
            }
            $rentalId = (int) $id;
            $reservationId = (int) $reservering_id;
            $tenantId = (int) $huurder_id;
            $groupConversion =
                "<section class='maintenance-step'>" .
                '<h3>Omzetten naar eigen groep</h3>' .
                '<p>Deze verhuring komt op naam van de gekozen eigen groep. Andere verhuringen blijven ongewijzigd. Oude externe contactgegevens vervallen als ze nergens anders gebruikt worden. Oude bevestigingslinks worden ongeldig. Vooraf wordt een private herstelkopie gemaakt.</p>' .
                "<form method='post' action='item.php'>" .
                "<input type='hidden' name='i' value='{$rentalId}'>" .
                "<input type='hidden' name='a' value='sg'>" .
                "<input type='hidden' name='reservering_id' value='{$reservationId}'>" .
                "<input type='hidden' name='huurder_id' value='{$tenantId}'>" .
                '<p>' .
                "<label>Eigen groep <select name='group_id' required>{$choices}</select>" .
                '</label>' .
                '</p>' .
                "<button type='submit'>Omzetten naar eigen groep</button>" .
                '</form>' .
                '</section>';
        }
    }
    return "
  <h2>Reservering</h2>
  {$missingTenantNotice}
  {$groupConversion}
	<form method='post' action='item.php'>
	<input type='hidden' name='i' value='{$id}' />
	<input type='hidden' name='a' value='sr' />
	<input type='hidden' name='reservering_id' value='{$reservering_id}' />
	<table>
		<tr>
			<td class='descr'>Begindatum</td>
			<td>" .
        "<input type='text' name='begindatum' value='{$begindatum}' />" .
        "</td>
		</tr>
		<tr>
			<td class='descr'>Einddatum</td>
			<td>" .
        "<input type='text' name='einddatum' value='{$einddatum}' />" .
        "</td>
		</tr>
		<tr>
			<td class='descr'>Aantal personen</td>
			<td>" .
        "<input type='text' name='personen' value='{$personen}' />" .
        "</td>
		</tr>
		<tr>
			<td class='descr'>Beschrijving</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td colspan='2'>
				<textarea name='beschrijving' cols='40' rows='5'>{$beschrijving}</textarea>
			</td>
		</tr>
		<tr>
			<td class='descr'>Groep</td>
			<td>
			{$groupField}			
			</td>
		</tr>
		<tr>
			<td class='descr'>&nbsp;</td>
			<td>
				<input type='submit' value='Wijzig' />
			</td>
		</tr>
	</table>
	</form>";
} ?>
