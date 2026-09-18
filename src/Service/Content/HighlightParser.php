<?php

declare(strict_types=1);

namespace App\Service\Content;

use App\DTO\TextSegment;

/**
 * Splits an editable text into highlighted and plain runs, on the single `**…**` convention.
 *
 * Scanning is left to right with no backtracking, which is what makes malformed input
 * predictable: an unclosed marker stays literal rather than swallowing the rest of the paragraph.
 * The grammar and its thirteen pinned cases live in
 * specs/008-editable-about-cv/contracts/highlight-grammar.md.
 */
final class HighlightParser
{
    private const MARKER = '**';

    /**
     * @return list<TextSegment>
     */
    public function parse(?string $text): array
    {
        if (null === $text || '' === $text) {
            return [];
        }

        $segments = [];
        $plain = '';
        $cursor = 0;
        $length = \strlen($text);
        $markerLength = \strlen(self::MARKER);

        while ($cursor < $length) {
            $open = strpos($text, self::MARKER, $cursor);

            if (false === $open) {
                break;
            }

            $close = strpos($text, self::MARKER, $open + $markerLength);

            // No closing marker, or nothing between the two: neither forms a pair, so the marker
            // is ordinary text and the scan moves past it.
            if (false === $close || $close === $open + $markerLength) {
                $plain .= substr($text, $cursor, $open - $cursor + $markerLength);
                $cursor = $open + $markerLength;

                continue;
            }

            $plain .= substr($text, $cursor, $open - $cursor);

            if ('' !== $plain) {
                $segments[] = new TextSegment($plain, false);
                $plain = '';
            }

            $highlightedFrom = $open + $markerLength;
            $segments[] = new TextSegment(substr($text, $highlightedFrom, $close - $highlightedFrom), true);

            $cursor = $close + $markerLength;
        }

        $plain .= substr($text, $cursor);

        if ('' !== $plain) {
            $segments[] = new TextSegment($plain, false);
        }

        return $segments;
    }
}
