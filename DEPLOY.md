# DEPLOY.md — Pipeline Git → Hostinger hPanel
## Projet Autosav | devnenad.fr | Hostinger Premium

---

## Architecture du pipeline

```
Code local / Claude génère
         │
         ▼
   git commit + push
         │
         ▼
   GitHub (projet_web / main)
         │
         ▼
   hPanel → Git → Deploy  ← 1 clic ou auto-deploy
         │
         ▼
   /public_html (devnenad.fr)

FTP FileZilla  →  public/assets/vendor/   (1 seule fois)
phpMyAdmin     →  SQL master              (1 seule fois)
hPanel .env    →  Variables prod          (1 seule fois)
```

---

## ÉTAPE 1 — Préparer le repo GitHub

### 1.1 Placer les fichiers de setup à la racine du repo

Ajouter à la racine de `projet_web` :
- `.gitignore` (ce repo)
- `.env.example` (modèle de référence)
- `DEPLOY.md` (ce fichier)

### 1.2 Vérifier que vendor/ est présent et commité

```bash
# Dans le repo local
ls vendor/   # doit lister les packages composer
git add vendor/
git commit -m "feat: add vendor for Hostinger deployment (no SSH)"
```

### 1.3 Créer les .gitkeep pour les dossiers storage

```bash
mkdir -p storage/{logs,sessions,cache,uploads,exports,tmp}
touch storage/logs/.gitkeep
touch storage/sessions/.gitkeep
touch storage/cache/.gitkeep
touch storage/uploads/.gitkeep
touch storage/exports/.gitkeep
touch storage/tmp/.gitkeep
git add storage/
git commit -m "feat: add storage directories structure"
```

### 1.4 Push vers GitHub

```bash
git push origin main
```

---

## ÉTAPE 2 — Connecter GitHub → hPanel (1 seule fois)

### Dans hPanel → Websites → devnenad.fr

1. Menu latéral → **Git**
2. Cliquer **Connect to GitHub** (ou "Ajouter dépôt")
3. Renseigner :
   - Repository : `projet_web`
   - Branch : `main`
   - Deploy path : `/public_html`
4. Activer ou non **Auto-deploy** (recommandé : **ON**)
5. Cliquer **Save / Connect**

> ⚠️ Si hPanel demande une clé SSH : copier la clé publique générée par hPanel et l'ajouter dans GitHub → Settings → Deploy keys du repo projet_web.

---

## ÉTAPE 3 — Créer le .env sur le serveur (1 seule fois)

### Via hPanel → File Manager

1. Naviguer vers `/public_html/`
2. Créer un fichier `.env`
3. Coller le contenu de `.env.example` et remplir toutes les valeurs `CHANGEME_*`

**Valeurs clés à remplir :**

```env
APP_ENV=production
APP_URL=https://devnenad.fr
APP_KEY=<générer : php -r "echo bin2hex(random_bytes(32));"> 

DB_NAME=u166513890_v8
DB_USER=<utilisateur BDD hPanel>
DB_PASS=<mot de passe BDD hPanel>

MAIL_USERNAME=noreply@devnenad.fr
MAIL_PASSWORD=<mot de passe email Hostinger>
```

---

## ÉTAPE 4 — Uploader les assets vendor via FTP (1 seule fois)

### Logiciel : FileZilla

**Connexion FTP Hostinger :**
- Host : `ftp.devnenad.fr` (ou l'IP FTP dans hPanel → FTP Accounts)
- Port : 21
- Login/Pass : dans hPanel → FTP Accounts

**Structure à uploader dans `/public_html/public/assets/vendor/` :**

```
vendor/
├── adminlte/          ← AdminLTE 3.2.0
│   ├── css/
│   ├── js/
│   └── plugins/       ← jquery, fontawesome, etc.
├── sweetalert2/
│   ├── sweetalert2.min.css
│   └── sweetalert2.min.js
├── select2/
│   ├── css/select2.min.css
│   └── js/select2.min.js
├── datatables/
│   ├── css/dataTables.bootstrap4.min.css
│   └── js/jquery.dataTables.min.js
└── jquery/
    └── jquery.min.js   ← si pas dans adminlte/plugins/jquery/
```

**URLs de téléchargement :**
- AdminLTE 3.2 : https://github.com/ColorlibHQ/AdminLTE/releases/tag/v3.2.0
- SweetAlert2 : https://github.com/sweetalert2/sweetalert2/releases
- Select2 : https://select2.org/getting-started/installation
- DataTables : https://datatables.net/download/

---

## ÉTAPE 5 — Premier déploiement

### Option A : Auto-deploy (si activé à l'étape 2)
→ Chaque `git push origin main` déclenche automatiquement le déploiement.

### Option B : Manuel via hPanel
1. hPanel → Git → projet_web
2. Cliquer **Deploy**
3. Vérifier les logs de déploiement

---

## Workflow quotidien

```bash
# 1. Modifier le code localement ou avec Claude
# 2. Tester localement si possible

# 3. Commiter et pusher
git add .
git commit -m "feat: description de la modification"
git push origin main

# 4. Si auto-deploy ON → déployé automatiquement
# 5. Si auto-deploy OFF → hPanel → Git → Deploy
```

---

## Gestion des branches

```
main        ← production (devnenad.fr)
develop     ← développement / tests
feature/*   ← fonctionnalités en cours
hotfix/*    ← correctifs urgents
```

**Convention de commits :**
```
feat: nouvelle fonctionnalité
fix: correction de bug
refactor: refactoring sans nouvelle fonctionnalité
test: ajout/modification de tests
docs: documentation
chore: maintenance (gitignore, composer, etc.)
```

---

## Rollback en cas de problème

### Via hPanel Git
1. hPanel → Git → Historique des déploiements
2. Sélectionner le dernier commit stable
3. Cliquer **Deploy this commit**

### Via Git local
```bash
git log --oneline -10
git revert <commit_hash>
git push origin main
```

---

## Variables d'environnement — Référence rapide

| Variable | Environnement | Valeur type |
|----------|---------------|-------------|
| `APP_ENV` | Prod | `production` |
| `APP_DEBUG` | Prod | `false` |
| `DB_HOST` | Prod | `localhost` |
| `MAIL_HOST` | Prod | `smtp.hostinger.com` |
| `MAIL_PORT` | Prod | `587` |
| `SESSION_SECURE` | Prod | `true` |

---

## Checklist de déploiement initial

- [ ] `.gitignore` à la racine du repo
- [ ] `.env.example` commité
- [ ] `vendor/` commité
- [ ] `storage/*/gitkeep` commités
- [ ] GitHub → hPanel connecté
- [ ] `.env` créé sur le serveur via File Manager
- [ ] Assets AdminLTE uploadés via FTP
- [ ] SQL master exécuté via phpMyAdmin
- [ ] Test de connexion : https://devnenad.fr/login

---

*Dernière mise à jour : Mai 2026*
