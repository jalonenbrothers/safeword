<?php
/**
 * lib/helpers.php — output escaping + small view helpers.
 */
declare(strict_types=1);

function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Normalized compare string for challenge answers / safe words. */
function normalize_answer(string $s): string
{
    $s = trim($s);
    $s = preg_replace('/\s+/u', ' ', $s) ?? $s;
    return mb_strtolower($s, 'UTF-8');
}
