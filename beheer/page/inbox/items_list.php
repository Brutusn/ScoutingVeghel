<?php
require_once __DIR__ . '/../../lib/document.php';
require_once __DIR__ . '/../../security.php';
adminRequireLogin();

require_once __DIR__ . '/../../../php/database.php';
require_once __DIR__ . '/../../constants.php';
?>
<?php echo beheerDocumentStart(
    'Inbox item listing',
    '',
    '',
    ['/beheer/css/inbox-items_list.css'],
    []
); ?>
		<?php
  global $DATETIME_FORMAT;
  $database = siteDatabaseConnection();
  $status_id = isset($_GET['s']) && is_numeric($_GET['s']) ? $_GET['s'] : 0;
  $q = 'SELECT `status` FROM `verhuur_status` WHERE `status_id`=' . $status_id . ' LIMIT 1';
  $r = $database->query($q);
  $rw = $r->fetch_assoc();
  echo '<h2>' . $rw['status'] . '</h2>';
  echo '<p>Reserveringen op naam van "scouting veghel" zijn hier niet zichtbaar. Gebruik daarvoor de zoek-functie.</p>';
  $q =
      "SELECT 
				`verhuur_verhuring`.`id`,
				`verhuur_huurder`.`naam` 'naam',
				`verhuur_huurder`.`contactpersoon` 'contactpersoon',
				`verhuur_reservering`.`beschrijving` 'beschrijving',								
				DATE_FORMAT(`verhuur_verhuring`.`datum`, '" .
      $DATETIME_FORMAT .
      "') 'DatumVanReservering'
				FROM `verhuur_verhuring`
				LEFT JOIN `verhuur_huurder`
				ON `verhuur_verhuring`.`huurder_id` = `verhuur_huurder`.`id`
				LEFT JOIN `verhuur_reservering`
				ON `verhuur_reservering`.`id`= `verhuur_verhuring`.`reservering_id`
				WHERE `verhuur_reservering`.`status_id`=" .
      $status_id .
      " 
				AND `verhuur_huurder`.`naam` <> 'Scouting Veghel'
				ORDER BY `verhuur_verhuring`.`datum` DESC";
  $r = $database->query($q);
  if ($r->num_rows > 0) {
      echo '<ul id="items_listing">';
      foreach ($r as $rw) {
          echo '<li>
						  <a href="item.php?i=' .
              $rw['id'] .
              '" target="item">
							<span class="huurder">' .
              $rw['naam'] .
              '</span>
							<span class="beschrijving">Op naam van:' .
              substr($rw['contactpersoon'], 0, 60) .
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