<?php
/**
 * src/Member.php — member data access + validation + safe-word hashing.
 */
declare(strict_types=1);

final class Member
{
    public static function find(PDO $pdo, int $id): ?array
    {
        $st = $pdo->prepare('SELECT * FROM members WHERE id = ?');
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** Members of a family, insertion order. */
    public static function forFamily(PDO $pdo, int $familyId): array
    {
        $st = $pdo->prepare('SELECT * FROM members WHERE family_id = ? ORDER BY id');
        $st->execute([$familyId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Days since last drill for each member of a family. NULL = never. */
    public static function daysSinceDrill(PDO $pdo, int $familyId): array
    {
        $st = $pdo->prepare(
            'SELECT m.id, DATEDIFF(NOW(), MAX(d.created_at)) AS days
             FROM members m
             LEFT JOIN drills d ON d.member_id = m.id
             WHERE m.family_id = ?
             GROUP BY m.id'
        );
        $st->execute([$familyId]);
        $out = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $out[(int)$r['id']] = ($r['days'] === null) ? null : (int)$r['days'];
        }
        return $out;
    }

    /**
     * Validate + save a member. $data keys: name, phone, safe_word,
     * safe_word_hint, challenges (list of ['q'=>, 'a'=>]).
     * Edit mode: safe_word empty = keep current word; challenges always
     * resent in full and replaced wholesale.
     * Returns [ok(bool), memberId(int), errors(array<string,string>)].
     */
    public static function save(PDO $pdo, int $familyId, ?int $memberId, array $data): array
    {
        $errors = [];
        $name = trim($data['name'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $hint = trim($data['safe_word_hint'] ?? '');
        $word = $data['safe_word'] ?? '';

        if ($name === '' || mb_strlen($name) > 64) $errors['name'] = 'Enter a name (max 64 characters).';
        if (mb_strlen($phone) > 32) $errors['phone'] = 'Phone is too long.';
        if (mb_strlen($hint) > 191) $errors['safe_word_hint'] = 'Hint is too long.';

        // Challenges: keep only fully filled pairs, 2–3 max
        $pairs = [];
        foreach (($data['challenges'] ?? []) as $p) {
            $q = trim($p['q'] ?? '');
            $a = trim($p['a'] ?? '');
            if ($q === '' && $a === '') continue;
            if ($q === '') { $errors['challenges'] = 'Every question needs text.'; continue; }
            if ($a === '') { $errors['challenges'] = 'Every question needs an answer.'; continue; }
            if (count($pairs) >= 3) break;
            $pairs[] = ['q' => $q, 'a' => $a];
        }
        if (count($pairs) < 2) {
            $errors['challenges'] = $errors['challenges'] ?? 'Add at least 2 challenge questions.';
        }

        $isNew = ($memberId === null);
        $existing = $isNew ? null : self::find($pdo, $memberId);
        if ($existing && $existing['family_id'] != $familyId) {
            return [false, 0, ['member' => 'Member not found.']];
        }

        if ($isNew && $word === '') $errors['safe_word'] = 'Enter a safe word.';
        if ($word !== '' && mb_strlen($word) > 191) $errors['safe_word'] = 'Safe word is too long.';

        if ($errors) return [false, ($memberId ?? 0), $errors];

        if ($isNew) {
            $st = $pdo->prepare(
                'INSERT INTO members (family_id, name, phone, safe_word_hash, safe_word_hint)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $st->execute([$familyId, $name, $phone, password_hash($word, PASSWORD_DEFAULT), $hint]);
            $memberId = (int)$pdo->lastInsertId();
        } else {
            if ($word !== '') {
                $st = $pdo->prepare(
                    'UPDATE members SET name=?, phone=?, safe_word_hash=?, safe_word_hint=? WHERE id=? AND family_id=?'
                );
                $st->execute([$name, $phone, password_hash($word, PASSWORD_DEFAULT), $hint, $memberId, $familyId]);
            } else {
                $st = $pdo->prepare('UPDATE members SET name=?, phone=?, safe_word_hint=? WHERE id=? AND family_id=?');
                $st->execute([$name, $phone, $hint, $memberId, $familyId]);
            }
            // replace challenges wholesale (edit form always resends all pairs)
            $del = $pdo->prepare('DELETE FROM challenges WHERE member_id = ?');
            $del->execute([$memberId]);
        }

        $ins = $pdo->prepare('INSERT INTO challenges (member_id, question, answer_hash) VALUES (?, ?, ?)');
        foreach ($pairs as $p) {
            $ins->execute([$memberId, $p['q'], password_hash(normalize_answer($p['a']), PASSWORD_DEFAULT)]);
        }

        return [true, (int)$memberId, []];
    }

    /**
     * Card data: name, phone, hint + up to 2 example questions.
     * The query NEVER selects safe_word_hash — enforced here per UX plan §6.
     */
    public static function cardData(PDO $pdo, int $familyId): array
    {
        $st = $pdo->prepare(
            'SELECT id, name, phone, safe_word_hint FROM members WHERE family_id = ? ORDER BY id'
        );
        $st->execute([$familyId]);
        $members = $st->fetchAll(PDO::FETCH_ASSOC);
        $qst = $pdo->prepare('SELECT question FROM challenges WHERE member_id = ? ORDER BY id LIMIT 2');
        foreach ($members as &$m) {
            $qst->execute([$m['id']]);
            $m['questions'] = $qst->fetchAll(PDO::FETCH_COLUMN);
        }
        return $members;
    }
}
