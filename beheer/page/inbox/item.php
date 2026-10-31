<?php
require_once __DIR__ . '/../../lib/document.php';
// Enter the shell before starting a session on links opened directly from webmail.
// SameSite=Strict cookies may be omitted on that first cross-site request.
$directId = $_GET['i'] ?? '';
if (
    ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' &&
    ($_SERVER['HTTP_SEC_FETCH_DEST'] ?? '') === 'document' &&
    in_array($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '', ['cross-site', 'same-site'], true) &&
    is_string($directId) &&
    preg_match('/^[1-9][0-9]{0,9}$/D', $directId)
) {
    header('Location: /beheer/index.html#reserveringen?i=' . $directId, true, 302);
    exit();
}
require_once __DIR__ . '/../../security.php';
// Old mail links also enter through the normal shell and keep their destination.
$linkId = $_GET['i'] ?? '';
if (
    ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' &&
    is_string($linkId) &&
    preg_match('/^[1-9][0-9]{0,9}$/D', $linkId) &&
    !adminIsLoggedIn()
) {
    header('Location: /beheer/index.html#reserveringen?i=' . $linkId, true, 302);
    exit();
}
adminRequireLogin();
require_once __DIR__ . '/../../../php/privacy/service.php';

require_once __DIR__ . '/../../../php/database.php';
require_once __DIR__ . '/../../lib/functions.php';

require_once __DIR__ . '/../../templates/includes.php';
require_once __DIR__ . '/../../constants.php';

if (!isset($database)) {
    $database = siteDatabaseConnection();
}

function writeAuditTrail($database, $rentalId, $action, $summary) {
    $details = json_encode(
        ['summary' => $summary],
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );
    if ($details === false) {
        throw new RuntimeException('Auditdetails konden niet worden gecodeerd.');
    }
    $actor = (string) ($_SESSION['ADMIN_NAME'] ?? 'beheerder');
    $statement = $database->prepare(
        'INSERT INTO verhuur_mutaties (hoort_bij, naam, actie, datum, `query`) VALUES (?, ?, ?, NOW(), ?)'
    );
    $statement->bind_param('isss', $rentalId, $actor, $action, $details);
    $statement->execute();
    $statement->close();
}

function writeAuditTrailForTenant($database, $tenantId, $action, $summary) {
    $result = $database->query(
        'SELECT id FROM verhuur_verhuring WHERE huurder_id=' . (int) $tenantId
    );
    while ($row = $result->fetch_assoc()) {
        writeAuditTrail($database, (int) $row['id'], $action, $summary);
    }
}

