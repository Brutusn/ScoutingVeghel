<?php
require_once __DIR__ . '/../../lib/document.php';
require_once __DIR__ . '/../../security.php';
adminRequireLogin();
require_once __DIR__ . '/../../../php/privacy/service.php';
if (privacyEnabled()) {
    require __DIR__ . '/../../privacy-add.php';
    exit();
}

require_once __DIR__ . '/../../../php/database.php';
require_once __DIR__ . '/../../../php/rental-settings.php';
require_once __DIR__ . '/../../lib/functions.php';

$database = siteDatabaseConnection();
require_once __DIR__ . '/../../groups.php';
?>
<?php echo beheerDocumentStart(
    'Voeg verhuring toe',
    '',
    '',
    ['/beheer/css/toevoegen-verhuring.css'],
    ['/beheer/rental-form.js']
); ?>
		<h2>Toevoegen van een verhuring</h2>
		<form method="post" name="verhuring_toevoegen" action="verhuring_toevoegen.php">
		<h3>Selecteer een huurder:</h3>
		<table style="width: 300px;">
			<tr>
		<?php
  $q = 'SELECT `naam`, `id` FROM `verhuur_huurder` ORDER BY `naam` ASC';
  $r = $database->query($q);
  echo '<td class="first">Naam gebruiker:</td><td><select name="huurder">';
  echo '<option value="0">Selecteer huurder</option>';
  foreach ($r as $rw) {
      echo '<option value="' .
          $rw['id'] .
          '">' .
          htmlspecialchars($rw['naam'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') .
          '</option>';
  }
  echo '</select></td></tr>';
  ?>
		
		<tr><td class="first">Op naam van groep:</td>
			<td>
				<select name="groep" aria-label="Op naam van eigen groep">
                    <?php echo adminOwnGroupOptions($database); ?>
                </select>		
			</td>
		</tr>
		</table>

		<h3>Voer begindatum/einddatum in:</h3>
		<?php if (isset($_GET['p']) && $_GET['p'] == 'repeterend') {
      include __DIR__ . '/verhuring_repeterend.php';
  } else {
      include __DIR__ . '/verhuring_eenmalig.php';
  } ?>		
		<h3>Aantal personen/beschrijving:</h3>
		<table style="width:300px">
			<tr><td>Aantal personen:</td>
				<td>
					<?php echo display_select('personen', MIN_AANTAL_PERSONEN, MAX_AANTAL_PERSONEN, 1); ?><br />
				</td>
			</tr>
			<tr>
				<td>beschrijving:</td>
				<td><textarea name="beschrijving"></textarea></td>
			</tr>
		</table>
				
		<h3>Overige instellingen:</h3>
		<table>
		<?php
  $q =
      'SELECT `status`,`status_id` FROM `verhuur_status` WHERE `status_id` < 21 ORDER BY `status_id` ASC';
  $r = $database->query($q);
  echo '<tr><td class="first">Status:</td><td><select name="status">';
  foreach ($r as $rw) {
      echo '<option value="' .
          $rw['status_id'] .
          '"' .
          ($rw['status'] == 'Goedgekeurd' ? 'selected="selected"' : '') .
          '>' .
          $rw['status'] .
          '</option>';
  }
  echo '</select></td></tr>';
  ?>
		</table>
		<input style="width: 150px;" type="submit" value="Voeg verhuring toe" />
		</form>
	</body>
</html>