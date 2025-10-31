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

And the `MAIL2.php` file with:

```php
<?php
// Example SMTP configuration for local development.

$SMTP_SERVER = getenv('SV_SMTP_HOST');
$SMTP_PORT = (int) (getenv('SV_SMTP_PORT'));
$SMTP_USER = getenv('SV_SMTP_USER');
$SMTP_PASSWORD = getenv('SV_SMTP_PASSWORD');
$SMTP_MAIL_FROM = getenv('SV_SMTP_FROM');
?>
```

### Installing PHP using ASDF

To use PHP via ASDF, one needs to have [several packages installed](https://github.com/asdf-community/asdf-php).

### Running tests

Install the development dependencies and run PHPUnit:

```bash
# install dependencies (inside Docker or locally)
docker compose run --rm web composer install

# execute the unit test suite
docker compose run --rm web composer test > .phpunit.result.log

```


The first command creates the `vendor/` directory (ignored by Git) and generates `composer.lock` for consistent installs.

> ℹ️ Ensure the Docker stack (particularly MariaDB and MailHog) is running before executing the test suite, as several tests exercise database procedures and send emails captured by MailHog.
