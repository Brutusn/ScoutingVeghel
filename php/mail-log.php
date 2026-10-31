<?php
// Metadata only. Store outside the document root; never record message bodies or SMTP errors.
function mailLogDirectory($create = false) {
    $path = getenv('SV_MAIL_LOG_DIR') ?: dirname(__DIR__, 2) . '/private/mail-log';
    if ($create && !is_dir($path) && !@mkdir($path, 0700, true) && !is_dir($path)) {
        throw new RuntimeException('Maillogmap niet beschikbaar.');
    }
    if (!is_dir($path)) {
        return null;
    }
    $real = realpath($path);
    $webroot = realpath(dirname(__DIR__));
    if (!$real || $real === $webroot || strpos($real, $webroot . DIRECTORY_SEPARATOR) === 0) {
        throw new RuntimeException('Maillog moet buiten de website staan.');
    }
    return $real;
}
// One calendar month, clamped at month end (31 March -> 28/29 February).
function mailLogCutoff($now = null) {
    $now = $now ?? new DateTimeImmutable('now', new DateTimeZone('Europe/Amsterdam'));
    $previous = $now->modify('first day of this month')->modify('-1 month');
    return $now->setDate(
        (int) $previous->format('Y'),
        (int) $previous->format('m'),
        min((int) $now->format('d'), (int) $previous->format('t'))
    );
}
function mailLogCleanup($directory, $now = null) {
    $cutoff = mailLogCutoff($now);
    $day = $cutoff->format('Y-m-d');
    foreach (glob($directory . '/*.jsonl') ?: [] as $path) {
        $name = basename($path, '.jsonl');
        if (!preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/D', $name) || $name > $day) {
            continue;
        }
        if (is_link($path)) {
            throw new RuntimeException('Ongeldig logbestand.');
        }
        // Old files never receive new entries: all writers append to today's file.
        if ($name < $day) {
            if (!@unlink($path) && is_file($path)) {
                throw new RuntimeException('Oude maillog kon niet worden verwijderd.');
            }
            continue;
        }
        $file = @fopen($path, 'r+b');
        if (!$file) {
            if (!is_file($path)) {
                continue;
            }
            throw new RuntimeException('Maillog niet beschikbaar.');
        }
        try {
            if (!flock($file, LOCK_EX)) {
                throw new RuntimeException('Maillog niet beschikbaar.');
            }
            $keep = '';
            $changed = false;
            while (($line = fgets($file)) !== false) {
                $entry = json_decode($line, true);
                if (!is_array($entry) || !isset($entry['date'])) {
                    throw new RuntimeException('Maillog bevat een onleesbare regel.');
                }
                if (new DateTimeImmutable($entry['date']) < $cutoff) {
                    $changed = true;
                    continue;
                }
                $keep .= $line;
            }
            if ($changed) {
                rewind($file);
                if (
                    !ftruncate($file, 0) ||
                    ($keep !== '' && fwrite($file, $keep) !== strlen($keep)) ||
                    !fflush($file)
                ) {
                    throw new RuntimeException('Maillog kon niet worden opgeschoond.');
                }
            }
        } finally {
            flock($file, LOCK_UN);
            fclose($file);
        }
    }
}
function mailLogRecord($to, $subject, $from, $message, $success, $local) {
    $module = 'overig';
    if (preg_match('/<body\b[^>]*\bdata-mail-module="(contact|verhuur)"/i', $message, $match)) {
        $module = strtolower($match[1]);
    }
    $entry = [
        'date' => gmdate('c'),
        'module' => $module,
        'to' => (string) $to,
        'from' => (string) $from,
        'subject' => (string) $subject,
        'status' => $success ? ($local ? 'local' : 'sent') : 'failed'
    ];
    $line = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    if ($line === false) {
        throw new RuntimeException('Maillog kon niet worden gecodeerd.');
    }
    $directory = mailLogDirectory(true);
    mailLogCleanup($directory);
    $path =
        $directory .
        '/' .
        (new DateTimeImmutable('now', new DateTimeZone('Europe/Amsterdam')))->format('Y-m-d') .
        '.jsonl';
    if (is_link($path)) {
        throw new RuntimeException('Ongeldig logbestand.');
    }
    $file = @fopen($path, 'ab');
    if (!$file) {
        throw new RuntimeException('Maillog niet beschrijfbaar.');
    }
    @chmod($path, 0600);
    try {
        if (!flock($file, LOCK_EX)) {
            throw new RuntimeException('Maillog niet beschikbaar.');
        }
        if (fwrite($file, $line . "\n") !== strlen($line) + 1 || !fflush($file)) {
            throw new RuntimeException('Maillog niet opgeslagen.');
        }
    } finally {
        flock($file, LOCK_UN);
        fclose($file);
    }
}
function mailLogFilters($input) {
    $filters = [];
    $today = new DateTimeImmutable('today', new DateTimeZone('Europe/Amsterdam'));
    foreach (
        [
            'from' => $today->modify('-30 days')->format('Y-m-d'),
            'until' => $today->format('Y-m-d'),
            'module' => '',
            'status' => '',
            'search' => '',
            'page' => '1'
        ]
        as $key => $default
    ) {
        $value = $input[$key] ?? $default;
        if (!is_string($value)) {
            throw new InvalidArgumentException('Ongeldige zoekfilters.');
        }
        $filters[$key] = trim($value);
    }
    foreach (['from', 'until'] as $key) {
        $date = DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $filters[$key],
            new DateTimeZone('UTC')
        );
        if (!$date || $date->format('Y-m-d') !== $filters[$key]) {
            throw new InvalidArgumentException('Kies geldige datums.');
        }
    }
    $days = (strtotime($filters['until']) - strtotime($filters['from'])) / 86400;
    if ($days < 0 || $days > 366) {
        throw new InvalidArgumentException('Kies een periode van maximaal één jaar.');
    }
    if (
        !in_array($filters['module'], ['', 'contact', 'verhuur', 'overig'], true) ||
        !in_array($filters['status'], ['', 'sent', 'local', 'failed'], true) ||
        strlen($filters['search']) > 254 ||
        !preg_match('/^[1-9][0-9]{0,4}$/D', $filters['page'])
    ) {
        throw new InvalidArgumentException('Ongeldige zoekfilters.');
    }
    return $filters;
}
function mailLogRead($filters) {
    $directory = mailLogDirectory();
    if (!$directory) {
        return ['rows' => [], 'more' => false];
    }
    mailLogCleanup($directory);
    $rows = [];
    $skip = ((int) $filters['page'] - 1) * 50;
    $date = new DateTimeImmutable($filters['until'], new DateTimeZone('UTC'));
    $start = new DateTimeImmutable($filters['from'], new DateTimeZone('UTC'));
    for (; $date >= $start; $date = $date->modify('-1 day')) {
        $path = $directory . '/' . $date->format('Y-m-d') . '.jsonl';
        if (!is_file($path)) {
            continue;
        }
        if (is_link($path)) {
            throw new RuntimeException('Ongeldig logbestand.');
        }
        $file = @fopen($path, 'rb');
        if (!$file) {
            throw new RuntimeException('Maillog kon niet worden gelezen.');
        }
        try {
            if (!flock($file, LOCK_SH)) {
                throw new RuntimeException('Maillog niet beschikbaar.');
            }
            $entries = [];
            while (($line = fgets($file)) !== false) {
                $entry = json_decode($line, true);
                if (
                    !is_array($entry) ||
                    count(
                        array_intersect(
                            ['date', 'module', 'to', 'from', 'subject', 'status'],
                            array_keys($entry)
                        )
                    ) !== 6
                ) {
                    throw new RuntimeException('Het maillog bevat een onleesbare regel.');
                }
                $entries[] = $entry;
            }
        } finally {
            flock($file, LOCK_UN);
            fclose($file);
        }
        foreach (array_reverse($entries) as $entry) {
            if ($filters['module'] !== '' && $entry['module'] !== $filters['module']) {
                continue;
            }
            if ($filters['status'] !== '' && $entry['status'] !== $filters['status']) {
                continue;
            }
            if (
                $filters['search'] !== '' &&
                stripos($entry['to'] . ' ' . $entry['subject'], $filters['search']) === false
            ) {
                continue;
            }
            if ($skip > 0) {
                $skip--;
                continue;
            }
            $rows[] = $entry;
            if (count($rows) > 50) {
                array_pop($rows);
                return ['rows' => $rows, 'more' => true];
            }
        }
    }
    return ['rows' => $rows, 'more' => false];
}
