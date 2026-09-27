# Adult Directory

Standalone PHP 8.2+ and MySQL 8 classified directory. Phase 1 provides the application foundation: configuration, database access, migrations, seeds, routing, and error handling.

The directory is limited to adults aged 21 or older. Public pages, admin authentication, and automation are later phases.

## Setup

1. Copy `.env.example` to `.env` and set the database credentials and a long random `APP_KEY`.
2. Create the MySQL database named in `DB_NAME`.
3. Apply the schema and seed data:

```bash
php bin/console.php migrate
php bin/console.php seed
php bin/console.php status
```

4. Point Apache at this directory with `AllowOverride All`, or run a local server:

```bash
php -S 127.0.0.1:8080 -t . index.php
```

`/` is the foundation page. `/health` reports database connectivity and seed counts. It does not return credentials or personal data.

## Phase 1 checks

```bash
php tests/Phase1Test.php
```
