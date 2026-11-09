<?php if ($page === 'home'): ?>

    <?php
    $search = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
    $camps = rows(
        "SELECT k.*, (SELECT AVG(a.ivertinimas) FROM atsiliepimai a JOIN rezervacijos r ON
         r.id=a.rezervacijosId WHERE r.kempingoId=k.id AND a.pasleptas=0) AS reitingas, (SELECT
         COUNT(*) FROM atsiliepimai a JOIN rezervacijos r ON r.id=a.rezervacijosId WHERE
         r.kempingoId=k.id AND a.pasleptas=0) AS vertinimu FROM kempingai k WHERE k.aktyvus=1 AND
         (k.pavadinimas LIKE ? OR k.miestas LIKE ?) ORDER BY k.pavadinimas",
        ['%' . $search . '%', '%' . $search . '%']
    );
    ?>

    <h1>Kempingų portalas</h1>
    <p class="muted">Autorius: <?= h(getenv('APP_AUTHOR') ?: 'Vardas Pavardė') ?></p>
    <p>Raskite vietą poilsiui, rezervuokite paslaugas ir susisiekite su kempingo administracija.</p>
    <form method="get" class="card">
        <input
            type="hidden"
            name="page"
            value="home"
        >
        <label>Pavadinimas arba miestas
            <input
                name="q"
                value="<?= h($search) ?>"
                maxlength="150"
            >
        </label>
        <button type="submit">Ieškoti</button>
        <a href="<?= h(url()) ?>">Rodyti visus</a>
    </form>

    <?php if (!$camps): ?>
        <p>Pagal pasirinktus kriterijus kempingų nerasta.</p>
    <?php endif; ?>

    <div>

        <?php foreach ($camps as $camp): ?>

            <article class="card">
                <h2><?= h($camp['pavadinimas']) ?></h2>
                <p><?= h($camp['miestas']) ?> · <?= h($camp['adresas']) ?></p>
                <p><?= $camp['reitingas'] === null
                    ? 'Dar nėra atsiliepimų'
                    : 'Reitingas: ' .
                        h(number_format((float) $camp['reitingas'], 1, ',', '')) .
                        ' / 5 (' .
                        h($camp['vertinimu']) .
                        ')' ?></p>
                <p class="text"><?= h($camp['aprasymas']) ?></p>
                <a href="<?= h(url('camp', ['id' => $camp['id']])) ?>">Peržiūrėti kempingą</a>
            </article>

        <?php endforeach; ?>

    </div>

