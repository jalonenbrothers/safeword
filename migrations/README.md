# SafeWord — Migrations (operator instructions)

Plain SQL files, run in order. Each is idempotent-ish (IF NOT EXISTS /
plain inserts), safe to re-paste.

## How to run on Hostinger

1. Open **hPanel → Databases → phpMyAdmin** and select the SafeWord
   database you created for the subdomain (e.g. `u123456789_safeword`).
2. Open the **SQL** tab, paste the full contents of
   `migrations/001_create_tables.sql`, press **Go**.
3. Repeat for each newer `NNN_*.sql` file in this directory, in numeric
   order. (Currently there is only 001.)

No CLI access needed. If you prefer the terminal (VPS/SSH):

```
mysql -u <dbuser> -p <dbname> < migrations/001_create_tables.sql
```

## Status

| File | Purpose |
|---|---|
| 001_create_tables.sql | families, members, challenges, drills + FKs |
