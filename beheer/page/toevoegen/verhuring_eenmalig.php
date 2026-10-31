<?php
require_once __DIR__ . '/../../security.php';
adminRequireLogin();
?>
	<table style="width: 300px;">
		<tr><th class="first"></th>
			<th>Dag</th>
			<th>Maand</th>
			<th>Jaar</th>
			<th>&nbsp;</th>
			<th>Uur</th>
			<th>Min</th>
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
			<td>
				<?php echo display_select('VAN_UUR', 0, 23, 1); ?>	
			</td>
			<td>
				<?php echo display_select('VAN_MIN', 0, 45, 15); ?>	
			</td>
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
			<td>&nbsp;</td>
			<td>
				<?php echo display_select('TOT_UUR', 0, 23, 1); ?>	
			</td>
			<td>
				<?php echo display_select('TOT_MIN', 0, 45, 15); ?>	
			</td>
		</tr>
	</table>