<?php elseif ($page === 'camp'): ?>

    <?php
    $id = positive_id($_GET['id'] ?? '');
    $camp = one('SELECT * FROM kempingai WHERE id=?', [$id]);
    if (
        !$camp ||
        (!$camp['aktyvus'] &&
            !(
                $currentUser &&
                ($currentUser['role'] === 'portaloAdministratorius' ||
                    ($currentUser['role'] === 'kempingoAdministratorius' &&
                        (int) $camp['administratoriausId'] === (int) $currentUser['id']))
            ))
    ) {
        http_response_code(404);
        throw new RuntimeException('Kempingas nerastas.');
    }
    $services = rows(
        'SELECT p.*, t.pavadinimas AS tipas FROM paslaugos p JOIN paslaugu_tipai t ON t.id=p.tipoId
         WHERE p.kempingoId=? AND p.aktyvi=1 ORDER BY p.id',
        [$id]
    );
    $reviews = rows(
        'SELECT a.*, u.vardas FROM atsiliepimai a JOIN rezervacijos r ON r.id=a.rezervacijosId JOIN
         naudotojai u ON u.id=r.klientoId WHERE r.kempingoId=? AND a.pasleptas=0 ORDER BY
         a.sukurimoData DESC',
        [$id]
    );
    $start = input(
        'pradziosData',
        is_string($_GET['pradziosData'] ?? null)
            ? $_GET['pradziosData']
            : date('Y-m-d', strtotime('+1 day'))
    );
    $end = input(
        'pabaigosData',
        is_string($_GET['pabaigosData'] ?? null)
            ? $_GET['pabaigosData']
            : date('Y-m-d', strtotime('+3 days'))
    );
    $dateError = null;
    try {
        $days = dates($start, $end);
    } catch (RuntimeException $e) {
        $dateError = $e->getMessage();
    }
    ?>

    <h1><?= h($camp['pavadinimas']) ?></h1>

    <?php if (!$camp['aktyvus']): ?>
        <p class="error">Kempingas neaktyvus.</p>
    <?php endif; ?>

    <p><?= h($camp['miestas'] . ', ' . $camp['adresas']) ?></p>
    <p class="text"><?= h($camp['aprasymas']) ?></p>
    <p>Kontaktai: <?= h($camp['kontaktinisElPastas']) ?> · <?= h($camp['telefonas']) ?></p>
    <h2>Paslaugos ir rezervavimas</h2>
    <p>Visos paslaugos rezervuojamos paromis. Išvykimo diena neįskaičiuojama.</p>
    <form method="get" class="card">
        <input
            type="hidden"
            name="page"
            value="camp"
        >
        <input
            type="hidden"
            name="id"
            value="<?= $id ?>"
        >
        <label>Atvykimas
            <input
                type="date"
                name="pradziosData"
                value="<?= h($start) ?>"
                min="<?= date('Y-m-d') ?>"
                required
            >
        </label>
        <label>Išvykimas
            <input
                type="date"
                name="pabaigosData"
                value="<?= h($end) ?>"
                min="<?= date('Y-m-d') ?>"
                required
            >
        </label>
        <button type="submit">Tikrinti prieinamumą</button>
    </form>

    <?php if ($dateError): ?>
        <p class="error"><?= h($dateError) ?></p>
    <?php endif; ?>

    <form method="post" action="<?= h(url('camp', ['id' => $id])) ?>">
        <?= csrf() ?>
        <input
            type="hidden"
            name="action"
            value="reserve"
        >
        <input
            type="hidden"
            name="campId"
            value="<?= $id ?>"
        >
        <input
            type="hidden"
            name="pradziosData"
            value="<?= h($start) ?>"
        >
        <input
            type="hidden"
            name="pabaigosData"
            value="<?= h($end) ?>"
        >
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Paslauga</th>
                        <th>Vieneto kaina parai</th>
                        <th>Laisva visam laikotarpiui</th>
                        <th>Pasirinkti kiekį</th>
                    </tr>
                </thead>
                <tbody>

                    <?php
                    foreach ($services as $service):
                        $free = $dateError
                            ? null
                            : max(
                                0,
                                (int) $service['bendrasKiekis'] - peak_reserved((int) $service['id'], $start, $end)
                            );
                        ?>

                        <tr>
                            <td>
                                <strong><?= h($service['pavadinimas']) ?></strong>
                                <br>
                                <span class="small"><?= h($service['tipas']) ?></span>
                                <p class="small text"><?= h($service['aprasymas']) ?></p>
                            </td>
                            <td><?= h(euros(cents($service['parosKaina']))) ?></td>
                            <td><?= $free === null ? 'Patikslinkite datas' : $free ?></td>
                            <td>
                                <label class="small">Kiekis
                                    <input
                                        aria-label="<?= h($service['pavadinimas']) ?>: kiekis"
                                        type="number"
                                        name="kiekis[<?= (int) $service['id'] ?>]"
                                        value="<?= h(
                                            is_scalar($_POST['kiekis'][$service['id']] ?? null)
                                                ? $_POST['kiekis'][$service['id']]
                                                : '0'
                                        ) ?>"
                                        min="0"
                                        max="100000"
                                        step="1"
                                    >
                                </label>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                    <?php if (!$services): ?>
                        <tr>
                            <td colspan="4">Aktyvių paslaugų nėra.</td>
                        </tr>
                    <?php endif; ?>

                </tbody>
            </table>
        </div>

        <?php if ($currentUser && $currentUser['role'] === 'klientas' && $camp['aktyvus']): ?>

            <p>Pasirinktas laikotarpis:
                <?= h($start . ' – ' . $end) ?><?= $dateError ? '' : ' (' . $days . ' paros)' ?>.
                Suma bus apskaičiuota pagal
                pasirinktus kiekius ir pateikta rezervacijoje.</p>
            <p>Pateikus rezervaciją vietos laikomos 30 minučių. Patvirtinkite ją el. paštu gauta nuoroda.
            </p>
            <button type="submit">Pateikti rezervaciją</button>

        <?php elseif (!$currentUser): ?>
            <p>
                <a href="<?= h(url('login')) ?>">Prisijunkite</a>, kad galėtumėte rezervuoti paslaugas.
            </p>
        <?php endif; ?>

    </form>

    <?php if ($currentUser && $currentUser['role'] === 'klientas'): ?>
        <p>
            <a href="<?= h(url('messages', ['new' => $id])) ?>">Parašyti kempingui</a>
        </p>
    <?php endif; ?>

    <h2>Atsiliepimai</h2>
    <p><?= !$reviews
        ? 'Dar nėra atsiliepimų.'
        : 'Reitingas: ' .
            h(
                number_format(
                    array_sum(array_column($reviews, 'ivertinimas')) / count($reviews),
                    1,
                    ',',
                    ''
                )
            ) .
            ' / 5 (' .
            count($reviews) .
            ' atsiliepimai)' ?></p>

    <?php foreach ($reviews as $review): ?>
        <article class="card">
            <strong><?= h($review['vardas']) ?> · <?= (int) $review['ivertinimas'] ?> / 5</strong>
            <p class="text"><?= h($review['komentaras']) ?></p>
            <small><?= h(local_time($review['sukurimoData'])) ?></small>
        </article>
    <?php endforeach; ?>

