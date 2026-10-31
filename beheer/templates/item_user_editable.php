<?php
function item_user_editable(
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
<h2>Huurder</h2>
	<form method='post' action='item.php'>
	<input type='hidden' name='i' value='{$id}' />
	<input type='hidden' name='a' value='su' />
	<input type='hidden' name='reservering_id' value='{$reservering_id}' />
	<input type='hidden' name='huurder_id' value='{$huurder_id}' />
	<table>
		<tr>
			<td class='descr'>Naam</td>
			<td>" .
        "<input type='text' name='naam' value='{$naam}' />" .
        "</td>
		</tr>
		<tr>
			<td class='descr'>Contactpersoon</td>
			<td>" .
        "<input type='text' name='contactpersoon' value='{$contactpersoon}' />" .
        "</td>
		</tr>
		<tr>
			<td class='descr'>Adres</td>
			<td>" .
        "<input type='text' name='adres' value='{$adres}' />" .
        "</td>
		</tr>
		<tr>
			<td class='descr'>Postcode</td>
			<td>" .
        "<input type='text' name='postcode' value='{$postcode}' />" .
        "</td>
		</tr>
		<tr>
			<td class='descr'>Plaats</td>
			<td>" .
        "<input type='text' name='plaats' value='{$plaats}' />" .
        "</td>
		</tr>		
		<tr>
			<td class='descr'>E-mail</td>
			<td>" .
        "<input type='text' name='email' value='{$email}' />" .
        "</td>
		</tr>
		<tr>
			<td class='descr'>Telefoon</td>
			<td>" .
        "<input type='text' name='telefoon' value='{$telefoon}' />" .
        "</td>
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
