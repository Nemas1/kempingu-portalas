<?php
$account = require_role('klientas', 'kempingoAdministratorius');
if (!empty($_GET['id'])) {
    $booking = booking_access(positive_id($_GET['id']), $account);
    $lines = booking_lines((int) $booking['id']);
    $state = booking_state($booking);
    $canCancel =
        in_array($state, ['laukiaPatvirtinimo', 'patvirtinta'], true) &&
        $booking['pradziosData'] > date('Y-m-d');
    ?>

    <h1>Rezervacija #<?= (int) $booking['id'] ?></h1>
    <p>
        <a href="<?= h(url('reservations')) ?>">Visos rezervacijos</a>
    </p>
    <div class="card">
        <h2><?= h($booking['pavadinimas']) ?></h2>
        <p><?= h($booking['pradziosData'] . ' – ' . $booking['pabaigosData']) ?></p>
        <p>Būsena: <strong><?= h(state_label($state)) ?></strong>
        </p>

        <?php if ($state === 'laukiaPatvirtinimo'): ?>
            <p>Patvirtinkite iki <?= h(local_time($booking['patvirtinimoTerminas'])) ?>. Patvirtinimo nuoroda pateikta el. laiške.</p>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>Paslauga</th>
                    <th>Kiekis</th>
                    <th>Užfiksuota vieneto kaina parai</th>
                </tr>
            </thead>
            <tbody>

                <?php foreach ($lines as $line): ?>
                    <tr>
                        <td><?= h($line['pavadinimas']) ?></td>
                        <td><?= (int) $line['kiekis'] ?></td>
                        <td><?= h(euros(cents($line['parosKaina']))) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p>
            <strong>Bendra suma: <?= h(euros(booking_total($booking, $lines))) ?></strong>
        </p>

        <?php if ($booking['atsaukimoData']): ?>
            <p>Atšaukta: <?= h(local_time($booking['atsaukimoData'])) ?></p>
            <p class="text"><?= h($booking['atsaukimoPriezastis']) ?></p>
        <?php endif; ?>

        <?php if ($state === 'laukiaPatvirtinimo' && $account['role'] === 'klientas'): ?>

            <form method="post">
                <?= csrf() ?>
                <input
                    type="hidden"
                    name="action"
                    value="resend"
                >
                <input
                    type="hidden"
                    name="id"
                    value="<?= (int) $booking['id'] ?>"
                >
                <button type="submit">Siųsti patvirtinimo laišką dar kartą</button>
            </form>

        <?php endif; ?>

        <?php if ($canCancel): ?>
            <details>
                <summary>Atšaukti rezervaciją</summary>
                <p>Atšaukus rezervaciją, jos vietos bus atlaisvintos.</p>
                <form method="post">
                    <?= csrf() ?>
                    <input
                        type="hidden"
                        name="action"
                        value="cancel"
                    >
                    <input
                        type="hidden"
                        name="id"
                        value="<?= (int) $booking['id'] ?>"
                    >
                    <label>Atšaukimo priežastis<?= $account['role'] === 'klientas' ? ' (neprivaloma)' : '' ?>
                        <textarea
                            name="atsaukimoPriezastis"
                            maxlength="500"
                            <?= $account['role'] === 'kempingoAdministratorius' ? ' required' : '' ?>
                        ><?= field('atsaukimoPriezastis') ?></textarea>
                    </label>
                    <button type="submit">Patvirtinti atšaukimą</button>
                </form>
            </details>
        <?php endif; ?>

    </div>

    <?php
    if (
        $account['role'] === 'klientas' &&
        $state === 'patvirtinta' &&
        $booking['pabaigosData'] <= date('Y-m-d')
    ):
        $review = one('SELECT * FROM atsiliepimai WHERE rezervacijosId=?', [$booking['id']]);
        ?>

        <h2>Atsiliepimas</h2>

        <?php if ($review): ?>
            <div class="card">
                <p>Šiai rezervacijai jau pateikėte atsiliepimą: <?= (int) $review['ivertinimas'] ?> / 5.</p>
                <p class="text"><?= h($review['komentaras']) ?></p>
                <?php if ($review['pasleptas']): ?>
                    <p>Atsiliepimas paslėptas: <?= h($review['paslepimoPriezastis']) ?></p>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <form method="post" class="card">
                <?= csrf() ?>
                <input
                    type="hidden"
                    name="action"
                    value="review"
                >
                <input
                    type="hidden"
                    name="id"
                    value="<?= (int) $booking['id'] ?>"
                >
                <label>Įvertinimas<select name="ivertinimas">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <option value="<?= $i ?>" <?= selected(input('ivertinimas', '5'), $i) ?>><?= $i ?> / 5</option>
                        <?php endfor; ?>
                    </select>
                </label>
                <label>Komentaras
                    <textarea
                        name="komentaras"
                        required
                        maxlength="2000"
                    ><?= field('komentaras') ?></textarea>
                </label>
                <button type="submit">Pateikti atsiliepimą</button>
            </form>
        <?php endif; ?>

<?php
    endif;
    return;
}
$condition = $account['role'] === 'klientas' ? 'r.klientoId=?' : 'k.administratoriausId=?';
$bookings = rows(
    'SELECT r.*, k.pavadinimas, u.vardas, u.pavarde FROM rezervacijos r JOIN kempingai k ON
     k.id=r.kempingoId JOIN naudotojai u ON u.id=r.klientoId WHERE ' .
        $condition .
        ' ORDER BY r.id DESC',
    [$account['id']]
);
?>

<h1><?= $account['role'] === 'klientas' ? 'Mano rezervacijos' : 'Mano kempingų rezervacijos' ?></h1>

<?php if (!$bookings): ?>
    <p>Rezervacijų dar nėra.</p>
<?php else: ?>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Numeris</th>
                    <th>Kempingas</th>
                    <th>Datos</th>
                    <th>Būsena</th>
                    <?php if ($account['role'] !== 'klientas'): ?>
                        <th>Klientas</th>
                    <?php endif; ?>
                    <th>
                    </th>
                </tr>
            </thead>
            <tbody>

                <?php foreach ($bookings as $booking): ?>
                    <tr>
                        <td>#<?= (int) $booking['id'] ?></td>
                        <td><?= h($booking['pavadinimas']) ?></td>
                        <td><?= h($booking['pradziosData'] . ' – ' . $booking['pabaigosData']) ?></td>
                        <td><?= h(state_label(booking_state($booking))) ?></td>
                        <?php if ($account['role'] !== 'klientas'): ?>
                            <td><?= h($booking['vardas'] . ' ' . $booking['pavarde']) ?></td>
                        <?php endif; ?>
                        <td>
                            <a href="<?= h(url('reservations', ['id' => $booking['id']])) ?>">Peržiūrėti</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
