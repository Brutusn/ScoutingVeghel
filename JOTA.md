# JOTA-pagina

De pagina is lokaal en op https://test.scoutingveghel.nl/jota/ gecontroleerd.
De nieuwste editie is 2026. De jaarkeuze toont ook eerdere edities.
`jota.scoutingveghel.nl` verwijst naar `https://www.scoutingveghel.nl/jota/`,
met behoud van pad en query. Zo werken de gedeelde afbeeldingen en stijlen ook
voor bezoekers die via het subdomein binnenkomen.

## Publiceren

Publiceer de volgende bestanden vanuit deze repository naar dezelfde paden in
de documentroot van de website:

- `jota/index.html`, `jota/jota.css` en `jota/jota.js`
- `jota/.htaccess` (ook dit verborgen bestand meenemen)
- `jota/krant/*.pdf` en `jota/previews/*.jpg`
- `css/site-theme.css`
- `images/scouting-veghel-mail-logo.png`
- `images/toren-vanaf-beneden-web.webp`
- `images/toren-verticaal-web.webp`

De pagina gebruikt daarnaast het bestaande `images/favicons/favicon.ico`.
Het bestaande Windows-buildscript neemt JOTA en deze afhankelijkheden mee naar
`Release/`. Deze documentatie hoeft niet naar de website.

De CSP in `jota/.htaccess` geldt alleen voor deze map en staat de gebruikte
YouTube-speler toe. Apache moet `mod_headers` en deze `.htaccess` ondersteunen.
Controleer na publicatie de jaarkeuze, PDF-links, videospeler en de logobalk op
desktop en mobiel. Er zijn geen databasewijzigingen of private instellingen nodig.

## Inhoud aanpassen

De jaargangen staan als HTML-secties in `index.html`; de jaarkeuze gebruikt
`data-year`. Voeg nieuwe edities bovenaan toe en werk de jaarkeuze en de
aantallen in de introductie bij. Afbeeldingen voor krantjes en filmpjes staan
in `previews/`; de originele krantjes staan in `krant/`.
