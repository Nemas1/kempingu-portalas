<?php

function dates($start, $end)
{
    $a = DateTimeImmutable::createFromFormat('!Y-m-d', $start);
    $b = DateTimeImmutable::createFromFormat('!Y-m-d', $end);
    if (
        !$a ||
        !$b ||
        $a->format('Y-m-d') !== $start ||
        $b->format('Y-m-d') !== $end ||
        $start < date('Y-m-d') ||
        $end <= $start
    ) {
        throw new RuntimeException(
            'Atvykimo data negali būti praeityje, o išvykimo data turi būti vėlesnė už atvykimo datą.'
        );
    }
    $days = (int) $a->diff($b)->days;
    if ($days > 365) {
        throw new RuntimeException('Vienos rezervacijos trukmė negali viršyti 365 parų.');
    }
    return $days;
}

// Randamas didžiausias vienu metu užimtas kiekis. Išvykimo diena neįtraukiama.
function peak_reserved($serviceId, $start, $end = null)
{
    $sql = "SELECT r.pradziosData, r.pabaigosData, e.kiekis FROM rezervacijos_eilutes e JOIN
            rezervacijos r ON r.id=e.rezervacijosId WHERE e.paslaugosId=? AND r.pabaigosData>? AND
            (r.busena='patvirtinta' OR (r.busena='laukiaPatvirtinimo' AND
            r.patvirtinimoTerminas>UTC_TIMESTAMP()))";
    $params = [$serviceId, $start];
    if ($end !== null) {
        $sql .= ' AND r.pradziosData<?';
        $params[] = $end;
    }
    $events = [];
    foreach (rows($sql, $params) as $booking) {
        $a = max($start, $booking['pradziosData']);
        $b = $end === null ? $booking['pabaigosData'] : min($end, $booking['pabaigosData']);
        $events[$a] = ($events[$a] ?? 0) + (int) $booking['kiekis'];
        $events[$b] = ($events[$b] ?? 0) - (int) $booking['kiekis'];
    }
    ksort($events);
    $current = $peak = 0;
    foreach ($events as $change) {
        $current += $change;
        $peak = max($peak, $current);
    }
    return $peak;
}

function lock_services($ids)
{
    $ids = array_values(array_unique(array_map('intval', $ids)));
    sort($ids, SORT_NUMERIC);
    $services = [];
    foreach ($ids as $id) {
        $service = one('SELECT * FROM paslaugos WHERE id=? FOR UPDATE', [$id]);
        if (!$service) {
            throw new RuntimeException('Pasirinkta paslauga nerasta.');
        }
        $services[$id] = $service;
    }
    return $services;
}

function create_booking($user, $campId, $start, $end, $quantities)
{
    dates($start, $end);
    if ($user['role'] !== 'klientas') {
        throw new RuntimeException('Rezervuoti gali tik klientas.');
    }
    $chosen = [];
    foreach ($quantities as $key => $value) {
        if (!is_scalar($value) || !preg_match('/^\d{1,6}$/D', (string) $value)) {
            throw new RuntimeException('Paslaugų kiekiai turi būti sveiki neneigiami skaičiai.');
        }
        if ((int) $value > 0) {
            $chosen[positive_id($key)] = (int) $value;
        }
    }
    if (!$chosen) {
        throw new RuntimeException('Pasirinkite bent vieną paslaugą ir jos kiekį.');
    }
    db()->beginTransaction();
    try {
        // Užraktai neleidžia dviem klientams užimti tos pačios paskutinės vietos.
        $camp = one('SELECT * FROM kempingai WHERE id=? FOR UPDATE', [$campId]);
        if (!$camp || !$camp['aktyvus']) {
            throw new RuntimeException('Kempingas šiuo metu nepriima naujų rezervacijų.');
        }
        $services = lock_services(array_keys($chosen));
        foreach ($chosen as $id => $quantity) {
            $service = $services[$id];
            if ((int) $service['kempingoId'] !== $campId || !$service['aktyvi']) {
                throw new RuntimeException(
                    'Pasirinkta paslauga nepriklauso kempingui arba yra neaktyvi.'
                );
            }
            $free = (int) $service['bendrasKiekis'] - peak_reserved($id, $start, $end);
            if ($quantity > $free) {
                throw new RuntimeException(
                    $service['pavadinimas'] .
                        ': pasirinktam laikotarpiui laisvas kiekis – ' .
                        max(0, $free) .
                        '.'
                );
            }
        }
        query(
            'INSERT INTO rezervacijos
             (klientoId,kempingoId,pradziosData,pabaigosData,patvirtinimoTerminas) VALUES
             (?,?,?,?,DATE_ADD(UTC_TIMESTAMP(), INTERVAL 30 MINUTE))',
            [$user['id'], $campId, $start, $end]
        );
        $id = (int) db()->lastInsertId();
        foreach ($chosen as $serviceId => $quantity) {
            query(
                'INSERT INTO rezervacijos_eilutes (rezervacijosId,paslaugosId,kiekis,parosKaina)
                 VALUES (?,?,?,?)',
                [$id, $serviceId, $quantity, $services[$serviceId]['parosKaina']]
            );
        }
        $token = bin2hex(random_bytes(32));
        query(
            'INSERT INTO patvirtinimo_nuorodos (rezervacijosId,zetonoMaisa,galiojimoPabaiga) SELECT
             id,?,patvirtinimoTerminas FROM rezervacijos WHERE id=?',
            [hash('sha256', $token), $id]
        );
        db()->commit();
        return [$id, $token];
    } catch (Throwable $error) {
        db()->rollBack();
        throw $error;
    }
}

