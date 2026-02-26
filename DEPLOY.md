# Deployment Checklist (Hostinger / Linux)

To ensure the scraping system works the same in production as locally:

## 1. Document root et .htaccess (Hostinger)

Sur Hostinger, le **document root** pointe vers `public_html` (racine du projet), alors que `index.php` et `.htaccess` sont dans `public/`. Le fichier **`.htaccess` à la racine** redirige toutes les requêtes vers `public/` :

```
.htaccess (racine)  →  redirige vers  →  public/index.php
```

**Structure attendue sur Hostinger** :
```
public_html/          ← document root Hostinger
├── .htaccess         ← redirige vers public/
├── app/
├── config/
├── public/           ← index.php, .htaccess, assets
├── routes/
└── ...
```

**Alternative** : Si Hostinger permet de changer le document root, pointez-le directement vers `public_html/public` (ou le dossier `public` de votre projet).

## 2. Case sensitivity (Linux)

The folder must be `app/services` (lowercase), not `app/Services`. Git preserves case. If you see `BindingResolutionException` for `GeminiService` or similar, verify:

```bash
ls -la app/ | grep -i service
# Should show: services (lowercase)
```

## 3. After deploy: clear & rebuild caches

```bash
composer install --no-dev --optimize-autoloader
php artisan config:clear
php artisan l5-swagger:generate
php artisan config:cache
php artisan route:clear
php artisan view:clear
```

**Important:** Run `config:clear` before `l5-swagger:generate` to avoid "Documentation config not found". Generate docs before `config:cache`.

## 4. Verify config file exists

```bash
ls -la config/scraping.php
```

If missing, the blacklist will default to empty (no URLs filtered). The app will still run.

## 5. Queue worker (if using ProcessScrapedEntryJob)

```bash
php artisan queue:restart
```

## 6. L5-Swagger (API docs)

- **URL Swagger UI** : `/api/swagger-ui` (évite 403 Hostinger sur `/api/documentation`)
- **Génération** : `php artisan l5-swagger:generate` (après `config:clear`, avant `config:cache`)
- Si `storage/api-docs/` n'existe pas : `mkdir -p storage/api-docs && chmod 775 storage/api-docs`

## 7. Common deploy errors

| Error | Fix |
|-------|-----|
| 403 Forbidden / page blanche | Le `.htaccess` à la racine doit rediriger vers `public/`. Vérifiez que Hostinger pointe le document root sur la racine du projet (pas `public/`). |
| `Documentation config not found` | Run `php artisan config:clear` then `php artisan l5-swagger:generate` before `config:cache`. |
| 403 on Swagger UI | Use `/api/swagger-ui` (not `/api/documentation`). Hostinger ModSecurity may block "documentation". |
| `Class "App\services\GeminiService" not found` | `app/services` folder must be lowercase. Run `composer dump-autoload`. |
| `config(scraping.blacklist)` returns null | Run `php artisan config:clear` then `php artisan config:cache`. |
| DOMDocument / libxml issues | ContentSanitizer falls back to strip_tags if DOM fails. Check PHP extensions: `php -m \| grep -E 'dom|libxml|mbstring'` |
