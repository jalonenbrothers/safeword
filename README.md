# SafeWord

Family safe-word + challenge-question registry for deepfake voice scams.
Is that really Grandma calling? Families agree on safe words and challenge
questions; SafeWord stores them (words hashed) and runs a live
verification drill anyone can use — no login needed for the drill.

MVP Lab team (separate from the Innovation Lab) — Sprint 2.

## Stack

- PHP 8+, no frameworks, no composer packages
- MySQL 5.7+/8 via PDO (prepared statements only)
- Vanilla HTML5/CSS/JS, no build step
- **Repo root = webroot** (Hostinger subdomain deploy:
  myapp2mvp.earth-turtle.com)

## Files

| File | Purpose |
|---|---|
| index.php | Landing + create family |
| login.php | Family name + passphrase sign-in |
| join.php?token=… | Invite-link join (no login first) |
| dashboard.php | Members, stats chips, invite link |
| member-edit.php | Add/edit member, safe word (hashed) + 2–3 challenge Q&A |
| drill.php | Public drill: 2 random questions → PASS/FAIL verdict |
| cards.php | Printable wallet cards (hint only, word/answers never printed) |
| logout.php | End session |
| lib/ | config (copy from config.example.php), PDO db, bootstrap, helpers |
| src/ | Member + Drill logic classes |
| migrations/ | Plain SQL migrations — see migrations/README.md |
| tests/ | `php tests/run.php` (needs dev DB; see below) |

## Setup (operator)

1. Create a MySQL database + user in Hostinger hPanel.
2. Copy `lib/config.example.php` → `lib/config.php`, fill in
   DB credentials and `base_url`.
3. Run migrations — see `migrations/README.md`.
4. Point the subdomain document root at the repo root.

## Deploy on Hostinger (myapp2mvp.earth-turtle.com)

1. hPanel → **Subdomains → Add subdomain**: `myapp2mvp.earth-turtle.com`,
   document root = the SafeWord repo root (repo root = web root; the
   public pages are the top-level `.php` files).
2. hPanel → **Databases → MySQL**: create database + user
   (e.g. `u…_safeword`), note the credentials.
3. Open **phpMyAdmin**, paste `migrations/001_create_tables.sql` in the
   SQL tab, press Go (details in `migrations/README.md`).
4. Copy `lib/config.example.php` → `lib/config.php`, fill in
   `db_host=localhost`, the DB name/user/pass, and
   `base_url=https://myapp2mvp.earth-turtle.com`.
5. Deploy WITHOUT the `.git/` directory (repo root = web root):
   `git archive master | ssh …` or upload an export — `.htaccess`
   denies `.git` only if present, but don't ship it at all.
6. Force HTTPS is automatic (subdomain gets free SSL); additionally set
   `session.cookie_secure=1` in the Hostinger PHP settings (QA note:
   session cookie is otherwise httponly+SameSite=Lax but not `secure`).
7. Smoke-test: open `https://myapp2mvp.earth-turtle.com/` (landing 200),
   create a family, add a member, run a drill; verify
   `https://myapp2mvp.earth-turtle.com/README.md` returns 403.

## Local dev (tested)

- Container `safeword-dev`: `php:8.3-apache` with the repo mounted at
  `/var/www/html`, port 18084; MySQL 8 in container `dev-mysql-1`
  (host `dev-mysql-1`, db `safeword_dev`, user `safeword`).
- `lib/config.php` is gitignored dev config (`db_port` 3306 supported).
- Verified green: `php tests/run.php` (23/23 unit) and the HTTP e2e
  smoke (20/20 checks incl. `.htaccess` internals denial with and
  without mod_rewrite).

## Security notes

- Safe words and challenge answers are stored via `password_hash()`
  and never rendered back or printed — only a user-typed hint.
- All DB access uses PDO prepared statements.
- Output escaped via `e()` (htmlspecialchars) everywhere.
- CSRF token on every POST form; session cookies httponly + SameSite=Lax.
- `.htaccess` denies direct HTTP access to lib/, src/, migrations/, config.
- Drills are good practice, not proof of identity (disclaimer on every page).

## External APIs

None. Zero external calls, $0 API cost.

## Tests

```
php tests/run.php
```

Uses the dev DB from `lib/config.php` (schema from migrations/001).
Exits non-zero on failure; prints `ok …` / `FAIL …` per check.
