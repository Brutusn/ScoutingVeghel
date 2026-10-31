<?php
function adminMfaSecret() {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $secret = '';
    foreach (str_split(random_bytes(32)) as $byte) {
        $secret .= $alphabet[ord($byte) & 31];
    }
    return $secret;
}
function adminTotp($secret, $step) {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $bits = '';
    foreach (str_split($secret) as $letter) {
        $value = strpos($alphabet, $letter);
        if ($value === false) {
            throw new InvalidArgumentException('Invalid key');
        }
        $bits .= str_pad(decbin($value), 5, '0', STR_PAD_LEFT);
    }
    $key = '';
    for ($i = 0; $i + 8 <= strlen($bits); $i += 8) {
        $key .= chr(bindec(substr($bits, $i, 8)));
    }
    $hash = hash_hmac('sha1', pack('N2', 0, $step), $key, true);
    $offset = ord($hash[19]) & 15;
    $number = unpack('N', substr($hash, $offset, 4))[1] & 0x7fffffff;
    return str_pad((string) ($number % 1000000), 6, '0', STR_PAD_LEFT);
}
function adminMfaStep($secret, $code, $last = -1) {
    if (!is_string($code) || !preg_match('/^[0-9]{6}$/D', $code)) {
        return false;
    }
    $now = (int) floor(time() / 30);
    foreach ([$now, $now - 1, $now + 1] as $step) {
        if ($step > $last && hash_equals(adminTotp($secret, $step), $code)) {
            return $step;
        }
    }
    return false;
}
function adminMfaVerify($database, $name, $code) {
    $stmt = $database->prepare(
        'SELECT mfa_secret,mfa_last,mfa_recovery FROM verhuur_administrators WHERE naam=?'
    );
    $stmt->bind_param('s', $name);
    $stmt->execute();
    $stmt->bind_result($secret, $last, $recovery);
    $found = $stmt->fetch();
    $stmt->close();
    if (!$found || !$secret || !is_string($code)) {
        return false;
    }
    $step = adminMfaStep($secret, $code, (int) $last);
    if ($step !== false) {
        $stmt = $database->prepare(
            'UPDATE verhuur_administrators SET mfa_last=? WHERE naam=? AND mfa_last=? AND mfa_secret=?'
        );
        $stmt->bind_param('isis', $step, $name, $last, $secret);
    } else {
        $codes = json_decode($recovery ?: '[]', true);
        $match = false;
        foreach ($codes as $i => $hash) {
            if (hash_equals($hash, hash('sha256', $code))) {
                $match = $i;
                break;
            }
        }
        if ($match === false) {
            return false;
        }
        unset($codes[$match]);
        $new = json_encode(array_values($codes));
        $stmt = $database->prepare(
            'UPDATE verhuur_administrators SET mfa_recovery=? WHERE naam=? AND mfa_recovery=? AND mfa_secret=?'
        );
        $stmt->bind_param('ssss', $new, $name, $recovery, $secret);
    }
    $stmt->execute();
    $ok = $stmt->affected_rows === 1;
    $stmt->close();
    return $ok;
}
