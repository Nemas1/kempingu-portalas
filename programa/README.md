# Kempingų portalas

PHP, MySQL ir paprastas CSS. Paleidimui reikia Docker; Windows sistemoje – paleisto Docker Desktop.

Pirmą kartą nukopijuokite `.env.example` į `.env`: Windows PowerShell komanda `Copy-Item .env.example .env`, Linux komanda `cp .env.example .env`. Vietinis `.env` į GitHub nekeliamas.

Šiame aplanke vykdykite:

```text
docker compose up -d --build
```

Portalas: http://localhost:8080

Pradžios puslapyje rodomas projekto pavadinimas ir autorius. Savo vardą bei pavardę įrašykite `.env` failo eilutėje `APP_AUTHOR="Vardas Pavardė"`. Pakeitę šį nustatymą, vykdykite `docker compose up -d web`.

Duomenų bazės peržiūra per phpMyAdmin: http://localhost:8081. Prisijungimo vardas `stud`, slaptažodis `stud`. Kairėje pasirinkite duomenų bazę `kempingai`, tada lentelę ir skirtuką „Naršyti“. Tai ta pati portalo duomenų bazė.

Laiškų dėžutė rezervacijos patvirtinimui: http://localhost:8025. Laiškai demonstravimui gaunami čia; į tikrą paštą siunčiama nurodžius savo SMTP nustatymus `.env` faile.

Portalo prisijungimai: `user` (klientas), `camp` (kempingo administratorius), `admin` (portalo administratorius). Visų slaptažodis: `stud`. Papildomos paskyros: `user2`, `camp2`. DB naudotojo `stud` ir DB administratoriaus `root` slaptažodis taip pat `stud`.

Portalo administratorius naudotojų sąraše gali keisti paskyrų roles. Kempingus turinčio administratoriaus rolė keičiama tik perdavus jo kempingus kitam administratoriui; sistemoje turi likti aktyvus portalo administratorius.

Programos failai sugrupuoti pagal paskirtį:

- `app/public/index.php` – puslapio parinkimas, meniu ir pranešimai.
- `app/public/style.css` – paprastas puslapio stilius.
- `app/config.php` – DB jungtis ir bendros funkcijos.
- `app/actions.php` – formų veiksmai ir laiškų siuntimas.
- `app/booking.php` – rezervavimo logika.
- `app/pages/catalog.php` – kempingai ir rezervacijos patvirtinimas.
- `app/pages/account.php` – prisijungimas ir registracija.
- `app/pages/reservations.php` – rezervacijų peržiūra ir atsiliepimo forma.
- `app/pages/messages.php` – susirašinėjimas.
- `app/pages/admin.php` – kempingų ir portalo administravimas.

`database/schema.sql` aprašo DB lenteles. `database/seed.php` pirmą kartą sukuria demonstracinius duomenis.

`docker/web.Dockerfile` paruošia PHP ir Apache aplinką. `docker/db.Dockerfile` prideda mūsų lentelių struktūrą prie oficialaus MySQL atvaizdo ir tikrina DB parengtį. Mailpit ir phpMyAdmin naudojami paruošti, todėl jiems atskirų Dockerfile nereikia. `compose.yaml` sujungia šiuos keturis komponentus, nustato prievadus ir duomenų saugojimą. Keičiami nustatymai ir slaptažodžiai laikomi `.env` faile.

Pakeitus Dockerfile ar DB struktūros failą, atvaizdus reikia perrinkti su `docker compose up -d --build`. Lentelių kūrimo SQL vykdomas tik tuščioje DB; esamų įrašų perrinkimas nepanaikina.

Į Linux Mint VM perkelkite šį aplanką ir vykdykite tą pačią komandą. Jei portalą atversite iš kito kompiuterio, `.env` faile pakeiskite `APP_URL` į `http://VM_IP:8080`, kad el. laiškuose būtų tinkamos nuorodos. `APP_AUTHOR` pakeiskite į savo vardą ir pavardę. Pirmam paleidimui reikalingas internetas atvaizdams atsisiųsti.

Sustabdyti: `docker compose stop`. Vėl paleisti: `docker compose up -d`. Duomenys lieka MySQL tome.

Norėdami peržiūrėti duomenų bazę, šiame aplanke atverkite terminalą ir vykdykite:

```text
docker compose exec db mysql -u stud -p kempingai
```

Įveskite DB slaptažodį iš `.env` failo `DB_PASSWORD` eilutės. MySQL lange galima rašyti:

```sql
SHOW TABLES;
SELECT id, vardas, pavarde, role FROM naudotojai;
SELECT * FROM rezervacijos;
```

Išeiti iš MySQL: `exit`.
