<?php
function item_uneditable(
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
    $tenantSection = "<h2>Huurder</h2>
	<table>
		<tr>
			<td class='descr'>Huurder-ID</td>
			<td>{$huurder_id}</td>
		</tr>
		<tr>
			<td class='descr'>Naam</td>
			<td>{$naam}</td>
		</tr>
		<tr>
			<td class='descr'>Contactpersoon</td>
			<td>{$contactpersoon}</td>
		</tr>
		<tr>
			<td class='descr'>Adres</td>
			<td>{$adres}</td>
		</tr>
		<tr>
			<td class='descr'>PC/plaats</td>
			<td>{$postcode} {$plaats}</td>
		</tr>
		<tr>
			<td class='descr'>E-mail</td>
			<td>{$email}</td>
		</tr>
		<tr>
			<td class='descr'>Telefoon</td>
			<td>{$telefoon}</td>
		</tr>
	</table>";
    if ($naam === null) {
        $tenantSection =
            '<h2>Huurder ontbreekt</h2>' .
            "<p role='alert'>Het oorspronkelijke huurderrecord is niet meer aanwezig. Via Reservering wijzigen kun je deze verhuring expliciet aan een bestaande eigen groep koppelen.</p>";
    }
    return "
{$tenantSection}
<h2>Reservering</h2>
	<table>
		<tr>
			<td class='descr'>Reservering-ID</td>
			<td>{$reservering_id}</td>
		</tr>
		<tr>
			<td class='descr'>Begindatum</td>
			<td>{$display_begindatum}</td>
		</tr>
		<tr>
			<td class='descr'>Einddatum</td>
			<td>{$display_einddatum}</td>
		</tr>
		<tr>
			<td class='descr'>Aantal personen</td>
			<td>{$personen}</td>
		</tr>
		<tr>
			<td class='descr'>Borg</td>
			<td>" .
        "</td>
		</tr>
		<tr>
			<td class='descr'>Beschrijving</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td>
				{$beschrijving}
			</td>
		</tr>
		<tr>
			<td class='descr'>Groep</td>
			<td>{$groep}</td>
		</tr>
	</table>			
<h2>Gegevens</h2>
	<table>
		<tr>
			<td class='descr'>Verhuring-ID</td>
			<td>{$id}</td>
		</tr>
		<tr>
			<td class='descr'>Gereserveerd op:</td>
			<td>{$display_datum}</td>
		</tr>
		<tr>
			<td class='descr'>Status:</td>
			<td>{$status}</td>
		</tr>		
	</table>
<h2>Documenten</h2>
	<ul>
		<li>" .
        "<a href='http://www.scoutingveghel.nl/php/huurovereenkomst.php?key={$confirm}' target='_blank'>Huurovereenkomst</a>" .
        "</li>
		<li>" .
        "<a href='http://www.scoutingveghel.nl/docs/huurvoorwaarden.pdf' target='_blank'>Huurvoorwaarden</a>" .
        "</li>
	</ul>";
} ?>