<?php elseif ($page === 'confirm'): ?>

    <?php
    $token = is_string($_GET['token'] ?? null) ? $_GET['token'] : '';
    $booking = token_booking($token);
    ?>

    <h1>Rezervacijos patvirtinimas</h1>

    <?php if (!token_valid($booking)): ?>

        <p class="error">Nuoroda nebegalioja arba jau panaudota.</p>
        <p>Patikrinkite savo rezervaciją arba paprašykite naujo laiško, jei pradinis terminas dar
            nepasibaigęs.</p>

    <?php else: ?>

        <div class="card">
            <h2>Rezervacija #<?= (int) $booking['id'] ?></h2>
            <p><?= h($booking['pavadinimas']) ?></p>
            <p><?= h($booking['pradziosData'] . ' – ' . $booking['pabaigosData']) ?></p>

            <?php
            $lines = booking_lines((int) $booking['id']);
            foreach ($lines as $line):
                ?>
                <p><?= h($line['pavadinimas']) ?> × <?= (int) $line['kiekis'] ?></p>
            <?php endforeach; ?>

            <p>
                <strong>Bendra suma: <?= h(euros(booking_total($booking, $lines))) ?></strong>
            </p>
            <p>Nuoroda galioja iki <?= h(local_time($booking['patvirtinimoTerminas'])) ?>.</p>
            <p>Vien atvėrus šį puslapį rezervacija dar nepatvirtinama.</p>
            <form method="post">
                <?= csrf() ?>
                <input
                    type="hidden"
                    name="action"
                    value="confirm"
                >
                <input
                    type="hidden"
                    name="token"
                    value="<?= h($token) ?>"
                >
                <button type="submit">Patvirtinti rezervaciją</button>
            </form>
        </div>

    <?php endif; ?>

<?php elseif ($page === 'confirmed'): ?>

    <h1>Rezervacijos patvirtinimas</h1>
    <p>Rezervacijos būseną galite peržiūrėti savo paskyroje.</p>
    <p>
        <a href="<?= h(url('reservations')) ?>">Mano rezervacijos</a> · <a href="<?= h(url()) ?>">Kempingų sąrašas</a>
    </p>

<?php endif; ?>
