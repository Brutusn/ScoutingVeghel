<?php
//Settings for URL and paths
define('BASE_URL','http://nieuw.scoutingveghel.nl/');
define('VERIFY_BASE_URL','http://nieuw.scoutingveghel.nl/php/verhuur-confirm.php?key=');
define('HUURVOOORWAARDEN_URL','http://nieuw.scoutingveghel.nl/docs/huurvoorwaarden.pdf');
define('HUUROVEREENKOMST_BASE_URL','http://nieuw.scoutingveghel.nl/php/huurovereenkomst.php?key=');
define('HUUROVEREENKOMST_TEMPLATE_URL', 'templates/huurovereenkomst.tpl');


//Settings for mailing
define('MAIL_ADDRESS_VERHUUR','website@scoutingveghel.nl');
define('MAIL_ADDRESS_WEBSTIE','website@scoutingveghel.nl');
define('MAIL_ADDRESS_GEBE','website@scoutingveghel.nl');
define('MAIL_ADDRESS_BESTUUR','website@scoutingveghel.nl');

//Settings for mail headers
define('FROM_HEADER_VERHUUR','Scouting Veghel Verhuur <' . MAIL_ADDRESS_VERHUUR . '>');
define('FROM_HEADER_WEBSITE','Scouting Veghel Website <' . MAIL_ADDRESS_WEBSTIE . '>');

//Settings for date time format
define('DATE_TIME_FORMAT','Y-m-d H:i:s');
define('DATE_FORMAT','Y-m-d');
define('TIME_ZONE','Europe/Paris');


//Settings that are left over / old
//define('DATE_FORMAT','%d/%m/%Y');
//define('DATETIME_FORMAT','%d/%m/%Y - %H:%i');
//define('DATETIME_PHP','d/m/Y - H:i');

?>
