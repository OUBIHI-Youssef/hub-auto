# Hub Auto (Projet perso PHP / MySQL)

Petit gestionnaire pour suivre l'entretien et les dépenses de mes voitures (consommation de carburant, suivi des pièces et vidange).

Projet initialement développé en local sous XAMPP, déposé sur GitHub pour archivage et versionnement.

## Fonctionnalités actuelles
- Gestion de plusieurs véhicules (sélecteur rapide)
- Calcul de la consommation moyenne (L/100 km) entre les pleins
- Calcul dynamique du prix au litre en JS pendant la saisie
- Jauge d'alerte pour l'échéance de la vidange
- Enregistrement des pièces et suivi du statut (commandée, reçue, montée)

## Installation en local
1. Cloner ou télécharger le dossier dans `xampp/htdocs/hub-auto`
2. Importer `schema.sql` dans phpMyAdmin sur une base nommée `hub_auto`
3. Vérifier la connexion dans `db.php` (par défaut `root` sans mot de passe)
4. Lancer `http://localhost/hub-auto/`