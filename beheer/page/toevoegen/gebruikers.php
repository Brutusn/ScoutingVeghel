<?php
require_once __DIR__ . '/../../lib/document.php';
require_once __DIR__ . '/../../security.php';
adminRequireLogin();
require_once __DIR__ . '/../../../php/privacy/service.php';
if (privacyEnabled()) {
    http_response_code(410);
    exit(
        'Externe huurders worden voortaan bij een verhuring aangemaakt. Eigen groepen beheert u via Eigen groepen.'
    );
}

require_once __DIR__ . '/../../../php/database.php';
require_once __DIR__ . '/../../lib/functions.php';

$database = siteDatabaseConnection();
?>
<?php echo beheerDocumentStart(
    'Gebruikers-lijst',
    '',
    '',
    ['/beheer/css/toevoegen-gebruikers.css'],
    []
); ?>
		<?php
  if (isset($_REQUEST['a']) && $database->real_escape_string($_REQUEST['a']) == 'add') {
      echo '<h2>Huurder toevoegen</h2>
					<form method="post" action="gebruikers.php">					
					<table>
						<tr>
							<td class="descr">Naam</td>
							<td>' .
          '<input type="text" name="naam" />' .
          '</td>
						</tr>
						<tr>
							<td class="descr">Contactpersoon</td>
							<td>' .
          '<input type="text" name="contactpersoon" />' .
          '</td>
						</tr>
						<tr>
							<td class="descr">Adres</td>
							<td>' .
          '<input type="text" name="adres" />' .
          '</td>
						</tr>
						<tr>
							<td class="descr">Postcode</td>
							<td>' .
          '<input type="text" name="postcode" />' .
          '</td>
						</tr>
						<tr>
							<td class="descr">Plaats</td>
							<td>' .
          '<input type="text" name="plaats" />' .
          '</td>
						</tr>		
						<tr>
							<td class="descr">E-mail</td>
							<td>' .
          '<input type="text" name="email" />' .
          '</td>
						</tr>
						<tr>
							<td class="descr">Telefoon</td>
							<td>' .
          '<input type="text" name="telefoon" />' .
          '</td>
						</tr>
						<tr>
							<td class="descr">&nbsp;</td>
							<td>
								<input type="submit" value="Opslaan" />
							</td>
						</tr>
					</table>
					</form>';
  }
  $variables_of_form = [
      'naam',
      'contactpersoon',
      'adres',
      'postcode',
      'plaats',
      'email',
      'telefoon'
  ];
  if (m_isset($variables_of_form)) {
      //voeg nieuwe gebruiker toe. :-D
      foreach ($variables_of_form as $k) {
          $_POST[$k] = $database->real_escape_string($_POST[$k]);
      }
      $t =
          'INSERT INTO `verhuur_huurder` (`naam`, `contactpersoon`,`adres`, `postcode`, `plaats`, `email`, `telefoon`) VALUES(';
      $t .= "'" . $_POST['naam'] . "',";
      $t .= "'" . $_POST['contactpersoon'] . "',";
      $t .= "'" . $_POST['adres'] . "',";
      $t .= "'" . $_POST['postcode'] . "',";
      $t .= "'" . $_POST['plaats'] . "',";
      $t .= "'" . $_POST['email'] . "',";
      $t .= "'" . $_POST['telefoon'] . "'";
      $t .= ')';
      $r = $database->query($t);
  }
  ?>
			<?php if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
       $q = 'SELECT * FROM `verhuur_huurder` ORDER BY `naam` ASC';
       $r = $database->query($q);
       echo '<h2>Lijst van huurders</h2>';
       foreach ($r as $rw) {
           echo '<table class="person_item">';
           echo '<tr><td><span class="name">' . $rw['naam'] . '</td></tr>';
           echo '<tr><td><span class="mail">contact: ' .
               $rw['contactpersoon'] .
               '</span></td></tr>';
           echo '<tr><td><span class="mail">mail: ' . $rw['email'] . '</span></td></tr>';
           echo '<tr><td><span class="mail">tel.: ' . $rw['telefoon'] . '</span></td></tr>';
           echo '</table>';
       }
   } else {
       $q = 'SELECT * FROM `verhuur_huurder` WHERE `id`=' . $_GET['id'] . ' ORDER BY `naam` ASC';
       $r = $database->query($q);
       echo '<h2>Huurder</h2>';
       foreach ($r as $rw) {
           echo '<table class="person_item">';
           echo '<tr><td>Naam:</td><td><span class="mail">' . $rw['naam'] . '</td></tr>';
           echo '<tr><td>Contact:</td><td><span class="mail">' .
               $rw['contactpersoon'] .
               '</span></td></tr>';
           echo '<tr><td>Telefoon:</td><td><span class="mail">' .
               $rw['telefoon'] .
               '</span></td></tr>';
           echo '<tr><td>E-mail:</td><td><span class="mail">' . $rw['email'] . '</span></td></tr>';
           echo '<tr><td>Adres:</td><td><span class="mail">' . $rw['adres'] . '</span></td></tr>';
           echo '<tr><td>PC/Plaats:</td><td><span class="mail">' .
               $rw['postcode'] .
               ' ' .
               $rw['plaats'] .
               '</span></td></tr>';
           echo '</table>';
           echo '<hr />';
       }
   } ?>	
	</body>
</html>