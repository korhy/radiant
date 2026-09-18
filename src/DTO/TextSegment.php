<?php

declare(strict_types=1);

namespace App\DTO;

/**
 * One run of text from an editable field, and whether it is highlighted.
 *
 * The type is the point: `text` stays plain text all the way to Twig, which escapes it. No markup
 * is ever assembled in PHP, so nothing typed into the back-office can become page structure.
 */
final readonly class TextSegment
{
    public function __construct(
        public string $text,
        public bool $highlighted,
    ) {
    }
}
