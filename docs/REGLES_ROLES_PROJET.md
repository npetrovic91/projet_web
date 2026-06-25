# Regles de roles issues de PROJET.md

Ce document garde la synthese operationnelle de `PROJET.md` pour aligner le RBAC, les menus et les controles applicatifs.

## Super-admin

- Cree les societes dans le systeme.
- Cree le premier administrateur general de chaque societe.
- Autorise les modules metier et gere les formules souscrites.
- Analyse logs systeme, securite et statistiques.
- Gere maintenance, purge, blocage entreprise et reactivation definitive.
- Accede a toutes les donnees.

## Administrateur general societe

- Administre uniquement la societe qu'il represente.
- Cree, modifie et bloque les utilisateurs de sa societe.
- Attribue roles, fonctions et competences.
- Cree equipes, competences, fonctions, standards, references inventaire et roles propres a sa societe.
- Cree ou lie des societes externes a sa societe.
- Gere les informations de sa societe.
- Gere les relations hierarchiques entre utilisateurs et avec clients/sous-traitants.
- Valide ou delegue certaines operations sensibles.
- Valide le panier d'achat.

Application actuelle :
- role demo : `administrateur_general_societe`
- permissions noyau donnees : `utilisateur.creer`, `utilisateur.modifier`, `utilisateur.bloquer`, `societe.creer`, `societe.modifier`, `role.gerer`, `module.acceder`, `notification.consulter`, `validation.gerer`
- portee : societes rattachees dans `sav_adhesions_utilisateurs_societes`

## Administrateur departement

- Vision globale des services de son departement.
- Dispose des capacites d'un administrateur service dans son perimetre.
- Administre les administrateurs service.

## Administrateur service

- Vision globale de son service, sans voir les autres services.
- Peut faire ce qu'un administrateur equipe fait.
- Cree, modifie et bloque les utilisateurs de sa societe dans son perimetre.
- Attribue roles, fonctions, competences, utilisateurs aux equipes et horaires.
- Declenche les inventaires.
- Valide les demandes des utilisateurs de son service.
- Peut recevoir par delegation des droits plus larges de l'administrateur general.

## Administrateur equipe

- Supervise son equipe.
- Gere absences, retards, demandes utilisateurs et commandes futures.
- Visualise le panier d'achat.
- Cree une demande d'achat.

## Utilisateur standard

- Consulte ses propres donnees.
- Ne voit pas les autres utilisateurs.
- Cree ses demandes de conges et de materiel.
- Peut ajouter plus tard des articles dans le panier general de la societe.

## Regles transversales

- Chaque utilisateur, sauf l'administrateur general, doit avoir un superieur hierarchique.
- Un utilisateur peut etre administre par le super-admin, l'administrateur general de sa societe, l'administrateur service, l'administrateur equipe, ou de maniere limitee par lui-meme.
- Les droits doivent rester contextuels : societe active, concession active, marque active, service, equipe, fonction, module actif et relations entre societes.
