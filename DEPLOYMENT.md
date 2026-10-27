# SPAD backend deployment runbook (preflight)

This repository contains the Laravel 8 backend and React/Laravel Mix admin frontend. The observed live tree is `/home/spadesta/react-amlak`. It had many modifications relative to its original vendor remote; this private repository is a separate snapshot, not an authorized automatic production replacement.

## Guardrails

- Never automatically deploy production on push to `main`.
- Do not commit `.env`, uploads, runtime storage, credentials, databases or SSH private keys.
- Do not run database migrations, `composer install`, `php artisan optimize`, alter web document roots, or replace live files until a backup, rollback plan and compatibility checks exist.
- Preserve `storage`, `public/storage`, any shared media, PHP runtime config and production environment configuration.
- The GitHub repo does not include excluded live runtime files. Never delete live content simply because Git does not track it.
- Require explicit approval, staging tests, and production health checks before promotion.

## Information required before implementing automation

1. PHP and Composer versions and enabled extensions.
2. Domain-to-document-root mappings (including panel/API hostnames).
3. Whether `public` is web-facing and how symlinks/storage are configured.
4. Backup procedures for app files and database; validated restore and database migration/rollback procedures.
5. Whether the bundled local package `packages/spad/crm` installs successfully with the production Composer setup.
6. Any build process for React admin assets and whether compiled assets are deployed from Git or generated on host.

Once these are checked, implement manual, approval-gated deployment with rollback; do not combine with frontend deployment until independent recovery works.
