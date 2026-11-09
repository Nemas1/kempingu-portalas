<?php
require dirname(__DIR__) . '/config.php';
require dirname(__DIR__) . '/actions.php';

$pages = [
    'home' => ['Kempingai', 'catalog'],
    'camp' => ['Kempingo informacija', 'catalog'],
    'confirm' => ['Rezervacijos patvirtinimas', 'catalog'],
    'confirmed' => ['Patvirtinimas', 'catalog'],
    'login' => ['Prisijungimas', 'account'],
    'register' => ['Registracija', 'account'],
    'reservations' => ['Rezervacijos', 'reservations'],
    'messages' => ['Žinutės', 'messages'],
    'manage' => ['Mano kempingai', 'admin'],
    'users' => ['Naudotojai', 'admin'],
    'camps' => ['Kempingų valdymas', 'admin'],
    'moderation' => ['Atsiliepimų priežiūra', 'admin']
];
$page = is_string($_GET['page'] ?? null) ? $_GET['page'] : 'home';
if (!isset($pages[$page])) {
    http_response_code(404);
    $page = 'home';
}
[$title, $view] = $pages[$page];
$error = null;
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        check_csrf();
        handle_action(input('action'));
    }
} catch (Throwable $exception) {
    $error = error_message($exception);
}

// Puslapis rodomas ir po klaidos, kad formoje liktų įvesti duomenys.
ob_start();
try {
    $currentUser = user();
    require dirname(__DIR__) . '/pages/' . $view . '.php';
    $content = ob_get_clean();
} catch (Throwable $exception) {
    ob_end_clean();
    $content = '';
    if (!$error) {
        $error = error_message($exception);
    }
}
$currentUser ??= null;
?>

<!doctype html>
<html lang="lt">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= h($title) ?> · Kempingų portalas</title>
        <link rel="stylesheet" href="/style.css">
    </head>

    <body>
        <header>
            <div>
                <a class="brand" href="<?= h(url()) ?>">Kempingų portalas</a>
                <nav aria-label="Pagrindinis meniu">
                    <a href="<?= h(url()) ?>">Kempingai</a>

                    <?php if ($currentUser): ?>

                        <?php if (in_array($currentUser['role'], ['klientas', 'kempingoAdministratorius'], true)): ?>

                            <a href="<?= h(url('reservations')) ?>">Rezervacijos</a>
                            <a href="<?= h(url('messages')) ?>">Žinutės</a>

                        <?php endif; ?>

                        <?php if ($currentUser['role'] === 'kempingoAdministratorius'): ?>
                            <a href="<?= h(url('manage')) ?>">Mano kempingai</a>
                        <?php endif; ?>

                        <?php if ($currentUser['role'] === 'portaloAdministratorius'): ?>

                            <a href="<?= h(url('users')) ?>">Naudotojai</a>
                            <a href="<?= h(url('camps')) ?>">Kempingų valdymas</a>
                            <a href="<?= h(url('moderation')) ?>">Atsiliepimai</a>

                        <?php endif; ?>

                        <form
                            method="post"
                            class="inline"
                            action="<?= h(url()) ?>"
                        >
                            <?= csrf() ?>
                            <input
                                type="hidden"
                                name="action"
                                value="logout"
                            >
                            <button type="submit">Atsijungti</button>
                        </form>

                    <?php else: ?>
                        <a href="<?= h(url('login')) ?>">Prisijungti</a>
                        <a href="<?= h(url('register')) ?>">Registruotis</a>
                    <?php endif; ?>

                </nav>
            </div>
        </header>
        <main>

            <?php if ($currentUser): ?>
                <p class="muted">
                    Prisijungęs: <?= h($currentUser['vardas'] . ' ' . $currentUser['pavarde']) ?>
                    · <?= h(role_label($currentUser['role'])) ?>
                </p>
            <?php endif; ?>

            <?php if (isset($_SESSION['flash'])): ?>
                <div class="notice" role="status"><?= h($_SESSION['flash']) ?></div>
            <?php
                unset($_SESSION['flash']);
            endif;
            ?>

            <?php if ($error): ?>
                <div class="error" role="alert"><?= h($error) ?></div>
            <?php endif; ?>

            <?= $content ?>

            <?php if ($content === '' && !$currentUser): ?>
                <p>
                    <a href="<?= h(url('login')) ?>">Prisijungti</a> · <a href="<?= h(url()) ?>">Grįžti į
                        kempingų sąrašą</a>
                </p>
            <?php endif; ?>

        </main>
    </body>

</html>
