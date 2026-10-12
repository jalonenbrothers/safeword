<?php
/**
 * tests/run.php — tiny test runner (no framework, exit code = failures).
 * Usage: php tests/run.php
 * Requires the dev DB in lib/config.php to exist with migrations applied:
 *   mysql> CREATE DATABASE safeword; then apply migrations/001.
 */
declare(strict_types=1);

require_once __DIR__ . '/../lib/helpers.php';

$PASS = 0; $FAIL = 0; $FAILURES = [];

function check(bool $cond, string $name): void
{
    global $PASS, $FAIL, $FAILURES;
    if ($cond) { $PASS++; echo "ok  $name\n"; }
    else { $FAIL++; $FAILURES[] = $name; echo "FAIL $name\n"; }
}

/* --- pure logic: normalize_answer --- */
check(normalize_answer('  Biscuit  ') === 'biscuit', 'normalize trims');
check(normalize_answer("OLD   OAK\tTREE") === 'old oak tree', 'normalize collapses whitespace, lowercases');
check(mb_strlen(normalize_answer('Älles')) === 5, 'normalize handles UTF-8');

/* --- DB-backed tests --- */
require_once __DIR__ . '/../lib/bootstrap.php';
$pdo = db();

// clean slate for test family
$pdo->exec('DELETE FROM families WHERE name LIKE "TESTFAM%"');

// 1. create family via Member::save etc.
require_once __DIR__ . '/../src/Member.php';
require_once __DIR__ . '/../src/Drill.php';

dbq('INSERT INTO families (name, passphrase_hash, invite_token) VALUES (?, ?, ?)',
    ['TESTFAM_A', password_hash('passphrase1', PASSWORD_DEFAULT), bin2hex(random_bytes(16))]);
$famA = (int)$pdo->lastInsertId();

dbq('INSERT INTO families (name, passphrase_hash, invite_token) VALUES (?, ?, ?)',
    ['TESTFAM_B', password_hash('passphrase2', PASSWORD_DEFAULT), bin2hex(random_bytes(16))]);
$famB = (int)$pdo->lastInsertId();

// 2. member validation: too few challenges fails
[$ok, $mid, $errs] = Member::save($pdo, $famA, null, [
    'name' => 'Emma', 'phone' => '555-0100', 'safe_word' => 'MapleSyrup',
    'safe_word_hint' => 'the old oak tree',
    'challenges' => [['q' => 'First dog?', 'a' => 'Biscuit']],
]);
check(!$ok && isset($errs['challenges']), 'save rejects <2 challenges');

// 3. valid member save
[$ok, $emmaId, $errs] = Member::save($pdo, $famA, null, [
    'name' => 'Emma', 'phone' => '555-0100', 'safe_word' => 'MapleSyrup',
    'safe_word_hint' => 'the old oak tree',
    'challenges' => [
        ['q' => 'What was the name of our first dog?', 'a' => '  Biscuit '],
        ['q' => 'Where did we summer?', 'a' => "Cape   Cod"],
    ],
]);
check($ok && $emmaId > 0, 'save accepts valid member');

// 4. safe word stored hashed, not plaintext
$row = Member::find($pdo, $emmaId);
check($row['safe_word_hash'] !== 'MapleSyrup' && str_starts_with($row['safe_word_hash'], '$2y$'), 'safe word hashed on save');
check(password_verify('MapleSyrup', $row['safe_word_hash']), 'safe word verifies');

// 5. cross-family edit blocked
[$ok2] = Member::save($pdo, $famB, $emmaId, [
    'name' => 'Hacker', 'safe_word' => 'x', 'challenges' => [
        ['q' => 'a?', 'a' => 'a'], ['q' => 'b?', 'a' => 'b'],
    ]]);
check(!$ok2, 'cross-family member edit rejected');

// 6. drill question picking: always exactly 2 distinct
$qs = Drill::pickQuestions($pdo, $emmaId);
check(count($qs) === 2, 'picks exactly 2 questions');
$ids = array_map(fn($q) => $q['id'], $qs);
check(count(array_unique($ids)) === 2, 'picked questions are distinct');

// 7. drill PASS with case/whitespace-insensitive answers
$v = Drill::check($pdo, $emmaId, [
    (int)$ids[0] => 'BISCUIT',
    (int)$ids[1] => 'cape cod',
]);
check($v['passed'] === true, 'drill PASS on matching answers (case-insensitive)');

// 8. drill FAIL on one wrong answer
$v2 = Drill::check($pdo, $emmaId, [
    (int)$ids[0] => 'Biscuit',
    (int)$ids[1] => 'wrong answer',
]);
check($v2['passed'] === false, 'drill FAIL on one wrong answer');

// 9. drill FAIL when nothing answered
$v3 = Drill::check($pdo, $emmaId, []);
check($v3['passed'] === false, 'drill FAIL on empty answers');

// 10. drills recorded
$n = (int)dbq('SELECT COUNT(*) c FROM drills WHERE member_id = ?', [$emmaId])->fetch()['c'];
check($n === 3, 'drills recorded (3 rows)');

// 11. days since drill
$days = Member::daysSinceDrill($pdo, $famA);
check($days[$emmaId] === 0, 'days-since-drill = 0 right after drill');

// 12. cardData never exposes hash/answers, includes hint + 2 questions
$cards = Member::cardData($pdo, $famA);
check(count($cards) === 1, 'cardData returns the member');
check(!isset($cards[0]['safe_word_hash']), 'cardData excludes safe_word_hash');
check($cards[0]['safe_word_hint'] === 'the old oak tree', 'cardData includes hint');
check(count($cards[0]['questions']) === 2, 'cardData includes 2 example questions');

// 13. edit keeping safe word blank preserves hash
$origHash = $row['safe_word_hash'];
[$ok3, ] = Member::save($pdo, $famA, $emmaId, [
    'name' => 'Emma', 'phone' => '555-0100', 'safe_word' => '', 'safe_word_hint' => 'oak',
    'challenges' => [
        ['q' => 'q1', 'a' => 'a1'], ['q' => 'q2', 'a' => 'a2'],
    ]]);
$row2 = Member::find($pdo, $emmaId);
check($ok3 && $row2['safe_word_hash'] === $origHash, 'blank safe word on edit keeps hash');
check(password_verify('a1', $row2 ? dbq('SELECT answer_hash FROM challenges WHERE member_id = ? ORDER BY id LIMIT 1', [$emmaId])->fetch()['answer_hash'] : ''), 'edit replaces challenge answers');

// 14. 3rd optional challenge allowed, 4th ignored
[$ok4, , ] = Member::save($pdo, $famA, null, [
    'name' => 'Grandpa', 'safe_word' => 'w', 'safe_word_hint' => '',
    'challenges' => [
        ['q' => '1', 'a' => '1'], ['q' => '2', 'a' => '2'],
        ['q' => '3', 'a' => '3'], ['q' => '4', 'a' => '4'],
    ]]);
$cnt = dbq('SELECT COUNT(*) c FROM challenges c JOIN members m ON m.id=c.member_id WHERE m.family_id=? AND m.name="Grandpa"', [$famA])->fetch()['c'];
check($ok4 && (int)$cnt === 3, 'max 3 challenges enforced');

// cleanup
$pdo->prepare('DELETE FROM families WHERE id IN (?, ?)')->execute([$famA, $famB]);

echo "\n$PASS passed, $FAIL failed\n";
if ($FAIL) {
    echo "FAILED: " . implode(', ', $FAILURES) . "\n";
    exit(1);
}
exit(0);
