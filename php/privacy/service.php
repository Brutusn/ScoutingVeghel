<?php
// Shared ownership and retention rules. Never enable online implicitly.
function privacyConfig() {
    $defaults = [
        'enabled' => getenv('SV_ENV') === 'local',
        'placeholder_id' => 0,
        'personal_days' => null,
        'booking_days' => null,
        'retention_basis' => null,
        'automatic' => false,
        'disabled_groups' => []
    ];
    $path = dirname(__DIR__, 3) . '/private/privacy.php';
    $config = is_file($path) ? require $path : [];
    if (!is_array($config)) {
        throw new RuntimeException('Ongeldige privacyconfiguratie.');
    }
    return array_replace($defaults, $config);
}
function privacyEnabled() {
    return privacyConfig()['enabled'] === true;
}
function privacyQuery($db, $sql, $types = '', $values = []) {
    $s = $db->prepare($sql);
    if ($types !== '') {
        $s->bind_param($types, ...$values);
    }
    $s->execute();
    privacyMeta($db, ['insert_id' => (int) $s->insert_id, 'affected_rows' => $s->affected_rows]);
    $result = $s->get_result();
    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $s->close();
    return $rows;
}
function privacyMeta($db, $value = null) {
    static $metadata = [];
    $key = spl_object_id($db);
    if ($value !== null) {
        $metadata[$key] = $value;
    }
    return $metadata[$key] ?? ['insert_id' => 0, 'affected_rows' => 0];
}
function privacyLock($db, $callback) {
    static $depth = [];
    $key = spl_object_id($db);
    if (!empty($depth[$key])) {
        return $callback();
    }
    // Actual table locks also exclude legacy/non-cooperating writers on MyISAM.
    $db->query(
        'LOCK TABLES verhuur_huurder WRITE, verhuur_verhuring WRITE, verhuur_reservering WRITE, verhuur_eigen_groep WRITE, verhuur_notes WRITE, verhuur_mutaties WRITE, verhuur_status READ'
    );
    $depth[$key] = 1;
    try {
        return $callback();
    } finally {
        unset($depth[$key]);
        $db->query('UNLOCK TABLES');
    }
}
function privacyProtected($db, $id, $config = null) {
    $config = $config ?? privacyConfig();
    return (int) $id === (int) $config['placeholder_id'] ||
        count(
            privacyQuery(
                $db,
                'SELECT huurder_id FROM verhuur_eigen_groep WHERE huurder_id=?',
                'i',
                [(int) $id]
            )
        ) > 0;
}
function privacyAudit(
    $db,
    $id,
    $action,
    $summary = 'Privacybewerking uitgevoerd',
    $actor = 'systeem'
) {
    $details = json_encode(['summary' => $summary], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    privacyQuery(
        $db,
        'INSERT INTO verhuur_mutaties (hoort_bij,naam,actie,datum,`query`) VALUES (?, ?, ?, NOW(), ?)',
        'isss',
        [(int) $id, $actor, $action, $details]
    );
}
function privacyDate($value) {
    if (!is_string($value)) {
        throw new InvalidArgumentException('Ongeldige datum.');
    }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value);
    if (!$date || $date->format('Y-m-d H:i:s') !== $value || $date->format('Y') < '1900') {
        throw new InvalidArgumentException('Ongeldige datum.');
    }
    return $date;
}
function privacyCreate(
    $db,
    $contact,
    $start,
    $end,
    $people,
    $description,
    $groupId = 0,
    $status = 0
) {
    if (!privacyEnabled()) {
        throw new RuntimeException('Privacyflow is niet ingeschakeld.');
    }
    if (privacyDate($start) >= privacyDate($end)) {
        throw new InvalidArgumentException('De einddatum moet na de begindatum liggen.');
    }
    if (
        filter_var($people, FILTER_VALIDATE_INT) === false ||
        (int) $people < 1 ||
        (int) $people > 10000 ||
        !is_string($description) ||
        trim($description) === ''
    ) {
        throw new InvalidArgumentException('Controleer personen en beschrijving.');
    }
    if (
        !privacyQuery($db, 'SELECT status_id FROM verhuur_status WHERE status_id=?', 'i', [
            (int) $status
        ])
    ) {
        throw new InvalidArgumentException('Ongeldige status.');
    }
    $fields = ['naam', 'contactpersoon', 'email', 'telefoon', 'adres', 'postcode', 'plaats', 'ip'];
    $values = [];
    foreach ($fields as $field) {
        $value = $contact[$field] ?? '';
        if (!is_string($value) || strlen($value) > 2000) {
            throw new InvalidArgumentException('Ongeldige contactgegevens.');
        }
        $values[] = trim($value);
    }
    if (
        !$groupId &&
        (in_array('', array_slice($values, 0, 7), true) ||
            !filter_var($values[2], FILTER_VALIDATE_EMAIL))
    ) {
        throw new InvalidArgumentException('Vul de contactgegevens volledig in.');
    }
    return privacyLock($db, function () use (
        $db,
        $values,
        $start,
        $end,
        $people,
        $description,
        $groupId,
        $status
    ) {
        if ($groupId) {
            $config = privacyConfig();
            if (
                (int) $groupId === (int) $config['placeholder_id'] ||
                in_array((int) $groupId, array_map('intval', $config['disabled_groups']), true)
            ) {
                throw new InvalidArgumentException('Deze groep is niet beschikbaar.');
            }
            if (
                !privacyQuery(
                    $db,
                    'SELECT huurder_id FROM verhuur_eigen_groep WHERE huurder_id=?',
                    'i',
                    [(int) $groupId]
                )
            ) {
                throw new InvalidArgumentException('Kies een eigen groep.');
            }
        }
        $token = bin2hex(random_bytes(32));
        $args = array_merge($values, [
            (int) $groupId,
            $start,
            $end,
            (int) $people,
            $description,
            (int) $status,
            $token
        ]);
        $s = $db->prepare('CALL PrivacyCreateBooking(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $s->bind_param('ssssssssissisis', ...$args);
        $s->execute();
        $row = $s->get_result()->fetch_assoc();
        while ($s->more_results()) {
            $s->next_result();
            $extra = $s->get_result();
            if ($extra) {
                $extra->free();
            }
        }
        $s->close();
        if (!$row || (int) $row['id'] <= 0) {
            throw new RuntimeException('Aanvraag kon niet worden opgeslagen.');
        }
        return $row;
    });
}
function privacyPreview($db, $config = null) {
    $config = $config ?? privacyConfig();
    $rows = $db
        ->query(
            'SELECT id,huurder_id,reservering_id,datum,`groep` FROM verhuur_verhuring ORDER BY id'
        )
        ->fetch_all(MYSQLI_ASSOC);
    $tenants = array_column(
        $db->query('SELECT id FROM verhuur_huurder')->fetch_all(MYSQLI_ASSOC),
        'id'
    );
    $reservations = array_column(
        $db->query('SELECT id FROM verhuur_reservering')->fetch_all(MYSQLI_ASSOC),
        'id'
    );
    $protected = array_column(
        $db->query('SELECT huurder_id FROM verhuur_eigen_groep')->fetch_all(MYSQLI_ASSOC),
        'huurder_id'
    );
    $protected[] = (int) $config['placeholder_id'];
    $tenantUses = array_count_values(array_column($rows, 'huurder_id'));
    $reservationUses = array_count_values(array_column($rows, 'reservering_id'));
    $result = [
        'split' => [],
        'missing_tenant' => [],
        'missing_reservation' => [],
        'shared_reservation' => [],
        'historical_group' => [],
        'ambiguous_audit' => [],
        'unused_tenant' => array_values(
            array_map('intval', array_diff($tenants, array_keys($tenantUses), $protected))
        ),
        'unused_reservation' => array_values(
            array_map('intval', array_diff($reservations, array_keys($reservationUses)))
        )
    ];
    $seen = [];
    foreach ($rows as $r) {
        $h = (int) $r['huurder_id'];
        $rid = (int) $r['reservering_id'];
        $id = (int) $r['id'];
        $valid = true;
        if (privacyLegacyAuditConflict($db, $r)) {
            $result['ambiguous_audit'][] = $id;
        }
        if (!in_array($h, $tenants)) {
            $result['missing_tenant'][] = $id;
            $valid = false;
        }
        if (!in_array($rid, $reservations)) {
            $result['missing_reservation'][] = $id;
            $valid = false;
        }
        if (($reservationUses[$rid] ?? 0) > 1) {
            $result['shared_reservation'][] = $id;
            $valid = false;
        }
        if (in_array($h, $protected)) {
            continue;
        }
        if (
            trim((string) $r['groep']) !== '' &&
            strtoupper(trim((string) $r['groep'])) !== 'NULL'
        ) {
            $result['historical_group'][] = $id;
            $valid = false;
        }
        // Keep the original with the first rental, including an incomplete first rental.
        $first = !isset($seen[$h]);
        $seen[$h] = true;
        if ($valid && !$first && ($tenantUses[$h] ?? 0) > 1) {
            $result['split'][] = ['id' => $id, 'huurder_id' => $h, 'reservering_id' => $rid];
        }
    }
    return $result;
}
function privacyFingerprint($value) {
    return hash('sha256', json_encode($value, JSON_THROW_ON_ERROR));
}
function privacyMigrate($db, $expected, $config = null) {
    return privacyLock($db, function () use ($db, $expected, $config) {
        $plan = privacyPreview($db, $config);
        if (!hash_equals($expected, privacyFingerprint($plan))) {
            throw new RuntimeException('Preview is gewijzigd; maak een nieuwe preview.');
        }
        $count = 0;
        foreach ($plan['split'] as $r) {
            $id = (int) $r['id'];
            $old = (int) $r['huurder_id'];
            privacyCloneTenant($db, $old);
            $new = privacyMeta($db)['insert_id'];
            try {
                privacyQuery(
                    $db,
                    'UPDATE verhuur_verhuring SET huurder_id=? WHERE id=? AND huurder_id=?',
                    'iii',
                    [$new, $id, $old]
                );
                if (privacyMeta($db)['affected_rows'] !== 1) {
                    throw new RuntimeException('Koppeling is gewijzigd.');
                }
            } catch (Throwable $e) {
                privacyQuery($db, 'DELETE FROM verhuur_huurder WHERE id=?', 'i', [$new]);
                throw $e;
            }
            // Keep rental ID, reservation ID and the already mailed token unchanged.
            $count++;
        }
        return $count;
    });
}
function privacyEligible($db, $kind, $config = null, $now = null) {
    $config = $config ?? privacyConfig();
    $now = $now ?? new DateTimeImmutable('now');
    if (!in_array($kind, ['personal', 'booking'], true)) {
        throw new InvalidArgumentException('Ongeldige opschoningsactie.');
    }
    $days = $config[$kind . '_days'];
    if ($days === null || $config['retention_basis'] === null) {
        return [];
    }
    if ($config['retention_basis'] !== 'end_date_closed') {
        throw new RuntimeException('Onbekend startmoment voor bewaartermijnen.');
    }
    if (!is_int($days) || $days < 1) {
        throw new RuntimeException('Bewaartermijn moet een positief aantal dagen zijn.');
    }
    $cutoff = $now->modify('-' . $days . ' days')->format('Y-m-d H:i:s');
    $rows = $db
        ->query(
            'SELECT verhuur_verhuring.id,verhuur_verhuring.huurder_id,verhuur_verhuring.reservering_id,verhuur_verhuring.confirm,verhuur_verhuring.groep,verhuur_reservering.einddatum,verhuur_reservering.status_id FROM verhuur_verhuring JOIN verhuur_reservering ON verhuur_reservering.id=verhuur_verhuring.reservering_id JOIN verhuur_huurder ON verhuur_huurder.id=verhuur_verhuring.huurder_id ORDER BY verhuur_verhuring.id'
        )
        ->fetch_all(MYSQLI_ASSOC);
    $result = [];
    foreach ($rows as $row) {
        $h = (int) $row['huurder_id'];
        $rid = (int) $row['reservering_id'];
        $placeholder = $h === (int) $config['placeholder_id'];
        if (!in_array((int) $row['status_id'], [8, 10, 11, 19, 20, 21], true)) {
            continue;
        }
        try {
            privacyDate($row['einddatum']);
        } catch (Throwable $e) {
            continue;
        }
        if ($row['einddatum'] >= $cutoff || $row['einddatum'] >= $now->format('Y-m-d H:i:s')) {
            continue;
        }
        if (!$placeholder && privacyProtected($db, $h, $config)) {
            continue;
        }
        if (
            !$placeholder &&
            trim((string) $row['groep']) !== '' &&
            strtoupper(trim((string) $row['groep'])) !== 'NULL'
        ) {
            continue;
        }
        if ($kind === 'personal' && $placeholder) {
            continue;
        }
        if (privacyLegacyAuditConflict($db, $row)) {
            continue;
        }
        if (
            !$placeholder &&
            (int) privacyQuery(
                $db,
                'SELECT COUNT(*) n FROM verhuur_verhuring WHERE huurder_id=?',
                'i',
                [$h]
            )[0]['n'] !== 1
        ) {
            continue;
        }
        if (
            (int) privacyQuery(
                $db,
                'SELECT COUNT(*) n FROM verhuur_verhuring WHERE reservering_id=?',
                'i',
                [$rid]
            )[0]['n'] !== 1
        ) {
            continue;
        }
        $result[] = [
            'id' => (int) $row['id'],
            'huurder_id' => $h,
            'reservering_id' => $rid,
            'einddatum' => $row['einddatum'],
            'status_id' => (int) $row['status_id']
        ];
    }
    return $result;
}
function privacyCleanup($db, $kind, $expected, $config = null) {
    $config = $config ?? privacyConfig();
    return privacyLock($db, function () use ($db, $kind, $expected, $config) {
        $rows = privacyEligible($db, $kind, $config);
        if (!hash_equals($expected, privacyFingerprint($rows))) {
            throw new RuntimeException('Selectie gewijzigd; bekijk de selectie opnieuw.');
        }
        $placeholder = (int) $config['placeholder_id'];
        if (
            $placeholder <= 0 ||
            !privacyQuery(
                $db,
                "SELECT id FROM verhuur_huurder WHERE id=? AND naam='Verwijderde huurder' AND COALESCE(contactpersoon,'')='' AND email='' AND telefoon='' AND adres='' AND postcode='' AND plaats='' AND COALESCE(ip,'')=''",
                'i',
                [$placeholder]
            )
        ) {
            throw new RuntimeException('Configureer eerst een lege placeholder.');
        }
        if (
            privacyQuery(
                $db,
                'SELECT huurder_id FROM verhuur_eigen_groep WHERE huurder_id=?',
                'i',
                [$placeholder]
            )
        ) {
            throw new RuntimeException('Placeholder mag geen eigen groep zijn.');
        }
        foreach ($rows as $r) {
            $id = $r['id'];
            $h = $r['huurder_id'];
            $rid = $r['reservering_id'];
            // Invalidate public links first. A failure leaves a protected, retryable partial step.
            privacyQuery($db, "UPDATE verhuur_verhuring SET confirm='' WHERE id=?", 'i', [$id]);
            privacyQuery($db, 'DELETE FROM verhuur_notes WHERE hoort_bij=?', 'i', [$id]);
            privacyQuery($db, 'DELETE FROM verhuur_mutaties WHERE hoort_bij=?', 'i', [$id]);
            if ($rid !== $id) {
                privacyQuery(
                    $db,
                    'DELETE FROM verhuur_mutaties WHERE hoort_bij=? AND NOT JSON_VALID(`query`)',
                    'i',
                    [$rid]
                );
            }
            privacyQuery($db, "UPDATE verhuur_reservering SET beschrijving='' WHERE id=?", 'i', [
                $rid
            ]);
            if ($kind === 'booking') {
                privacyQuery($db, 'DELETE FROM verhuur_verhuring WHERE id=?', 'i', [$id]);
                privacyQuery($db, 'DELETE FROM verhuur_reservering WHERE id=?', 'i', [$rid]);
            } else {
                privacyQuery(
                    $db,
                    'UPDATE verhuur_verhuring SET huurder_id=?, groep=NULL WHERE id=?',
                    'ii',
                    [$placeholder, $id]
                );
            }
            if ($h !== $placeholder) {
                privacyQuery($db, 'DELETE FROM verhuur_huurder WHERE id=?', 'i', [$h]);
            }
        }
        // Counts only: no former tenant IDs, tokens, free text or user names.
        if ($rows) {
            privacyAudit($db, 0, 'PRIVACY_' . strtoupper($kind) . '_' . count($rows));
        }
        return count($rows);
    });
}
function privacyBackup($db) {
    $directory = dirname(__DIR__, 3) . '/private/privacy-backups';
    if (!is_dir($directory) && !mkdir($directory, 0700, true)) {
        throw new RuntimeException('Back-upmap niet beschikbaar.');
    }
    chmod($directory, 0700);
    $path = $directory . '/' . gmdate('Ymd-His') . '-' . bin2hex(random_bytes(8)) . '.json';
    $data = [];
    foreach (
        [
            'verhuur_huurder',
            'verhuur_reservering',
            'verhuur_verhuring',
            'verhuur_eigen_groep',
            'verhuur_notes',
            'verhuur_mutaties'
        ]
        as $t
    ) {
        $data[$t] = $db->query('SELECT * FROM ' . $t)->fetch_all(MYSQLI_ASSOC);
    }
    $f = fopen($path, 'x');
    if (!$f) {
        throw new RuntimeException('Back-up opslaan mislukt.');
    }
    chmod($path, 0600);
    $json = json_encode($data, JSON_THROW_ON_ERROR);
    try {
        if (fwrite($f, $json) !== strlen($json)) {
            throw new RuntimeException('Back-up onvolledig.');
        }
    } finally {
        fclose($f);
    }
    return basename($path);
}
function privacyEnsurePlaceholder($db) {
    return privacyLock($db, function () use ($db) {
        $config = privacyConfig();
        if ((int) $config['placeholder_id'] > 0) {
            return (int) $config['placeholder_id'];
        }
        privacyQuery(
            $db,
            "INSERT INTO verhuur_huurder (naam,contactpersoon,email,telefoon,adres,postcode,plaats,ip) VALUES ('Verwijderde huurder','','','','','','','')"
        );
        $id = privacyMeta($db)['insert_id'];
        $config['placeholder_id'] = $id;
        $path = dirname(__DIR__, 3) . '/private/privacy.php';
        $tmp = $path . '.' . bin2hex(random_bytes(8));
        $f = fopen($tmp, 'x');
        if (!$f) {
            throw new RuntimeException('Configuratie opslaan mislukt.');
        }
        chmod($tmp, 0600);
        fwrite($f, "<?php\nreturn " . var_export($config, true) . ";\n");
        fclose($f);
        if (!rename($tmp, $path)) {
            throw new RuntimeException('Configuratie opslaan mislukt.');
        }
        return $id;
    });
}
function privacySaveGroup($db, $id, $name, $code, $email) {
    if (
        !is_string($name) ||
        trim($name) === '' ||
        strlen($name) > 200 ||
        !is_string($code) ||
        (strlen($code) < 16 &&
            (!$id ||
                !privacyQuery(
                    $db,
                    'SELECT huurder_id FROM verhuur_eigen_groep WHERE huurder_id=? AND code=?',
                    'is',
                    [(int) $id, $code]
                ))) ||
        !preg_match('/^[a-zA-Z0-9_-]+$/D', $code) ||
        !filter_var($email, FILTER_VALIDATE_EMAIL)
    ) {
        throw new InvalidArgumentException(
            'Vul een naam, groepsmailadres en code van minstens 16 letters/cijfers in.'
        );
    }
    return privacyLock($db, function () use ($db, $id, $name, $code, $email) {
        if ((int) $id === (int) privacyConfig()['placeholder_id'] && (int) $id > 0) {
            throw new InvalidArgumentException('Dit is de placeholder.');
        }
        if (
            privacyQuery(
                $db,
                'SELECT huurder_id FROM verhuur_eigen_groep WHERE code=? AND huurder_id<>?',
                'si',
                [$code, (int) $id]
            )
        ) {
            throw new InvalidArgumentException('Deze code is al in gebruik.');
        }
        if ($id) {
            if (
                !privacyQuery(
                    $db,
                    'SELECT huurder_id FROM verhuur_eigen_groep WHERE huurder_id=?',
                    'i',
                    [(int) $id]
                )
            ) {
                throw new InvalidArgumentException('Onbekende eigen groep.');
            }
            privacyQuery(
                $db,
                "UPDATE verhuur_huurder SET naam=?,email=?,contactpersoon='',telefoon='',adres='',postcode='',plaats='',ip='' WHERE id=?",
                'ssi',
                [trim($name), $email, (int) $id]
            );
            privacyQuery($db, 'UPDATE verhuur_eigen_groep SET code=? WHERE huurder_id=?', 'si', [
                $code,
                (int) $id
            ]);
        } else {
            privacyQuery(
                $db,
                "INSERT INTO verhuur_huurder (naam,email,telefoon,adres,postcode,plaats) VALUES (?,?,'','','','')",
                'ss',
                [trim($name), $email]
            );
            $id = privacyMeta($db)['insert_id'];
            try {
                privacyQuery(
                    $db,
                    'INSERT INTO verhuur_eigen_groep (code,huurder_id) VALUES (?,?)',
                    'si',
                    [$code, $id]
                );
            } catch (Throwable $e) {
                privacyQuery($db, 'DELETE FROM verhuur_huurder WHERE id=?', 'i', [$id]);
                throw $e;
            }
        }
        return $id;
    });
}
function privacyCreateSeries($db, $contact, $dates, $people, $description, $groupId, $status) {
    if (!$dates || count($dates) > 50) {
        throw new InvalidArgumentException('Een reeks bevat maximaal 50 verhuringen.');
    }
    foreach ($dates as $date) {
        if (privacyDate($date[0]) >= privacyDate($date[1])) {
            throw new InvalidArgumentException('Ongeldige periode in reeks.');
        }
    }
    return privacyLock($db, function () use (
        $db,
        $contact,
        $dates,
        $people,
        $description,
        $groupId,
        $status
    ) {
        $created = [];
        try {
            foreach ($dates as $date) {
                $created[] = privacyCreate(
                    $db,
                    $contact,
                    $date[0],
                    $date[1],
                    $people,
                    $description,
                    $groupId,
                    $status
                );
            }
        } catch (Throwable $e) {
            foreach (array_reverse($created) as $r) {
                privacyQuery($db, 'DELETE FROM verhuur_mutaties WHERE hoort_bij=?', 'i', [
                    (int) $r['id']
                ]);
                privacyQuery($db, 'DELETE FROM verhuur_verhuring WHERE id=?', 'i', [
                    (int) $r['id']
                ]);
                privacyQuery($db, 'DELETE FROM verhuur_reservering WHERE id=?', 'i', [
                    (int) $r['reservering_id']
                ]);
                if (!$groupId) {
                    privacyQuery($db, 'DELETE FROM verhuur_huurder WHERE id=?', 'i', [
                        (int) $r['huurder_id']
                    ]);
                }
            }
            throw $e;
        }
        return $created;
    });
}
function privacyRental($db, $id, $allowMissingTenant = false) {
    if (!is_scalar($id) || !ctype_digit((string) $id) || (int) $id < 1) {
        throw new InvalidArgumentException('Ongeldige verhuring.');
    }
    $rows = privacyQuery($db, 'SELECT * FROM verhuur_verhuring WHERE id=?', 'i', [(int) $id]);
    if (count($rows) !== 1) {
        throw new InvalidArgumentException('Verhuring niet gevonden.');
    }
    $r = $rows[0];
    if (
        !privacyQuery($db, 'SELECT id FROM verhuur_reservering WHERE id=?', 'i', [
            (int) $r['reservering_id']
        ])
    ) {
        throw new InvalidArgumentException(
            'De reservering ontbreekt. Deze koppeling moet eerst worden hersteld.'
        );
    }
    if (
        !$allowMissingTenant &&
        !privacyQuery($db, 'SELECT id FROM verhuur_huurder WHERE id=?', 'i', [
            (int) $r['huurder_id']
        ])
    ) {
        throw new InvalidArgumentException(
            'De huurder ontbreekt. Kies bij Reservering wijzigen een bestaande eigen groep om deze koppeling te herstellen.'
        );
    }
    if (
        (int) privacyQuery(
            $db,
            'SELECT COUNT(*) n FROM verhuur_verhuring WHERE reservering_id=?',
            'i',
            [(int) $r['reservering_id']]
        )[0]['n'] !== 1
    ) {
        throw new InvalidArgumentException(
            'Deze reservering wordt gedeeld; beoordeel eerst de migratiepreview.'
        );
    }
    if (
        (int) privacyConfig()['placeholder_id'] > 0 &&
        (int) $r['huurder_id'] === (int) privacyConfig()['placeholder_id']
    ) {
        throw new InvalidArgumentException(
            'Deze verhuring is geanonimiseerd en kan niet opnieuw van persoonsgegevens worden voorzien.'
        );
    }
    return $r;
}
function privacyEdit($db, $kind, $input) {
    return privacyLock($db, function () use ($db, $kind, $input) {
        $r = privacyRental($db, $input['i'] ?? '', $kind === 'group');
        $id = (int) $r['id'];
        $h = (int) $r['huurder_id'];
        $rid = (int) $r['reservering_id'];
        if ((int) ($input['reservering_id'] ?? 0) !== $rid) {
            throw new InvalidArgumentException('Koppeling gewijzigd. Ververs de pagina.');
        }
        $actor =
            isset($_SESSION['ADMIN_NAME']) &&
            is_string($_SESSION['ADMIN_NAME']) &&
            ($_SESSION['ADMIN_LOGGED_IN'] ?? false) === true
                ? $_SESSION['ADMIN_NAME']
                : 'systeem';
        $summary = '';
        $result = null;
        if ($kind === 'group') {
            if ((int) ($input['huurder_id'] ?? 0) !== $h) {
                throw new InvalidArgumentException('De huurder is gewijzigd. Ververs de pagina.');
            }
            $target = filter_var($input['group_id'] ?? '', FILTER_VALIDATE_INT);
            if (
                $target === false ||
                $target < 1 ||
                $target === (int) privacyConfig()['placeholder_id']
            ) {
                throw new InvalidArgumentException('Kies een geldige eigen groep.');
            }
            $group = privacyQuery($db, 'SELECT id,naam FROM verhuur_huurder WHERE id=?', 'i', [
                $target
            ]);
            if (
                !$group ||
                !privacyQuery(
                    $db,
                    'SELECT huurder_id FROM verhuur_eigen_groep WHERE huurder_id=?',
                    'i',
                    [$target]
                )
            ) {
                throw new InvalidArgumentException('Kies een bestaande eigen groep.');
            }
            $name = $group[0]['naam'];
            if ($target === $h && (string) $r['groep'] === $name) {
                return;
            }
            if (
                $target !== $h &&
                in_array($target, array_map('intval', privacyConfig()['disabled_groups']), true)
            ) {
                throw new InvalidArgumentException('Deze eigen groep is niet actief.');
            }
            // Preserve a recoverable copy before replacing ownership or dropping a sole snapshot.
            $backup = privacyBackup($db);
            $protected = privacyProtected($db, $h);
            privacyQuery(
                $db,
                "UPDATE verhuur_verhuring SET huurder_id=?,groep=?,confirm='' WHERE id=?",
                'isi',
                [$target, $name, $id]
            );
            if (
                !$protected &&
                !privacyQuery(
                    $db,
                    'SELECT id FROM verhuur_verhuring WHERE huurder_id=? LIMIT 1',
                    'i',
                    [$h]
                )
            ) {
                privacyQuery($db, 'DELETE FROM verhuur_huurder WHERE id=?', 'i', [$h]);
            }
            $summary =
                'Verhuring omgezet naar eigen groep: ' .
                $name .
                '. Oude bevestigingslinks ongeldig gemaakt.';
            $result = ['backup' => $backup];
        } elseif ($kind === 'contact') {
            if ((int) ($input['huurder_id'] ?? 0) !== $h) {
                throw new InvalidArgumentException('Huurder gewijzigd. Ververs de pagina.');
            }
            if (privacyProtected($db, $h)) {
                throw new InvalidArgumentException('Bewerk eigen groepen via Eigen groepen.');
            }
            if (
                trim((string) $r['groep']) !== '' &&
                strtoupper(trim((string) $r['groep'])) !== 'NULL'
            ) {
                throw new InvalidArgumentException(
                    'Beoordeel eerst deze historische groepskoppeling.'
                );
            }
            $values = [];
            foreach (
                ['naam', 'contactpersoon', 'email', 'telefoon', 'adres', 'postcode', 'plaats']
                as $field
            ) {
                if (
                    !isset($input[$field]) ||
                    !is_string($input[$field]) ||
                    trim($input[$field]) === '' ||
                    strlen($input[$field]) > 2000
                ) {
                    throw new InvalidArgumentException('Vul de contactgegevens volledig in.');
                }
                $values[] = trim($input[$field]);
            }
            if (!filter_var($values[2], FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Ongeldig e-mailadres.');
            }
            $previous = privacyQuery(
                $db,
                'SELECT naam,contactpersoon,email,telefoon,adres,postcode,plaats FROM verhuur_huurder WHERE id=?',
                'i',
                [$h]
            )[0];
            $changed = [];
            $labels = [
                'naam' => 'Naam',
                'contactpersoon' => 'Contactpersoon',
                'email' => 'E-mailadres',
                'telefoon' => 'Telefoon',
                'adres' => 'Adres',
                'postcode' => 'Postcode',
                'plaats' => 'Plaats'
            ];
            $index = 0;
            foreach ($labels as $field => $label) {
                if ((string) $previous[$field] !== $values[$index]) {
                    $changed[] = $label;
                }
                $index++;
            }
            if (!$changed) {
                return;
            }
            $summary =
                'Contactgegevens gewijzigd. Aangepaste velden: ' . implode(', ', $changed) . '.';

            if (
                (int) privacyQuery(
                    $db,
                    'SELECT COUNT(*) n FROM verhuur_verhuring WHERE huurder_id=?',
                    'i',
                    [$h]
                )[0]['n'] > 1
            ) {
                privacyCloneTenant($db, $h);
                $new = privacyMeta($db)['insert_id'];
                try {
                    privacyQuery(
                        $db,
                        'UPDATE verhuur_verhuring SET huurder_id=? WHERE id=?',
                        'ii',
                        [$new, $id]
                    );
                } catch (Throwable $e) {
                    privacyQuery($db, 'DELETE FROM verhuur_huurder WHERE id=?', 'i', [$new]);
                    throw $e;
                }
                $h = $new;
            }
            $values[] = $h;
            privacyQuery(
                $db,
                'UPDATE verhuur_huurder SET naam=?,contactpersoon=?,email=?,telefoon=?,adres=?,postcode=?,plaats=? WHERE id=?',
                'sssssssi',
                $values
            );
        } elseif ($kind === 'reservation') {
            $start = $input['begindatum'] ?? '';
            $end = $input['einddatum'] ?? '';
            if (privacyDate($start) >= privacyDate($end)) {
                throw new InvalidArgumentException('Controleer de periode.');
            }
            $people = filter_var($input['personen'] ?? '', FILTER_VALIDATE_INT);
            if (
                !$people ||
                $people < 1 ||
                $people > 10000 ||
                !is_string($input['beschrijving'] ?? null) ||
                trim($input['beschrijving']) === ''
            ) {
                throw new InvalidArgumentException('Controleer personen en beschrijving.');
            }
            if (($input['groep'] ?? '') !== (string) ($r['groep'] ?? '')) {
                throw new InvalidArgumentException(
                    'De eigen groep wordt bij aanmaken gekozen; wijzig die via een nieuwe verhuring.'
                );
            }
            $previous = privacyQuery(
                $db,
                'SELECT begindatum,einddatum,personen,beschrijving FROM verhuur_reservering WHERE id=?',
                'i',
                [$rid]
            )[0];
            $changed = [];
            foreach (
                [
                    'begindatum' => 'Begindatum',
                    'einddatum' => 'Einddatum',
                    'personen' => 'Aantal personen',
                    'beschrijving' => 'Beschrijving'
                ]
                as $field => $label
            ) {
                if ((string) $previous[$field] !== (string) $input[$field]) {
                    $changed[] = $label;
                }
            }
            if (!$changed) {
                return;
            }
            $summary = 'Reservering gewijzigd. Aangepaste velden: ' . implode(', ', $changed) . '.';
            privacyQuery(
                $db,
                'UPDATE verhuur_reservering SET begindatum=?,einddatum=?,personen=?,beschrijving=? WHERE id=?',
                'ssisi',
                [$start, $end, $people, $input['beschrijving'], $rid]
            );
        } elseif ($kind === 'status') {
            $status = filter_var($input['status'] ?? '', FILTER_VALIDATE_INT);
            $newStatus =
                $status === false
                    ? []
                    : privacyQuery(
                        $db,
                        'SELECT status_id,status FROM verhuur_status WHERE status_id=?',
                        'i',
                        [$status]
                    );
            if (!$newStatus) {
                throw new InvalidArgumentException('Ongeldige status.');
            }
            $previous = privacyQuery(
                $db,
                'SELECT status_id FROM verhuur_reservering WHERE id=?',
                'i',
                [$rid]
            )[0];
            $oldId = (int) $previous['status_id'];
            if ($oldId === $status) {
                return;
            }
            $oldStatus = privacyQuery(
                $db,
                'SELECT status FROM verhuur_status WHERE status_id=?',
                'i',
                [$oldId]
            );
            $label = function ($value) {
                return trim(
                    html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8')
                );
            };
            $summary =
                'Status gewijzigd van ' .
                $label($oldStatus[0]['status'] ?? 'status #' . $oldId) .
                ' naar ' .
                $label($newStatus[0]['status']) .
                '.';
            privacyQuery($db, 'UPDATE verhuur_reservering SET status_id=? WHERE id=?', 'ii', [
                $status,
                $rid
            ]);
        } else {
            throw new InvalidArgumentException('Ongeldige bewerking.');
        }
        privacyAudit($db, $id, 'GEWIJZIGD_' . strtoupper($kind), $summary, $actor);
        return $result;
    });
}
function privacyWriteConfig($config) {
    $path = dirname(__DIR__, 3) . '/private/privacy.php';
    $tmp = $path . '.' . bin2hex(random_bytes(8));
    $f = fopen($tmp, 'x');
    if (!$f) {
        throw new RuntimeException('Configuratie opslaan mislukt.');
    }
    chmod($tmp, 0600);
    $data = "<?php\nreturn " . var_export($config, true) . ";\n";
    try {
        if (fwrite($f, $data) !== strlen($data)) {
            throw new RuntimeException('Configuratie opslaan mislukt.');
        }
    } finally {
        fclose($f);
    }
    if (!rename($tmp, $path)) {
        throw new RuntimeException('Configuratie opslaan mislukt.');
    }
}
function privacyDisableGroup($db, $id, $disabled) {
    privacyLock($db, function () use ($db, $id, $disabled) {
        if (
            !privacyQuery(
                $db,
                'SELECT huurder_id FROM verhuur_eigen_groep WHERE huurder_id=?',
                'i',
                [(int) $id]
            )
        ) {
            throw new InvalidArgumentException('Onbekende groep.');
        }
        $config = privacyConfig();
        $ids = array_map('intval', $config['disabled_groups']);
        $ids = array_values(array_diff($ids, [(int) $id]));
        if ($disabled) {
            $ids[] = (int) $id;
        }
        $config['disabled_groups'] = $ids;
        privacyWriteConfig($config);
    });
}
function privacyAddNote($db, $id, $message, $actor) {
    if (
        !is_string($message) ||
        trim($message) === '' ||
        strlen($message) > 10000 ||
        !is_string($actor)
    ) {
        throw new InvalidArgumentException('Ongeldige opmerking.');
    }
    privacyLock($db, function () use ($db, $id, $message, $actor) {
        $r = privacyRental($db, $id);
        privacyQuery(
            $db,
            'INSERT INTO verhuur_notes (hoort_bij,opmerking,schrijver,datum) VALUES (?,?,?,NOW())',
            'iss',
            [(int) $r['id'], $message, $actor]
        );
    });
}
function privacyLegacyAuditConflict($db, $r) {
    $id = (int) $r['id'];
    $rid = (int) $r['reservering_id'];
    if ($id === $rid) {
        return false;
    }
    $rawByRental = privacyQuery(
        $db,
        'SELECT id FROM verhuur_mutaties WHERE hoort_bij=? AND NOT JSON_VALID(`query`) LIMIT 1',
        'i',
        [$id]
    );
    if (
        $rawByRental &&
        privacyQuery(
            $db,
            'SELECT id FROM verhuur_verhuring WHERE reservering_id=? AND id<>? LIMIT 1',
            'ii',
            [$id, $id]
        )
    ) {
        return true;
    }
    $rawByReservation = privacyQuery(
        $db,
        'SELECT id FROM verhuur_mutaties WHERE hoort_bij=? AND NOT JSON_VALID(`query`) LIMIT 1',
        'i',
        [$rid]
    );
    return $rawByReservation &&
        count(privacyQuery($db, 'SELECT id FROM verhuur_verhuring WHERE id=?', 'i', [$rid])) > 0;
}
function privacyToken($db, $key, $config = null) {
    $config = $config ?? privacyConfig();
    if (!is_string($key) || !preg_match('/^(?:[a-f0-9]{56}|[a-f0-9]{64})$/D', $key)) {
        return [];
    }
    $rows = privacyQuery(
        $db,
        'SELECT verhuur_verhuring.id,verhuur_verhuring.huurder_id,verhuur_verhuring.reservering_id FROM verhuur_verhuring JOIN verhuur_huurder ON verhuur_huurder.id=verhuur_verhuring.huurder_id JOIN verhuur_reservering ON verhuur_reservering.id=verhuur_verhuring.reservering_id WHERE verhuur_verhuring.confirm=? AND verhuur_verhuring.huurder_id<>?',
        'si',
        [$key, (int) $config['placeholder_id']]
    );
    return count($rows) === 1 ? $rows : [];
}

function privacyCloneTenant($db, $id) {
    // SELECT and INSERT separately: MyISAM LOCK TABLES treats INSERT...SELECT
    // on the same real table as separate table references requiring aliases.
    $fields = ['naam', 'contactpersoon', 'email', 'telefoon', 'adres', 'postcode', 'plaats', 'ip'];
    $rows = privacyQuery(
        $db,
        'SELECT naam,contactpersoon,email,telefoon,adres,postcode,plaats,ip FROM verhuur_huurder WHERE id=?',
        'i',
        [(int) $id]
    );
    if (count($rows) !== 1) {
        throw new RuntimeException('Huurder bestaat niet meer.');
    }
    $values = [];
    foreach ($fields as $field) {
        $values[] = $rows[0][$field];
    }
    privacyQuery(
        $db,
        'INSERT INTO verhuur_huurder (naam,contactpersoon,email,telefoon,adres,postcode,plaats,ip) VALUES (?,?,?,?,?,?,?,?)',
        'ssssssss',
        $values
    );
    return privacyMeta($db)['insert_id'];
}
