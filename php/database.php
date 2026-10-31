<?php
/** Database configuration is shared by the public site and administration. */
function siteDatabaseConfiguration() {
    if (getenv('SV_ENV') === 'local') {
        if (
            getenv('SV_DB_NAME') !== 'scoutingveghel_local' ||
            getenv('SV_DB_HOST') !== '127.0.0.1' ||
            getenv('SV_DB_PORT') !== '3307'
        ) {
            throw new RuntimeException('Invalid local database configuration');
        }
        $config = [
            'host' => '127.0.0.1',
            'port' => 3307,
            'database' => 'scoutingveghel_local',
            'user' => getenv('SV_DB_USER'),
            'password' => getenv('SV_DB_PASSWORD')
        ];
    } elseif (getenv('BEHEER_DB_NAME')) {
        $config = [
            'host' => getenv('BEHEER_DB_HOST') ?: 'localhost',
            'port' => getenv('BEHEER_DB_PORT') ?: 3306,
            'database' => getenv('BEHEER_DB_NAME'),
            'user' => getenv('BEHEER_DB_USER'),
            'password' => getenv('BEHEER_DB_PASSWORD')
        ];
    } else {
        $file = realpath(
            getenv('BEHEER_DB_CONFIG') ?: dirname(__DIR__, 2) . '/private/beheer-database.php'
        );
        $webroot = realpath(dirname(__DIR__));
        if (
            !$file ||
            !is_file($file) ||
            $file === $webroot ||
            strpos($file, $webroot . DIRECTORY_SEPARATOR) === 0
        ) {
            throw new RuntimeException(
                'Configureer de database buiten public_html of via BEHEER_DB_*'
            );
        }
        $config = (static function ($path) {
            return require $path;
        })($file);
    }
    if (!is_array($config)) {
        throw new RuntimeException('Ongeldige databaseconfiguratie');
    }
    foreach (['host', 'database', 'user', 'password'] as $key) {
        if (!isset($config[$key]) || !is_string($config[$key])) {
            throw new RuntimeException('Ongeldige databaseconfiguratie');
        }
    }
    $port = filter_var($config['port'] ?? 3306, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1, 'max_range' => 65535]
    ]);
    if ($port === false) {
        throw new RuntimeException('Ongeldige databasepoort');
    }
    $config['port'] = $port;
    return $config;
}

/** Each caller owns its connection and closes it after use. */
function siteDatabaseConnection() {
    $config = siteDatabaseConfiguration();
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $database = new mysqli(
        $config['host'],
        $config['user'],
        $config['password'],
        $config['database'],
        $config['port']
    );
    $database->set_charset('utf8mb4');
    return $database;
}
