# Comptes de demonstration lot35

> **AVERTISSEMENT (audit securite 2026-06-26, LOW-4) :** le mot de passe
> ci-dessous est PUBLIC (visible par quiconque a acces a ce depot Git).
> Ces comptes ne doivent **jamais exister dans la base de production
> reelle** — ni avec ce mot de passe, ni avec un autre. Ils servent
> uniquement aux environnements de demonstration/recette.
> `database/seeds/` n'est jamais applique automatiquement par
> `bin/migrate.php` (qui ne scanne que `database/migrations/`) : ne pas
> importer ces fichiers sur la base client.

Ces comptes sont crees par `database/seeds/2026_06_03_lot35_demo_reseau_automobile.sql`.

Mot de passe temporaire commun : `DemoAutosav!2026`.

Le script stocke uniquement le hash Argon2id de ce mot de passe. Les comptes sont marques comme devant changer leur mot de passe apres connexion. Sur un environnement de demonstration partage ou expose publiquement, supprimer ces comptes (ou changer leur mot de passe) reste necessaire malgre ce marquage.

Si la connexion affiche `Identifiants invalides`, importer le correctif SQL :
`database/seeds/2026_06_03_lot36_reset_demo_passwords.sql`.

| Identifiant | Email | Role | Fonction | Societe / concession active | Marque active |
| --- | --- | --- | --- | --- | --- |
| `admin.general` | `admin.general@autosav.demo` | Administrateur general societe | Direction generale | Auto Avenue Groupe | Toutes |
| `responsable.groupe` | `responsable.groupe@autosav.demo` | Responsable groupe de concessions | Direction generale | Auto Avenue Groupe | Toutes |
| `directeur.paris` | `directeur.paris@autosav.demo` | Directeur de concession | Direction concession | Auto Avenue Paris | Volkswagen |
| `responsable.sav` | `responsable.sav@autosav.demo` | Responsable apres-vente | Responsable apres-vente | Auto Avenue Paris | Audi |
| `conseiller.service` | `conseiller.service@autosav.demo` | Conseiller service | Conseiller service | Auto Avenue Marseille | Renault |
| `technicien.diagnostic` | `technicien.diagnostic@autosav.demo` | Technicien SAV | Technicien diagnostic | Auto Avenue Lyon | BMW |
| `gestionnaire.pieces` | `gestionnaire.pieces@autosav.demo` | Gestionnaire pieces | Gestionnaire pieces | Premium Motors Strasbourg | Mercedes |
| `responsable.garantie` | `responsable.garantie@autosav.demo` | Responsable garantie | Responsable garantie | Auto Avenue Lille | Peugeot |
| `magasinier.lille` | `magasinier.lille@autosav.demo` | Gestionnaire pieces | Magasinier pieces | Auto Avenue Lille | Opel |
| `conseiller.toulouse` | `conseiller.toulouse@autosav.demo` | Conseiller service | Conseiller service | Garage Multimarque Toulouse | Fiat |
