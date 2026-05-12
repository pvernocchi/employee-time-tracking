# Update / Upgrade Guide

This guide covers updating an existing deployment to a newer release.

## 1) Backup before updating

Always create:

- A database backup
- A backup of current application files

## 2) Deploy new release files

Upload new code to the same application path, keeping:

- Existing `config/config.php`
- Existing writable folders/files required by your hosting setup

If you deploy with GitHub Actions FTP, the workflow already runs:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
```

before upload.

## 3) Trigger application upgrade

- Open the application URL after deployment.
- If schema updates are required, the app redirects to `/install/upgrade`.

The upgrader will:

1. Detect pending migrations in `database/migrations/`
2. Apply them in version order
3. Record applied versions in `schema_migrations`
4. Update `app_meta` database version metadata

## 4) Verify upgrade success

- Confirm you can log in and navigate key pages
- Confirm `/install/upgrade` no longer appears
- Optionally verify latest migration version exists in `schema_migrations`

## Legacy database behavior

If your DB was created before migration tracking existed:

- The upgrader detects the legacy install
- Registers/applies required migration structures
- Brings schema forward without a manual SQL import flow

## Rollback strategy

If update fails:

1. Restore DB backup
2. Restore previous application files
3. Re-run update in a staging copy to identify failing migration
