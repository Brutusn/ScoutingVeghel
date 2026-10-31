# Beheer — installatie en migraties

Deze PR bouwt voort op de hoofdwebsite-PR met gedeelde PHP-code en
configuratievoorbeelden. Merge die eerst. Alleen de inhoud van `beheer/` en
de gedeelde modules hoort op hosting; onderhoudsbestanden nooit uploaden.

Back-up en schema-inspectie gaan vóór elke handmatige migratie. Deze SQL
bestanden bevatten schema/routines en fictieve of lege voorbeeldinstellingen,
geen accountdata, huurders, exports, wachtwoorden of hosting-DEFINER.

- `admin-passwords.sql`: alleen als `pass_hash` nog ontbreekt.
- `admin-access.sql`: alleen als de rechten-/MFA-kolommen en sjabloontabel ontbreken.
- `admin-groups.sql`: daarna; groepen, modulevinkjes en sessie-auth_version.
- `beheer-independent.sql`: referentie voor beheerinstellingen, routines en
  engineconversies. Niet blind opnieuw importeren op een bestaande database.

De beheeraccounttabel moet InnoDB gebruiken. Bestaande accounts blijven
behouden; deel ze na migratie bewust in rechtengroepen in. MFA is optioneel
per account. De laatste account met accountbeheerrechten kan die toegang
niet kwijtraken. Er is geen standaardaccount of standaardwachtwoord.

Echte contractcontactgegevens en het rekeningnummer staan niet in deze
schema’s. Vul hostinginstellingen en `private/rental-contract.php` bewust in.
Privacytermijnen en automatische verwerking staan standaard uit.

Lokaal gecontroleerd: login, moduletoegang, CSRF, wachtwoordreset, intrekken
van sessies, MFA-inschrijving, TOTP-hergebruik, herstelcodes, mailtemplates,
reserveringen en mobiele weergave. Tests gebruikten tijdelijke gegevens.
