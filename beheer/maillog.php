<?php
require_once __DIR__ . '/security.php';
adminRequireLogin();
require_once __DIR__ . '/lib/functions.php';
require_once __DIR__ . '/../php/mail-log.php';
$modules = ['contact' => 'Contact', 'verhuur' => 'Verhuur', 'overig' => 'Overig'];
$statuses = ['sent' => 'Verstuurd', 'local' => 'Lokaal opgeslagen', 'failed' => 'Mislukt'];
$error = '';
$result = ['rows' => [], 'more' => false];
$filters = mailLogFilters([]);
try {
    $filters = mailLogFilters($_GET);
    $result = mailLogRead($filters);
} catch (InvalidArgumentException $exception) {
    http_response_code(400);
    $error = $exception->getMessage();
} catch (Throwable $exception) {
    http_response_code(503);
    $error = 'Het maillog is tijdelijk niet beschikbaar. Probeer het later opnieuw.';
}
function mailLogPageUrl($filters, $page) {
    $filters['page'] = (string) $page;
    return 'maillog.php?' . http_build_query($filters);
}
beheerHead('Maillog');
?>
<main class="mail-log-page">
<header class="workspace-heading"><h1>Maillog</h1><p>Bekijk welke mails vanuit de website zijn verstuurd en naar wie.</p></header>
<p>Dit overzicht bevat verzendpogingen vanaf de invoering van het maillog. ‘Verstuurd’ betekent aangenomen door de mailserver, niet gegarandeerd bezorgd. Lokale testmails worden alleen opgeslagen.</p>
<form class="mail-log-filters" method="get" action="maillog.php">
<label>Vanaf <input type="date" name="from" value="<?= beheerEscape(
    $filters['from']
) ?>" required></label>
<label>Tot en met <input type="date" name="until" value="<?= beheerEscape(
    $filters['until']
) ?>" required></label>
<label>Module <select name="module"><option value="">Alle modules</option><?php foreach (
    $modules
    as $key => $label
): ?><option value="<?= beheerEscape($key) ?>"<?= $filters['module'] === $key
    ? ' selected'
    : '' ?>><?= beheerEscape($label) ?></option><?php endforeach; ?></select></label>
<label>Resultaat <select name="status"><option value="">Alle resultaten</option><?php foreach (
    $statuses
    as $key => $label
): ?><option value="<?= beheerEscape($key) ?>"<?= $filters['status'] === $key
    ? ' selected'
    : '' ?>><?= beheerEscape($label) ?></option><?php endforeach; ?></select></label>
<label>Zoeken <input type="search" name="search" maxlength="254" placeholder="Ontvanger of onderwerp" value="<?= beheerEscape(
    $filters['search']
) ?>"></label>
<button type="submit">Filteren</button><a href="maillog.php">Filters wissen</a>
</form>
<?php if ($error): ?><p role="alert"><?= beheerEscape($error) ?></p>
<?php elseif (!$result['rows']): ?><p class="empty-state">Geen mails gevonden voor deze filters.</p>
<?php else: ?>
<div class="mail-log-table"><table><thead><tr><th scope="col">Datum en tijd</th><th scope="col">Module</th><th scope="col">Aan</th><th scope="col">Onderwerp</th><th scope="col">Resultaat</th></tr></thead><tbody>
<?php foreach ($result['rows'] as $entry):
    $date = (new DateTimeImmutable($entry['date']))->setTimezone(
        new DateTimeZone('Europe/Amsterdam')
    ); ?>
<tr><td><time datetime="<?= beheerEscape($entry['date']) ?>"><?= beheerEscape(
    $date->format('d-m-Y H:i:s')
) ?></time></td><td><?= beheerEscape(
    $modules[$entry['module']] ?? 'Overig'
) ?></td><td class="mail-log-recipient"><?= beheerEscape($entry['to']) ?></td><td><?= beheerEscape(
    $entry['subject']
) ?></td><td><span class="mail-log-status mail-log-status-<?= beheerEscape(
    $entry['status']
) ?>"><?= beheerEscape($statuses[$entry['status']] ?? 'Onbekend') ?></span></td></tr>
<?php
endforeach; ?>
</tbody></table></div>
<?php endif; ?>
<nav class="mail-log-pages" aria-label="Maillogpagina’s">
<?php if ((int) $filters['page'] > 1): ?><a href="<?= beheerEscape(
    mailLogPageUrl($filters, (int) $filters['page'] - 1)
) ?>">← Vorige pagina</a><?php endif; ?>
<span>Pagina <?= (int) $filters['page'] ?> · maximaal 50 mails per pagina</span>
<?php if ($result['more']): ?><a href="<?= beheerEscape(
    mailLogPageUrl($filters, (int) $filters['page'] + 1)
) ?>">Volgende pagina →</a><?php endif; ?>
</nav>
<p class="mail-log-note">Alleen verzendgegevens worden bewaard; de mailinhoud en bevestigingslinks staan niet in dit log. De bewaartermijn is één kalendermaand. Oudere logregels worden automatisch opgeruimd bij een verzendpoging of bij het openen van dit overzicht.</p>
</main>
<?php beheerFoot(); ?>
