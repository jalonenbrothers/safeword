<?php
/**
 * drill.php — public, no login: pick claimed member → 2 random challenge
 * questions → submit → PASS/FAIL verdict with hang-up guidance.
 * GET: no member → picker; ?member=<id> → questions form.
 * POST: answers checked → verdict panel (same page).
 */
declare(strict_types=1);
require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/src/Drill.php';
require_once __DIR__ . '/src/Member.php';

// Public picker lists only names (all families) — MVP scope: member picker
// of first names. To avoid cross-family guessing abuse we show names only.
$members = dbq('SELECT id, name FROM members ORDER BY name')->fetchAll();

$memberId = isset($_GET['member']) ? (int)$_GET['member'] : null;
$questions = [];
$verdict = null;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $memberId = (int)($_POST['member'] ?? 0);
    $answers = $_POST['answer'] ?? [];
    $questions = Drill::pickQuestions(db(), $memberId); // repose same 2? No —
    // Re-fetch the questions that were posed (ids come from POST keys via hidden fields)
    $posedIds = array_map('intval', $_POST['posed'] ?? []);
    $st = db()->prepare('SELECT id, question FROM challenges WHERE id = ?');
    $posed = [];
    foreach ($posedIds as $pid) {
        $st->execute([$pid]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        if ($r) $posed[] = $r;
    }
    if ($memberId && $posed) {
        // answers keyed by challenge id
        $verdict = Drill::check(db(), $memberId, $answers);
        $verdict['questions'] = $posed;
    } else {
        $error = 'Pick a member first.';
        $memberId = null;
    }
} elseif ($memberId) {
    $questions = Drill::pickQuestions(db(), $memberId);
    if (!$questions) {
        $error = 'This member has no challenge questions yet.';
        $memberId = null;
        $questions = [];
    }
}

$notice = flash_notice();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Verification drill — SafeWord</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<main class="wrap">
  <h1>Verification drill</h1>

  <?php if ($notice): ?><p class="notice"><?= e($notice) ?></p><?php endif; ?>
  <?php if ($error): ?><p class="field-error"><?= e($error) ?></p><?php endif; ?>

  <?php if ($verdict): ?>
    <section class="verdict <?= $verdict['passed'] ? 'pass' : 'fail' ?>">
      <?php if ($verdict['passed']): ?>
        <h2>Answers match</h2>
        <p>This is good practice — but not a guarantee. If anything still feels
        off, hang up and call back on a number you know.</p>
      <?php else: ?>
        <h2>Answers do NOT match</h2>
        <p>Hang up now and call back on a known number.</p>
      <?php endif; ?>
      <p class="verdict-again"><a href="drill.php">Run another drill</a></p>
    </section>
  <?php elseif ($memberId && $questions): ?>
    <?php $m = dbq('SELECT name FROM members WHERE id = ?', [$memberId])->fetch(); ?>
    <p>Claimed caller: <strong><?= e($m['name']) ?></strong>. Ask them these
    questions. Both answers must match.</p>
    <form method="post" action="drill.php" novalidate>
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="member" value="<?= $memberId ?>">
      <?php foreach ($questions as $i => $q): ?>
        <input type="hidden" name="posed[]" value="<?= (int)$q['id'] ?>">
        <label for="a<?= $i ?>">Q<?= $i + 1 ?>: <?= e($q['question']) ?></label>
        <input id="a<?= $i ?>" type="text" name="answer[<?= (int)$q['id'] ?>]" required autocomplete="off">
      <?php endforeach; ?>
      <button type="submit" class="primary">Check answers</button>
    </form>
    <p class="help">Answers are compared case-insensitively. A wrong answer to
    either question is a FAIL.</p>
  <?php else: ?>
    <p>Someone calling sounds familiar? Pick who they claim to be — the drill
    asks them two challenge questions. No sign-in needed.</p>
    <form method="get" action="drill.php">
      <label for="d-member">Who are they claiming to be?</label>
      <select id="d-member" name="member" required>
        <option value="">— pick a member —</option>
        <?php foreach ($members as $m): ?>
          <option value="<?= (int)$m['id'] ?>"><?= e($m['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="primary">Start drill</button>
    </form>
    <p class="help">Drills are good practice, not proof of identity.</p>
  <?php endif; ?>

  <p class="alt-links"><a href="index.php">Home</a><?php if (current_family()): ?> &middot; <a href="dashboard.php">Dashboard</a><?php endif; ?></p>
</main>
<footer><p>Drills are good practice, not proof of identity.</p></footer>
</body>
</html>
