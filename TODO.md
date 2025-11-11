# TODO - Déploiement WordPress.org via GitHub Actions

## 📋 Vue d'ensemble

Ce document décrit comment configurer le déploiement automatique du plugin vers le dépôt WordPress.org SVN en utilisant l'action GitHub [10up/action-wordpress-plugin-deploy](https://github.com/10up/action-wordpress-plugin-deploy).

## ✅ Prérequis

1. **Compte WordPress.org** avec accès SVN au plugin
   - URL du dépôt SVN : `https://plugins.svn.wordpress.org/wp-pierre/`
   - Obtenir les identifiants SVN depuis [votre profil WordPress.org](https://wordpress.org/support/users/your-username/edit/)

2. **Secrets GitHub** à configurer dans le repository :
   - `SVN_USERNAME` : votre identifiant WordPress.org
   - `SVN_PASSWORD` : votre mot de passe WordPress.org (ou token d'application)

## 📁 Structure des assets WordPress.org

Les assets pour WordPress.org doivent être placés dans `.wordpress-org/` à la racine du repository :

```
wp-pierre/
├── .wordpress-org/          # Assets WordPress.org (déployés vers SVN assets/)
│   ├── icon-128x128.png     # Icône 128x128 (requis)
│   ├── icon-256x256.png     # Icône 256x256 (requis)
│   ├── icon.svg              # Icône SVG (requis)
│   ├── banner-772x250.png    # Bannière 772x250 (optionnel mais recommandé)
│   ├── banner-1544x500.png   # Bannière 1544x500 (optionnel mais recommandé)
│   ├── screenshot-1.png     # Capture d'écran 1 (optionnel)
│   └── screenshot-2.png      # Capture d'écran 2 (optionnel)
├── .github/
│   └── workflows/
│       └── deploy.yml        # Workflow GitHub Actions (à créer)
├── .distignore               # Fichiers à exclure du déploiement (à créer)
└── ...
```

**Note** : L'action déplace automatiquement tout le contenu de `.wordpress-org/` vers `assets/` dans SVN (au même niveau que `trunk/` et `tags/`).

## 🔧 Configuration

### 1. Créer `.distignore`

Créez un fichier `.distignore` à la racine pour exclure les fichiers qui ne doivent pas être déployés :

```
/.wordpress-org
/.git
/.github
/node_modules
/vendor
/tests
/docs
composer.json
composer.lock
composer.phar
phpcs.xml
phpunit.xml
junit.xml
.distignore
.gitignore
*.md
!readme.txt
```

**Note** : `.distignore` est utilisé par l'action pour exclure les fichiers du déploiement. Si ce fichier n'existe pas, l'action cherchera un `.gitattributes` avec `export-ignore`.

### 2. Créer le workflow GitHub Actions

Créez le fichier `.github/workflows/deploy.yml` :

```yaml
name: Deploy to WordPress.org

on:
  push:
    tags:
      - '*'

jobs:
  deploy:
    runs-on: ubuntu-latest
    
    steps:
      - name: Checkout code
        uses: actions/checkout@v4
      
      - name: Deploy to WordPress.org
        uses: 10up/action-wordpress-plugin-deploy@stable
        with:
          generate-zip: true
        env:
          SVN_USERNAME: ${{ secrets.SVN_USERNAME }}
          SVN_PASSWORD: ${{ secrets.SVN_PASSWORD }}
          # SLUG: wp-pierre  # Optionnel, par défaut = nom du repo
          # VERSION: 1.0.0   # Optionnel, par défaut = nom du tag
          # ASSETS_DIR: .wordpress-org  # Optionnel, par défaut = .wordpress-org
```

### 3. Workflow avec build (optionnel)

Si vous avez un processus de build (npm, composer, etc.), utilisez `BUILD_DIR` :

```yaml
name: Deploy to WordPress.org

on:
  push:
    tags:
      - '*'

jobs:
  deploy:
    runs-on: ubuntu-latest
    
    steps:
      - name: Checkout code
        uses: actions/checkout@v4
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
      
      - name: Install Composer dependencies
        run: composer install --no-dev --optimize-autoloader
      
      - name: Build assets (si nécessaire)
        run: |
          npm ci
          npm run build
      
      - name: Deploy to WordPress.org
        uses: 10up/action-wordpress-plugin-deploy@stable
        with:
          generate-zip: true
        env:
          SVN_USERNAME: ${{ secrets.SVN_USERNAME }}
          SVN_PASSWORD: ${{ secrets.SVN_PASSWORD }}
          BUILD_DIR: .  # Déploie depuis la racine après build
```

### 4. Workflow avec ZIP attaché à la release GitHub

Pour générer un ZIP et l'attacher automatiquement à la release GitHub :

```yaml
name: Deploy to WordPress.org

on:
  push:
    tags:
      - '*'

jobs:
  deploy:
    runs-on: ubuntu-latest
    
    steps:
      - name: Checkout code
        uses: actions/checkout@v4
      
      - name: Deploy to WordPress.org
        id: deploy
        uses: 10up/action-wordpress-plugin-deploy@stable
        with:
          generate-zip: true
        env:
          SVN_USERNAME: ${{ secrets.SVN_USERNAME }}
          SVN_PASSWORD: ${{ secrets.SVN_PASSWORD }}
      
      - name: Create GitHub Release
        uses: softprops/action-gh-release@v1
        with:
          files: ${{ steps.deploy.outputs.zip-path }}
        env:
          GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}
```

## 🚀 Processus de déploiement

### Étapes pour publier une nouvelle version

1. **Mettre à jour les versions** dans le code :
   - `wp-pierre.php` : `Version: 1.0.1`
   - `readme.txt` : `Stable tag: 1.0.1`
   - Constante `PIERRE_VERSION` dans `wp-pierre.php`

2. **Commit et push** les changements :
   ```bash
   git add .
   git commit -m "Bump version to 1.0.1"
   git push origin main
   ```

3. **Créer et pousser un tag** :
   ```bash
   git tag 1.0.1
   git push origin 1.0.1
   ```

4. **Le workflow GitHub Actions se déclenche automatiquement** :
   - Checkout du code au tag
   - Exclusion des fichiers via `.distignore`
   - Déploiement vers SVN `trunk/`
   - Création du tag SVN `tags/1.0.1/`
   - Déploiement des assets depuis `.wordpress-org/` vers `assets/`

5. **Vérification** :
   - Vérifier sur [plugins.svn.wordpress.org](https://plugins.svn.wordpress.org/wp-pierre/)
   - Vérifier que les assets sont bien dans `assets/`
   - Vérifier que le tag est créé dans `tags/1.0.1/`

## 🧪 Test en mode dry-run

Pour tester sans commit SVN, utilisez `dry-run: true` :

```yaml
- name: Deploy to WordPress.org (dry-run)
  uses: 10up/action-wordpress-plugin-deploy@stable
  with:
    dry-run: true
  env:
    SVN_USERNAME: ${{ secrets.SVN_USERNAME }}
    SVN_PASSWORD: ${{ secrets.SVN_PASSWORD }}
```

**Note** : En mode `dry-run`, les secrets SVN ne sont pas obligatoires.

## 📝 Mise à jour des assets entre les releases

Pour mettre à jour uniquement les assets (icônes, bannières, screenshots) sans créer une nouvelle version :

Utilisez l'action dédiée : [10up/action-wordpress-plugin-readme-asset-update](https://github.com/10up/action-wordpress-plugin-readme-asset-update)

```yaml
name: Update WordPress.org Assets

on:
  workflow_dispatch  # Déclenchement manuel
  push:
    paths:
      - '.wordpress-org/**'
      - 'readme.txt'

jobs:
  update-assets:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      
      - uses: 10up/action-wordpress-plugin-readme-asset-update@stable
        env:
          SVN_USERNAME: ${{ secrets.SVN_USERNAME }}
          SVN_PASSWORD: ${{ secrets.SVN_PASSWORD }}
```

## ⚠️ Points d'attention

1. **Ne jamais modifier un tag existant** : Créez toujours une nouvelle version (1.0.1, 1.0.2, etc.) plutôt que de réécrire 1.0.0.

2. **Cohérence des versions** : Assurez-vous que :
   - Le tag Git = version dans `wp-pierre.php` = `Stable tag` dans `readme.txt`

3. **Assets globaux** : Les assets dans `.wordpress-org/` sont déployés vers `assets/` qui est global à toutes les versions. Ils n'ont pas besoin d'être mis à jour à chaque release.

4. **Exclusion des fichiers** : Vérifiez que `.distignore` exclut bien :
   - `.wordpress-org/` (ne doit pas être dans le ZIP du plugin)
   - Fichiers de développement (tests, docs, etc.)
   - Fichiers de build temporaires

5. **Screenshots** : Si vous ajoutez des screenshots, mettez à jour la section `== Screenshots ==` dans `readme.txt` avec une liste numérotée.

## 📚 Ressources

- [Action 10up - Documentation complète](https://github.com/10up/action-wordpress-plugin-deploy)
- [Guide WordPress.org - Plugin Assets](https://developer.wordpress.org/plugins/wordpress-org/plugin-assets/)
- [Guide WordPress.org - SVN](https://developer.wordpress.org/plugins/wordpress-org/how-to-use-subversion/)

## ✅ Checklist avant le premier déploiement

- [ ] Secrets `SVN_USERNAME` et `SVN_PASSWORD` configurés dans GitHub
- [ ] Fichier `.distignore` créé et testé
- [ ] Assets dans `.wordpress-org/` (icônes au minimum)
- [ ] Workflow `.github/workflows/deploy.yml` créé
- [ ] Versions cohérentes dans `wp-pierre.php` et `readme.txt`
- [ ] Test en mode `dry-run` effectué
- [ ] Tag Git créé et poussé pour déclencher le déploiement

