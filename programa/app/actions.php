<?php

// Formų veiksmai: kiekvienas jų tikrina naudotojo teises.
function handle_action($action)
{
    switch ($action) {
        case 'register':
            $name = require_text(input('vardas'), 'Vardas', 80);
            $surname = require_text(input('pavarde'), 'Pavardė', 80);
            $login = input('prisijungimoVardas');
            if (!preg_match('/^[a-zA-Z0-9_.-]{3,50}$/D', $login)) {
                throw new RuntimeException(
                    'Prisijungimo vardui naudokite 3–50 lotyniškų raidžių, skaitmenų, tašką, brūkšnelį arba pabraukimą.'
                );
            }
            $email = email_address(input('elPastas'));
            valid_password(input('slaptazodis'), input('pakartojimas'));
            if (
                one('SELECT id FROM naudotojai WHERE prisijungimoVardas=? OR elPastas=?', [
                    $login,
                    $email
                ])
            ) {
                throw new RuntimeException(
                    'Toks prisijungimo vardas arba el. paštas jau naudojamas.'
                );
            }
            query(
                "INSERT INTO naudotojai
                 (vardas,pavarde,prisijungimoVardas,elPastas,slaptazodzioMaisa,role) VALUES
                 (?,?,?,?,?,'klientas')",
                [
                    $name,
                    $surname,
                    $login,
                    $email,
                    password_hash(input('slaptazodis'), PASSWORD_DEFAULT)
                ]
            );
            flash('Paskyra sukurta. Dabar galite prisijungti.');
            redirect('login');

        case 'login':
            $account = one(
                "SELECT * FROM naudotojai WHERE prisijungimoVardas=? AND busena='aktyvi'",
                [input('prisijungimoVardas')]
            );
            if (
                !$account ||
                !password_verify(input('slaptazodis'), $account['slaptazodzioMaisa'])
            ) {
                throw new RuntimeException(
                    'Neteisingas prisijungimo vardas arba slaptažodis, arba paskyra neaktyvi.'
                );
            }
            session_regenerate_id(true);
            $_SESSION['userId'] = $account['id'];
            unset($_SESSION['csrf']);
            flash('Sėkmingai prisijungėte.');
            redirect('home');

        case 'logout':
            $_SESSION = [];
            session_regenerate_id(true);
            flash('Atsijungėte nuo sistemos.');
            redirect('home');

        case 'reserve':
            $account = require_role('klientas');
            if (!is_array($_POST['kiekis'] ?? null)) {
                throw new RuntimeException('Pasirinkite paslaugas.');
            }
            [$id, $token] = create_booking(
                $account,
                positive_id(input('campId')),
                input('pradziosData'),
                input('pabaigosData'),
                $_POST['kiekis']
            );
            $sent = send_confirmation($id, $token);
            flash(
                $sent
                    ? 'Rezervacija pateikta. Patvirtinkite ją nuoroda el. laiške per 30 minučių.'
                    : 'Rezervacija pateikta, bet laiško išsiųsti nepavyko. Bandykite siųsti dar kartą iki nurodyto termino.'
            );
            redirect('reservations', ['id' => $id]);

        case 'confirm':
            confirm_booking(input('token'));
            flash('Rezervacija patvirtinta. Laukiame jūsų atvykstant!');
            redirect('confirmed');

        case 'resend':
            $account = require_role('klientas');
            $id = positive_id(input('id'));
            $token = resend_booking($account, $id);
            flash(
                send_confirmation($id, $token)
                    ? 'Naujas laiškas išsiųstas. Ankstesnė nuoroda nebegalioja; pradinis terminas nepasikeitė.'
                    : 'Laiško išsiųsti nepavyko. Pakartokite siuntimą iki pradinio termino.'
            );
            redirect('reservations', ['id' => $id]);

        case 'cancel':
            $account = require_role('klientas', 'kempingoAdministratorius');
            $id = positive_id(input('id'));
            cancel_booking($account, $id, input('atsaukimoPriezastis'));
            flash('Rezervacija atšaukta.');
            redirect('reservations', ['id' => $id]);

        case 'thread':
            $account = require_role('klientas');
            $campId = positive_id(input('campId'));
            if (!one('SELECT id FROM kempingai WHERE id=? AND aktyvus=1', [$campId])) {
                throw new RuntimeException('Kempingas nerastas arba neaktyvus.');
            }
            $subject = require_text(input('tema'), 'Tema', 150);
            $message = require_text(input('tekstas'), 'Žinutė');
            db()->beginTransaction();
            try {
                query('INSERT INTO pokalbiai (klientoId,kempingoId,tema) VALUES (?,?,?)', [
                    $account['id'],
                    $campId,
                    $subject
                ]);
                $id = (int) db()->lastInsertId();
                query('INSERT INTO zinutes (pokalbioId,siuntejoId,tekstas) VALUES (?,?,?)', [
                    $id,
                    $account['id'],
                    $message
                ]);
                db()->commit();
            } catch (Throwable $error) {
                db()->rollBack();
                throw $error;
            }
            flash('Pokalbis sukurtas, žinutė išsiųsta.');
            redirect('messages', ['id' => $id]);

        case 'message':
            $account = require_role('klientas', 'kempingoAdministratorius');
            $id = positive_id(input('id'));
            $message = require_text(input('tekstas'), 'Žinutė');
            db()->beginTransaction();
            try {
                $thread = conversation($id, $account);
                // Siunčiant žinutę kempingo administratorius negali būti pakeistas.
                one('SELECT id FROM kempingai WHERE id=? FOR UPDATE', [$thread['kempingoId']]);
                conversation($id, $account);
                query('INSERT INTO zinutes (pokalbioId,siuntejoId,tekstas) VALUES (?,?,?)', [
                    $id,
                    $account['id'],
                    $message
                ]);
                db()->commit();
            } catch (Throwable $error) {
                db()->rollBack();
                throw $error;
            }
            flash('Žinutė išsiųsta.');
            redirect('messages', ['id' => $id]);

        case 'review':
            $account = require_role('klientas');
            $id = positive_id(input('id'));
            $rating = input('ivertinimas');
            if (!preg_match('/^[1-5]$/D', $rating)) {
                throw new RuntimeException('Įvertinimas turi būti sveikas skaičius nuo 1 iki 5.');
            }
            $comment = require_text(input('komentaras'), 'Komentaras');
            db()->beginTransaction();
            try {
                $booking = one('SELECT * FROM rezervacijos WHERE id=? FOR UPDATE', [$id]);
                if (
                    !$booking ||
                    (int) $booking['klientoId'] !== (int) $account['id'] ||
                    $booking['busena'] !== 'patvirtinta' ||
                    $booking['pabaigosData'] > date('Y-m-d')
                ) {
                    throw new RuntimeException(
                        'Vertinti galima tik savo patvirtintą ir jau pasibaigusią rezervaciją.'
                    );
                }
                if (one('SELECT id FROM atsiliepimai WHERE rezervacijosId=?', [$id])) {
                    throw new RuntimeException('Šiai rezervacijai jau pateikėte atsiliepimą.');
                }
                query(
                    'INSERT INTO atsiliepimai (rezervacijosId,ivertinimas,komentaras) VALUES (?,?,?)',
                    [$id, $rating, $comment]
                );
                db()->commit();
            } catch (Throwable $error) {
                db()->rollBack();
                throw $error;
            }
            flash('Atsiliepimas išsaugotas. Kempingo reitingas atnaujintas.');
            redirect('camp', ['id' => $booking['kempingoId']]);

        case 'saveCamp':
            save_camp();
            break;
        case 'saveService':
            save_service();
            break;
        case 'createUser':
            require_role('portaloAdministratorius');
            $name = require_text(input('vardas'), 'Vardas', 80);
            $surname = require_text(input('pavarde'), 'Pavardė', 80);
            $login = input('prisijungimoVardas');
            if (!preg_match('/^[a-zA-Z0-9_.-]{3,50}$/D', $login)) {
                throw new RuntimeException(
                    'Prisijungimo vardui naudokite 3–50 lotyniškų raidžių, skaitmenų, tašką, brūkšnelį arba pabraukimą.'
                );
            }
            $email = email_address(input('elPastas'));
            valid_password(input('slaptazodis'), input('pakartojimas'));
            $role = input('role');
            if (
                !in_array(
                    $role,
                    ['klientas', 'kempingoAdministratorius', 'portaloAdministratorius'],
                    true
                )
            ) {
                throw new RuntimeException('Neteisinga rolė.');
            }
            if (
                one('SELECT id FROM naudotojai WHERE prisijungimoVardas=? OR elPastas=?', [
                    $login,
                    $email
                ])
            ) {
                throw new RuntimeException('Prisijungimo vardas arba el. paštas jau naudojamas.');
            }
            query(
                'INSERT INTO naudotojai
                 (vardas,pavarde,prisijungimoVardas,elPastas,slaptazodzioMaisa,role) VALUES
                 (?,?,?,?,?,?)',
                [
                    $name,
                    $surname,
                    $login,
                    $email,
                    password_hash(input('slaptazodis'), PASSWORD_DEFAULT),
                    $role
                ]
            );
            flash('Naudotojo paskyra sukurta.');
            redirect('users');

        case 'userRole':
            $account = require_role('portaloAdministratorius');
            $id = positive_id(input('id'));
            $role = input('role');
            if (!in_array(
                $role,
                ['klientas', 'kempingoAdministratorius', 'portaloAdministratorius'],
                true
            )) {
                throw new RuntimeException('Neteisinga rolė.');
            }
            db()->beginTransaction();
            try {
                $accounts = rows('SELECT id, role, busena FROM naudotojai ORDER BY id FOR UPDATE');
                $target = one('SELECT * FROM naudotojai WHERE id=?', [$id]);
                if (!$target) {
                    throw new RuntimeException('Naudotojas nerastas.');
                }
                if ($target['role'] === 'kempingoAdministratorius' && $role !== $target['role']) {
                    if (one('SELECT id FROM kempingai WHERE administratoriausId=? LIMIT 1', [$id])) {
                        throw new RuntimeException(
                            'Pirmiausia perduokite šio naudotojo kempingus kitam administratoriui.'
                        );
                    }
                }
                if ($target['role'] === 'portaloAdministratorius' && $role !== $target['role']) {
                    $otherAdmin = false;
                    foreach ($accounts as $item) {
                        if (
                            (int) $item['id'] !== $id &&
                            $item['role'] === 'portaloAdministratorius' &&
                            $item['busena'] === 'aktyvi'
                        ) {
                            $otherAdmin = true;
                        }
                    }
                    if (!$otherAdmin) {
                        throw new RuntimeException(
                            'Paskutinio aktyvaus portalo administratoriaus rolės keisti negalima.'
                        );
                    }
                }
                query('UPDATE naudotojai SET role=? WHERE id=?', [$role, $id]);
                db()->commit();
            } catch (Throwable $error) {
                db()->rollBack();
                throw $error;
            }
            flash('Naudotojo rolė pakeista.');
            redirect(
                (int) $account['id'] === $id && $role !== 'portaloAdministratorius'
                    ? 'home'
                    : 'users'
            );

        case 'userStatus':
            $account = require_role('portaloAdministratorius');
            $id = positive_id(input('id'));
            $status = input('busena');
            if (!in_array($status, ['aktyvi', 'blokuota'], true)) {
                throw new RuntimeException('Neteisinga būsena.');
            }
            db()->beginTransaction();
            try {
                rows('SELECT id FROM naudotojai ORDER BY id FOR UPDATE');
                $target = one('SELECT * FROM naudotojai WHERE id=?', [$id]);
                if (!$target) {
                    throw new RuntimeException('Naudotojas nerastas.');
                }
                if ($status === 'blokuota') {
                    if ((int) $account['id'] === $id) {
                        throw new RuntimeException('Savo paskyros blokuoti negalite.');
                    }
                    if (
                        one('SELECT id FROM kempingai WHERE administratoriausId=? LIMIT 1', [$id])
                    ) {
                        throw new RuntimeException(
                            'Pirmiausia perduokite visus šio administratoriaus kempingus kitam aktyviam administratoriui.'
                        );
                    }
                    if (
                        $target['role'] === 'portaloAdministratorius' &&
                        (int) query(
                            "SELECT COUNT(*) FROM naudotojai WHERE role='portaloAdministratorius'
                             AND busena='aktyvi' AND id<>?",
                            [$id]
                        )->fetchColumn() === 0
                    ) {
                        throw new RuntimeException(
                            'Paskutinio aktyvaus portalo administratoriaus blokuoti negalite.'
                        );
                    }
                }
                query('UPDATE naudotojai SET busena=? WHERE id=?', [$status, $id]);
                db()->commit();
            } catch (Throwable $error) {
                db()->rollBack();
                throw $error;
            }
            flash('Paskyros būsena atnaujinta.');
            redirect('users');

        case 'moderate':
            require_role('portaloAdministratorius');
            $id = positive_id(input('id'));
            $hidden = input('pasleptas') === '1';
            $reason = $hidden
                ? require_text(input('paslepimoPriezastis'), 'Paslėpimo priežastis', 500)
                : null;
            if (!one('SELECT id FROM atsiliepimai WHERE id=?', [$id])) {
                throw new RuntimeException('Atsiliepimas nerastas.');
            }
            query('UPDATE atsiliepimai SET pasleptas=?, paslepimoPriezastis=? WHERE id=?', [
                (int) $hidden,
                $reason,
                $id
            ]);
            flash(
                $hidden
                    ? 'Atsiliepimas paslėptas ir neįtraukiamas į reitingą.'
                    : 'Atsiliepimas vėl viešas.'
            );
            redirect('moderation');

        default:
            throw new RuntimeException('Nežinomas veiksmas.');
    }
}

