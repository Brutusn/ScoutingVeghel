(Get-Content batch.php) | ForEach-Object {
$_.replace('$MAIL_ADDRESS_VERHUUR = ''website@scoutingveghel.nl'';', '$MAIL_ADDRESS_VERHUUR = ''verhuur@scoutingveghel.nl'';').`
replace('$BASE_VERHUUR_URL = ''http://nieuw.scoutingveghel.nl/#verhuur'';', '$BASE_VERHUUR_URL = ''http://www.scoutingveghel.nl/#verhuur'';')`
} | Set-Content batch.php

(Get-Content poetsrooster.php) | ForEach-Object {
$_.replace('$POETS_CHECKLIST_URL = ''http://nieuw.scoutingveghel.nl/docs/poets_checklist.docx'';', '$POETS_CHECKLIST_URL = ''http://www.scoutingveghel.nl/docs/poets_checklist.docx'';').`
replace('$MAIL_ADDRESS_BEHEER = ''website@scoutingveghel.nl'';', '$MAIL_ADDRESS_BEHEER = ''beheer@scoutingveghel.nl'';').`
replace(' 1 => ["website@scoutingveghel.nl"],', ' 1 => ["welpen@scoutingveghel.nl"],').`
replace(' 2 => ["website@scoutingveghel.nl"],', ' 2 => ["scouts@scoutingveghel.nl"],').`
replace(' 3 => ["website@scoutingveghel.nl"],', ' 3 => ["verkenners@scoutingveghel.nl"],').`
replace(' 4 => ["website@scoutingveghel.nl"],', ' 4 => ["plusscouts@scoutingveghel.nl"],').`
replace(' 5 => ["website@scoutingveghel.nl"],', ' 5 => ["bevers@scoutingveghel.nl"],').`
replace(' 6 => ["website@scoutingveghel.nl"],', ' 6 => ["welpennishaani@scoutingveghel.nl"],').`
replace(' 7 => ["website@scoutingveghel.nl"],', ' 7 => ["bestuur@scoutingveghel.nl", "groepsbegeleiding@scoutingveghel.nl"],').`
replace(' 8 => ["website@scoutingveghel.nl"],', ' 8 => [],').`
replace(' 9 => ["website@scoutingveghel.nl"],', ' 9 => ["explorerbegeleiding@scoutingveghel.nl"],').`
replace(' 10 => ["website@scoutingveghel.nl"],', ' 10 => [],').`
replace(' 11 => ["website@scoutingveghel.nl"],', ' 11 => ["klavertje4@scoutingveghel.nl"],').`
replace(' 12 => ["website@scoutingveghel.nl"],', ' 12 => ["stam@scoutingveghel.nl"],').`
replace(' 1 => "Website-Jan",', ' 1 => "Welpen Sionie Horde",').`
replace(' 2 => "Website-Feb",', ' 2 => "Scouts",').`
replace(' 3 => "Website-Mar",', ' 3 => "Verkenners",').`
replace(' 4 => "Website-Apr",', ' 4 => "Plusscouts",').`
replace(' 5 => "Website-Mei",', ' 5 => "Bevers",').`
replace(' 6 => "Website-Jun",', ' 6 => "Welpen Nishaani Horde",').`
replace(' 7 => "Website-Jul",', ' 7 => "Bestuur & Groepsbegeleiding",').`
replace(' 8 => "Website-Aug",', ' 8 => "-",').`
replace(' 9 => "Website-Sep",', ' 9 => "Explorerbegeleiding",').`
replace(' 10 => "Website-Okt",', ' 10 => "-",').`
replace(' 11 => "Website-Nov",', ' 11 => "Welpen Klavertje 4",').`
replace(' 12 => "Website-Dec",', ' 12 => "S5Stam",')`
} | Set-Content poetsrooster.php