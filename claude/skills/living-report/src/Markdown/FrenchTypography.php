<?php

namespace LivingReport\Markdown;

/**
 * The non-breaking spaces of French typography, so that the YAML is written with plain spaces.
 */
final class FrenchTypography
{
    private const string NBSP = "\u{00A0}";

    public static function apply(string $text): string
    {
        return preg_replace(
            [
                // Before the high punctuation and the closing guillemet
                '/ (?=[:;?!»])/u',
                '/« /u',
                // 1 459, 2 000 000
                '/(?<=\d) (?=\d{3}(?!\d))/u',
                // 8 h, 30 min, 30 %, 1 €
                '/(?<=\d) (?=(?:h|min)\b|[%€])/u',
                // lot 1a, lots 2 et 3
                '/\b([Ll]ots?) (?=\d)/u',
            ],
            [self::NBSP, '«' . self::NBSP, self::NBSP, self::NBSP, '$1' . self::NBSP],
            $text,
        ) ?? throw new \LogicException('The French typography patterns are broken.');
    }
}
