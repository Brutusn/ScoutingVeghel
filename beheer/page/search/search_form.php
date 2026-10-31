<?php
require_once __DIR__ . '/../../lib/document.php';
require_once __DIR__ . '/../../security.php';
adminRequireLogin();

require_once __DIR__ . '/../../../php/database.php';

$database = siteDatabaseConnection();
?>
<?php echo beheerDocumentStart('Zoek-formulier', '', ''); ?>
<h2>Zoekfilters</h2>
		<ul id="zoekformulier">
			<form method="post" action="results.php" target="results">
				<table>
					<tr>
						<td>Kenmerk (getal):</td>
						<td><input type="text" name="id" class="small" /></td>
					</tr>
					<tr>
						<td>Groep:</td>
						<td><input type="text" name="groep" class="small" /></td>
					</tr>
					<tr>
						<td>Op naam van:</td>
						<td>
							<select name="onv" class="small">
								<?php
        global $database;
        $q = 'SELECT `id`, `naam` FROM `verhuur_huurder` ORDER BY `naam`';
        $r = $database->query($q);
        echo '<option value="-1">-- Selecteer een naam --</option>' . "\n";
        foreach ($r as $rw) {
            echo '<option value="' . $rw['id'] . '">' . $rw['naam'] . '</option>' . "\n";
        }
        ?>
							</select>
						</td>
					</tr>				
					<tr>
						<td>&nbsp;</td>
						<td><input type="submit" value="zoek!" class="small" /></td>
					</tr>
				</table>
			</form>
		</ul>
	</body>
</html>