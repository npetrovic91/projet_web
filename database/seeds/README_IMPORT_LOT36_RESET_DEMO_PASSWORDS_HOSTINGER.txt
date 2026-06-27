AUTOSAV — Import du correctif lot36 sur hébergement Hostinger
================================================================

Symptôme : après avoir importé la migration lot35 (réseau démo Auto
Avenue) via phpMyAdmin sur Hostinger, la connexion avec un compte
démo (ex. admin.general / DemoAutosav!2026) affiche systématiquement
« Identifiants invalides », alors que le mot de passe annoncé dans
docs/COMPTES_DEMO_LOT35.md est correct.

Cause la plus fréquente : l'import partiel ou répété d'un dump sur
Hostinger laisse parfois certains comptes avec uti_est_verrouille=1
ou un compteur uti_echecs_connexion non remis à zéro (verrouillage
hérité d'un import précédent), ce qui bloque la connexion même avec
le bon mot de passe.

Procédure :
1. Se connecter à hPanel → Bases de données → phpMyAdmin.
2. Sélectionner la base u166513890_base.
3. Onglet « Importer » → choisir le fichier
   database/seeds/2026_06_03_lot36_reset_demo_passwords.sql
   → Exécuter.
4. Réessayer la connexion avec un compte démo et le mot de passe
   DemoAutosav!2026 (le changement de mot de passe sera demandé à la
   première connexion : c'est attendu, uti_doit_changer_mot_de_passe
   est remis à 1 par ce correctif).

Si « Identifiants invalides » persiste après ce correctif, vérifier
en priorité :
- que uti_email_normalise correspond bien à l'email en minuscules
  (ex. admin.general@autosav.demo) ;
- qu'aucune entrée sav_security_blocks / blocage IP n'est active
  pour l'adresse depuis laquelle vous testez ;
- que l'horloge serveur Hostinger n'a pas placé uti_verrouille_jusqua
  dans le futur (ce correctif le remet à NULL, donc ne devrait plus
  se produire après import).
