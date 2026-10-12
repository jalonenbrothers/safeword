<?php
/**
 * index.php — landing: 3-sentence pitch + create-family form.
 * Also links to Join (no login) and Drill (no login).
 */
declare(strict_types=1);
require_once __DIR__ . '/lib/bootstrap.php';

$errors = [];
$old = ['name' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = trim($_POST['name'] ?? '');
    $p1 = $_POST['passphrase'] ?? '';
    $p2 = $_POST['passphrase2'] ?? '';
    $old['name'] = $name;

    if ($name === '' || mb_strlen($name) > 64) $errors['name'] = 'Enter a family name (max 64 characters).';
    if (mb_strlen($p1) < 6) $errors['passphrase'] = 'Passphrase must be at least 6 characters.';
    if ($p1 !== $p2) $errors['passphrase2'] = 'Passphrases do not match.';

    if (!$errors) {
        $dup = dbq('SELECT id FROM families WHERE name = ?', [$name])->fetch();
        if ($dup) {
            $errors['name'] = 'That family name is taken. Try another.';
        } else {
            $token = bin2hex(random_bytes(16)); // 32 chars
            dbq('INSERT INTO families (name, passphrase_hash, invite_token) VALUES (?, ?, ?)',
                [$name, password_hash($p1, PASSWORD_DEFAULT), $token]);
            $_SESSION['family_id'] = (int)db()->lastInsertId();
            unset($_SESSION['member_id']);
            redirect('dashboard.php');
        }
    }
}

$notice = flash_notice();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SafeWord — is that really Grandma calling?</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<main class="wrap">
  <h1>SafeWord</h1>
  <p class="pitch">Is that really Grandma calling? Families agree on safe words
  and challenge questions — so a scam caller can&rsquo;t fake a familiar voice.
  SafeWord keeps your family&rsquo;s words and questions in one place, and gives
  you a way to check any caller in seconds.</p>

  <?php if ($notice): ?><p class="notice"><?= e($notice) ?></p><?php endif; ?>

  <div class="card">
    <h2>Create your family</h2>
    <?php if ($errors): ?><p class="error-summary">Check the highlighted fields.</p><?php endif; ?>
    <form method="post" action="index.php" novalidate>
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <label for="f-name">Family name</label>
      <input id="f-name" type="text" name="name" value="<?= e($old['name']) ?>" required>
      <?php if (isset($errors['name'])): ?><p class="field-error"><?= e($errors['name']) ?></p><?php endif; ?>

      <label for="f-p1">Passphrase</label>
      <input id="f-p1" type="password" name="passphrase" required>
      <p class="help">One passphrase for the whole family. No email, no reset — keep it somewhere safe.</p>
      <?php if (isset($errors['passphrase'])): ?><p class="field-error"><?= e($errors['passphrase']) ?></p><?php endif; ?>

      <label for="f-p2">Passphrase again</label>
      <input id="f-p2" type="password" name="passphrase2" required>
      <?php if (isset($errors['passphrase2'])): ?><p class="field-error"><?= e($errors['passphrase2']) ?></p><?php endif; ?>

      <button type="submit" class="primary">Create family</button>
    </form>
  </div>

  <p class="alt-links">
    <a href="login.php">Sign in to your family</a> &middot;
    <a href="join.php">Join a family with an invite link</a> &middot;
    <a href="drill.php">Run a drill</a>
  </p>
</main>
<footer><p>Drills are good practice, not proof of identity.</p></footer>
</body>
</html>
