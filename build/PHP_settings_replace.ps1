$files=("contact-form.php", "verhuur-confirm.php", "mail_layer.php")
ForEach($file in $files){(Get-Content $file) | ForEach-Object {
$_.replace('$MAIL_ADDRESS_VERHUUR = ''website@scoutingveghel.nl'';', '$MAIL_ADDRESS_VERHUUR = ''verhuur@scoutingveghel.nl'';').`
replace('$MAIL_ADDRESS_GEBE = ''website@scoutingveghel.nl'';', '$MAIL_ADDRESS_GEBE = ''groepsbegeleiding@scoutingveghel.nl'';').`
replace('$MAIL_ADDRESS_BESTUUR = ''website@scoutingveghel.nl'';', '$MAIL_ADDRESS_BESTUUR = ''bestuur@scoutingveghel.nl'';').`
replace('$VERIFY_BASE_URL = ''http://nieuw.scoutingveghel.nl/php/verhuur-confirm.php?key='';','$VERIFY_BASE_URL = ''http://www.scoutingveghel.nl/php/verhuur-confirm.php?key='';').`
replace('$HUURVOOORWAARDEN_URL = ''http://nieuw.scoutingveghel.nl/docs/huurvoorwaarden.pdf'';','$HUURVOOORWAARDEN_URL = ''http://www.scoutingveghel.nl/docs/huurvoorwaarden.pdf'';').`
replace('HUUROVEREENKOMST_BASE_URL = ''http://nieuw.scoutingveghel.nl/php/huurovereenkomst.php?key='';', 'HUUROVEREENKOMST_BASE_URL = ''http://www.scoutingveghel.nl/php/huurovereenkomst.php?key='';').`
replace('$BASE_URL = ''http://nieuw.scoutingveghel.nl/'';', '$BASE_URL = ''http://www.scoutingveghel.nl/'';')`
} | Set-Content $file}