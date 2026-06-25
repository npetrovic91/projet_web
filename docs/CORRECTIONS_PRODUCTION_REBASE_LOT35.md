# Corrections production appliquées sur la base lot35

Base retenue : `autosav_alignement_lot35_corrections_finales_production(2).zip`.

Cette archive conserve l'intégralité du lot35 : Core/Modules, 430 routes, binaires de maintenance, tests, migrations et services production.

Ajouts/corrections appliqués sans réduire le périmètre fonctionnel :

- ajout d'un `.htaccess` racine pour empêcher l'exposition de Core, Modules, config, vendor, storage, database, tests, docs, deploy et bin ;
- renforcement de `public/.htaccess` ;
- ajout de `public/.user.ini` ;
- vidage du journal `storage/logs/error.log` avant livraison ;
- ajout du dump `database/u166513890_base.sql` lorsqu'il est disponible dans le dossier projet ;
- ajout de `scripts/check-production.php` ;
- ajout du rapport de comparaison `docs/RAPPORT_COMPARAISON_LOT35.md`.

Aucune route du lot35 n'a été supprimée.
Aucun module métier du lot35 n'a été retiré.
