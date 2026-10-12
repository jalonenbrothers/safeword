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
