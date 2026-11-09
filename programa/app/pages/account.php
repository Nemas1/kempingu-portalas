<?php if ($page === 'login'): ?>

    <h1>Prisijungimas</h1>
    <form
        method="post"
        class="card"
        action="<?= h(url('login')) ?>"
    >
        <?= csrf() ?>
        <input
            type="hidden"
            name="action"
            value="login"
        >
        <label>Prisijungimo vardas
            <input
                name="prisijungimoVardas"
                value="<?= field('prisijungimoVardas') ?>"
                required
                maxlength="50"
                autocomplete="username"
            >
        </label>
        <label>Slaptažodis
            <input
                type="password"
                name="slaptazodis"
                required
                maxlength="72"
                autocomplete="current-password"
            >
        </label>
        <button type="submit">Prisijungti</button>
        <p>Neturite paskyros? <a href="<?= h(url('register')) ?>">Registruotis</a>
        </p>
    </form>

<?php elseif ($page === 'register'): ?>

    <h1>Kliento registracija</h1>
    <form
        method="post"
        class="card"
        action="<?= h(url('register')) ?>"
    >
        <?= csrf() ?>
        <input
            type="hidden"
            name="action"
            value="register"
        >
        <label>Vardas
            <input
                name="vardas"
                value="<?= field('vardas') ?>"
                required
                maxlength="80"
                autocomplete="given-name"
            >
        </label>
        <label>Pavardė
            <input
                name="pavarde"
                value="<?= field('pavarde') ?>"
                required
                maxlength="80"
                autocomplete="family-name"
            >
        </label>
        <label>Prisijungimo vardas
            <input
                name="prisijungimoVardas"
                value="<?= field('prisijungimoVardas') ?>"
                required
                minlength="3"
                maxlength="50"
                autocomplete="username"
            >
        </label>
        <p class="muted small">Naudokite lotyniškas raides, skaitmenis, tašką, brūkšnelį arba
            pabraukimą.</p>
        <label>El. paštas
            <input
                type="email"
                name="elPastas"
                value="<?= field('elPastas') ?>"
                required
                maxlength="254"
                autocomplete="email"
            >
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
        <button type="submit">Registruotis</button>
    </form>

<?php endif; ?>
