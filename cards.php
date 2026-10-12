<?php
/**
 * cards.php — print view: one wallet card per member (login required).
 * Shows name, phone, safe-word HINT and up to 2 example questions.
 * Never selects or prints the safe word or answers.
 */
declare(strict_types=1);
require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/src/Member.php';

$fam = require_family();
$members = Member::cardData(db(), (int)$fam['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Wallet cards — SafeWord</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<main class="wrap">
  <h1>Wallet cards</h1>
  <p class="no-print">One card per member. Answers are never printed — only the
  safe-word hint and example questions.</p>
  <p class="no-print"><button type="button" class="primary" id="print-btn" hidden>Print</button></p>

  <?php if (!$members): ?>
    <p>No members yet. <a href="member-edit.php">Add your first member</a>.</p>
  <?php else: ?>
    <div class="cards-grid">
      <?php foreach ($members as $m): ?>
        <div class="wallet-card">
          <p class="wc-name"><?= e($m['name']) ?></p>
          <?php if ($m['phone']): ?><p class="wc-phone"><?= e($m['phone']) ?></p><?php endif; ?>
          <p class="wc-hint"><span class="wc-label">Safe word hint</span><br><?= e($m['safe_word_hint'] ?: 'none set') ?></p>
          <?php if ($m['questions']): ?>
            <p class="wc-qs"><span class="wc-label">Ask me things like</span><br>
            <?php foreach ($m['questions'] as $q): ?>&ldquo;<?= e($q) ?>&rdquo;<br><?php endforeach; ?>
            </p>
          <?php endif; ?>
          <p class="wc-mark">SafeWord</p>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <p class="no-print alt-links"><a href="dashboard.php">Back to dashboard</a></p>
</main>
<footer class="no-print"><p>Drills are good practice, not proof of identity.</p></footer>
<script src="assets/app.js" defer></script>
</body>
</html>
