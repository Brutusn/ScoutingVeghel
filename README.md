# ScoutingVeghel

De vernieuwde website van Scouting Veghel, deze zal te vinden zijn op [www.scoutingveghel.nl](www.scoutingveghel.nl).

## Developer Notes

Start the stack fresh using `docker compose up --build`. When you want to start from fresh, run `docker compose down`, for example to refresh the PHP files in Apache. When you also want to take down the database as well, run `docker compose down -v`.

### Submodules

To get the latest code for the submodules, please run the following command: `git submodule update --init --recursive`.

### Configuration files

One needs the `DB2.php` file with the following content:

```php
<?php
// Example configuration for local development.

function databaseMYSQLi() {
    $host = getenv('SV_DB_HOST');
    $user = getenv('SV_DB_USER');
    $password = getenv('SV_DB_PASSWORD');
    $database = getenv('SV_DB_NAME');
    $port = (int) (getenv('SV_DB_PORT'));

    $mysqli = new mysqli($host, $user, $password, $database, $port);

    if ($mysqli->connect_errno) {
        throw new RuntimeException('Failed to connect to MariaDB: ' . $mysqli->connect_error);
    }

    // Ensure UTF-8 connections.
    $mysqli->set_charset('utf8mb4');

    return $mysqli;
}
?>
```

And the `MAIL2.php` file with either the variables directly or as loaded from the environment:

```php
<?php
// Example SMTP configuration for local development.

global $SMTP_SERVER;
global $SMTP_PORT;
global $SMTP_USER;
global $SMTP_PASSWORD;
global $SMTP_MAIL_FROM;
global $SMTP_SECURE;
global $SMTP_AUTO_TLS;
global $PHPMAILER_DEBUG;
global $MAIL_ADDRESS_WEBSITE;
global $MAIL_ADDRESS_VERHUUR;

$SMTP_SERVER = "mailhog";
$SMTP_PORT= 1025;
$SMTP_USER= "testuser";
$SMTP_PASSWORD= "testpass";
$SMTP_MAIL_FROM= "website@example.test";
$SMTP_SECURE= false;
$SMTP_AUTO_TLS= false;
$PHPMAILER_DEBUG= 0;
$MAIL_ADDRESS_WEBSITE= "website@example.test";
$MAIL_ADDRESS_VERHUUR= "verhuur@example.test";
?>
```

or

```php
<?php
// Example SMTP configuration for local development.
$SMTP_SERVER = getenv('SV_SMTP_HOST');
$SMTP_PORT = (int) (getenv('SV_SMTP_PORT'));
$SMTP_USER = getenv('SV_SMTP_USER');
$SMTP_PASSWORD = getenv('SV_SMTP_PASSWORD');
$SMTP_MAIL_FROM = getenv('SV_SMTP_FROM');

// Parse boolean values properly from environment
$smtpSecure = getenv('SV_SMTP_SECURE');
$SMTP_SECURE = ($smtpSecure === 'false' || $smtpSecure === '0' || $smtpSecure === '') ? false : $smtpSecure;

$smtpAutoTls = getenv('SV_SMTP_AUTO_TLS');
$SMTP_AUTO_TLS = ($smtpAutoTls === 'false' || $smtpAutoTls === '0' || $smtpAutoTls === '') ? false : (bool)$smtpAutoTls;
?>
```

### Installing PHP using ASDF

To use PHP via ASDF, one needs to have [several packages installed](https://github.com/asdf-community/asdf-php).

### Running tests

Install the development dependencies and run PHPUnit:

```bash
# start the Docker stack (web, db, mailhog)
docker compose up -d

# install dependencies (inside Docker or locally)
docker compose exec web composer install

# execute the unit test suite
docker compose exec web composer test > .phpunit.result.log
```

The second command creates the `vendor/` directory (ignored by Git) and generates `composer.lock` for consistent installs.

> ℹ️ Ensure the Docker stack (particularly MariaDB and MailHog) is running before executing the test suite, as several tests exercise database procedures and send emails captured by MailHog.

### Debugging

To debug the local setup, one can use the following commands to verify if the services are running as expected.

- To check if the database has the right stored procedures:

    ```bash
    docker compose exec db mysql -u sv_user -psv_password sv_database -e "SHOW PROCEDURE STATUS WHERE Db='sv_database';"
    ```

- To check if the tables are created correctly:

    ```bash
    docker compose exec db mysql -u sv_user -psv_password sv_database -e "SHOW TABLES;"
    ```

- To check if the test data is injected:

    ```bash
    docker compose exec db mysql -u sv_user -psv_password sv_database -e "SELECT COUNT(*) as verhuur_status_count FROM verhuur_status; SELECT COUNT(*) as verhuur_huurder_count FROM verhuur_huurder; SELECT COUNT(*) as verhuur_reservering_count FROM verhuur_reservering; SELECT COUNT(*) as verhuur_verhuring_count FROM verhuur_verhuring;"
    ```

- To test the MailHog settings and connection directly:

    ```bash
    docker compose exec web php tests/test_mailhog_direct.php
    ```

- To test the PHPMailer settings and connection directly:

    ```bash
    docker compose exec web php tests/test_phpmailer_direct.php
    ```

