<?php

declare(strict_types=1);

namespace Pure\Core;

/**
 * "Did you mean" helper for names typed by hand.
 *
 * Only single-edit differences count: one insertion, deletion, substitution or
 * adjacent transposition (case included). A suggestion is therefore almost
 * always the intended spelling, never a guess between distant names.
 *
 * @internal
 */
final class Suggestion
{
    /**
     * The candidate one edit away from $needle, or null.
     *
     * @param list<string> $candidates
     */
    public static function nearest(string $needle, array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if ($candidate !== $needle && self::oneEditApart($needle, $candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private static function oneEditApart(string $a, string $b): bool
    {
        $lengthA = strlen($a);
        $lengthB = strlen($b);

        if ($lengthA === $lengthB) {
            $mismatch = -1;

            for ($i = 0; $i < $lengthA; $i++) {
                if ($a[$i] === $b[$i]) {
                    continue;
                }

                if ($mismatch >= 0) {
                    // A second mismatch is only one edit when the two characters
                    // are an adjacent transposition.
                    return $mismatch === $i - 1 && $a[$mismatch] === $b[$i] && $a[$i] === $b[$mismatch];
                }

                $mismatch = $i;
            }

            return $mismatch >= 0;
        }

        if (abs($lengthA - $lengthB) !== 1) {
            return false;
        }

        [$shorter, $longer] = $lengthA < $lengthB ? [$a, $b] : [$b, $a];
        $shortLength = strlen($shorter);
        $i = 0;
        $j = 0;
        $skipped = false;

        while ($i < $shortLength && $j < strlen($longer)) {
            if ($shorter[$i] === $longer[$j]) {
                $i++;
                $j++;

                continue;
            }

            if ($skipped) {
                return false;
            }

            $skipped = true;
            $j++;
        }

        return true;
    }
}
