# Scouting Veghel website

Statische hoofdpagina’s, PHP-verhuur en contactformulier. Bestaande inhoud en
opmaak zijn behouden; de broncode is leesbaar en gebruikt gedeelde modules.
Geen npm-build nodig. PHP 8.2+ met mysqli, mbstring en DOM en een bestaande
MariaDB-database met de gebruikte verhuurprocedures zijn vereist. Installeer
PHPMailer met `git submodule update --init --recursive` na het klonen.

## Privéconfiguratie

De Git-repository bevat geen echte ontvangers, SMTP-wachtwoorden of
databasecredentials. Kopieer de voorbeelden uit `config-examples/` naar een
map `private/` naast en buiten de website-documentroot. Vul daar de echte
waarden in. Publiceer die map nooit en voeg hem niet toe aan Git.

- `beheer-database.php`: databaseverbinding, of `BEHEER_DB_*`-omgevingsvariabelen.
- `mail.php`: SMTP-verbinding en afzender, of `SV_SMTP_*`-omgevingsvariabelen.
- `recipients.php`: ontvangers per contactcategorie. Alternatief: `SV_MAIL_GEBE`,
  `SV_MAIL_RENTAL`, `SV_MAIL_BOARD`, `SV_MAIL_SPONSOR`, `SV_MAIL_WEBSITE`.
- `contact.php`: CAPTCHA-geheim, of `RECAPTCHA_SECRET`.
- `rental-contract.php`: eigen contractcontactgegevens en rekeningnummer.
- `privacy.php`: bestaande privacyinstellingen; termijnen blijven standaard uit.

Instellingenpaden zijn zo nodig te wijzigen via `SV_RECIPIENTS_CONFIG`,
`SV_CONTACT_CONFIG`, `SV_RENTAL_CONTRACT_CONFIG`, `SV_SMTP_CONFIG` en
`BEHEER_DB_CONFIG`. Configuratie binnen de webroot wordt geweigerd.
Ontbrekende ontvangers of contractgegevens veroorzaken een fout in plaats
van verzending naar een fictief standaardadres of een onvolledig contract.
Een ontbrekende sponsorontvanger gebruikt de ingestelde bestuursontvanger.

`SV_ENV=local` is uitsluitend voor de geïsoleerde lokale runtime: uitgaande
mail wordt daar op schijf opgevangen en fictieve adressen worden gebruikt.
Gebruik dit nooit op hosting. De projectruntime staat buiten deze repository
in `test.scoutingveghel.nl/local/dev.py`; echte lokale instellingen blijven daar.

## Controle

`python3 maintenance/check-source.py` controleert alle getrackte bronbestanden op
privébestanden, echte interne mailadressen en credentialpatronen. Dezelfde
controle draait in GitHub Actions; gevonden waarden worden niet gelogd.

`php tests/configuration.php` controleert routering, omgevingsvariabelen,
ontbrekende configuratie, headerinjectie en private bestanden buiten de webroot.
`php tests/php_dependencies.php` controleert PHP-syntax en include-afhankelijkheden.
De bestaande lokale integratiesuites controleren reserveringen, echte mailinhoud,
login, MFA, groepsrechten en mobiele weergave met tijdelijke testgegevens.

## Publiceren

Test eerst lokaal en daarna op de testsite. Upload alleen websitebestanden uit
`maintenance/release-files.json`. Upload nooit `config-examples/`, `maintenance/`,
`tests/`, `.git/`, lokale tooling, logs, exports of echte privéconfiguratie.
Controleer vóór deployment dat de echte ontvangers en contractgegevens op die
omgeving zijn ingesteld; de oude PowerShell-adresvervanging is vervallen.

De hoofdwebsite-PR bevat gedeelde PHP-code. Beheer en JOTA worden in aparte
PR’s aangeleverd die daarop voortbouwen. Dit is bronpublicatie, geen opdracht
om bestanden of databasewijzigingen naar een website te publiceren.

## Bestaande Git-geschiedenis

De oude contactcode bevatte een reCAPTCHA-geheim. Dat is verwijderd uit de
actuele code, maar oude commits blijven bestaan. Vernieuw de sleutel bij de
provider; deze PR herschrijft geen bestaande Git-geschiedenis. Publieke
veiligheidscontacten en auteursvermeldingen van bibliotheken zijn bewust
behouden; interne ontvangers staan uitsluitend in privéconfiguratie.
