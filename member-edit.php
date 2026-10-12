<?php
/**
 * member-edit.php — add/edit one member: name, phone (optional),
 * safe word + hint, 2–3 challenge Q&A pairs. Safe word is never
 * rendered back; edit shows blank word = keep current.
 */
declare(strict_types=1);
require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/src/Member.php';

$fam = require_family();
$familyId = (int)$fam['id'];

$memberId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$member = null;
$existingChallenges = [];
if ($memberId !== null) {
    $member = Member::find(db(), $memberId);
    if (!$member || $member['family_id'] != $familyId) {
        http_response_code(404);
        exit('Member not found.');
    }
    $st = dbq('SELECT question FROM challenges WHERE member_id = ? ORDER BY id', [$memberId]);
    foreach ($st->fetchAll() as $r) $existingChallenges[] = $r['question'];
}

// Form rows: 3 challenge pairs always rendered (2 required, 3rd optional)
$rows = [];
if ($member) {
    for ($i = 0; $i < 3; $i++) $rows[$i] = $existingChallenges[$i] ?? '';
} else {
    $rows = ['', '', ''];
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $data = [
        'name' => $_POST['name'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'safe_word' => $_POST['safe_word'] ?? '',
        'safe_word_hint' => $_POST['safe_word_hint'] ?? '',
        'challenges' => [],
    ];
    for ($i = 0; $i < 3; $i++) {
        $data['challenges'][] = [
            'q' => $_POST['q'][$i] ?? '',
            'a' => $_POST['a'][$i] ?? '',
        ];
    }
    [$ok, $mid, $errors] = Member::save(db(), $familyId, $memberId, $data);
    if ($ok) {
        set_flash('Member saved.');
        redirect('dashboard.php');
    }
    // repopulate rows from POST questions
    for ($i = 0; $i < 3; $i++) $rows[$i] = trim($_POST['q'][$i] ?? '');
    if (!$member) $member = ['name' => $data['name'], 'phone' => $data['phone'], 'safe_word_hint' => $data['safe_word_hint']];
    else { $member['name'] = $data['name']; $member['phone'] = $data['phone']; $member['safe_word_hint'] = $data['safe_word_hint']; }
}

$notice = flash_notice();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $member && $memberId ? 'Edit ' . e($member['name']) : 'Add member' ?> — SafeWord</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<main class="wrap">
  <h1><?= $memberId ? 'Edit member' : 'Add member' ?></h1>

  <?php if ($notice): ?><p class="notice"><?= e($notice) ?></p><?php endif; ?>

  <?php if ($errors): ?><p class="error-summary">Check the highlighted fields.</p><?php endif; ?>

  <div class="card">
    <form method="post" action="<?= $memberId ? 'member-edit.php?id=' . $memberId : 'member-edit.php' ?>" novalidate>
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

      <label for="m-name">Name</label>
      <input id="m-name" type="text" name="name" value="<?= e($member['name'] ?? '') ?>" required>
      <?php if (isset($errors['name'])): ?><p class="field-error"><?= e($errors['name']) ?></p><?php endif; ?>

      <label for="m-phone">Phone <span class="muted">(optional, for your own records only)</span></label>
      <input id="m-phone" type="text" name="phone" value="<?= e($member['phone'] ?? '') ?>">
      <?php if (isset($errors['phone'])): ?><p class="field-error"><?= e($errors['phone']) ?></p><?php endif; ?>

      <label for="m-word">Safe word <?= $memberId ? '<span class="muted">(leave blank to keep current word)</span>' : '' ?></label>
      <input id="m-word" type="password" name="safe_word" autocomplete="new-password">
      <p class="help">A word only the real person would say. On save it is hidden for good.</p>
      <?php if (isset($errors['safe_word'])): ?><p class="field-error"><?= e($errors['safe_word']) ?></p><?php endif; ?>

      <label for="m-hint">Safe word hint</label>
      <input id="m-hint" type="text" name="safe_word_hint" value="<?= e($member['safe_word_hint'] ?? '') ?>">
      <p class="help">A hint you will understand on the wallet card — the word itself is never shown again.</p>
      <?php if (isset($errors['safe_word_hint'])): ?><p class="field-error"><?= e($errors['safe_word_hint']) ?></p><?php endif; ?>

      <h2>Challenge questions</h2>
      <p class="help">Two minimum, three max. Answers are case-insensitive and checked
      during every drill. Both answers must match for a pass.</p>
      <?php if (isset($errors['challenges'])): ?><p class="field-error"><?= e($errors['challenges']) ?></p><?php endif; ?>
      <?php for ($i = 0; $i < 3; $i++): $third = ($i === 2 && $rows[2] === ''); ?>
        <fieldset class="challenge<?= $third ? ' third hidden-optional' : '' ?>">
          <legend>Question <?= $i + 1 ?><?= $third ? ' (optional)' : '' ?></legend>
          <label for="q<?= $i ?>">Question</label>
          <input id="q<?= $i ?>" type="text" name="q[<?= $i ?>]" value="<?= e($rows[$i]) ?>" placeholder="e.g. What was the name of our first dog?">
          <label for="a<?= $i ?>">Answer</label>
          <input id="a<?= $i ?>" type="text" name="a[<?= $i ?>]" value="" placeholder="e.g. Biscuit">
          <p class="help"><?= $memberId ? 'On save, questions are replaced by what you type here — leave a pair blank to remove it. Keep at least 2.' : 'Answers are never shown or printed after save.' ?></p>
        </fieldset>
      <?php endfor; ?>
      <?php if (!$memberId || true): ?>
        <button type="button" class="secondary" id="add-third" data-reveal=".challenge.third" hidden>Add another question</button>
      <?php endif; ?>

      <button type="submit" class="primary">Save member</button>
    </form>
  </div>

  <p class="alt-links"><a href="dashboard.php">Back to dashboard</a></p>
</main>
<footer><p>Drills are good practice, not proof of identity.</p></footer>
<script src="assets/app.js" defer></script>
</body>
</html>
