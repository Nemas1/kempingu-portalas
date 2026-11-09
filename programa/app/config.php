<?php

date_default_timezone_set('Europe/Vilnius');
if (PHP_SAPI !== 'cli') {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
    ]);
    session_start();
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: no-referrer');
    header('Cache-Control: no-store');
    header(
        "Content-Security-Policy: default-src 'self'; style-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'"
    );
}

function db()
{
    static $connection;
    if (!$connection) {
        $connection = new PDO(
            'mysql:host=' .
                (getenv('DB_HOST') ?: 'db') .
                ';dbname=' .
                (getenv('DB_NAME') ?: 'kempingai') .
                ';charset=utf8mb4',
            getenv('DB_USER') ?: 'stud',
            getenv('DB_PASSWORD') ?: 'stud',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        $connection->exec("SET time_zone = '+00:00'");
        // Laukus užrakto reikia matyti kito kliento ką tik pateiktą rezervaciją.
        $connection->exec('SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED');
    }
    return $connection;
}

function query($sql, $params = [])
{
    $statement = db()->prepare($sql);
    $statement->execute($params);
    return $statement;
}

function error_message($error)
{
    if ($error instanceof PDOException) {
        error_log((string) $error);
        http_response_code(500);
        return 'Nepavyko išsaugoti arba perskaityti duomenų. Patikrinkite formą ir bandykite dar kartą.';
    }
    if ($error instanceof RuntimeException) {
        return $error->getMessage();
    }
    error_log((string) $error);
    http_response_code(500);
    return 'Įvyko sistemos klaida. Bandykite dar kartą.';
}

function one($sql, $params = [])
{
    return query($sql, $params)->fetch() ?: null;
}

function rows($sql, $params = [])
{
    return query($sql, $params)->fetchAll();
}

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function text_length($text)
{
    return preg_match_all('/./us', $text);
}

function input($name, $default = '')
{
    return isset($_POST[$name]) && is_scalar($_POST[$name])
        ? trim((string) $_POST[$name])
        : $default;
}

function field($name, $default = '')
{
    return h(input($name, (string) $default));
}

function selected($a, $b)
{
    return (string) $a === (string) $b ? ' selected' : '';
}

function checked($value)
{
    return $value ? ' checked' : '';
}

function url($page = 'home', $params = [])
{
    return '/index.php?' . http_build_query(['page' => $page] + $params);
}

function redirect($page, $params = [])
{
    header('Location: ' . url($page, $params), true, 303);
    exit();
}

function flash($text)
{
    $_SESSION['flash'] = $text;
}

function csrf()
{
    $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
    return '<input type="hidden" name="csrf" value="' . h($_SESSION['csrf']) . '">';
}

function check_csrf()
{
    if (
        !is_string($_POST['csrf'] ?? null) ||
        !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'])
    ) {
        http_response_code(403);
        throw new RuntimeException('Forma paseno. Atnaujinkite puslapį ir bandykite dar kartą.');
    }
}

function user()
{
    if (empty($_SESSION['userId'])) {
        return null;
    }
    $user = one("SELECT * FROM naudotojai WHERE id=? AND busena='aktyvi'", [$_SESSION['userId']]);
    if (!$user) {
        unset($_SESSION['userId']);
    }
    return $user;
}

function require_role(...$roles)
{
    $user = user();
    if (!$user) {
        http_response_code(401);
        throw new RuntimeException('Norėdami tęsti, prisijunkite.');
    }
    if ($roles && !in_array($user['role'], $roles, true)) {
        http_response_code(403);
        throw new RuntimeException('Šio veiksmo atlikti negalite.');
    }
    return $user;
}

function positive_id($value)
{
    if (
        !is_scalar($value) ||
        !ctype_digit((string) $value) ||
        (int) $value < 1 ||
        strlen((string) $value) > 10
    ) {
        throw new RuntimeException('Neteisingas įrašo numeris.');
    }
    return (int) $value;
}

function require_text($value, $label, $max = 2000)
{
    if ($value === '' || !preg_match('//u', $value) || text_length($value) > $max) {
        throw new RuntimeException($label . ': įveskite nuo 1 iki ' . $max . ' ženklų.');
    }
    return $value;
}

function email_address($value)
{
    $value = strtolower($value);
    if (strlen($value) > 254 || !filter_var($value, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Įveskite tinkamą el. pašto adresą.');
    }
    return $value;
}

function valid_password($password, $repeat)
{
    if (strlen($password) > 72 || text_length($password) < 4 || $password !== $repeat) {
        throw new RuntimeException(
            'Slaptažodyje turi būti bent 4 ženklai (iki 72 baitų), o pakartojimas turi sutapti.'
        );
    }
}

function role_label($role)
{
    return [
        'klientas' => 'Klientas',
        'kempingoAdministratorius' => 'Kempingo administratorius',
        'portaloAdministratorius' => 'Portalo administratorius'
    ][$role] ?? $role;
}

function booking_state($booking)
{
    if (
        $booking['busena'] === 'laukiaPatvirtinimo' &&
        $booking['patvirtinimoTerminas'] <= gmdate('Y-m-d H:i:s')
    ) {
        return 'nepatvirtintaLaiku';
    }
    return $booking['busena'];
}

function state_label($state)
{
    return [
        'laukiaPatvirtinimo' => 'Laukia patvirtinimo',
        'patvirtinta' => 'Patvirtinta',
        'nepatvirtintaLaiku' => 'Nepatvirtinta laiku',
        'atsaukta' => 'Atšaukta'
    ][$state] ?? $state;
}

function local_time($utc)
{
    return $utc
        ? new DateTimeImmutable($utc, new DateTimeZone('UTC'))
            ->setTimezone(new DateTimeZone('Europe/Vilnius'))
            ->format('Y-m-d H:i')
        : '—';
}

function cents($price)
{
    [$whole, $part] = array_pad(explode('.', $price, 2), 2, '0');
    return (int) $whole * 100 + (int) str_pad($part, 2, '0');
}

function decimal_price($cents)
{
    return intdiv($cents, 100) . '.' . str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
}

function euros($cents)
{
    return number_format($cents / 100, 2, ',', ' ') . ' €';
}

function parse_price($value)
{
    $value = str_replace(',', '.', $value);
    if (!preg_match('/^\d{1,6}(\.\d{1,2})?$/D', $value)) {
        throw new RuntimeException(
            'Kaina turi būti nuo 0 iki 999999,99, daugiausia du skaitmenys po kablelio.'
        );
    }
    return decimal_price(cents($value));
}

function owned_camp($id, $user, $lock = false)
{
    $camp = one('SELECT * FROM kempingai WHERE id=?' . ($lock ? ' FOR UPDATE' : ''), [$id]);
    if (
        !$camp ||
        $user['role'] !== 'kempingoAdministratorius' ||
        (int) $camp['administratoriausId'] !== (int) $user['id']
    ) {
        http_response_code(403);
        throw new RuntimeException('Šio kempingo administruoti negalite.');
    }
    return $camp;
}

function conversation($id, $user)
{
    $thread = one(
        'SELECT p.*, k.pavadinimas, k.administratoriausId FROM pokalbiai p JOIN kempingai k ON
         k.id=p.kempingoId WHERE p.id=?',
        [$id]
    );
    if (
        !$thread ||
        !(
            ($user['role'] === 'klientas' && (int) $thread['klientoId'] === (int) $user['id']) ||
            ($user['role'] === 'kempingoAdministratorius' &&
                (int) $thread['administratoriausId'] === (int) $user['id'])
        )
    ) {
        http_response_code(403);
        throw new RuntimeException('Šio pokalbio peržiūrėti negalite.');
    }
    return $thread;
}

function booking_access($id, $user)
{
    $booking = one(
        'SELECT r.*, k.administratoriausId, k.pavadinimas FROM rezervacijos r JOIN kempingai k ON
         k.id=r.kempingoId WHERE r.id=?',
        [$id]
    );
    if (
        !$booking ||
        !(
            ($user['role'] === 'klientas' && (int) $booking['klientoId'] === (int) $user['id']) ||
            ($user['role'] === 'kempingoAdministratorius' &&
                (int) $booking['administratoriausId'] === (int) $user['id'])
        )
    ) {
        http_response_code(403);
        throw new RuntimeException('Šios rezervacijos peržiūrėti negalite.');
    }
    return $booking;
}

function booking_lines($id)
{
    return rows(
        'SELECT e.*, p.pavadinimas FROM rezervacijos_eilutes e JOIN paslaugos p ON
         p.id=e.paslaugosId WHERE e.rezervacijosId=? ORDER BY e.id',
        [$id]
    );
}

function booking_total($booking, $lines)
{
    $days = (int) new DateTimeImmutable($booking['pradziosData'])->diff(
        new DateTimeImmutable($booking['pabaigosData'])
    )->days;
    $sum = 0;
    foreach ($lines as $line) {
        $sum += $days * (int) $line['kiekis'] * cents($line['parosKaina']);
    }
    return $sum;
}

require __DIR__ . '/booking.php';
