<?php if ($page === 'manage'): ?>

    <?php
    $account = require_role('kempingoAdministratorius');
    $myCamps = rows('SELECT * FROM kempingai WHERE administratoriausId=? ORDER BY pavadinimas', [
        $account['id']
    ]);
    ?>

    <h1>Mano kempingai</h1>

    <?php if (!$myCamps): ?>
        <p>Jums dar nepriskirta kempingų. Kreipkitės į portalo administratorių.</p>
    <?php
        return;
    endif;
    ?>

    <div>
        <?php foreach ($myCamps as $item): ?>
            <a href="<?= h(url('manage', ['id' => $item['id']])) ?>"><?= h($item['pavadinimas']) ?></a>
        <?php endforeach; ?>
    </div>

    <?php
    $campId = isset($_GET['id']) ? positive_id($_GET['id']) : (int) $myCamps[0]['id'];
    $camp = owned_camp($campId, $account);
    $types = rows('SELECT * FROM paslaugu_tipai ORDER BY id');
    $services = rows('SELECT * FROM paslaugos WHERE kempingoId=? ORDER BY id', [$campId]);
    $edit = null;
    if (!empty($_GET['service'])) {
        $edit = one('SELECT * FROM paslaugos WHERE id=? AND kempingoId=?', [
            positive_id($_GET['service']),
            $campId
        ]);
        if (!$edit) {
            throw new RuntimeException('Paslauga nerasta.');
        }
    }
    $campPost = input('action') === 'saveCamp';
    $servicePost = input('action') === 'saveService';
    ?>

    <h2><?= h($camp['pavadinimas']) ?></h2>
    <details <?= $campPost ? ' open' : '' ?>>
        <summary>Kempingo informacija</summary>
        <form method="post" action="<?= h(url('manage', ['id' => $campId])) ?>">
            <?= csrf() ?>
            <input
                type="hidden"
                name="action"
                value="saveCamp"
            >
            <input
                type="hidden"
                name="id"
                value="<?= $campId ?>"
            >

            <?php
            foreach (
                [
                    'pavadinimas' => 'Pavadinimas',
                    'adresas' => 'Adresas',
                    'miestas' => 'Miestas',
                    'kontaktinisElPastas' => 'Kontaktinis el. paštas',
                    'telefonas' => 'Telefonas'
                ]
                as $key => $label
            ):
                ?>

                <label><?= h($label) ?>
                    <input
                        name="<?= h($key) ?>"
                        type="<?= $key === 'kontaktinisElPastas' ? 'email' : 'text' ?>"
                        value="<?= h($campPost ? input($key) : $camp[$key]) ?>"
                        required
                        maxlength="<?= [
                            'pavadinimas' => 150,
                            'adresas' => 200,
                            'miestas' => 100,
                            'kontaktinisElPastas' => 254,
                            'telefonas' => 30
                        ][$key] ?>"
                    >
                </label>

            <?php endforeach; ?>

            <label>Aprašymas
                <textarea
                    name="aprasymas"
                    required
                    maxlength="5000"
                ><?= h($campPost ? input('aprasymas') : $camp['aprasymas']) ?></textarea>
            </label>
            <label>
                <input
                    type="checkbox"
                    name="aktyvus"
                    value="1"
                    <?= checked($campPost ? isset($_POST['aktyvus']) : (bool) $camp['aktyvus']) ?>
                >Kempingas aktyvus
            </label>
            <button type="submit">Išsaugoti kempingą</button>
        </form>
    </details>
    <h2>Paslaugos</h2>
    <p>
        <a href="<?= h(url('manage', ['id' => $campId])) ?>">Pridėti naują paslaugą</a> · <a href="<?= h(url('reservations')) ?>">Peržiūrėti
            rezervacijas</a>
    </p>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Paslauga</th>
                    <th>Kaina parai</th>
                    <th>Bendras kiekis</th>
                    <th>Būsena</th>
                    <th>
                    </th>
                </tr>
            </thead>
            <tbody>

                <?php foreach ($services as $service): ?>
                    <tr>
                        <td><?= h($service['pavadinimas']) ?></td>
                        <td><?= h(euros(cents($service['parosKaina']))) ?></td>
                        <td><?= (int) $service['bendrasKiekis'] ?></td>
                        <td><?= $service['aktyvi'] ? 'Aktyvi' : 'Neaktyvi' ?></td>
                        <td>
                            <a href="<?= h(url('manage', ['id' => $campId, 'service' => $service['id']])) ?>">Redaguoti</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <form
        method="post"
        class="card"
        action="<?= h(url('manage', ['id' => $campId] + ($edit ? ['service' => $edit['id']] : []))) ?>"
    >
        <h2><?= $edit ? 'Redaguoti paslaugą' : 'Nauja paslauga' ?></h2>
        <?= csrf() ?>
        <input
            type="hidden"
            name="action"
            value="saveService"
        >
        <input
            type="hidden"
            name="campId"
            value="<?= $campId ?>"
        >
        <input
            type="hidden"
            name="id"
            value="<?= $edit ? (int) $edit['id'] : '' ?>"
        >
        <label>Pavadinimas
            <input
                name="pavadinimas"
                value="<?= h($servicePost ? input('pavadinimas') : $edit['pavadinimas'] ?? '') ?>"
                required
                maxlength="150"
            >
        </label>
        <label>Tipas<select name="tipoId">
                <?php foreach ($types as $type): ?>
                    <option
                        value="<?= (int) $type['id'] ?>"
                        <?= selected(
                            $servicePost ? input('tipoId') : ($edit['tipoId'] ?? ''),
                            $type['id']
                        ) ?>
                    ><?= h($type['pavadinimas']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Aprašymas
            <textarea
                name="aprasymas"
                required
                maxlength="2000"
            ><?= h($servicePost ? input('aprasymas') : $edit['aprasymas'] ?? '') ?></textarea>
        </label>
        <label>Vieneto kaina parai, Eur
            <input
                name="parosKaina"
                inputmode="decimal"
                value="<?= h($servicePost ? input('parosKaina') : $edit['parosKaina'] ?? '0.00') ?>"
                required
            >
        </label>
        <label>Bendras kiekis
            <input
                type="number"
                name="bendrasKiekis"
                value="<?= h($servicePost ? input('bendrasKiekis') : $edit['bendrasKiekis'] ?? '1') ?>"
                min="1"
                max="100000"
                required
            >
        </label>
        <label>
            <input
                type="checkbox"
                name="aktyvi"
                value="1"
                <?= checked($servicePost ? isset($_POST['aktyvi']) : (bool) ($edit['aktyvi'] ?? true)) ?>
            >Paslauga aktyvi
        </label>
        <button type="submit">Išsaugoti paslaugą</button>
    </form>

<?php elseif ($page === 'users'): ?>

    <?php
    require_role('portaloAdministratorius');
    $accounts = rows('SELECT * FROM naudotojai ORDER BY id');
    ?>

    <h1>Naudotojų valdymas</h1>
    <p>Paskyros blokuojamos išsaugant jų istoriją. Prieš blokuodami kempingo administratorių perduokite
        visus jo kempingus kitam administratoriui.</p>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Naudotojas</th>
                    <th>El. paštas</th>
                    <th>Rolė</th>
                    <th>Būsena</th>
                    <th>Veiksmas</th>
                </tr>
            </thead>
            <tbody>

                <?php foreach ($accounts as $item): ?>
                    <tr>
                        <td><?= h($item['vardas'] . ' ' . $item['pavarde']) ?>
                            <br>
                            <small><?= h($item['prisijungimoVardas']) ?></small>
                        </td>
                        <td><?= h($item['elPastas']) ?></td>
                        <td>
                            <form method="post" class="user-role">
                                <?= csrf() ?>
                                <input type="hidden" name="action" value="userRole">
                                <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                <select name="role" aria-label="Naudotojo rolė">
                                    <?php foreach (['klientas', 'kempingoAdministratorius', 'portaloAdministratorius'] as $role): ?>
                                        <option
                                            value="<?= h($role) ?>"
                                            <?= selected($item['role'], $role) ?>
                                        ><?= h(role_label($role)) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit">Keisti rolę</button>
                            </form>
                        </td>
                        <td><?= $item['busena'] === 'aktyvi' ? 'Aktyvi' : 'Blokuota' ?></td>
                        <td>

                            <?php if ((int) $item['id'] !== (int) $currentUser['id']): ?>
                                <form method="post">
                                    <?= csrf() ?>
                                    <input
                                        type="hidden"
                                        name="action"
                                        value="userStatus"
                                    >
                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= (int) $item['id'] ?>"
                                    >
                                    <input
                                        type="hidden"
                                        name="busena"
                                        value="<?= $item['busena'] === 'aktyvi' ? 'blokuota' : 'aktyvi' ?>"
                                    >
                                    <button type="submit"><?= $item['busena'] === 'aktyvi' ? 'Blokuoti' : 'Aktyvinti' ?></button>
                                </form>
                            <?php else: ?>
                                Jūsų paskyra
                            <?php endif; ?>

                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <form method="post" class="card">
        <?= csrf() ?>
        <input
            type="hidden"
            name="action"
            value="createUser"
        >
        <h2>Sukurti naudotoją ir priskirti rolę</h2>
        <label>Vardas
            <input
                name="vardas"
                value="<?= field('vardas') ?>"
                required
                maxlength="80"
            >
        </label>
        <label>Pavardė
            <input
                name="pavarde"
                value="<?= field('pavarde') ?>"
                required
                maxlength="80"
            >
        </label>
        <label>Prisijungimo vardas
            <input
                name="prisijungimoVardas"
                value="<?= field('prisijungimoVardas') ?>"
                required
                minlength="3"
                maxlength="50"
                autocomplete="off"
            >
        </label>
        <label>El. paštas
            <input
                type="email"
                name="elPastas"
                value="<?= field('elPastas') ?>"
                required
                maxlength="254"
            >
        </label>
        <label>Rolė<select name="role">
                <?php foreach (['kempingoAdministratorius', 'klientas', 'portaloAdministratorius'] as $role): ?>
                    <option
                        value="<?= h($role) ?>"
                        <?= selected(input('role', 'kempingoAdministratorius'), $role) ?>
                    ><?= h(role_label($role)) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Slaptažodis (bent 4 ženklai)
            <input
                type="password"
                name="slaptazodis"
                required
                minlength="4"
                maxlength="72"
                autocomplete="new-password"
            >
        </label>
        <label>Pakartokite slaptažodį
            <input
                type="password"
                name="pakartojimas"
                required
                minlength="4"
                maxlength="72"
                autocomplete="new-password"
            >
        </label>
        <button type="submit">Sukurti paskyrą</button>
    </form>

<?php elseif ($page === 'camps'): ?>

    <?php
    require_role('portaloAdministratorius');
    $camps = rows(
        'SELECT k.*, u.prisijungimoVardas FROM kempingai k JOIN naudotojai u ON
         u.id=k.administratoriausId ORDER BY k.id'
    );
    $admins = rows(
        "SELECT * FROM naudotojai WHERE role='kempingoAdministratorius' AND busena='aktyvi' ORDER
         BY prisijungimoVardas"
    );
    $edit = null;
    if (!empty($_GET['id'])) {
        $edit = one('SELECT * FROM kempingai WHERE id=?', [positive_id($_GET['id'])]);
        if (!$edit) {
            throw new RuntimeException('Kempingas nerastas.');
        }
    }
    $posted = input('action') === 'saveCamp';
    ?>

    <h1>Kempingų valdymas</h1>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Kempingas</th>
                    <th>Miestas</th>
                    <th>Administratorius</th>
                    <th>Būsena</th>
                    <th>
                    </th>
                </tr>
            </thead>
            <tbody>

                <?php foreach ($camps as $camp): ?>
                    <tr>
                        <td><?= h($camp['pavadinimas']) ?></td>
                        <td><?= h($camp['miestas']) ?></td>
                        <td><?= h($camp['prisijungimoVardas']) ?></td>
                        <td><?= $camp['aktyvus'] ? 'Aktyvus' : 'Neaktyvus' ?></td>
                        <td>
                            <a href="<?= h(url('camps', ['id' => $camp['id']])) ?>">Redaguoti / perduoti</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p>
        <a href="<?= h(url('camps')) ?>">Sukurti naują kempingą</a>
    </p>
    <form method="post" class="card">
        <h2><?= $edit ? 'Redaguoti kempingą' : 'Naujas kempingas' ?></h2>
        <?= csrf() ?>
        <input
            type="hidden"
            name="action"
            value="saveCamp"
        >
        <input
            type="hidden"
            name="id"
            value="<?= $edit ? (int) $edit['id'] : '' ?>"
        >

        <?php
        foreach (
            [
                'pavadinimas' => 'Pavadinimas',
                'adresas' => 'Adresas',
                'miestas' => 'Miestas',
                'kontaktinisElPastas' => 'Kontaktinis el. paštas',
                'telefonas' => 'Telefonas'
            ]
            as $key => $label
        ):
            ?>

            <label><?= h($label) ?>
                <input
                    name="<?= h($key) ?>"
                    type="<?= $key === 'kontaktinisElPastas' ? 'email' : 'text' ?>"
                    value="<?= h($posted ? input($key) : $edit[$key] ?? '') ?>"
                    required
                    maxlength="<?= [
                        'pavadinimas' => 150,
                        'adresas' => 200,
                        'miestas' => 100,
                        'kontaktinisElPastas' => 254,
                        'telefonas' => 30
                    ][$key] ?>"
                >
            </label>

        <?php endforeach; ?>

        <label>Aprašymas
            <textarea
                name="aprasymas"
                required
                maxlength="5000"
            ><?= h($posted ? input('aprasymas') : $edit['aprasymas'] ?? '') ?></textarea>
        </label>
        <label>Administratorius<select name="administratoriausId" required>
                <option value="">Pasirinkite</option>
                <?php foreach ($admins as $admin): ?>
                    <option value="<?= (int) $admin['id'] ?>" <?= selected(
                        $posted ? input('administratoriausId') : $edit['administratoriausId'] ?? '',
                        $admin['id']
                    ) ?>><?= h($admin['vardas'] . ' ' . $admin['pavarde'] . ' (' . $admin['prisijungimoVardas'] . ')') ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <input
                type="checkbox"
                name="aktyvus"
                value="1"
                <?= checked($posted ? isset($_POST['aktyvus']) : (bool) ($edit['aktyvus'] ?? true)) ?>
            >Kempingas aktyvus
        </label>
        <button type="submit">Išsaugoti kempingą</button>
    </form>