function save_camp()
{
    $account = require_role('kempingoAdministratorius', 'portaloAdministratorius');
    $id = input('id') === '' ? null : positive_id(input('id'));
    $name = require_text(input('pavadinimas'), 'Pavadinimas', 150);
    $description = require_text(input('aprasymas'), 'Aprašymas', 5000);
    $address = require_text(input('adresas'), 'Adresas', 200);
    $city = require_text(input('miestas'), 'Miestas', 100);
    $email = email_address(input('kontaktinisElPastas'));
    $phone = require_text(input('telefonas'), 'Telefonas', 30);
    if (!preg_match('/^[+0-9 ()-]{5,30}$/D', $phone)) {
        throw new RuntimeException('Neteisingas telefono numeris.');
    }
    $active = isset($_POST['aktyvus']) ? 1 : 0;
    db()->beginTransaction();
    try {
        if ($account['role'] === 'portaloAdministratorius') {
            rows('SELECT id FROM naudotojai ORDER BY id FOR UPDATE');
            $adminId = positive_id(input('administratoriausId'));
            if (
                !one(
                    "SELECT id FROM naudotojai WHERE id=? AND role='kempingoAdministratorius' AND
                     busena='aktyvi'",
                    [$adminId]
                )
            ) {
                throw new RuntimeException('Pasirinkite aktyvų kempingo administratorių.');
            }
            if ($id && !one('SELECT id FROM kempingai WHERE id=? FOR UPDATE', [$id])) {
                throw new RuntimeException('Kempingas nerastas.');
            }
        } else {
            if (!$id) {
                throw new RuntimeException('Kurti kempingą gali tik portalo administratorius.');
            }
            $camp = owned_camp($id, $account, true);
            $adminId = $camp['administratoriausId'];
        }
        $values = [$name, $description, $address, $city, $email, $phone, $active, $adminId];
        if ($id) {
            query(
                'UPDATE kempingai SET
                 pavadinimas=?,aprasymas=?,adresas=?,miestas=?,kontaktinisElPastas=?,telefonas=?,aktyvus=?,administratoriausId=?
                 WHERE id=?',
                array_merge($values, [$id])
            );
        } else {
            query(
                'INSERT INTO kempingai
                 (pavadinimas,aprasymas,adresas,miestas,kontaktinisElPastas,telefonas,aktyvus,administratoriausId)
                 VALUES (?,?,?,?,?,?,?,?)',
                $values
            );
        }
        db()->commit();
    } catch (Throwable $error) {
        db()->rollBack();
        throw $error;
    }
    flash('Kempingo duomenys išsaugoti.');
    redirect(
        $account['role'] === 'portaloAdministratorius' ? 'camps' : 'manage',
        $account['role'] === 'portaloAdministratorius' ? [] : ['id' => $id]
    );
}

