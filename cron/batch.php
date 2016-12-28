<?php
require_once('../php/includes/class/database.php');
require_once('../php/includes/settings.php');

  $BASE_VERHUUR_URL = 'http://nieuw.scoutingveghel.nl/#verhuur';
  $MAIL_ADDRESS_VERHUUR = 'website@scoutingveghel.nl';
  $FROM_HEADER_VERHUUR = 'Scouting Veghel Verhuur <' . $MAIL_ADDRESS_VERHUUR . '>';

  $database = new database();

  /**
  * log the mutation event to the database
  */
  function logEvent($affected_id,$action, $query, $user){
		$query = htmlentities($query,ENT_QUOTES);
		$query2 =   "INSERT INTO `verhuur_mutaties`
					SET `hoort_bij` = ".$affected_id.",
						`actie` = '".$action."',
						`datum` = NOW(),
						`naam` = '".$user."',
						`query` = '".$query."'";
		$result = mysql_query($query2) or die(mysql_error());
	}

  /**
  * Update all expired options to status 11 and notify the contact that is reservation is expired.
  */
  function Delete_Expired_Options(){
  	global $database;
    global $MAIL_ADDRESS_VERHUUR;
    global $FROM_HEADER_VERHUUR;
  	//first, retrieve all (relevant) id's:

  	$query = 	"SELECT `verhuur_reservering`.`id`, `verhuur_huurder`.`contactpersoon`, `verhuur_huurder`.`email`
  				 FROM `verhuur_verhuring`
  				 LEFT JOIN `verhuur_reservering`
  				 ON `verhuur_reservering`.`id`=`verhuur_verhuring`.`reservering_id`
  				 LEFT JOIN `verhuur_huurder`
  				 ON `verhuur_verhuring`.`huurder_id` = `verhuur_huurder`.`id`
  				 WHERE `verhuur_reservering`.`status_id`=0
  				 AND (
  						(`datum` + INTERVAL ".NUMBER_OF_DAYS_OPTION_IS_VALID." DAY) < NOW()
  						OR
  						(`begindatum`='0000-00-00 00:00:00')
  					  )";
  	$result = $database->query($query);

  	$ids = '(-1';
  	while($rw = mysql_fetch_assoc($result)){
  		$ids .= ','.$rw['id'];
  		logEvent($rw['id'],'VERVALLEN','none','system');

      $subject = "[SV.nl/verhuur] Je optie is vervallen!";
      $message = "Beste ".$rw['contactpersoon'].",\r\n".
       NUMBER_OF_DAYS_OPTION_IS_VALID." dagen geleden heeft u een optie genomen op onze blokhut.
       Omdat u die tot op heden niet bevestigd hebt, is deze komen te vervallen.
       Indien u de blokhut wel wilt gebruiken op de door u opgegeven data, dient deze opnieuw gereserveerd te worden!\r\n
       Dit kan op onze webpagina: ". $BASE_VERHUUR_URL.". \r\n
       Indien u nog vragen heeft, kunt u deze mailen naar ". $MAIL_ADDRESS_VERHUUR .", wij proberen dan zo spoedig mogelijk antwoord te geven! \r\n\r\n
       Met vriendelijke groeten,\r\n
       Verhuurder Scouting Veghel\r\n\r\n
      P.S. U krijgt deze mail ook indien uw reservering een ongeldige datum (vaak: 00:00 00-00-0000) bevat.
      Wij willen u dan waarschuwen dat u waarschijnlijk de 'terug'-knop van uw browser hebt gebruikt, waardoor de ingevulde datum is komen te vervallen!";
      $headers = "From: " . $FROM_HEADER_VERHUUR ."\r\n";
      $headers .= "Reply-To: " . $MAIL_ADDRESS_VERHUUR;
  		if( mail($rw['email'], $subject, $message, $headers)){
  				logEvent($rw['id'], 'HUURDER GEMAILD','none','system');
  			} else {
  				logEvent($rw['id'], 'MAILEN FAALDE!','none','system');
  			}
  	}
  	$ids .= ')';

  	$query =
  		"UPDATE `verhuur_reservering`
  		 SET `status_id`=11
  		 WHERE `id` IN ".$ids;
  	$result = $database->query($query);
  }

  /**
  * Set all the currently ongoing reservations
  */
  function Set_Current_Occupations(){
  	global $database;
  	$query = "SELECT `id`
  			  FROM `verhuur_reservering`
  			  WHERE `status_id`=4
  			    AND TIMEDIFF(`begindatum`,NOW()) < 0
  				AND TIMEDIFF(`einddatum`,NOW()) > 0";
  	$result = $database->query($query);

  	$ids = '(-1';
  	while($rw = mysql_fetch_assoc($result)){
  		$ids .= ','.$rw['id'];
  		logEvent($rw['id'],'BEZIG','none','system');
  	}
  	$ids .= ')';


  	$query =
  		"UPDATE `verhuur_reservering`
  		 SET `status_id`=5
  		 WHERE `id` IN ".$ids;
  	$result = $database->query($query);
  }

  /**
  * Set all reservations that are finished to done
  */
  function Set_Already_Left(){
    global $database;
  	$query = "SELECT `id`
  			  FROM `verhuur_reservering`
  			  WHERE `status_id`=5
  				AND TIMEDIFF(`einddatum`,NOW()) < 0";
  	$result = $database->query($query);

  	$ids = '(-1';
  	while($rw = mysql_fetch_assoc($result)){
  		$ids .= ','.$rw['id'];
  		logEvent($rw['id'],'AFGELOPEN','none','system');
  	}
  	$ids .= ')';

  	$query =
  		"UPDATE `verhuur_reservering`
  		 SET `status_id`=6
  		 WHERE `id` IN ".$ids;
  	$result = $database->query($query);
  }

  /**
  * Set all reservations that happened without payment
  */
  function Set_Unpaid_Left(){
    global $database;
  	$query = "SELECT `id`
  			  FROM `verhuur_reservering`
  			  WHERE (`status_id`=3 OR `status_id`=2)
  			    AND TIMEDIFF(`einddatum`,NOW()) < 0";
  	$result = $database->query($query);

  	$ids = '(-1';
  	while($rw = mysql_fetch_assoc($result)){
  		$ids .= ','.$rw['id'];
  		logEvent($rw['id'],'ONBETAALD VERLOPEN','none','system');
  	}
  	$ids .= ')';

  	$query =
  		"UPDATE `verhuur_reservering`
  		 SET `status_id`=12
  		 WHERE `id` IN ".$ids;
  	$result = $database->query($query);
  }

  Delete_Expired_Options();
  Set_Current_Occupations();
  Set_Already_Left();
  Set_Unpaid_Left();
?>