<?php elseif ($page === 'moderation'): ?>

    <?php
    require_role('portaloAdministratorius');
    $reviews = rows(
        'SELECT a.*, k.pavadinimas, u.prisijungimoVardas FROM atsiliepimai a JOIN rezervacijos r ON
         r.id=a.rezervacijosId JOIN kempingai k ON k.id=r.kempingoId JOIN naudotojai u ON
         u.id=r.klientoId ORDER BY a.id DESC'
    );
    ?>

    <h1>Atsiliepimų priežiūra</h1>
    <p>Paslėpti atsiliepimai išsaugomi, tačiau nerodomi lankytojams ir neįtraukiami į reitingą.</p>

    <?php if (!$reviews): ?>
        <p>Atsiliepimų dar nėra.</p>
    <?php endif; ?>

    <?php foreach ($reviews as $review): ?>
        <article class="card">
            <h2><?= h($review['pavadinimas']) ?> · <?= (int) $review['ivertinimas'] ?> / 5</h2>
            <p><?= h($review['prisijungimoVardas']) ?> · <?= $review['pasleptas'] ? 'Paslėptas' : 'Viešas' ?></p>
            <p class="text"><?= h($review['komentaras']) ?></p>

            <?php if ($review['pasleptas']): ?>
                <p>Priežastis: <?= h($review['paslepimoPriezastis']) ?></p>
            <?php endif; ?>

            <form method="post">
                <?= csrf() ?>
                <input
                    type="hidden"
                    name="action"
                    value="moderate"
                >
                <input
                    type="hidden"
                    name="id"
                    value="<?= (int) $review['id'] ?>"
                >
                <input
                    type="hidden"
                    name="pasleptas"
                    value="<?= $review['pasleptas'] ? '0' : '1' ?>"
                >

                <?php if (!$review['pasleptas']): ?>
                    <label>Paslėpimo priežastis
                        <textarea
                            name="paslepimoPriezastis"
                            required
                            maxlength="500"
                        ><?= (int) input('id', '0') === (int) $review['id'] ? field('paslepimoPriezastis') : '' ?></textarea>
                    </label>
                <?php endif; ?>

                <button type="submit"><?= $review['pasleptas'] ? 'Vėl rodyti viešai' : 'Paslėpti atsiliepimą' ?></button>
            </form>
        </article>
    <?php endforeach; ?>

<?php endif; ?>