function save_service()
{
    $account = require_role('kempingoAdministratorius');
    $campId = positive_id(input('campId'));
    $id = input('id') === '' ? null : positive_id(input('id'));
    $name = require_text(input('pavadinimas'), 'Pavadinimas', 150);
    $description = require_text(input('aprasymas'), 'Aprašymas', 2000);
    $price = parse_price(input('parosKaina'));
    $quantity = input('bendrasKiekis');
    if (!ctype_digit($quantity) || (int) $quantity < 1 || (int) $quantity > 100000) {
        throw new RuntimeException('Bendras kiekis turi būti nuo 1 iki 100000.');
    }
    $type = positive_id(input('tipoId'));
    if (!one('SELECT id FROM paslaugu_tipai WHERE id=?', [$type])) {
        throw new RuntimeException('Paslaugos tipas nerastas.');
    }
    $active = isset($_POST['aktyvi']) ? 1 : 0;
    db()->beginTransaction();
    try {
        owned_camp($campId, $account, true);
        if ($id) {
            $service = lock_services([$id])[$id];
            if ((int) $service['kempingoId'] !== $campId) {
                throw new RuntimeException('Paslauga nepriklauso šiam kempingui.');
            }
            $minimum = peak_reserved($id, date('Y-m-d'));
            if ((int) $quantity < $minimum) {
                throw new RuntimeException(
                    'Kiekio sumažinti negalima: galiojančioms rezervacijoms reikia bent ' .
                        $minimum .
                        ' vienetų.'
                );
            }
            query(
                'UPDATE paslaugos SET
                 pavadinimas=?,aprasymas=?,parosKaina=?,bendrasKiekis=?,tipoId=?,aktyvi=? WHERE
                 id=?',
                [$name, $description, $price, $quantity, $type, $active, $id]
            );
        } else {
            query(
                'INSERT INTO paslaugos
                 (kempingoId,pavadinimas,aprasymas,parosKaina,bendrasKiekis,tipoId,aktyvi) VALUES
                 (?,?,?,?,?,?,?)',
                [$campId, $name, $description, $price, $quantity, $type, $active]
            );
        }
        db()->commit();
    } catch (Throwable $error) {
        db()->rollBack();
        throw $error;
    }
    flash('Paslauga išsaugota. Ankstesnių rezervacijų kainos nepasikeitė.');
    redirect('manage', ['id' => $campId]);
}

