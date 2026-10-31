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
  function loadOptions($id) {
      global $DATETIME_FORMAT;
      global $database;
      return '<ul id="item_actions">
					<li><a href="../inbox/item.php?i=' .
          $id .
          '">Overzicht</a></li>
					<li><a href="../inbox/item.php?i=' .
          $id .
          '&amp;a=eu">Gegevens wijzigen</a></li>
					<li><a href="../inbox/item.php?i=' .
          $id .
          '&amp;a=er">Reservering</a></li>
					<li><a href="../inbox/item.php?i=' .
          $id .
          '&amp;a=es">Status</a></li>
					<li><a href="../overzichten/conflicten.php?id=' .
          $id .
          '">Conflicten</a></li>
					<li><a href="../overzichten/opmerkingen.php?id=' .
          $id .
          '">Opmerkingen</a></li>
					<li><a href="../overzichten/mutaties.php?id=' .
          $id .
          '">Audit trail</a></li>
				</ul>';
  }
  if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
      echo loadOptions($_REQUEST['id']);
      if ($_REQUEST['id'] == 0) {
          die('Selecteer aub eerst een item');
      }
      $q1 =
          "
					SELECT 
						`verhuur_reservering`.`begindatum`,
						`verhuur_reservering`.`einddatum`
					FROM `verhuur_verhuring`
					LEFT JOIN `verhuur_reservering`
					ON `verhuur_reservering`.`id` = `verhuur_verhuring`.`reservering_id`
					WHERE `verhuur_verhuring`.`id`=" .
          $_REQUEST['id'] .
          ' LIMIT 0,1';
      $r1 = $database->query($q1);
      if ($r1->num_rows == 1) {
          $rw = $r1->fetch_assoc();
          $start = $rw['begindatum'];
          $end = $rw['einddatum'];
          echo '<table style="width: 100%"><tr><th>Naam:</th><th>Op naam van:</th><th>Gereserveerd op:</th><th>Status:</th></tr>' .
              "\n";
          $q =
              "SELECT 
							`verhuur_verhuring`.`id`,
							`verhuur_huurder`.`naam`, 
							`verhuur_verhuring`.`groep`, 
							`verhuur_status`.`status`,
							DATE_FORMAT(`verhuur_verhuring`.`datum`,'" .
              $DATETIME_FORMAT .
              "') 'datum'
						FROM `verhuur_verhuring`
						LEFT JOIN `verhuur_huurder` ON `verhuur_huurder`.`id` = `verhuur_verhuring`.`huurder_id`
						LEFT JOIN `verhuur_reservering` ON `verhuur_reservering`.`id` = `verhuur_verhuring`.`reservering_id`
						LEFT JOIN `verhuur_status` ON `verhuur_reservering`.`status_id` = `verhuur_status`.`status_id`
						WHERE (
						   (`begindatum` <= '" .
              $start .
              "' AND `einddatum` >= '" .
              $end .
              "') 
						OR (`begindatum` >= '" .
              $start .
              "' AND `einddatum` <= '" .
              $end .
              "') 
						OR (`begindatum` <= '" .
              $start .
              "' AND `einddatum` >= '" .
              $start .
              "' AND `einddatum`<='" .
              $end .
              "') 
						OR ('begindatum' >= '" .
              $start .
              "' AND `begindatum`<= '" .
              $end .
              "' AND `einddatum` >= '" .
              $end .
              "')
						)
						ORDER BY `datum` ASC";
          $r = $database->query($q);
          foreach ($r as $rw) {
              $s =
                  '<tr><td><a href="../inbox/item.php?i=' .
                  $rw['id'] .
                  '">' .
                  $rw['naam'] .
                  '</a></td>';
              $s .= '<td>' . $rw['groep'] . '</td>';
              $s .= '<td>' . $rw['datum'] . '</td>';
              $s .= '<td>' . $rw['status'] . '</td>';
              echo $s . "\n";
          }
      } else {
          echo '<p class="error"><span>[foutmelding]</span> Er is iets misgegaan bij het selecteren van het item.</p>';
      }
  } else {
      echo '<p class="error"><span>[foutmelding]</span> Id heeft geen waarde.</p>';
  }
  echo '</table>';
  ?>	
	</body>
</html>