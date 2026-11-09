# Kempingų portalas

Mokomasis PHP ir MySQL projektas. Paprasta svetainė lietuvių kalba, skirta kempingų paslaugoms rezervuoti. Aplinka paleidžiama su Docker Compose Windows arba Linux sistemoje.

## Funkcijos

- Kempingų paieška ir paslaugų rezervavimas pasirinktoms datoms.
- Palapinių ir kemperių vietų, laužaviečių, vandens dviračių bei hamakų rezervavimas.
- Rezervacijos patvirtinimas unikalia nuoroda el. laiške.
- Kliento ir kempingo administratoriaus susirašinėjimas.
- Atsiliepimai po apsilankymo ir kempingų reitingai.
- Kliento, kempingo administratoriaus ir portalo administratoriaus aplinkos.

## Paleidimas

Reikia Git, Docker ir Docker Compose. Windows sistemoje pirmiausia paleiskite Docker Desktop su Linux konteineriais. Pirmam paleidimui reikalingas internetas.

```text
git clone https://github.com/Nemas1/kempingu-portalas.git
cd kempingu-portalas/programa
```

Sukurkite savo nustatymų failą iš pateikto pavyzdžio.

Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

Linux:

```bash
cp .env.example .env
```

Paleiskite iš `programa` aplanko:

```text
docker compose up -d --build
```

Pirmą kartą aplinkos paruošimas gali užtrukti kelias minutes. DB lentelės ir demonstraciniai duomenys sukuriami automatiškai.

| Komponentas | Adresas | Paskirtis |
| --- | --- | --- |
| Portalas | http://localhost:8080 | Svetainė |
| phpMyAdmin | http://localhost:8081 | Tos pačios MySQL duomenų bazės peržiūra |
| Mailpit | http://localhost:8025 | Demonstracinių el. laiškų peržiūra |

## Demonstracinės paskyros

| Prisijungimo vardas | Slaptažodis | Rolė |
| --- | --- | --- |
| `user` | `stud` | Klientas |
| `camp` | `stud` | Kempingo administratorius |
| `admin` | `stud` | Portalo administratorius |

Papildomos paskyros: `user2` ir `camp2`, slaptažodis `stud`. phpMyAdmin prisijungimas: `stud`, slaptažodis `stud`, duomenų bazė `kempingai`. Tai vietiniam mokymuisi skirti demonstraciniai prisijungimai.

## Rezervacijos patvirtinimas

Prisijunkite kaip klientas, pasirinkite kempingą, datas ir paslaugas. Pateikę rezervaciją atverkite Mailpit, pasirinkite patvirtinimo laišką ir paspauskite jame esančią nuorodą. Atsidariusiame puslapyje patvirtinkite rezervaciją.

Mailpit priima mūsų programos siunčiamus laiškus vietoje tikros pašto dėžutės. Į Gmail ar kitą išorinį paštą numatytoji konfigūracija laiškų nesiunčia. SMTP nustatymai keičiami `.env`, o laiško siuntimo kodas yra `send_confirmation()` funkcijoje faile [programa/app/actions.php](programa/app/actions.php).

## Projekto failai

| Kelias | Turinys |
| --- | --- |
| [programa/app](programa/app) | PHP kodas ir CSS |
| [programa/database/schema.sql](programa/database/schema.sql) | Duomenų bazės lentelių struktūra |
| [programa/database/seed.php](programa/database/seed.php) | Pradiniai demonstraciniai duomenys |
| [programa/docker](programa/docker) | PHP ir MySQL Dockerfile failai |
| [programa/compose.yaml](programa/compose.yaml) | Keturių konteinerių nustatymai |
| [programa/.env.example](programa/.env.example) | Nustatymų pavyzdys |
| [UML/Untitled1.mdzip](UML/Untitled1.mdzip) | MSOSA UML projektas su panaudojimo atvejų, loginio duomenų modelio ir trimis sekų diagramomis |
| [Kempingu_portalas_visas.py](Kempingu_portalas_visas.py) | MSOSA Jython makroprograma modeliui sukurti |

UML projektą galima atidaryti tiesiogiai MSOSA programoje. Makroprograma skirta vykdyti MSOSA aplinkoje; Docker jos nenaudoja. Rankomis pakeistoms diagramoms peržiūrėti atverkite `.mdzip` failą.

Detalesnis PHP failų ir Docker aplinkos aprašymas yra [programa/README.md](programa/README.md).

## Kasdienės komandos

Visas komandas vykdykite iš `programa` aplanko.

```text
docker compose stop
docker compose up -d
docker compose ps
docker compose logs web
```

Pirma komanda sustabdo projektą, antra vėl paleidžia, trečia parodo konteinerių būseną, ketvirta parodo svetainės žurnalą. MySQL įrašai lieka Docker tome `kempingu-portalas_db_data`. PHP ir CSS failų pakeitimai matomi atnaujinus puslapį. Pakeitus Dockerfile, vykdykite `docker compose up -d --build`.

Svetainės pradžios puslapio autorius nustatomas `.env` eilutėje `APP_AUTHOR="Vardas Pavardė"`. Pakeitę šį nustatymą vykdykite `docker compose up -d web`.

Linux Mint VM naudojamos tos pačios paleidimo komandos. Jei portalą atversite iš kito kompiuterio, `.env` faile pakeiskite `APP_URL` į `http://VM_IP:8080`, kad laiškų nuorodos vestų į VM.

Vietinis `.env`, esama Docker duomenų bazė, studijų ataskaita ir dėstytojo pateikti dokumentai į šį repozitoriumą neįtraukiami. Vietiniame darbo aplanke naujausia ataskaita lieka `ataskaita/Kempingu_portalas.docx` ir `ataskaita/Kempingu_portalas.pdf`, o metodiniai dokumentai lieka `saltiniai` aplanke.
