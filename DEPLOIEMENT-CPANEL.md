# Mettre le backend en ligne sur un hébergement cPanel

Objectif : l'API répond sur `https://api.votre-domaine.com/api/v1/surahs`.
Remplacez partout `api.votre-domaine.com` par votre sous-domaine et `utilisateur` par votre identifiant cPanel.

Pré-requis : Laravel 12 demande **PHP 8.2 ou plus** et une base **MySQL**.

---

## 1. Préparer l'archive sur votre ordinateur

Dans le dossier `backend/` :

1. Exportez votre base locale (elle contient déjà les 114 sourates et les versets) :
   `mysqldump -u root -p quran_db > quran_db.sql`
   (ou depuis phpMyAdmin local : base `quran_db` › Exporter › SQL)
2. Compressez le dossier `backend/` **avec** `vendor/`, **sans** `.env`, `node_modules/` ni `storage/logs/*.log` → `backend.zip`.

## 2. Créer la base de données (cPanel › Bases de données MySQL)

1. Créez une base, par ex. `utilisateur_quran`.
2. Créez un utilisateur MySQL avec un mot de passe fort.
3. Ajoutez l'utilisateur à la base avec **Tous les privilèges**.
4. cPanel › **phpMyAdmin** › choisissez la base › **Importer** › `quran_db.sql`.

## 3. Choisir la version de PHP (cPanel › Sélectionner une version PHP / MultiPHP Manager)

Mettez le sous-domaine en **PHP 8.2 ou 8.3**. Extensions à cocher si la liste est proposée :
`pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `curl`.

## 4. Envoyer les fichiers (cPanel › Gestionnaire de fichiers)

1. Créez le dossier `/home/utilisateur/quran-backend` (**en dehors** de `public_html`).
2. Envoyez `backend.zip` dedans, puis **Extraire**.
3. Donnez les droits d'écriture (Permissions **775**) à `storage/` et `bootstrap/cache/` (cochez « récursif »).

## 5. Pointer le sous-domaine vers `public/` (cPanel › Domaines ou Sous-domaines)

Modifiez le sous-domaine : **Racine du document** = `/home/utilisateur/quran-backend/public`.
C'est le point le plus important : seul le dossier `public/` de Laravel doit être visible sur le web.

> Si l'hébergeur n'accepte pas de racine hors de `public_html` : mettez le projet dans
> `/home/utilisateur/quran-backend`, puis copiez le contenu de `public/` dans la racine du
> sous-domaine et, dans le `index.php` copié, remplacez `__DIR__.'/../` par
> `'/home/utilisateur/quran-backend/` (deux lignes : `vendor/autoload.php` et `bootstrap/app.php`).

## 6. Créer le fichier `.env` (Gestionnaire de fichiers › `quran-backend/` › Nouveau fichier `.env`)

```
APP_NAME=Bayan
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.votre-domaine.com
APP_KEY=        # copiez la ligne APP_KEY de votre .env local

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=utilisateur_quran
DB_USERNAME=utilisateur_quranuser
DB_PASSWORD=le-mot-de-passe

LOG_CHANNEL=stack
LOG_LEVEL=error
CACHE_STORE=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
```

`APP_DEBUG=false` est obligatoire en ligne : sinon les erreurs affichent vos mots de passe.

## 7. Activer le HTTPS (cPanel › SSL/TLS Status)

Cochez le sous-domaine › **Run AutoSSL**. Quelques minutes plus tard, `https://` fonctionne.
L'application Android refuse les adresses `http://` : cette étape est indispensable.

## 8. Mettre en cache la configuration (cPanel › Terminal, si disponible)

```
cd ~/quran-backend
php artisan config:cache
php artisan route:cache
```

Sans Terminal : ce n'est pas obligatoire, l'API fonctionne sans.

## 9. Vérifier

Ouvrez dans le navigateur : `https://api.votre-domaine.com/api/v1/surahs`
Vous devez voir un texte qui commence par `{"success":true,"data":[{"id":1,...`.

En cas de page blanche ou d'erreur 500 : regardez `quran-backend/storage/logs/laravel.log`
(souvent : mauvais identifiants MySQL dans `.env`, ou droits manquants sur `storage/`).

## 10. Brancher l'application

Remplacez l'adresse locale par l'adresse en ligne, à deux endroits :

1. **EAS** (utilisé pour les APK) :
   `npx eas-cli env:update --variable-name EXPO_PUBLIC_API_URL --variable-environment preview --value https://api.votre-domaine.com/api`
   (même commande avec `--variable-environment production` et `--variable-environment development`,
   ou depuis expo.dev › projet › Environment variables)
2. **`quran-app/.env`** (utilisé en développement) :
   `EXPO_PUBLIC_API_URL=https://api.votre-domaine.com/api`

Puis refaites l'APK : `npx eas-cli build -p android --profile preview`.
Tant que le backend n'est pas en ligne, l'application charge le texte depuis Quran.com : elle reste utilisable.
