# Deployment Checklist (Hostinger / Linux)

To ensure the scraping system works the same in production as locally:

## 1. Case sensitivity (Linux)

The folder must be `app/services` (lowercase), not `app/Services`. Git preserves case. If you see `BindingResolutionException` for `GeminiService` or similar, verify:

```bash
ls -la app/ | grep -i service
# Should show: services (lowercase)
```

## 2. After deploy: clear & rebuild caches

```bash
composer install --no-dev --optimize-autoloader
php artisan config:clear
php artisan config:cache
php artisan route:clear
php artisan view:clear
```

**Important:** `config:clear` then `config:cache` ensures the new `config/scraping.php` is included.

## 3. Verify config file exists

```bash
ls -la config/scraping.php
```

If missing, the blacklist will default to empty (no URLs filtered). The app will still run.

## 4. Queue worker (if using ProcessScrapedEntryJob)

```bash
php artisan queue:restart
```

## 5. Common deploy errors

| Error | Fix |
|-------|-----|
| `Class "App\services\GeminiService" not found` | `app/services` folder must be lowercase. Run `composer dump-autoload`. |
| `config(scraping.blacklist)` returns null | Run `php artisan config:clear` then `php artisan config:cache`. |
| DOMDocument / libxml issues | ContentSanitizer falls back to strip_tags if DOM fails. Check PHP extensions: `php -m \| grep -E 'dom|libxml|mbstring'` |
