<?php
$code = isset($_POST['code']) && is_string($_POST['code']) ? $_POST['code'] : '';
$group = isset($_POST['group']) && is_string($_POST['group']) ? $_POST['group'] : '';
$answers = [
    'group1' => [
        'EXPLOASSIGNMENT' => 'StomPost1',
        'GBLRORFONJAAAA' => 'Frikandel01Post2',
        '4H3RU12N57P7671' => 'TrommelPost3',
        '23573CAHEGDBF2195' => 'S5Post4'
    ],
    'group2' => [
        'INTERACTIVEEXPLO' => 'StomPost1',
        'REDLGDCWAIVTLB' => 'Frikandel01Post2',
        '4W3EG12C57A7671' => 'TrommelPost3',
        '23573271648532195' => 'S5Post4'
    ]
];
$message = 'Go go go! Get started, lazy you!';
$result = '';
if (isset($_POST['subm'])) {
    $message =
        $code === '' ? 'Misschien moet je maar iets invoeren.' : 'Wat kunnen jullie goed niks!';
    if (isset($answers[$group][$code])) {
        $result =
            '<img src="' .
            $answers[$group][$code] .
            '.png" alt="Interactive Explo Assignment Answer" />';
    }
}
if ($result === '') {
    $result = '<p style="margin: 50px; text-align: center;">' . $message . '</p>';
}
?>
<!doctype html>
<html lang="nl">
    <head>
        <title>Interactive Explo Assignment</title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <link href="c.css" rel="stylesheet">
    </head>
    <body>
        <div id="head">
            <h1>Interactive Explo Assignment</h1>
        </div>
        <div id="input">
            <form id="answer" method="POST" action="index.php">
                <select name="group" id="group">
                    <option value="group1">Flierefluiters</option>
                    <option value="group2">Lapzwansen</option>
                </select>
                <input type="text" name="code" id="code" placeholder="Geheime code..." value="<?= htmlspecialchars(
                    $code,
                    ENT_QUOTES | ENT_SUBSTITUTE,
                    'UTF-8'
                ) ?>">
                <input type="submit" name="subm" id="subm" value="Check">
            </form>
            <p id="copy">&copy;2012 - made by Sahi and Bennies.</p>
        </div>
        <div id="result"><?php echo $result; ?></div>
        <div id="blank">&nbsp;</div>
    </body>
</html>
