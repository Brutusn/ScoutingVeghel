<?php
function maintenanceScopes() {
    return [
        'completed' => [
            'title' => 'Afgehandelde reserveringen',
            'description' =>
                'Verwijder oude reserveringen met de status Afgehandeld, inclusief bijbehorende opmerkingen.',
            'statuses' => [8]
        ],
        'cancelled' => [
            'title' => 'Geannuleerde en vervallen aanvragen',
            'description' =>
                'Ruim oude geannuleerde, vervallen, afgekeurde of als verwijderd gemarkeerde aanvragen op, inclusief opmerkingen.',
            'statuses' => [10, 11, 19, 20, 21]
        ],
        'tenants' => [
            'title' => 'Oude externe huurders',
            'description' =>
                'Verwijder externe huurders als al hun aanvragen zijn afgesloten en zowel de aanvraag- als einddatum vóór de datumgrens liggen. Oude verhuringen en opmerkingen gaan mee.',
            'statuses' => []
        ],
        'unused_tenants' => [
            'title' => 'Externe huurders zonder reserveringen',
            'description' =>
                'Verwijder externe huurders zonder gekoppelde reserveringen. Hun ouderdom is onbekend; de datumgrens geldt hier niet. Eigen groepen blijven behouden.',
            'statuses' => []
        ],
        'logs' => [
            'title' => 'Oude wijzigingslogs',
            'description' =>
                'Verwijder wijzigingsregistraties van vóór de gekozen datum. Reserveringen en opmerkingen blijven bestaan.',
            'statuses' => []
        ]
    ];
}
function maintenanceEscape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function maintenanceCutoff($value) {
    if (!is_string($value)) {
        throw new InvalidArgumentException('Kies een geldige datum.');
    }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    if (!$date || $date->format('Y-m-d') !== $value || $date > new DateTimeImmutable('today')) {
        throw new InvalidArgumentException('Kies een geldige datum die niet in de toekomst ligt.');
    }
    return $value;
}
function maintenanceIds($rows, $field = 'id') {
    return array_values(
        array_unique(
            array_map(function ($row) use ($field) {
                return (int) $row[$field];
            }, $rows)
        )
    );
}
function maintenanceSelectIds($db, $table, $ids, $field = 'id') {
    if (!$ids) {
        return [];
    }
    $allowed = ['verhuur_reservering', 'verhuur_huurder', 'verhuur_notes', 'verhuur_status'];
    if (
        !in_array($table, $allowed, true) ||
        !in_array($field, ['id', 'hoort_bij', 'status_id'], true)
    ) {
        throw new InvalidArgumentException('Invalid selection');
    }
    return $db
        ->query(
            'SELECT * FROM ' .
                $table .
                ' WHERE ' .
                $field .
                ' IN (' .
                implode(',', array_map('intval', $ids)) .
                ') ORDER BY ' .
                $field .
                ($field === 'hoort_bij' ? ',id' : '')
        )
        ->fetch_all(MYSQLI_ASSOC);
}
function maintenanceTenantScope($scope) {
    return in_array($scope, ['tenants', 'unused_tenants'], true);
}
function maintenanceUnit($scope) {
    return $scope === 'logs'
        ? 'wijzigingslogs'
        : (maintenanceTenantScope($scope)
            ? 'huurders'
            : 'verhuringen');
}
function maintenanceRentalSnapshot($db, $scope, $cutoff, $rentals, $tenants = null) {
    $reservations = maintenanceSelectIds(
        $db,
        'verhuur_reservering',
        maintenanceIds($rentals, 'reservering_id')
    );
    return [
        'scope' => $scope,
        'cutoff' => $cutoff,
        'rentals' => $rentals,
        'reservations' => $reservations,
        'tenants' =>
            $tenants === null
                ? maintenanceSelectIds(
                    $db,
                    'verhuur_huurder',
                    maintenanceIds($rentals, 'huurder_id')
                )
                : $tenants,
        'notes' => maintenanceSelectIds(
            $db,
            'verhuur_notes',
            maintenanceIds($rentals),
            'hoort_bij'
        ),
        'statuses' => maintenanceSelectIds(
            $db,
            'verhuur_status',
            maintenanceIds($reservations, 'status_id'),
            'status_id'
        )
    ];
}
function maintenanceLastUse($snapshot, $tenantId, $field = 'einddatum') {
    $reservations = array_column($snapshot['reservations'], null, 'id');
    $last = '';
    foreach ($snapshot['rentals'] as $rental) {
        if ((int) $rental['huurder_id'] !== (int) $tenantId) {
            continue;
        }
        $date =
            $field === 'datum'
                ? $rental['datum']
                : $reservations[$rental['reservering_id']]['einddatum'] ?? '';
        if ($date > $last) {
            $last = $date;
        }
    }
    return $last;
}
function maintenanceSnapshot($db, $scope, $cutoff) {
    $scopes = maintenanceScopes();
    if (!isset($scopes[$scope])) {
        throw new InvalidArgumentException('Kies een geldige opruimoptie.');
    }
    maintenanceCutoff($cutoff);
    if (maintenanceTenantScope($scope)) {
        $query = "SELECT verhuur_huurder.* FROM verhuur_huurder
            WHERE LOWER(TRIM(verhuur_huurder.naam)) <> 'scouting veghel'
            AND NOT EXISTS (SELECT 1 FROM verhuur_eigen_groep WHERE verhuur_eigen_groep.huurder_id=verhuur_huurder.id)";
        if ($scope === 'unused_tenants') {
            $query .=
                ' AND NOT EXISTS (SELECT 1 FROM verhuur_verhuring WHERE verhuur_verhuring.huurder_id=verhuur_huurder.id)';
        } else {
            $query .= " AND EXISTS (SELECT 1 FROM verhuur_verhuring WHERE verhuur_verhuring.huurder_id=verhuur_huurder.id)
                AND NOT EXISTS (SELECT 1 FROM verhuur_verhuring LEFT JOIN verhuur_reservering ON verhuur_reservering.id=verhuur_verhuring.reservering_id
                    WHERE verhuur_verhuring.huurder_id=verhuur_huurder.id AND (
                        verhuur_verhuring.groep IS NOT NULL AND TRIM(verhuur_verhuring.groep)<>''
                        OR verhuur_reservering.id IS NULL
                        OR verhuur_reservering.status_id NOT IN (8,10,11,19,20,21)
                        OR verhuur_reservering.einddatum <= '1000-01-01' OR verhuur_reservering.einddatum >= ?
                        OR verhuur_verhuring.datum <= '1000-01-01' OR verhuur_verhuring.datum >= ?))";
        }
        $query .= ' ORDER BY verhuur_huurder.id';
        $statement = $db->prepare($query);
        if ($scope === 'tenants') {
            $statement->bind_param('ss', $cutoff, $cutoff);
        }
        $statement->execute();
        $tenants = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();
        $ids = maintenanceIds($tenants);
        $rentals = $ids
            ? $db
                ->query(
                    'SELECT * FROM verhuur_verhuring WHERE huurder_id IN (' .
                        implode(',', $ids) .
                        ') ORDER BY id'
                )
                ->fetch_all(MYSQLI_ASSOC)
            : [];
        return maintenanceRentalSnapshot($db, $scope, $cutoff, $rentals, $tenants);
    }
    if ($scope === 'logs') {
        $statement = $db->prepare(
            'SELECT * FROM verhuur_mutaties WHERE datum > \'1000-01-01\' AND datum < ? ORDER BY id'
        );
        $statement->bind_param('s', $cutoff);
        $statement->execute();
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();
        return ['scope' => $scope, 'cutoff' => $cutoff, 'logs' => $rows];
    }
    $statuses = implode(',', $scopes[$scope]['statuses']);
    $statement = $db->prepare(
        'SELECT verhuur_verhuring.* FROM verhuur_verhuring JOIN verhuur_reservering ON verhuur_reservering.id=verhuur_verhuring.reservering_id WHERE verhuur_reservering.status_id IN (' .
            $statuses .
            ') AND verhuur_reservering.einddatum > \'1000-01-01\' AND verhuur_reservering.einddatum < ? ORDER BY verhuur_verhuring.id'
    );
    $statement->bind_param('s', $cutoff);
    $statement->execute();
    $rentals = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    $statement->close();
    return maintenanceRentalSnapshot($db, $scope, $cutoff, $rentals);
}
function maintenanceCount($snapshot) {
    return count(
        $snapshot[
            $snapshot['scope'] === 'logs'
                ? 'logs'
                : (maintenanceTenantScope($snapshot['scope'])
                    ? 'tenants'
                    : 'rentals')
        ]
    );
}
function maintenanceFingerprint($snapshot) {
    return hash('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR));
}
function maintenanceCsvValue($value) {
    $value = (string) ($value ?? '');
    // Spreadsheet programs must not interpret stored input as formulas.
    return preg_match('/^[\s\x00-\x20]*[=+\-@]|^[\t\r\n]/', $value) ? "'" . $value : $value;
}
function maintenanceCsv($snapshot) {
    $stream = fopen('php://temp/maxmemory:5242880', 'w+');
    fwrite($stream, "\xEF\xBB\xBF");
    if (maintenanceTenantScope($snapshot['scope'])) {
        $columns = [
            'id',
            'naam',
            'contactpersoon',
            'email',
            'telefoon',
            'adres',
            'postcode',
            'plaats',
            'ip'
        ];
        fputcsv(
            $stream,
            array_merge($columns, [
                'laatste_einddatum',
                'laatste_aanvraag',
                'verhuurhistorie_json',
                'opmerkingen_json'
            ]),
            ';',
            '"',
            ''
        );
        $reservations = array_column($snapshot['reservations'], null, 'id');
        foreach ($snapshot['tenants'] as $tenant) {
            $history = [];
            $ids = [];
            foreach ($snapshot['rentals'] as $rental) {
                if ((int) $rental['huurder_id'] === (int) $tenant['id']) {
                    $ids[] = (int) $rental['id'];
                    $rental['reservering'] = $reservations[$rental['reservering_id']] ?? null;
                    $history[] = $rental;
                }
            }
            $notes = array_values(
                array_filter($snapshot['notes'], function ($note) use ($ids) {
                    return in_array((int) $note['hoort_bij'], $ids, true);
                })
            );
            $row = array_map(function ($column) use ($tenant) {
                return $tenant[$column] ?? '';
            }, $columns);
            $row[] = maintenanceLastUse($snapshot, $tenant['id']);
            $row[] = maintenanceLastUse($snapshot, $tenant['id'], 'datum');
            $row[] = json_encode($history, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            $row[] = json_encode($notes, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            fputcsv($stream, array_map('maintenanceCsvValue', $row), ';', '"', '');
        }
    } elseif ($snapshot['scope'] === 'logs') {
        $columns = ['id', 'hoort_bij', 'naam', 'actie', 'datum', 'query'];
        fputcsv($stream, $columns, ';', '"', '');
        foreach ($snapshot['logs'] as $row) {
            fputcsv(
                $stream,
                array_map(function ($column) use ($row) {
                    return maintenanceCsvValue($row[$column]);
                }, $columns),
                ';',
                '"',
                ''
            );
        }
    } else {
        $reservations = array_column($snapshot['reservations'], null, 'id');
        $tenants = array_column($snapshot['tenants'], null, 'id');
        $statuses = array_column($snapshot['statuses'], 'status', 'status_id');
        $notes = [];
        foreach ($snapshot['notes'] as $note) {
            $notes[$note['hoort_bij']][] = $note;
        }
        fputcsv(
            $stream,
            [
                'verhuring_id',
                'reservering_id',
                'huurder_id',
                'huurder',
                'contactpersoon',
                'email',
                'telefoon',
                'adres',
                'postcode',
                'plaats',
                'eigen_groep',
                'begindatum',
                'einddatum',
                'personen',
                'beschrijving',
                'status_id',
                'status',
                'aangemaakt',
                'opmerkingen_json'
            ],
            ';',
            '"',
            ''
        );
        foreach ($snapshot['rentals'] as $rental) {
            $r = $reservations[$rental['reservering_id']];
            $h = $tenants[$rental['huurder_id']] ?? [];
            $row = [
                $rental['id'],
                $r['id'],
                $rental['huurder_id'],
                $h['naam'] ?? '',
                $h['contactpersoon'] ?? '',
                $h['email'] ?? '',
                $h['telefoon'] ?? '',
                $h['adres'] ?? '',
                $h['postcode'] ?? '',
                $h['plaats'] ?? '',
                $rental['groep'],
                $r['begindatum'],
                $r['einddatum'],
                $r['personen'],
                $r['beschrijving'],
                $r['status_id'],
                strip_tags($statuses[$r['status_id']] ?? ''),
                $rental['datum'],
                json_encode(
                    $notes[$rental['id']] ?? [],
                    JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
                )
            ];
            fputcsv($stream, array_map('maintenanceCsvValue', $row), ';', '"', '');
        }
    }
    rewind($stream);
    return $stream;
}
function maintenanceStorage() {
    $path = sys_get_temp_dir() . '/sv-maintenance-' . substr(hash('sha256', __DIR__), 0, 16);
    if (!is_dir($path) && !mkdir($path, 0700, true) && !is_dir($path)) {
        throw new RuntimeException('Momentopname opslaan mislukt.');
    }
    return $path;
}
function maintenanceSave($snapshot) {
    $token = bin2hex(random_bytes(24));
    $path = maintenanceStorage() . '/' . $token . '.json';
    $file = fopen($path, 'x');
    if (!$file) {
        throw new RuntimeException('Momentopname opslaan mislukt.');
    }
    chmod($path, 0600);
    $json = json_encode($snapshot, JSON_THROW_ON_ERROR);
    $ok = fwrite($file, $json) === strlen($json);
    fclose($file);
    if (!$ok) {
        throw new RuntimeException('Momentopname opslaan mislukt.');
    }
    // Temporary copies are private and expire after 24 hours.
    foreach (glob(maintenanceStorage() . '/*.json') as $old) {
        if (filemtime($old) < time() - 86400) {
            @unlink($old);
        }
    }
    return [
        'token' => $token,
        'hash' => maintenanceFingerprint($snapshot),
        'scope' => $snapshot['scope'],
        'cutoff' => $snapshot['cutoff'],
        'created' => time(),
        'exported' => false,
        'deleted' => false
    ];
}
function maintenanceLoad($preview, $token) {
    if (
        !is_array($preview) ||
        !is_string($token) ||
        !preg_match('/^[a-f0-9]{48}$/D', $token) ||
        !hash_equals($preview['token'], $token) ||
        $preview['created'] < time() - 1800
    ) {
        throw new InvalidArgumentException('De selectie is verlopen. Bekijk de selectie opnieuw.');
    }
    $json = @file_get_contents(maintenanceStorage() . '/' . $token . '.json');
    if ($json === false) {
        throw new InvalidArgumentException('De selectie is verlopen. Bekijk de selectie opnieuw.');
    }
    $snapshot = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    if (!hash_equals($preview['hash'], maintenanceFingerprint($snapshot))) {
        throw new RuntimeException('De momentopname klopt niet. Maak een nieuwe selectie.');
    }
    return $snapshot;
}
function maintenanceDelete($db, $preview, $snapshot) {
    require_once __DIR__ . '/../php/privacy/service.php';
    if (privacyEnabled()) {
        throw new RuntimeException('Gebruik de nieuwe privacy-opschoning.');
    }
    if (
        empty($preview['exported']) ||
        !empty($preview['deleted']) ||
        !hash_equals($preview['hash'], maintenanceFingerprint($snapshot))
    ) {
        throw new InvalidArgumentException('Download eerst de CSV van deze selectie.');
    }
    // The legacy tables use MyISAM. Explicit locks prevent a selection from changing during cleanup.
    $db->query(
        'LOCK TABLES verhuur_verhuring WRITE, verhuur_reservering WRITE, verhuur_notes WRITE, verhuur_mutaties WRITE, verhuur_huurder WRITE, verhuur_status READ, verhuur_eigen_groep READ'
    );
    try {
        $fresh = maintenanceSnapshot($db, $snapshot['scope'], $snapshot['cutoff']);
        if (!hash_equals(maintenanceFingerprint($snapshot), maintenanceFingerprint($fresh))) {
            throw new InvalidArgumentException(
                'De gegevens zijn gewijzigd. Bekijk de selectie opnieuw en download een nieuwe CSV.'
            );
        }
        $count = maintenanceCount($snapshot);
        if ($snapshot['scope'] === 'logs') {
            $ids = maintenanceIds($snapshot['logs']);
            if ($ids) {
                $db->query('DELETE FROM verhuur_mutaties WHERE id IN (' . implode(',', $ids) . ')');
            }
        } else {
            $ids = maintenanceIds($snapshot['rentals']);
            if ($ids) {
                $list = implode(',', $ids);
                $db->query('DELETE FROM verhuur_notes WHERE hoort_bij IN (' . $list . ')');
                $db->query('DELETE FROM verhuur_verhuring WHERE id IN (' . $list . ')');
                $reservationIds = implode(',', maintenanceIds($snapshot['reservations']));
                $db->query(
                    'DELETE FROM verhuur_reservering WHERE id IN (' .
                        $reservationIds .
                        ') AND NOT EXISTS (SELECT 1 FROM verhuur_verhuring WHERE verhuur_verhuring.reservering_id=verhuur_reservering.id)'
                );
            }
        }
        if (maintenanceTenantScope($snapshot['scope']) && $snapshot['tenants']) {
            $tenantIds = implode(',', maintenanceIds($snapshot['tenants']));
            $db->query(
                'DELETE FROM verhuur_huurder WHERE id IN (' .
                    $tenantIds .
                    ') AND NOT EXISTS (SELECT 1 FROM verhuur_verhuring WHERE verhuur_verhuring.huurder_id=verhuur_huurder.id) AND NOT EXISTS (SELECT 1 FROM verhuur_eigen_groep WHERE verhuur_eigen_groep.huurder_id=verhuur_huurder.id)'
            );
            if ($db->affected_rows !== count($snapshot['tenants'])) {
                throw new RuntimeException('Niet alle huurders konden worden verwijderd.');
            }
        }
    } finally {
        $db->query('UNLOCK TABLES');
    }
    return $count;
}
