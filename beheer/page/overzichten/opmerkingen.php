<?php
require_once __DIR__ . '/../../lib/document.php';
require_once __DIR__ . '/../../security.php';
adminRequireLogin();
require_once __DIR__ . '/../../../php/privacy/service.php';

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
  if (privacyEnabled() && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
      try {
          privacyAddNote(
              $database,
              $_POST['id'] ?? '',
              $_POST['bericht'] ?? '',
              (string) ($_SESSION['ADMIN_NAME'] ?? 'beheerder')
          );
      } catch (InvalidArgumentException $e) {
          http_response_code(400);
          exit(htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
      }
  } else {
      if (isset($_POST['bericht'], $_POST['id']) && $_POST['bericht'] != '') {
          $schrijver = init($_SESSION['ADMIN_NAME'], 'NYI');
          $datum = date('Y-m-d H:i:s');
          my_var_post();
          global $_V;
          $query =
              "INSERT INTO `verhuur_notes` 
				 SET `hoort_bij` = " .
              $_V['POST']['id'] .
              ",
					`opmerking` = '" .
              $_V['POST']['bericht'] .
              "',
					`schrijver` = '" .
              $schrijver .
              "',
					`datum` = '" .
              $datum .
              "'";
          $database->query($query);
      }
  }
  if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
      echo loadOptions($_REQUEST['id']);
      if ($_REQUEST['id'] == 0) {
          die('Selecteer aub eerst een item');
      }
      echo '<form method="post" action="opmerkingen.php">';
      echo '<input type="hidden" name="id" value="' . $_REQUEST['id'] . '" />';
      echo '<table>';
      echo '<tr><td>Bericht</td></tr>';
      echo '<tr><td><textarea name="bericht" cols="60" rows="5"></textarea></td></tr>';
      echo '</table>';
      echo '<input type="submit" value="Voeg toe" />';
      echo '</form>';
      echo '<hr />';
      $q =
          "SELECT DATE_FORMAT(`datum`,'" .
          $DATETIME_FORMAT .
          "') `datum`, `schrijver`, `opmerking`
					FROM `verhuur_notes` WHERE `hoort_bij`=" .
          $_REQUEST['id'] .
          ' ORDER BY `id` DESC';
      $r = $database->query($q);
      foreach ($r as $rw) {
          echo '<div id="note_item">';
          echo '<h6>' .
              htmlspecialchars(
                  $rw['schrijver'] . ' - ' . $rw['datum'],
                  ENT_QUOTES | ENT_SUBSTITUTE,
                  'UTF-8'
              ) .
              '</h6>';
          echo '<p>' .
              htmlspecialchars($rw['opmerking'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') .
              '</p>';
          echo '</div>';
      }
  } else {
      echo '<p class="error"><span>[foutmelding]</span> Id heeft geen waarde.</p>';
  }
  ?>	
	</body>
</html>