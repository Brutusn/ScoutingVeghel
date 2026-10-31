<?php
function item_status_editable(
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
    return "
<h2>Gegevens</h2>
	<form method='post' action='item.php'>
	<input type='hidden' name='i' value='{$id}' />
	<input type='hidden' name='a' value='ss' />
	<input type='hidden' name='reservering_id' value='{$reservering_id}' />
	<table>
		<tr>
			<td class='descr'>Status:</td>
			<td>{$status}</td>
		</tr>
		<tr>		
			<td>" .
        "</td>
			<td>" .
        "<input type='submit' value='Wijzigen' />" .
        "</td>
		</tr>
	</table>
	</form>";
} ?>
