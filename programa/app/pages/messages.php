<?php
$account = require_role('klientas', 'kempingoAdministratorius');
if (!empty($_GET['new'])) {
    require_role('klientas');
    $campId = positive_id($_GET['new']);
    $camp = one('SELECT * FROM kempingai WHERE id=? AND aktyvus=1', [$campId]);
    if (!$camp) {
        throw new RuntimeException('Kempingas nerastas arba neaktyvus.');
    }
    ?>

    <h1>Naujas pokalbis: <?= h($camp['pavadinimas']) ?></h1>
    <form method="post" class="card">
        <?= csrf() ?>
        <input
            type="hidden"
            name="action"
            value="thread"
        >
        <input
            type="hidden"
            name="campId"
            value="<?= $campId ?>"
        >
        <label>Tema
            <input
                name="tema"
                value="<?= field('tema') ?>"
                required
                maxlength="150"
            >
        </label>
        <label>Pirmoji žinutė
            <textarea
                name="tekstas"
                required
                maxlength="2000"
            ><?= field('tekstas') ?></textarea>
        </label>
        <button type="submit">Pradėti pokalbį</button>
    </form>

<?php
    return;
}
if (!empty($_GET['id'])) {
    $thread = conversation(positive_id($_GET['id']), $account);
    query(
        'UPDATE zinutes SET perskaitymoData=UTC_TIMESTAMP() WHERE pokalbioId=? AND siuntejoId<>?
         AND perskaitymoData IS NULL',
        [$thread['id'], $account['id']]
    );
    $messages = rows(
        'SELECT z.*, u.vardas, u.pavarde FROM zinutes z JOIN naudotojai u ON u.id=z.siuntejoId
         WHERE z.pokalbioId=? ORDER BY z.id',
        [$thread['id']]
    );
    ?>

    <h1><?= h($thread['tema']) ?></h1>
    <p><?= h($thread['pavadinimas']) ?> · <a href="<?= h(url('messages')) ?>">Visi pokalbiai</a>
    </p>

    <?php foreach ($messages as $message): ?>
        <article class="message">
            <strong><?= h($message['vardas'] . ' ' . $message['pavarde']) ?></strong>
            <span class="muted small"> · <?= h(local_time($message['issiuntimoData'])) ?></span>
            <p class="text"><?= h($message['tekstas']) ?></p>
            <?php if ((int) $message['siuntejoId'] === (int) $account['id']): ?>
                <small><?= $message['perskaitymoData'] ? 'Perskaityta' : 'Dar neperskaityta' ?></small>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>

    <form method="post" class="card">
        <?= csrf() ?>
        <input
            type="hidden"
            name="action"
            value="message"
        >
        <input
            type="hidden"
            name="id"
            value="<?= (int) $thread['id'] ?>"
        >
        <label>Atsakymas
            <textarea
                name="tekstas"
                required
                maxlength="2000"
            ><?= field('tekstas') ?></textarea>
        </label>
        <button type="submit">Siųsti žinutę</button>
    </form>

<?php
    return;
}
$condition = $account['role'] === 'klientas' ? 'p.klientoId=?' : 'k.administratoriausId=?';
$threads = rows(
    'SELECT p.*, k.pavadinimas, (SELECT COUNT(*) FROM zinutes z WHERE z.pokalbioId=p.id AND
     z.siuntejoId<>? AND z.perskaitymoData IS NULL) AS neperskaityta FROM pokalbiai p JOIN
     kempingai k ON k.id=p.kempingoId WHERE ' .
        $condition .
        ' ORDER BY p.id DESC',
    [$account['id'], $account['id']]
);
?>

<h1>Mano pokalbiai</h1>

<?php if ($account['role'] === 'klientas'): ?>
    <p>Naują pokalbį pradėkite pasirinkto kempingo puslapyje: <a href="<?= h(url()) ?>">rasti
            kempingą</a>.</p>
<?php endif; ?>

<?php if (!$threads): ?>
    <p>Pokalbių dar nėra.</p>
<?php endif; ?>

<?php foreach ($threads as $thread): ?>
    <article class="card">
        <h2>
            <a href="<?= h(url('messages', ['id' => $thread['id']])) ?>"><?= h($thread['tema']) ?></a>
        </h2>
        <p><?= h($thread['pavadinimas']) ?> · Neperskaityta: <?= (int) $thread['neperskaityta'] ?></p>
    </article>
<?php endforeach; ?>