function send_confirmation($id, $token)
{
    try {
        require_once dirname(__DIR__) . '/vendor/autoload.php';
        $booking = one(
            'SELECT r.*, u.elPastas, u.vardas, k.pavadinimas FROM rezervacijos r JOIN naudotojai u
             ON u.id=r.klientoId JOIN kempingai k ON k.id=r.kempingoId WHERE r.id=?',
            [$id]
        );
        $link =
            rtrim(getenv('APP_URL') ?: 'http://localhost:8080', '/') .
            url('confirm', ['token' => $token]);
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = getenv('SMTP_HOST') ?: 'mailpit';
        $mail->Port = (int) (getenv('SMTP_PORT') ?: 1025);
        $mail->Timeout = 10;
        $mail->CharSet = 'UTF-8';
        $mail->SMTPAuth = (getenv('SMTP_USER') ?: '') !== '';
        $mail->Username = getenv('SMTP_USER') ?: '';
        $mail->Password = getenv('SMTP_PASSWORD') ?: '';
        $mail->SMTPSecure = getenv('SMTP_SECURITY') ?: '';
        $mail->SMTPAutoTLS = $mail->SMTPSecure !== '';
        $mail->setFrom(getenv('MAIL_FROM') ?: 'portalas@example.lt', 'Kempingų portalas');
        $mail->addAddress($booking['elPastas']);
        $mail->Subject = 'Patvirtinkite rezervaciją #' . $id;
        $mail->Body =
            "Sveiki, {$booking['vardas']}!\n\n" .
            "Kempingas: {$booking['pavadinimas']}\n" .
            "Laikotarpis: {$booking['pradziosData']} – {$booking['pabaigosData']}\n" .
            'Suma: ' .
            euros(booking_total($booking, booking_lines($id))) .
            "\n\nRezervacijos patvirtinimo nuoroda:\n" .
            $link .
            "\n\nNuoroda galioja iki " .
            local_time($booking['patvirtinimoTerminas']) .
            ".\nAtvėrę nuorodą paspauskite „Patvirtinti rezervaciją“.\nJei nerezervavote, šio laiško nepaisykite.";
        $mail->send();
        return true;
    } catch (Throwable $error) {
        // Žurnale nerašoma patvirtinimo nuoroda ar SMTP slaptažodis.
        error_log('Nepavyko išsiųsti rezervacijos #' . $id . ' patvirtinimo laiško.');
        return false;
    }
}
