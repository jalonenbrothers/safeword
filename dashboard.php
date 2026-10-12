<?php
/**
 * dashboard.php — home screen after login: members, stats, invite link.
 */
declare(strict_types=1);
require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/src/Member.php';

$fam = require_family();
$familyId = (int)$fam['id'];
$members = Member::forFamily(db(), $familyId);
$days = Member::daysSinceDrill(db(), $familyId);
$notice = flash_notice();
$inviteUrl = base_url() . '/join.php?token=' . rawurlencode($fam['invite_token']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($fam['name']) ?> family — SafeWord</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<main class="wrap">
  <h1><?= e($fam['name']) ?> family</h1>

  <?php if ($notice): ?><p class="notice"><?= e($notice) ?></p><?php endif; ?>

  <div class="card invite-box">
    <h2>Invite link</h2>
    <p>Share this link with family. Anyone with the link <em>and</em> the family
    passphrase can join.</p>
    <div class="copy-row">
      <input type="text" readonly value="<?= e($inviteUrl) ?>" id="invite-url">
      <button type="button" class="secondary" data-copy="#invite-url" hidden>Copy</button>
    </div>
  </div>

  <?php if (!$members): ?>
    <div class="card">
      <h2>Get started</h2>
      <ol class="checklist">
        <li><a href="member-edit.php">Add your first member</a></li>
        <li><a href="cards.php">Print the wallet cards</a></li>
        <li><a href="drill.php">Run a practice drill</a></li>
      </ol>
    </div>
  <?php else: ?>
    <h2>Members</h2>
    <div class="member-grid">
      <?php foreach ($members as $m): $d = $days[$m['id']] ?? null; ?>
        <div class="card member-row">
          <div class="member-head">
            <strong><?= e($m['name']) ?></strong>
            <span class="stat-chip<?php if ($d === null || $d > 30): ?> stale<?php endif; ?>">
              <?= $d === null ? 'No drill yet' : 'Last drill ' . (int)$d . ' day' . ((int)$d === 1 ? '' : 's') . ' ago' ?>
              <?php if ($d !== null && $d > 30): ?> &middot; run a drill<?php endif; ?>
            </span>
          </div>
          <?php if ($m['phone']): ?><p class="muted">Phone: <?= e($m['phone']) ?> <span class="muted">(for your own records only)</span></p><?php endif; ?>
          <p class="muted">Safe word hint: <?= e($m['safe_word_hint'] ?: 'none set') ?></p>
          <p class="row-actions">
            <a href="member-edit.php?id=<?= (int)$m['id'] ?>">Edit</a>
          </p>
        </div>
      <?php endforeach; ?>
    </div>

    <p class="alt-links">
      <a href="member-edit.php">Add another member</a> &middot;
      <a href="cards.php">Print wallet cards</a> &middot;
      <a href="drill.php">Run a drill</a> &middot;
      <a href="logout.php">Sign out</a>
    </p>
  <?php endif; ?>

  <?php if (!$members): ?>
    <p class="alt-links"><a href="logout.php">Sign out</a></p>
  <?php endif; ?>
</main>
<footer><p>Drills are good practice, not proof of identity.</p></footer>
<script src="assets/app.js" defer></script>
</body>
</html>
