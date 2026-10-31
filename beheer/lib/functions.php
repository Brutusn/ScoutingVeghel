<?php
require_once __DIR__ . '/document.php';
require_once __DIR__ . '/../../php/database.php';

function beheerEscape($value) {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function beheerInput($name, $default = '') {
    $value = $_POST[$name] ?? ($_GET[$name] ?? $default);
    if (!is_scalar($value) && $value !== null) {
        throw new InvalidArgumentException('Ongeldige invoer');
    }
    return trim((string) $value);
}
function beheerId($name, $default = 0) {
    $value = beheerInput($name, (string) $default);
    if (!preg_match('/^\d+$/D', $value)) {
        throw new InvalidArgumentException('Ongeldig kenmerk');
    }
    return (int) $value;
}
function beheerSetting($name, $default = '') {
    static $settings;
    if ($settings === null) {
        $database = siteDatabaseConnection();
        try {
            $settings = array_column(
                $database
                    ->query('SELECT naam,waarde FROM beheer_settings')
                    ->fetch_all(MYSQLI_ASSOC),
                'waarde',
                'naam'
            );
        } finally {
            $database->close();
        }
    }
    return $settings[$name] ?? $default;
}
function beheerHead($title) {
    echo beheerDocumentStart($title . ' | Beheer');
}
function beheerFoot() {
    echo '</body></html>';
}
function display_select($name, $start, $end, $interval) {
    $html = '<select name="' . beheerEscape($name) . '" id="' . beheerEscape($name) . '">';
    for ($value = $start; $value <= $end; $value += $interval) {
        $html .= '<option value="' . $value . '">' . $value . '</option>';
    }
    return $html . '</select>';
}
// Compatibility for existing forms: presence checks use the submitted request.
function m_isset($fields) {
    foreach ($fields as $field) {
        if (!isset($_REQUEST[$field]) || !is_scalar($_REQUEST[$field])) {
            return false;
        }
    }
    return true;
}
function m_isempty($fields) {
    foreach ($fields as $field) {
        if (empty($_REQUEST[$field])) {
            return true;
        }
    }
    return false;
}
function my_var_post() {
    global $_V;
    $_V['POST'] = [];
    foreach ($_POST as $key => $value) {
        if (!is_scalar($value)) {
            throw new InvalidArgumentException('Ongeldige invoer');
        }
        $_V['POST'][$key] = htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
function init($value, $default = '') {
    return $value ?? $default;
}
