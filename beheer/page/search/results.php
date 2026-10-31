<?php
require_once __DIR__ . '/../../lib/document.php';
require_once __DIR__ . '/../../security.php';
adminRequireLogin();

require_once __DIR__ . '/../../../php/database.php';
require_once __DIR__ . '/../../constants.php';
require_once __DIR__ . '/../../lib/functions.php';

$database = siteDatabaseConnection();
?>
<?php echo beheerDocumentStart('Inbox item listing', '', ''); ?>
		<?php
  global $DATETIME_FORMAT;
  global $database;
  $required = ['id', 'groep', 'onv'];
  if (!m_isset($required)) {
      die('Not all values set.');
  }
  foreach ($_POST as $k => $v) {
      $_POST[$k] = trim($database->real_escape_string($v));
      $_POST[$k] = $_POST[$k] != '' ? $_POST[$k] : '-1';
  } # echo '<h2>'.$rw['zoekresultaten'].'</h2>';
  $q =
      "SELECT 
				`verhuur_verhuring`.`id`,
				`verhuur_huurder`.`naam` 'naam',
				`verhuur_reservering`.`beschrijving` 'beschrijving',								
				DATE_FORMAT(`verhuur_verhuring`.`datum`, '" .
      $DATETIME_FORMAT .
      "') 'DatumVanReservering'
				FROM `verhuur_verhuring`
				LEFT JOIN `verhuur_huurder`
				ON `verhuur_verhuring`.`huurder_id` = `verhuur_huurder`.`id`
				LEFT JOIN `verhuur_reservering`
				ON `verhuur_reservering`.`id`= `verhuur_verhuring`.`reservering_id`
				WHERE `verhuur_reservering`.`status_id` < 20 " .
      ($_POST['id'] >= 0 ? 'AND `verhuur_verhuring`.`id`=' . $_POST['id'] : '') .
      ($_POST['onv'] >= 0 ? " AND `verhuur_huurder`.`id`='" . $_POST['onv'] . "'" : '') .
      ($_POST['groep'] != '-1'
          ? " AND `verhuur_verhuring`.`groep`='" . $_POST['groep'] . "'"
          : '') .
      ' ORDER BY `verhuur_verhuring`.`datum` DESC';
  $r = $database->query($q);
  if ($r->num_rows > 0) {
      echo '<ul id="items_listing">';
      foreach ($r as $rw) {
          echo '<li>
						  <a href="../inbox/item.php?i=' .
              $rw['id'] .
              '" target="item">
							<span class="huurder">' .
              $rw['naam'] .
              '</span>
							<span class="beschrijving">' .
              substr($rw['beschrijving'], 0, 60) .
              '</span>
							<span class="datum">' .
              $rw['DatumVanReservering'] .
              '</span>
						  </a>						
						  </li>';
      }
      echo '</ul>';
  } else {
      echo 'Geen resultaten gevonden';
  }
  ?>	
	</body>
</html>