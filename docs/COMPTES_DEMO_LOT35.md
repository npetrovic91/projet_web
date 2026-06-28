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

Mot de passe d'origine de ce lot : `DemoAutosav!2026` (toujours valable si seul `database/seeds/2026_06_03_lot35_demo_reseau_automobile.sql` a ete importe).

**Mise a jour 2026-06-27 :** `database/seeds/2026_06_27_unifier_mot_de_passe_demo.sql` aligne **TOUS** les comptes demo (`@autosav.demo`, quel que soit le lot : reseau automobile, MotorGroup/ImportAuto/NeoVolt, Groupe/Concession) sur un seul mot de passe commun :

```
Demo2026!
```

Ce script a ete ajoute apres constat, sur un export reel, d'une incoherence : un compte (`pdg.neovolt@autosav.demo`) avait un mot de passe different des 10 autres comptes NeoVolt, jamais couvert par le correctif lot36 ci-dessous (qui ne ciblait que les comptes du reseau automobile). Si ce script a ete importe, **utiliser `Demo2026!` pour tous les comptes demo**, y compris ceux de ce tableau.

Le script stocke uniquement le hash Argon2id de ce mot de passe. Les comptes sont marques comme devant changer leur mot de passe apres connexion. Sur un environnement de demonstration partage ou expose publiquement, supprimer ces comptes (ou changer leur mot de passe) reste necessaire malgre ce marquage.

Si la connexion affiche `Identifiants invalides`, importer le correctif SQL :
`database/seeds/2026_06_03_lot36_reset_demo_passwords.sql` (mot de passe `DemoAutosav!2026`, reseau automobile uniquement) ou, pour tout unifier, `database/seeds/2026_06_27_unifier_mot_de_passe_demo.sql` (mot de passe `Demo2026!`, tous comptes demo).

## Compte démo super_administrateur

Aucun des lots ci-dessus ne crée de compte `super_administrateur` (rôle plateforme, sans société associée). `database/seeds/2026_06_28_demo_super_admin.sql` ajoute :

| Identifiant | Email | Rôle | Mot de passe |
| --- | --- | --- | --- |
| `super.admin.demo` | `super.admin.demo@autosav.demo` | Super-administrateur | `Demo2026!` |

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
