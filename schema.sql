CREATE DATABASE IF NOT EXISTS `hub_auto` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `hub_auto`;

-- tables principales
DROP TABLE IF EXISTS `pieces`;
DROP TABLE IF EXISTS `pleins`;
DROP TABLE IF EXISTS `vehicules`;
DROP TABLE IF EXISTS `vehicule`;

CREATE TABLE `vehicules` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `immatriculation` VARCHAR(15) NOT NULL,
    `marque` VARCHAR(50) NOT NULL,
    `modele` VARCHAR(50) NOT NULL,
    `kilometrage` INT NOT NULL,
    `intervalle_vidange` INT DEFAULT 15000,
    `km_derniere_vidange` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE `pleins` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `vehicule_id` INT NOT NULL,
    `date_plein` DATE NOT NULL,
    `kilometrage` INT NOT NULL,
    `litres` DECIMAL(6, 2) NOT NULL,
    `prix_total` DECIMAL(7, 2) NOT NULL,
    `prix_litre` DECIMAL(5, 3) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`vehicule_id`) REFERENCES `vehicules`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `pieces` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `vehicule_id` INT NOT NULL,
    `nom` VARCHAR(150) NOT NULL,
    `reference` VARCHAR(50) NULL,
    `prix` DECIMAL(7, 2) NOT NULL,
    `statut` ENUM('commandee', 'recue', 'montee') DEFAULT 'commandee',
    `date_operation` DATE NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`vehicule_id`) REFERENCES `vehicules`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- donnees de test
INSERT INTO `vehicules` (`id`, `immatriculation`, `marque`, `modele`, `kilometrage`, `intervalle_vidange`, `km_derniere_vidange`) VALUES
(1, 'AB-123-CD', 'Peugeot', '208 1.2 PureTech', 145000, 15000, 135000),
(2, 'EF-456-GH', 'Renault', 'Clio 4 1.5 dCi', 182000, 20000, 170000);

INSERT INTO `pleins` (`id`, `vehicule_id`, `date_plein`, `kilometrage`, `litres`, `prix_total`, `prix_litre`) VALUES
(1, 1, '2026-03-01', 144200, 42.50, 78.63, 1.850),
(2, 1, '2026-03-15', 145000, 46.10, 86.21, 1.870);

INSERT INTO `pieces` (`id`, `vehicule_id`, `nom`, `reference`, `prix`, `statut`, `date_operation`) VALUES
(1, 1, 'Filtre à huile', 'PUR-L343', 14.50, 'montee', '2026-02-10'),
(2, 1, 'Plaquettes avant', 'BRE-P230', 48.90, 'recue', '2026-03-02');