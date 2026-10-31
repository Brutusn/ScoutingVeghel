<?php
require_once __DIR__ . '/../../security.php';
adminRequireLogin();
?>
	
	<table style="width: 300px;">
		<tr><th class="first"></th>
			<th>Dag</th>
			<th>Maand</th>
			<th>Jaar</th>
		</tr>
		<tr>
			<td class="first">Van</td>
			<td>
				<?php echo display_select('VAN_DAG', 1, 31, 1); ?>		
			</td>
			<td>
				<?php echo display_select('VAN_MAAND', 1, 12, 1); ?>	
			</td>
			<td>
				<?php echo display_select('VAN_JAAR', date('Y'), date('Y') + 2, 1); ?>	
			</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td class="first">Tot</td>
			<td>
				<?php echo display_select('TOT_DAG', 1, 31, 1); ?>		
			</td>
			<td>
				<?php echo display_select('TOT_MAAND', 1, 12, 1); ?>	
			</td>
			<td>
				<?php echo display_select('TOT_JAAR', date('Y'), date('Y') + 2, 1); ?>	
			</td>
		</tr>
	</table>

	<h3>Tijden</h3>
	<table style="width:300px;">
	<tr>
		<td class="first">
			Begintijd
		</td>
		<td>
			<?php echo display_select('VAN_UUR', 4, 23, 1); ?>	
		</td>
		<td>
			<?php echo display_select('VAN_MIN', 0, 45, 15); ?>	
		</td>
	</tr>
	<tr>
		<td class="first">
			Eindtijd
		</td>
		<td>
			<?php echo display_select('TOT_UUR', 4, 23, 1); ?>	
		</td>
		<td>
			<?php echo display_select('TOT_MIN', 0, 45, 15); ?>	
		</td>
	</tr>
	</table>

	<h3>Periode:</h3>
	<p>Zorg ervoor dat de begindatum de juiste 'weekdag' aangeeft, de einddatum wordt daarop intern aangepast. Maximaal aantal evenementen dat zo ineens kan worden toegevoegd is 50.</p>
	<table style="width:300px;">
	<tr>
		<th>Weekdag</th><th>periode</th>
	</tr>
	<tr>
		<td>
			<select name="repeterend_dag">
				<option value="Mon">Maandag</option>
				<option value="Tue">Dinsdag</option>
				<option value="Wed">Woensdag</option>
				<option value="Thu">Donderdag</option>
				<option value="Fri">Vrijdag</option>
				<option value="Sat">Zaterdag</option>
				<option value="Sun">Zondag</option>
			</select>
		</td>		
		<td>
			<select name="repeterend_periode">
				<option value="w">wekelijks</option>
				<option value="t">twee-wekelijks</option>
				<option value="m">maandelijks</option>
			</select>
		</td>
	</tr>
	</table>