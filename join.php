<?php
/**
 * join.php — invite-link landing (no login).
 * ?token=... → shows family name → passphrase → member session.
 * A join session can manage the family (single-family MVP; scope has
 * no admin/member distinction beyond this).
 */
declare(strict_types=1);
require_once __DIR__ . '/lib/bootstrap.php';

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$fam = $token ? dbq('SELECT id, name FROM families WHERE invite_token = ?', [$token])->fetch() : null;

$error = '';
$notice = flash_notice();

if (!$fam) {
    // invalid / missing token
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $pass = $_POST['passphrase'] ?? '';
    $row = dbq('SELECT passphrase_hash FROM families WHERE id = ?', [$fam['id']])->fetch();
    if ($row && password_verify($pass, $row['passphrase_hash'])) {
        session_regenerate_id(true);
        $_SESSION['family_id'] = (int)$fam['id'];
        unset($_SESSION['member_id']);
        set_flash('Welcome to the ' . $fam['name'] . ' family.');
        redirect('dashboard.php');
    }
    $error = 'That passphrase is wrong.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Join a family — SafeWord</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<main class="wrap">
  <h1>Join a family</h1>

  <?php if ($notice): ?><p class="notice"><?= e($notice) ?></p><?php endif; ?>

  <?php if (!$fam): ?>
    <div class="card">
      <p>This invite link has expired or is wrong.</p>
      <p class="alt-links"><a href="index.php">Back to home</a></p>
    </div>
  <?php else: ?>
    <p>You have been invited to the <strong><?= e($fam['name']) ?></strong> family.
    Enter the family passphrase to join.</p>
    <?php if ($error): ?><p class="field-error"><?= e($error) ?></p><?php endif; ?>
    <div class="card">
      <form method="post" action="join.php?token=<?= e($token) ?>" novalidate>
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <label for="j-pass">Family passphrase</label>
        <input id="j-pass" type="password" name="passphrase" required>
        <button type="submit" class="primary">Join family</button>
      </form>
    </div>
  <?php endif; ?>
</main>
<footer><p>Drills are good practice, not proof of identity.</p></footer>
</body>
</html>
