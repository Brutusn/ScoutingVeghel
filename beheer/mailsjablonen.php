<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../php/mail-html.php';
$mailTabs = array_filter(
    mailHtmlCatalog(),
    function ($id) {
        return strpos($id, 'verhuur.') === 0
            ? adminCan('mailsjablonen')
            : adminCan('contact-sjablonen');
    },
    ARRAY_FILTER_USE_KEY
);
$tab = beheerInput('tab', '');
$notice = '';
$error = '';
$submitted = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $posted = beheerInput('naam');
    if (substr($posted, -5) === '.html') {
        $posted = substr($posted, 0, -5);
    }
    if (!isset($mailTabs[$posted])) {
        http_response_code(403);
        exit('Geen toegang tot dit sjabloon.');
    }
    $tab = $posted;
}
if (!isset($mailTabs[$tab])) {
    $tab = key($mailTabs);
}
$key = $tab . '.html';
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = beheerInput('action');
        if ($action === 'preview' || $action === 'save') {
            $submitted = beheerInput('inhoud');
            mailHtmlValidate($tab, $submitted);
            if ($action === 'preview') {
                $html = mailHtmlPreview($tab, $submitted);
                while (ob_get_level()) {
                    ob_end_clean();
                }
                header('Content-Type: application/json');
                echo json_encode(['html' => $html, 'subject' => mailHtmlSubject($html)]);
                exit();
            }
            $stmt = $database->prepare(
                'INSERT INTO beheer_mail_templates (naam,inhoud) VALUES (?,?) ON DUPLICATE KEY UPDATE inhoud=VALUES(inhoud)'
            );
            $stmt->bind_param('ss', $key, $submitted);
            $stmt->execute();
            $stmt->close();
            $notice = 'Het volledige mailsjabloon is opgeslagen.';
        } elseif ($action === 'reset') {
            $stmt = $database->prepare('DELETE FROM beheer_mail_templates WHERE naam=?');
            $stmt->bind_param('s', $key);
            $stmt->execute();
            $stmt->close();
            $notice = 'De standaardopmaak is hersteld.';
        } else {
            throw new InvalidArgumentException('Ongeldige actie.');
        }
    }
} catch (InvalidArgumentException $e) {
    $error = $e->getMessage();
} catch (Throwable $e) {
    http_response_code(503);
    $error = 'Het sjabloon kon niet worden geladen of opgeslagen.';
    error_log('Scouting Veghel: HTML template editor failed (' . get_class($e) . ').');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && beheerInput('action') === 'preview') {
    while (ob_get_level()) {
        ob_end_clean();
    }
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => $error]);
    exit();
}
// Read afresh after a save/reset; legacy text overrides remain part of the default template.
$stmt = $database->prepare('SELECT inhoud FROM beheer_mail_templates WHERE naam=?');
$stmt->bind_param('s', $key);
$stmt->execute();
$stmt->bind_result($stored);
$exists = $stmt->fetch();
$stmt->close();
$source = $submitted ?? ($exists ? $stored : mailHtmlDefault($tab));
if ($submitted === null || !$error) {
    $source = mailHtmlPretty($source);
}
$preview = '';
try {
    $preview = mailHtmlPreview($tab, $source);
} catch (Throwable $e) {
    if (!$error) {
        $error =
            $e instanceof InvalidArgumentException
                ? $e->getMessage()
                : 'Het voorbeeld kon niet worden geladen.';
    }
}
beheerHead('Mailsjablonen');
?>
<main class="mail-editor-page"><h1>Mailsjablonen</h1><p>Bewerk de volledige HTML van een mail. Het onderwerp staat in <code>&lt;title&gt;</code>. Het voorbeeld gebruikt fictieve gegevens en verandert direct tijdens het typen. Alleen Opslaan wijzigt de automatische mails.</p>
<?php if ($notice): ?><p class="success" role="status"><?= beheerEscape(
    $notice
) ?></p><?php endif; ?>
<?php if ($error): ?><p class="error" role="alert"><?= beheerEscape($error) ?></p><?php endif; ?>
<nav class="access-tabs" aria-label="Automatische mails"><?php foreach (
    $mailTabs
    as $id => $mail
): ?><a href="mailsjablonen.php?tab=<?= beheerEscape($id) ?>" <?= $tab === $id
    ? 'aria-current="page"'
    : '' ?>><?= beheerEscape($mail['label']) ?></a><?php endforeach; ?></nav>
<h2><?= beheerEscape($mailTabs[$tab]['label']) ?></h2><p><?= beheerEscape(
    $mailTabs[$tab]['description']
) ?></p>
<div class="mail-editor-layout"><form method="post" action="mailsjablonen.php" id="mail-html-form"><input type="hidden" name="naam" value="<?= beheerEscape(
    $key
) ?>">
<label for="mail-html"><strong>Volledig HTML-sjabloon</strong></label><textarea id="mail-html" name="inhoud" spellcheck="false" maxlength="60000" required><?= beheerEscape(
    $source
) ?></textarea>
<div class="mail-editor-actions"><button name="action" value="save">Opslaan</button><button name="action" value="reset" class="button-secondary" formnovalidate>Standaard herstellen</button></div>
<details><summary>Beschikbare invoegvelden</summary><p>Deze worden bij verzending automatisch ingevuld. Houd verplichte links en gegevens in het sjabloon.</p><ul><?php foreach (
    $mailTabs[$tab]['variables']
    as $variable
): ?><li><code>{{<?= beheerEscape($variable) ?>}}</code><?= in_array(
    $variable,
    $mailTabs[$tab]['required'],
    true
)
    ? ' (verplicht)'
    : '' ?></li><?php endforeach; ?></ul><p>Gebruik <code>cid:scouting-veghel-logo</code> als afbeelding voor het logo. De afbeelding wordt met de mail meegestuurd.</p></details></form>
<section class="mail-preview-panel" aria-label="Mailvoorbeeld"><h3>Voorbeeld</h3><p id="mail-preview-status" role="status">Voorbeeld met fictieve gegevens.</p><iframe id="mail-preview" title="Voorbeeld van de automatische mail" sandbox="allow-same-origin" scrolling="no" referrerpolicy="no-referrer" srcdoc="<?= beheerEscape(
    $preview
) ?>"></iframe></section></div>
<script src="mail-editor.js?v=20261029-autoheight"></script></main><?php beheerFoot();
