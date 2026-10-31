<?php
function adminAccountPassword($password, $confirmation) {
    if (
        !is_string($password) ||
        !is_string($confirmation) ||
        strlen($password) < 12 ||
        strlen($password) > 72 ||
        strpos($password, "\0") !== false
    ) {
        throw new InvalidArgumentException('Gebruik een wachtwoord van 12 tot 72 bytes.');
    }
    if (!hash_equals($password, $confirmation)) {
        throw new InvalidArgumentException('De twee wachtwoorden zijn niet gelijk.');
    }
    return password_hash($password, PASSWORD_DEFAULT);
}
function adminAccountGroup($db, $code) {
    $stmt = $db->prepare('SELECT code FROM beheer_access_groups WHERE code=?');
    $stmt->bind_param('s', $code);
    $stmt->execute();
    $stmt->bind_result($found);
    $exists = $stmt->fetch();
    $stmt->close();
    if (!$exists) {
        throw new InvalidArgumentException('Deze groep bestaat niet meer. Kies opnieuw.');
    }
}
function adminAccountMutation($db, $action, $input, $actor) {
    $db->begin_transaction();
    try {
        // Serialize access changes, including deletion, and check the caller inside the same lock.
        $db->query('SELECT code FROM beheer_access_groups ORDER BY code FOR UPDATE')->free();
        $db->query('SELECT naam FROM verhuur_administrators ORDER BY naam FOR UPDATE')->free();
        $stmt = $db->prepare(
            "SELECT a.auth_version FROM verhuur_administrators a JOIN beheer_access_permissions p ON p.groep=a.rol AND p.module='accounts' WHERE a.naam=?"
        );
        $stmt->bind_param('s', $actor);
        $stmt->execute();
        $stmt->bind_result($actorVersion);
        $authorized = $stmt->fetch();
        $stmt->close();
        if (!$authorized || (int) $actorVersion !== (int) ($_SESSION['ADMIN_AUTH_VERSION'] ?? -1)) {
            throw new InvalidArgumentException(
                'Je hebt geen toegang meer tot groepen en beheerders.'
            );
        }
        if ($action === 'group_save' || $action === 'group_create') {
            $label = $input('groepsnaam');
            if ($label === '' || strlen($label) > 100 || preg_match('/[\x00-\x1f\x7f]/', $label)) {
                throw new InvalidArgumentException('Vul een groepsnaam in van maximaal 100 bytes.');
            }
            $code =
                $action === 'group_create' ? 'groep-' . bin2hex(random_bytes(10)) : $input('groep');
            $modules = $_POST['modules'] ?? [];
            if (!is_array($modules)) {
                throw new InvalidArgumentException('Kies geldige modules.');
            }
            foreach ($modules as $module) {
                if (!is_string($module) || !isset(adminModules()[$module])) {
                    throw new InvalidArgumentException('Onbekende module.');
                }
            }
            if ($action === 'group_create') {
                $stmt = $db->prepare('INSERT INTO beheer_access_groups (code,naam) VALUES (?,?)');
                $stmt->bind_param('ss', $code, $label);
            } else {
                adminAccountGroup($db, $code);
                $stmt = $db->prepare('UPDATE beheer_access_groups SET naam=? WHERE code=?');
                $stmt->bind_param('ss', $label, $code);
            }
            $stmt->execute();
            $stmt->close();
            $stmt = $db->prepare('DELETE FROM beheer_access_permissions WHERE groep=?');
            $stmt->bind_param('s', $code);
            $stmt->execute();
            $stmt->close();
            $stmt = $db->prepare(
                'INSERT INTO beheer_access_permissions (groep,module) VALUES (?,?)'
            );
            foreach (array_unique($modules) as $module) {
                $stmt->bind_param('ss', $code, $module);
                $stmt->execute();
            }
            $stmt->close();
            $notice = 'Groep en moduletoegang opgeslagen.';
        } elseif ($action === 'group_delete') {
            $code = $input('groep');
            adminAccountGroup($db, $code);
            $stmt = $db->prepare('SELECT COUNT(*) FROM verhuur_administrators WHERE rol=?');
            $stmt->bind_param('s', $code);
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();
            if ($count) {
                throw new InvalidArgumentException(
                    'Verplaats eerst alle beheerders uit deze groep.'
                );
            }
            $stmt = $db->prepare('DELETE FROM beheer_access_groups WHERE code=?');
            $stmt->bind_param('s', $code);
            $stmt->execute();
            $stmt->close();
            $notice = 'Lege groep verwijderd.';
        } elseif ($action === 'account_create') {
            $name = $input('naam');
            $code = $input('groep');
            adminAccountGroup($db, $code);
            if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._@-]{2,99}$/D', $name)) {
                throw new InvalidArgumentException(
                    'Gebruik een gebruikersnaam van 3 tot 100 tekens: letters, cijfers, punt, streepje, @ of underscore.'
                );
            }
            $check = $db->prepare('SELECT COUNT(*) FROM verhuur_administrators WHERE naam=?');
            $check->bind_param('s', $name);
            $check->execute();
            $check->bind_result($duplicates);
            $check->fetch();
            $check->close();
            if ($duplicates) {
                throw new InvalidArgumentException(
                    'Deze gebruikersnaam bestaat al. Kies een andere naam.'
                );
            }
            $hash = adminAccountPassword(
                $_POST['password'] ?? null,
                $_POST['password_confirm'] ?? null
            );
            $stmt = $db->prepare(
                "INSERT INTO verhuur_administrators (naam,pass,pass_hash,rol,ip,activity) VALUES (?,'',?,?,'',NOW())"
            );
            $stmt->bind_param('sss', $name, $hash, $code);
            $stmt->execute();
            $stmt->close();
            $notice =
                'Beheerder aangemaakt. Deel de gebruikersnaam en het wachtwoord via een veilige weg.';
        } elseif ($action === 'account_group') {
            $name = $input('naam');
            $code = $input('groep');
            adminAccountGroup($db, $code);
            $check = $db->prepare('SELECT naam FROM verhuur_administrators WHERE naam=?');
            $check->bind_param('s', $name);
            $check->execute();
            $check->bind_result($existing);
            $found = $check->fetch();
            $check->close();
            if (!$found) {
                throw new InvalidArgumentException('Deze beheerder bestaat niet meer.');
            }
            $stmt = $db->prepare('UPDATE verhuur_administrators SET rol=? WHERE naam=?');
            $stmt->bind_param('ss', $code, $name);
            $stmt->execute();
            $stmt->close();
            $notice = 'Groep van beheerder opgeslagen.';
        } elseif ($action === 'password_reset') {
            $name = $input('naam');
            $hash = adminAccountPassword(
                $_POST['password'] ?? null,
                $_POST['password_confirm'] ?? null
            );
            $stmt = $db->prepare(
                "UPDATE verhuur_administrators SET pass='',pass_hash=?,auth_version=auth_version+1 WHERE naam=?"
            );
            $stmt->bind_param('ss', $hash, $name);
            $stmt->execute();
            $count = $stmt->affected_rows;
            $stmt->close();
            if ($count !== 1) {
                throw new InvalidArgumentException('Deze beheerder bestaat niet meer.');
            }
            $notice =
                'Nieuw wachtwoord opgeslagen. Bestaande sessies en lopende inlogpogingen zijn ingetrokken. Tweestapsverificatie blijft actief.';
        } else {
            throw new InvalidArgumentException('Onbekende actie.');
        }
        $row = $db
            ->query(
                "SELECT COUNT(*) AS aantal FROM verhuur_administrators a JOIN beheer_access_permissions p ON p.groep=a.rol AND p.module='accounts'"
            )
            ->fetch_assoc();
        if (!(int) $row['aantal']) {
            throw new InvalidArgumentException(
                'Er moet minstens één beheerder met toegang tot Groepen en beheerders overblijven. De wijziging is niet opgeslagen.'
            );
        }
        $db->commit();
        return $notice;
    } catch (Throwable $error) {
        $db->rollback();
        throw $error;
    }
}
