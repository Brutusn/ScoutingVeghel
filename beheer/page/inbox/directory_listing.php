<?php
require_once __DIR__ . '/../../lib/document.php';
require_once __DIR__ . '/../../security.php';
adminRequireLogin();

require_once __DIR__ . '/../../../php/database.php';
?>
<?php echo beheerDocumentStart('Inbox directory listing', '', ''); ?>
<h2>Filter op status</h2>
		<?php
  $q =
      'SELECT `verhuur_status`.`status` , `verhuur_status`.`status_id`, COUNT( `verhuur_reservering`.`id` ) \'sum\' ' .
      ' FROM `verhuur_status` ' .
      ' LEFT JOIN `verhuur_reservering` ON `verhuur_status`.`status_id` = `verhuur_reservering`.`status_id` ' .
      ' WHERE `verhuur_status`.`status_id` < 21 ' .
      ' GROUP BY `verhuur_status`.`status` ' .
      ' ORDER BY `verhuur_status`.`status_id` LIMIT 0, 30 ';
  $database = siteDatabaseConnection();
  $r = $database->query($q);
  echo '<table id="directory_listing">';
  foreach ($r as $rw) {
      echo '<tr><td><a href="items_list.php?s=' .
          $rw['status_id'] .
          '" target="items">' .
          $rw['status'] .
          '</a></td><td class="count">' .
          $rw['sum'] .
          '</td></tr>';
  }
  echo '</table>';
  ?>			
	</body>
</html>