function writeAuditTrailForReservation($database, $reservationId, $action, $summary) {
    $result = $database->query(
        'SELECT id FROM verhuur_verhuring WHERE reservering_id=' . (int) $reservationId
    );
    while ($row = $result->fetch_assoc()) {
        writeAuditTrail($database, (int) $row['id'], $action, $summary);
    }
}
?>
<?php echo beheerDocumentStart('Inbox item', '', ''); ?>
		<?php
  function loadOptions($id) {
      if ((int) $id <= 0) {
          return '';
      }
      $q = 'SELECT `id` FROM `verhuur_notes` WHERE `hoort_bij`=' . $id;
      global $database;
      $r = $database->query($q);
      if ($r->num_rows > 0) {
          $notes_available = true;
      } else {
          $notes_available = false;
      }
      return '<ul id="item_actions">
					<li><a href="?i=' .
          $id .
          '"><span>Overzicht</span></a></li>
					<li><a href="?i=' .
          $id .
          '&amp;a=eu"><span>Huurder wijzigen</span></a></li>
					<li><a href="?i=' .
          $id .
          '&amp;a=er"><span>Reservering wijzigen</span></a></li>
					<li><a href="?i=' .
          $id .
          '&amp;a=es"><span>Status wijzigen</span></a></li>
					<li><a href="../overzichten/conflicten.php?id=' .
          $id .
          '"><span>Conflicten</span></a></li>
					<li' .
          ($notes_available ? ' style="background-color: #f00;"' : '') .
          '><a href="../overzichten/opmerkingen.php?id=' .
          $id .
          '"><span>Opmerkingen</span></a></li>
					<li><a href="../overzichten/mutaties.php?id=' .
          $id .
          '"><span>Audit trail</span></a></li>
				</ul>';
  }
  function getItemInformation($id) {
      global $database;
      global $DATETIME_FORMAT;
      $q =
          "SELECT
						`verhuur_verhuring`.`huurder_id` 'huurder_id',
						`verhuur_huurder`.`contactpersoon` 'contactpersoon',
						`verhuur_huurder`.`naam` 'naam',
						`verhuur_huurder`.`adres` 'adres',
						`verhuur_huurder`.`postcode` 'postcode',
						`verhuur_huurder`.`plaats` 'plaats',
						`verhuur_huurder`.`email` 'email',
						`verhuur_huurder`.`telefoon` 'telefoon',
						`verhuur_reservering`.`id` 'reservering_id',
						`verhuur_reservering`.`begindatum` 'begindatum',
						`verhuur_reservering`.`einddatum` 'einddatum',
						 DATE_FORMAT(`verhuur_reservering`.`begindatum`,'" .
          $DATETIME_FORMAT .
          "') 'display_begindatum',
						 DATE_FORMAT(`verhuur_reservering`.`einddatum`,'" .
          $DATETIME_FORMAT .
          "') 'display_einddatum',
						`verhuur_reservering`.`personen` 'personen',
						`verhuur_reservering`.`beschrijving` 'beschrijving',
						`verhuur_verhuring`.`id` 'id',
						`verhuur_verhuring`.`datum` 'datum',
						DATE_FORMAT(`verhuur_verhuring`.`datum`,'" .
          $DATETIME_FORMAT .
          "') 'display_datum',
						`verhuur_verhuring`.`groep` 'groep',
						`verhuur_status`.`status` 'status',
                        `verhuur_verhuring`.`confirm` 'confirm'
					FROM  `verhuur_verhuring`
					LEFT JOIN `verhuur_reservering`
					ON `verhuur_verhuring`.`reservering_id`=`verhuur_reservering`.`id`
					LEFT JOIN `verhuur_huurder`
					ON `verhuur_verhuring`.`huurder_id`=`verhuur_huurder`.`id`
					LEFT JOIN `verhuur_status`
					ON 	`verhuur_reservering`.`status_id` = `verhuur_status`.`status_id`
					WHERE `verhuur_verhuring`.`id`=" .
          $id .
          "
					LIMIT 0,1";
      $r = $database->query($q);
      return $r;
  }
  function displayItem($id) {
      if ($id == 0) {
          return '<section class="empty-state"><h2>Selecteer een reservering</h2><p>Kies een aanvraag uit de lijst om de gegevens te bekijken of te wijzigen.</p></section>';
      }
      $r = getItemInformation($id);
      return item_uneditable(...$r->fetch_assoc());
  }
  function displayEditUser($id) {
      if ($id == 0) {
          return '<section class="empty-state"><h2>Selecteer een reservering</h2><p>Kies een aanvraag uit de lijst om de gegevens te bekijken of te wijzigen.</p></section>';
      }
      $r = getItemInformation($id);
      $row = $r->fetch_assoc();
      if (
          privacyEnabled() &&
          $row &&
          !privacyQuery($GLOBALS['database'], 'SELECT id FROM verhuur_huurder WHERE id=?', 'i', [
              (int) $row['huurder_id']
          ])
      ) {
          return '<p role="alert">Het oorspronkelijke huurderrecord ontbreekt. Kies Reservering wijzigen om deze verhuring aan een bestaande eigen groep te koppelen.</p>';
      }
      if (
          privacyEnabled() &&
          (!$row || privacyProtected($GLOBALS['database'], (int) $row['huurder_id']))
      ) {
          return '<p>Deze huurder is beschermd. Beheer eigen groepen via Eigen groepen.</p>';
      }
      return item_user_editable(...$row);
  }
  function saveEditedUser() {
      global $database;
      if (privacyEnabled()) {
          try {
              privacyEdit($database, 'contact', $_POST);
              return '<h3>Wijziging opgeslagen</h3>';
          } catch (InvalidArgumentException $e) {
              return '<p role="alert">' .
                  htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') .
                  '</p>';
          } catch (Throwable $e) {
              http_response_code(500);
              return '<p>Wijziging mislukt. Controleer de privacy-preview.</p>';
          }
      }
      $required = [
          'naam',
          'contactpersoon',
          'adres',
          'postcode',
          'plaats',
          'email',
          'telefoon',
          'reservering_id',
          'huurder_id'
      ];
      if (!isset($_POST['i']) || !ctype_digit((string) $_POST['i'])) {
          return '<h3 class="failure">Wijziging niet mogelijk</h3>';
      }
      $rentalId = (int) $_POST['i'];
      $currentItem = getItemInformation($rentalId)->fetch_assoc();
      if (
          !$currentItem ||
          (int) $currentItem['huurder_id'] !== (int) ($_POST['huurder_id'] ?? 0) ||
          (int) $currentItem['reservering_id'] !== (int) ($_POST['reservering_id'] ?? 0)
      ) {
          return '<h3 class="failure">Wijziging niet mogelijk</h3>';
      }
      foreach ($_POST as $k => $v) {
          $_POST[$k] = trim($database->real_escape_string($v));
      }
      if (m_isset($required) && !m_isempty($required) && is_numeric($_POST['huurder_id'])) {
          $q =
              "
					UPDATE `verhuur_huurder`
					SET
						`naam`='" .
              $_POST['naam'] .
              "',
						`contactpersoon`='" .
              $_POST['contactpersoon'] .
              "',
						`adres`='" .
              $_POST['adres'] .
              "',
						`postcode`='" .
              $_POST['postcode'] .
              "',
						`plaats`='" .
              $_POST['plaats'] .
              "',
						`email`='" .
              $_POST['email'] .
              "',
						`telefoon`='" .
              $_POST['telefoon'] .
              "'
					WHERE `id`=" .
              $_POST['huurder_id'];
          $r = $database->query($q);
          $labels = [
              'naam' => 'Naam',
              'contactpersoon' => 'Contactpersoon',
              'adres' => 'Adres',
              'postcode' => 'Postcode',
              'plaats' => 'Plaats',
              'email' => 'E-mailadres',
              'telefoon' => 'Telefoon'
          ];
          $changed = [];
          foreach ($labels as $field => $label) {
              if ((string) ($currentItem[$field] ?? '') !== (string) $_POST[$field]) {
                  $changed[] = $label;
              }
          }
          if ($r && $changed) {
              writeAuditTrailForTenant(
                  $database,
                  (int) $_POST['huurder_id'],
                  'HUURDER_GEWIJZIGD',
                  'Aangepaste velden: ' . implode(', ', $changed)
              );
          }
          return '<h3 class="succes">Gebruiker gewijzigd</h3>';
      } else {
          return '<h3 class="failure">Wijziging niet mogelijk</h3>';
      }
  }
  function displayEditReservation($id) {
      if ($id == 0) {
          return '<section class="empty-state"><h2>Selecteer een reservering</h2><p>Kies een aanvraag uit de lijst om de gegevens te bekijken of te wijzigen.</p></section>';
      }
      $r = getItemInformation($id);
      return item_reservation_editable(...$r->fetch_assoc());
  }
  function saveEditReservation() {
      global $database;
      if (privacyEnabled()) {
          try {
              privacyEdit($database, 'reservation', $_POST);
              return '<h3>Wijziging opgeslagen</h3>';
          } catch (InvalidArgumentException $e) {
              return '<p role="alert">' .
                  htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') .
                  '</p>';
          } catch (Throwable $e) {
              http_response_code(500);
              return '<p>Wijziging mislukt. Controleer de privacy-preview.</p>';
          }
      }
      $required = ['begindatum', 'einddatum', 'personen', 'beschrijving', 'reservering_id'];
      $requestedGroup = $_POST['groep'] ?? '';
      if (
          !is_string($requestedGroup) ||
          !isset($_POST['i']) ||
          !ctype_digit((string) $_POST['i'])
      ) {
          return '<h3 class="failure">Ongeldige groepskeuze</h3>';
      }
      $currentItem = getItemInformation((int) $_POST['i'])->fetch_assoc();
      $knownGroups = adminOwnGroups($database);
      if (
          !$currentItem ||
          (int) $currentItem['reservering_id'] !== (int) ($_POST['reservering_id'] ?? 0) ||
          ($requestedGroup !== '' &&
              $requestedGroup !== (string) $currentItem['groep'] &&
              !in_array($requestedGroup, $knownGroups, true))
      ) {
          return '<h3 class="failure">Kies een bestaande eigen groep</h3>';
      }
      foreach ($_POST as $k => $v) {
          $_POST[$k] = trim($database->real_escape_string($v));
      }
      if (
          m_isset($required) &&
          !m_isempty($required) &&
          is_numeric($_POST['reservering_id']) &&
          is_numeric($_POST['personen'])
      ) {
          $reservationChanges = [];
          $labels = [
              'begindatum' => 'Begindatum',
              'einddatum' => 'Einddatum',
              'personen' => 'Aantal personen',
              'beschrijving' => 'Beschrijving'
          ];
          foreach ($labels as $field => $label) {
              if ((string) ($currentItem[$field] ?? '') !== (string) $_POST[$field]) {
                  $reservationChanges[] = $label;
              }
          }
          $groupChanged = (string) ($currentItem['groep'] ?? '') !== $requestedGroup;
          $q =
              "
					UPDATE `verhuur_reservering`
					SET
						`begindatum`='" .
              $_POST['begindatum'] .
              "',
						`einddatum`='" .
              $_POST['einddatum'] .
              "',
						`personen`='" .
              $_POST['personen'] .
              "',
						`beschrijving`='" .
              $_POST['beschrijving'] .
              "'
					WHERE `id`=" .
              $_POST['reservering_id'];
          $reservationUpdated = $database->query($q);
          $q = 'UPDATE verhuur_verhuring SET groep = ? WHERE id = ?';
          $statement = $database->prepare($q);
          $rentalId = (int) $_POST['i'];
          $statement->bind_param('si', $requestedGroup, $rentalId);
          $groupUpdated = $statement->execute();
          $statement->close();
          if (!$reservationUpdated || !$groupUpdated) {
              return '<h3 class="failure">Wijziging niet opgeslagen</h3>';
          }
          if ($reservationChanges) {
              writeAuditTrailForReservation(
                  $database,
                  (int) $_POST['reservering_id'],
                  'RESERVERING_GEWIJZIGD',
                  'Aangepaste velden: ' . implode(', ', $reservationChanges)
              );
          }
          if ($groupChanged) {
              writeAuditTrail(
                  $database,
                  (int) $_POST['i'],
                  'GROEP_GEWIJZIGD',
                  'Eigen groep gewijzigd'
              );
          }
          return '<h3 class="success">Reservering gewijzigd</h3>';
      } else {
          return '<h3 class="failure">Wijziging niet mogelijk</h3>';
      }
  }
  function displayEditStatus($id) {
      if ($id == 0) {
          return '<section class="empty-state"><h2>Selecteer een reservering</h2><p>Kies een aanvraag uit de lijst om de gegevens te bekijken of te wijzigen.</p></section>';
      }
      global $database;
      $q = "
					SELECT `status`, `status_id`
					FROM `verhuur_status`
					ORDER BY `status_id`";
      $r = $database->query($q);
      $tags = getItemInformation($id)->fetch_assoc();
      $output = '<select name="status">';
      foreach ($r as $rw) {
          $output .= '<option value="' . $rw['status_id'] . '"';
          $output .= $tags['status'] == $rw['status'] ? ' selected="selected" ' : '';
          $output .= '>' . $rw['status'] . '</option>';
      }
      $output .= '</select>';
      $tags['status'] = $output;
      return item_status_editable(...$tags);
  }
  function saveEditStatus() {
      global $database;
      if (privacyEnabled()) {
          try {
              privacyEdit($database, 'status', $_POST);
              return '<h3>Wijziging opgeslagen</h3>';
          } catch (InvalidArgumentException $e) {
              return '<p role="alert">' .
                  htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') .
                  '</p>';
          } catch (Throwable $e) {
              http_response_code(500);
              return '<p>Wijziging mislukt. Controleer de privacy-preview.</p>';
          }
      }
      if (
          !isset($_POST['i'], $_POST['reservering_id'], $_POST['status']) ||
          !ctype_digit((string) $_POST['i']) ||
          !ctype_digit((string) $_POST['reservering_id']) ||
          !ctype_digit((string) $_POST['status'])
      ) {
          return '<h3 class="failure">Wijziging niet mogelijk</h3>';
      }
      $rentalId = (int) $_POST['i'];
      $currentItem = getItemInformation($rentalId)->fetch_assoc();
      if (
          !$currentItem ||
          (int) $currentItem['reservering_id'] !== (int) $_POST['reservering_id']
      ) {
          return '<h3 class="failure">Wijziging niet mogelijk</h3>';
      }
      $reservationId = (int) $currentItem['reservering_id'];
      $statusQuery = $database->prepare(
          'SELECT status_id FROM verhuur_reservering WHERE id=? LIMIT 1'
      );
      $statusQuery->bind_param('i', $reservationId);
      $statusQuery->execute();
      $statusQuery->bind_result($previousStatus);
      if (!$statusQuery->fetch()) {
          $statusQuery->close();
          return '<h3 class="failure">Wijziging niet mogelijk</h3>';
      }
      $statusQuery->close();
      $newStatus = (int) $_POST['status'];
      $update = $database->prepare('UPDATE verhuur_reservering SET status_id=? WHERE id=?');
      $update->bind_param('ii', $newStatus, $reservationId);
      $updated = $update->execute();
      $update->close();
      if (!$updated) {
          return '<h3 class="failure">Wijziging niet opgeslagen</h3>';
      }
      if ((int) $previousStatus !== $newStatus) {
          writeAuditTrailForReservation(
              $database,
              $reservationId,
              'STATUS_GEWIJZIGD',
              'Status gewijzigd van ' . (int) $previousStatus . ' naar ' . $newStatus
          );
      }
      return '<h3 class="success">Status gewijzigd</h3>';
  }
  $action_performed = false;
  if (!isset($_GET['i']) && !isset($_POST['i'])) {
      echo '<section class="empty-state"><h2>Selecteer een reservering</h2><p>Kies een aanvraag uit de lijst om de gegevens te bekijken of te wijzigen.</p></section>';
  }
  if (isset($_GET['i']) && is_numeric($_GET['i'])) {
      echo loadOptions($_GET['i']);
      if (isset($_GET['a'])) {
          switch ($_GET['a']) {
              case 'eu':
                  // Huurder wijzigen.
                  echo displayEditUser($_GET['i']);
                  break;
              case 'er':
                  // Reservering wijzigen.
                  echo displayEditReservation($_GET['i']);
                  break;
              case 'es':
                  // Status wijzigen.
                  echo displayEditStatus($_GET['i']);
                  break;
              default:
                  echo displayItem($_GET['i']);
          }
          $action_performed = true;
      } else {
          echo displayItem($_GET['i']);
      }
  }
  if (isset($_POST['i']) && is_numeric($_POST['i'])) {
      echo loadOptions($_POST['i']);
      if (isset($_POST['a'])) {
          switch ($_POST['a']) {
              case 'sg':
                  if (privacyEnabled()) {
                      try {
                          privacyEdit($database, 'group', $_POST);
                          echo '<p role="status">Verhuring gekoppeld aan de eigen groep.</p>';
                      } catch (InvalidArgumentException $e) {
                          echo '<p role="alert">' .
                              htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') .
                              '</p>';
                      } catch (Throwable $e) {
                          http_response_code(500);
                          echo '<p>Omzetten mislukt. Controleer de huidige koppeling voordat je opnieuw probeert.</p>';
                          error_log('Rental own-group conversion failed.');
                      }
                      echo displayItem($_POST['i']);
                  }
                  break;
              case 'su':
                  /*Save User*/ echo saveEditedUser();
                  echo displayItem($_POST['i']);
                  break;
              case 'sr':
                  /*Save Reservation*/ echo saveEditReservation();
                  echo displayItem($_POST['i']);
                  break;
              case 'ss':
                  /*Save Reservation*/ echo saveEditStatus();
                  echo displayItem($_POST['i']);
                  break;
              default:
                  break;
          }
          $action_performed = true;
      }
  }
  ?>
	</body>
</html>