function lock_booking($id)
{
    $booking = one('SELECT * FROM rezervacijos WHERE id=?', [$id]);
    if (!$booking) {
        throw new RuntimeException('Rezervacija nerasta.');
    }
    one('SELECT id FROM kempingai WHERE id=? FOR UPDATE', [$booking['kempingoId']]);
    lock_services(
        array_column(
            rows('SELECT paslaugosId FROM rezervacijos_eilutes WHERE rezervacijosId=?', [$id]),
            'paslaugosId'
        )
    );
    return one('SELECT * FROM rezervacijos WHERE id=? FOR UPDATE', [$id]);
}

function token_booking($token)
{
    if (!preg_match('/^[a-f0-9]{64}$/D', $token)) {
        return null;
    }
    return one(
        "SELECT r.*, k.pavadinimas, n.panaudojimoData, n.galiojimoPabaiga, u.busena AS
         klientoBusena FROM patvirtinimo_nuorodos n JOIN rezervacijos r ON r.id=n.rezervacijosId
         JOIN kempingai k ON k.id=r.kempingoId JOIN naudotojai u ON u.id=r.klientoId WHERE
         n.zetonoMaisa=?",
        [hash('sha256', $token)]
    );
}

function token_valid($booking)
{
    return $booking &&
        booking_state($booking) === 'laukiaPatvirtinimo' &&
        !$booking['panaudojimoData'] &&
        $booking['galiojimoPabaiga'] > gmdate('Y-m-d H:i:s') &&
        $booking['klientoBusena'] === 'aktyvi';
}

function confirm_booking($token)
{
    $preview = token_booking($token);
    if (!token_valid($preview)) {
        throw new RuntimeException('Nuoroda nebegalioja arba jau panaudota.');
    }
    db()->beginTransaction();
    try {
        lock_booking((int) $preview['id']);
        $link = one('SELECT * FROM patvirtinimo_nuorodos WHERE rezervacijosId=? FOR UPDATE', [
            $preview['id']
        ]);
        $booking = token_booking($token);
        if (!$link || !token_valid($booking)) {
            throw new RuntimeException('Nuoroda nebegalioja arba jau panaudota.');
        }
        query("UPDATE rezervacijos SET busena='patvirtinta' WHERE id=?", [$booking['id']]);
        query('UPDATE patvirtinimo_nuorodos SET panaudojimoData=UTC_TIMESTAMP() WHERE id=?', [
            $link['id']
        ]);
        db()->commit();
    } catch (Throwable $error) {
        db()->rollBack();
        throw $error;
    }
}

function resend_booking($user, $id)
{
    db()->beginTransaction();
    try {
        $booking = lock_booking($id);
        if ($user['role'] !== 'klientas' || (int) $booking['klientoId'] !== (int) $user['id']) {
            throw new RuntimeException('Šio veiksmo atlikti negalite.');
        }
        if (booking_state($booking) !== 'laukiaPatvirtinimo') {
            throw new RuntimeException(
                'Laišką pakartoti galima tik iki pradinio patvirtinimo termino.'
            );
        }
        $token = bin2hex(random_bytes(32));
        query(
            'UPDATE patvirtinimo_nuorodos SET zetonoMaisa=?, sukurimoData=UTC_TIMESTAMP(),
             panaudojimoData=NULL WHERE rezervacijosId=?',
            [hash('sha256', $token), $id]
        );
        db()->commit();
        return $token;
    } catch (Throwable $error) {
        db()->rollBack();
        throw $error;
    }
}

function cancel_booking($user, $id, $reason)
{
    db()->beginTransaction();
    try {
        $booking = lock_booking($id);
        booking_access($id, $user);
        if (
            $booking['pradziosData'] <= date('Y-m-d') ||
            !in_array(booking_state($booking), ['laukiaPatvirtinimo', 'patvirtinta'], true)
        ) {
            throw new RuntimeException(
                'Galima atšaukti tik dar neprasidėjusią galiojančią rezervaciją.'
            );
        }
        if ($user['role'] === 'kempingoAdministratorius' || $reason !== '') {
            require_text($reason, 'Atšaukimo priežastis', 500);
        }
        query(
            "UPDATE rezervacijos SET busena='atsaukta', atsaukimoData=UTC_TIMESTAMP(),
             atsaukimoPriezastis=? WHERE id=?",
            [$reason ?: null, $id]
        );
        db()->commit();
    } catch (Throwable $error) {
        db()->rollBack();
        throw $error;
    }
}
