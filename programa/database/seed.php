<?php
require dirname(__DIR__) . '/app/config.php';

if ((int) query('SELECT COUNT(*) FROM naudotojai')->fetchColumn() > 0) {
    echo "Duomenys jau yra; pradiniai įrašai pakartotinai nekuriami.\n";
    exit();
}
db()->beginTransaction();
try {
    $password = password_hash('stud', PASSWORD_DEFAULT);
    foreach (
        [
            ['Portalo', 'Administratorius', 'admin', 'portaloAdministratorius'],
            ['Jonas', 'Kempingas', 'camp', 'kempingoAdministratorius'],
            ['Lina', 'Klientė', 'user', 'klientas'],
            ['Mantas', 'Klientas', 'user2', 'klientas'],
            ['Rasa', 'Kempingas', 'camp2', 'kempingoAdministratorius']
        ]
        as [$name, $surname, $login, $role]
    ) {
        query(
            'INSERT INTO naudotojai
             (vardas,pavarde,prisijungimoVardas,elPastas,slaptazodzioMaisa,role) VALUES
             (?,?,?,?,?,?)',
            [$name, $surname, $login, $login . '@example.lt', $password, $role]
        );
    }
    foreach (
        ['Palapinės vieta', 'Kemperio vieta', 'Laužavietė', 'Vandens dviratis', 'Hamakas']
        as $type
    ) {
        query('INSERT INTO paslaugu_tipai (pavadinimas) VALUES (?)', [$type]);
    }
    foreach (
        [
            [
                2,
                'Ežero kempingas',
                'Rami poilsio vieta prie ežero. Tinka šeimoms ir aktyvaus poilsio mėgėjams.',
                'Ežero g. 12',
                'Molėtai'
            ],
            [
                5,
                'Miško poilsiavietė',
                'Poilsis pušyne, palapinių ir kemperių vietos, laužavietės.',
                'Pušų g. 8',
                'Anykščiai'
            ],
            [
                2,
                'Pajūrio kempingas',
                'Paprastas kempingas netoli jūros. Yra vanduo ir elektros įvadai.',
                'Kopų g. 5',
                'Šventoji'
            ]
        ]
        as [$admin, $name, $description, $address, $city]
    ) {
        query(
            'INSERT INTO kempingai
             (administratoriausId,pavadinimas,aprasymas,adresas,miestas,kontaktinisElPastas,telefonas)
             VALUES (?,?,?,?,?,?,?)',
            [$admin, $name, $description, $address, $city, 'info@example.lt', '+370 600 00000']
        );
        $campId = (int) db()->lastInsertId();
        foreach (
            [
                [1, 'Palapinės vieta', 'Vieta vienai palapinei ir iki keturių asmenų.', '15.00', 8],
                [2, 'Kemperio vieta', 'Vieta kemperiui su elektros įvadu.', '25.00', 4],
                [3, 'Laužavietė', 'Atskira laužavietė su suolais.', '10.00', 2],
                [4, 'Vandens dviratis', 'Vienas vandens dviratis pasirinktai parai.', '8.00', 3],
                [5, 'Hamakas', 'Vienas hamakas poilsiui.', '5.00', 6]
            ]
            as [$type, $service, $details, $price, $quantity]
        ) {
            query(
                'INSERT INTO paslaugos
                 (kempingoId,tipoId,pavadinimas,aprasymas,parosKaina,bendrasKiekis) VALUES
                 (?,?,?,?,?,?)',
                [$campId, $type, $service, $details, $price, $quantity]
            );
        }
    }
    // Pasibaigusios rezervacijos leidžia iš karto parodyti atsiliepimo pateikimą.
    foreach (
        [[3, 1, 1, -14, -12], [3, 1, 2, -7, -5], [4, 2, 6, -10, -8], [3, 1, 1, 7, 10]]
        as [$client, $camp, $service, $from, $to]
    ) {
        query(
            "INSERT INTO rezervacijos
             (klientoId,kempingoId,pradziosData,pabaigosData,busena,patvirtinimoTerminas) VALUES
             (?,?,?,?,'patvirtinta',UTC_TIMESTAMP())",
            [
                $client,
                $camp,
                date('Y-m-d', strtotime("$from days")),
                date('Y-m-d', strtotime("$to days"))
            ]
        );
        $bookingId = (int) db()->lastInsertId();
        query(
            'INSERT INTO rezervacijos_eilutes (rezervacijosId,paslaugosId,kiekis,parosKaina) SELECT
             ?,id,1,parosKaina FROM paslaugos WHERE id=?',
            [$bookingId, $service]
        );
        if ($from === -14 || $from === -10) {
            query(
                'INSERT INTO atsiliepimai (rezervacijosId,ivertinimas,komentaras) VALUES (?,?,?)',
                [
                    $bookingId,
                    $from === -14 ? 5 : 4,
                    $from === -14
                        ? 'Tvarkinga ir ramu. Patiko poilsis prie ežero.'
                        : 'Graži aplinka, malonus bendravimas.'
                ]
            );
        }
    }
    query('INSERT INTO pokalbiai (klientoId,kempingoId,tema) VALUES (3,1,?)', [
        'Dėl atvykimo laiko'
    ]);
    $threadId = (int) db()->lastInsertId();
    query('INSERT INTO zinutes (pokalbioId,siuntejoId,tekstas) VALUES (?,3,?)', [
        $threadId,
        'Sveiki, ar galime atvykti apie 18 valandą?'
    ]);
    query('INSERT INTO zinutes (pokalbioId,siuntejoId,tekstas) VALUES (?,2,?)', [
        $threadId,
        'Sveiki! Taip, lauksime jūsų iki 20 valandos.'
    ]);
    db()->commit();
    echo "Pradiniai naudotojai, kempingai ir demonstraciniai duomenys sukurti.\n";
} catch (Throwable $error) {
    db()->rollBack();
    throw $error;
}
