<?php
require_once __DIR__ . '/../../lib/document.php';
require_once __DIR__ . '/../../security.php';
adminRequireLogin();

require_once __DIR__ . '/../../../php/database.php';
require_once __DIR__ . '/../../constants.php';
require_once __DIR__ . '/../../lib/functions.php';

$database = siteDatabaseConnection();
?>
<?php echo beheerDocumentStart('Audit trail', '', ''); ?>
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
  if (isset($_GET['id']) && is_numeric($_GET['id'])) {
      echo loadOptions($_GET['id']);
      if ($_GET['id'] == 0) {
          die('Selecteer aub eerst een item');
      }
      $q =
          "SELECT DATE_FORMAT(`datum`,'" .
          $DATETIME_FORMAT .
          "') `datum`, `actie`, `naam`, `query`
					FROM `verhuur_mutaties` WHERE `hoort_bij`=" .
          $_GET['id'] .
          ' ORDER BY `id` DESC';
      $r = $database->query($q);
      if (!$r->num_rows) {
          echo '<p>Er zijn nog geen auditregels voor deze verhuring.</p>';
      } else {
          echo '<table><tr><th>Datum</th><th>Actie</th><th>Door</th><th>Details</th></tr>';
          foreach ($r as $rw) {
              $details = json_decode((string) $rw['query'], true);
              $summary =
                  is_array($details) && isset($details['summary']) && is_string($details['summary'])
                      ? $details['summary']
                      : 'Historische wijziging; details niet beschikbaar.';
              $escape = function ($value) {
                  return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
              };
              echo '<tr><td>' .
                  $escape($rw['datum']) .
                  '</td><td>' .
                  $escape($rw['actie']) .
                  '</td><td>' .
                  $escape($rw['naam']) .
                  '</td><td>' .
                  $escape($summary) .
                  '</td></tr>';
          }
          echo '</table>';
      }
  } else {
      echo '<p class="error"><span>[foutmelding]</span> Id heeft geen waarde.</p>';
  }
  ?>	
	</body>
</html>