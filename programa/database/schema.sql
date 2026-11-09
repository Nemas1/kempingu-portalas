SET NAMES utf8mb4;

CREATE TABLE naudotojai (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 vardas VARCHAR(80) NOT NULL,
 pavarde VARCHAR(80) NOT NULL,
 prisijungimoVardas VARCHAR(50) NOT NULL UNIQUE,
 elPastas VARCHAR(254) NOT NULL UNIQUE,
 slaptazodzioMaisa VARCHAR(255) NOT NULL,
 role ENUM('klientas','kempingoAdministratorius','portaloAdministratorius') NOT NULL DEFAULT 'klientas',
 busena ENUM('aktyvi','blokuota') NOT NULL DEFAULT 'aktyvi',
 registracijosData DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE kempingai (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 administratoriausId BIGINT UNSIGNED NOT NULL,
 pavadinimas VARCHAR(150) NOT NULL,
 aprasymas TEXT NOT NULL,
 adresas VARCHAR(200) NOT NULL,
 miestas VARCHAR(100) NOT NULL,
 kontaktinisElPastas VARCHAR(254) NOT NULL,
 telefonas VARCHAR(30) NOT NULL,
 aktyvus BOOLEAN NOT NULL DEFAULT TRUE,
 FOREIGN KEY (administratoriausId) REFERENCES naudotojai(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE paslaugu_tipai (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 pavadinimas VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE paslaugos (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 kempingoId BIGINT UNSIGNED NOT NULL,
 tipoId BIGINT UNSIGNED NOT NULL,
 pavadinimas VARCHAR(150) NOT NULL,
 aprasymas TEXT NOT NULL,
 parosKaina DECIMAL(10,2) NOT NULL,
 bendrasKiekis INT NOT NULL,
 aktyvi BOOLEAN NOT NULL DEFAULT TRUE,
 FOREIGN KEY (kempingoId) REFERENCES kempingai(id),
 FOREIGN KEY (tipoId) REFERENCES paslaugu_tipai(id),
 CHECK (parosKaina >= 0), CHECK (bendrasKiekis > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE rezervacijos (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 klientoId BIGINT UNSIGNED NOT NULL,
 kempingoId BIGINT UNSIGNED NOT NULL,
 pradziosData DATE NOT NULL,
 pabaigosData DATE NOT NULL,
 busena ENUM('laukiaPatvirtinimo','patvirtinta','nepatvirtintaLaiku','atsaukta') NOT NULL DEFAULT 'laukiaPatvirtinimo',
 patvirtinimoTerminas DATETIME NOT NULL,
 sukurimoData DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 atsaukimoData DATETIME NULL,
 atsaukimoPriezastis VARCHAR(500) NULL,
 FOREIGN KEY (klientoId) REFERENCES naudotojai(id),
 FOREIGN KEY (kempingoId) REFERENCES kempingai(id),
 INDEX prieinamumas (kempingoId, busena, pradziosData, pabaigosData),
 CHECK (pabaigosData > pradziosData)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE rezervacijos_eilutes (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 rezervacijosId BIGINT UNSIGNED NOT NULL,
 paslaugosId BIGINT UNSIGNED NOT NULL,
 kiekis INT NOT NULL,
 parosKaina DECIMAL(10,2) NOT NULL,
 FOREIGN KEY (rezervacijosId) REFERENCES rezervacijos(id),
 FOREIGN KEY (paslaugosId) REFERENCES paslaugos(id),
 UNIQUE (rezervacijosId, paslaugosId),
 INDEX paslaugosRezervacijos (paslaugosId, rezervacijosId),
 CHECK (kiekis > 0), CHECK (parosKaina >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE patvirtinimo_nuorodos (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 rezervacijosId BIGINT UNSIGNED NOT NULL UNIQUE,
 zetonoMaisa CHAR(64) NOT NULL UNIQUE,
 galiojimoPabaiga DATETIME NOT NULL,
 panaudojimoData DATETIME NULL,
 sukurimoData DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (rezervacijosId) REFERENCES rezervacijos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE pokalbiai (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 klientoId BIGINT UNSIGNED NOT NULL,
 kempingoId BIGINT UNSIGNED NOT NULL,
 tema VARCHAR(150) NOT NULL,
 sukurimoData DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (klientoId) REFERENCES naudotojai(id),
 FOREIGN KEY (kempingoId) REFERENCES kempingai(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE zinutes (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 pokalbioId BIGINT UNSIGNED NOT NULL,
 siuntejoId BIGINT UNSIGNED NOT NULL,
 tekstas TEXT NOT NULL,
 issiuntimoData DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 perskaitymoData DATETIME NULL,
 FOREIGN KEY (pokalbioId) REFERENCES pokalbiai(id),
 FOREIGN KEY (siuntejoId) REFERENCES naudotojai(id),
 INDEX pokalbioZinutes (pokalbioId, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE atsiliepimai (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 rezervacijosId BIGINT UNSIGNED NOT NULL UNIQUE,
 ivertinimas INT NOT NULL,
 komentaras TEXT NOT NULL,
 sukurimoData DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 pasleptas BOOLEAN NOT NULL DEFAULT FALSE,
 paslepimoPriezastis VARCHAR(500) NULL,
 FOREIGN KEY (rezervacijosId) REFERENCES rezervacijos(id),
 CHECK (ivertinimas BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
