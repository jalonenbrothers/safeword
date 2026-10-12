<?php
/**
 * src/Drill.php — drill logic: pick 2 random challenge questions,
 * check answers, record the drill, return a verdict.
 */
declare(strict_types=1);

final class Drill
{
    /**
     * Pick 2 distinct random challenge ids for a member (fewer if member
     * has fewer than 2 questions). Returns array of challenge rows.
     * @return array<int, array{id:int, question:string}>
     */
    public static function pickQuestions(PDO $pdo, int $memberId): array
    {
        $st = $pdo->prepare('SELECT id, question FROM challenges WHERE member_id = ?');
        $st->execute([$memberId]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        shuffle($rows);
        return array_slice($rows, 0, 2);
    }

    /**
     * Check submitted answers against stored hashes (case-insensitive,
     * whitespace-normalized). Any mismatch (or missing answer) = FAIL.
     * Records the drill and returns a verdict array for the view.
     *
     * @param array<int,string> $answers challengeId => submitted answer
     * @return array{passed:bool, checked:int}
     */
    public static function check(PDO $pdo, int $memberId, array $answers): array
    {
        $st = $pdo->prepare('SELECT id, answer_hash FROM challenges WHERE member_id = ?');
        $st->execute([$memberId]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        $passed = true;
        $checked = 0;
        foreach ($rows as $row) {
            $submitted = $answers[(int)$row['id']] ?? null;
            if ($submitted === null || $submitted === '') {
                continue; // question wasn't posed this round
            }
            $checked++;
            $ok = password_verify(normalize_answer((string)$submitted), $row['answer_hash']);
            if (!$ok) $passed = false;
        }
        if ($checked < 1) $passed = false; // nothing answered = not a pass

        $ins = $pdo->prepare('INSERT INTO drills (member_id, passed) VALUES (?, ?)');
        $ins->execute([$memberId, $passed ? 1 : 0]);

        return ['passed' => $passed, 'checked' => $checked];
    }
}
