<?php
/**
 * login.php — family name + passphrase → session.
 */
declare(strict_types=1);
require_once __DIR__ . '/lib/bootstrap.php';

$error = '';
$old = '';
$notice = flash_notice();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = trim($_POST['name'] ?? '');
    $pass = $_POST['passphrase'] ?? '';
    $old = $name;
    $fam = dbq('SELECT id, passphrase_hash FROM families WHERE name = ?', [$name])->fetch();
    if ($fam && password_verify($pass, $fam['passphrase_hash'])) {
        session_regenerate_id(true);
        $_SESSION['family_id'] = (int)$fam['id'];
        unset($_SESSION['member_id']);
        redirect('dashboard.php');
    }
    $error = 'Family name or passphrase is wrong.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sign in — SafeWord</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<main class="wrap">
  <h1>Sign in</h1>

  <?php if ($notice): ?><p class="notice"><?= e($notice) ?></p><?php endif; ?>
  <?php if ($error): ?><p class="field-error"><?= e($error) ?></p><?php endif; ?>

  <div class="card">
    <form method="post" action="login.php" novalidate>
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <label for="l-name">Family name</label>
      <input id="l-name" type="text" name="name" value="<?= e($old) ?>" required>

      <label for="l-pass">Passphrase</label>
      <input id="l-pass" type="password" name="passphrase" required>

      <button type="submit" class="primary">Sign in</button>
    </form>
  </div>

  <p class="alt-links"><a href="index.php">Back to home</a> &middot; <a href="drill.php">Run a drill</a></p>
</main>
<footer><p>Drills are good practice, not proof of identity.</p></footer>
</body>
</html>
