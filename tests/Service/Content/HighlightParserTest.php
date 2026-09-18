<?php

declare(strict_types=1);

namespace App\Tests\Service\Content;

use App\DTO\TextSegment;
use App\Service\Content\HighlightParser;
use PHPUnit\Framework\TestCase;

/**
 * The thirteen cases of specs/008-editable-about-cv/contracts/highlight-grammar.md.
 *
 * The grammar is deliberately backtrack-free, so a couple of results look surprising (cases 8
 * and 10). They are pinned here because "predictable" is the requirement, not "what the author
 * meant".
 */
final class HighlightParserTest extends TestCase
{
    private HighlightParser $parser;

    protected function setUp(): void
    {
        $this->parser = new HighlightParser();
    }

    /**
     * @param list<array{string, bool}> $expected
     *
     * @dataProvider grammarProvider
     */
    public function testParseFollowsTheGrammar(?string $input, array $expected): void
    {
        $segments = $this->parser->parse($input);

        $actual = array_map(
            static fn (TextSegment $segment): array => [$segment->text, $segment->highlighted],
            $segments,
        );

        self::assertSame($expected, $actual);
    }

    /**
     * @return iterable<string, array{?string, list<array{string, bool}>}>
     */
    public static function grammarProvider(): iterable
    {
        yield 'null' => [null, []];
        yield 'chaîne vide' => ['', []];
        yield 'texte nu' => ['Bonjour', [['Bonjour', false]]];

        yield 'un marqueur' => [
            'Un **mot** mis en valeur',
            [['Un ', false], ['mot', true], [' mis en valeur', false]],
        ];

        yield 'deux marqueurs' => [
            '**Début** et **fin**',
            [['Début', true], [' et ', false], ['fin', true]],
        ];

        yield 'tout le texte' => ['**Tout le texte**', [['Tout le texte', true]]];

        yield 'marqueur non refermé' => [
            'Marqueur **jamais refermé',
            [['Marqueur **jamais refermé', false]],
        ];

        yield 'apparence d’imbrication' => [
            '**a **b** c**',
            [['a ', true], ['b', false], [' c', true]],
        ];

        yield 'paire vide' => ['Vide **** ici', [['Vide **** ici', false]]];

        yield 'trois astérisques' => [
            'Trois ***mots*** ici',
            [['Trois ', false], ['*mots', true], ['* ici', false]],
        ];

        yield 'script saisi' => [
            '<script>alert(1)</script>',
            [['<script>alert(1)</script>', false]],
        ];

        yield 'balisage mis en valeur' => [
            '**<b>gras</b>**',
            [['<b>gras</b>', true]],
        ];

        yield 'retour à la ligne' => [
            "Ligne 1\nLigne 2",
            [["Ligne 1\nLigne 2", false]],
        ];
    }

    /**
     * The invariant that catches any character silently dropped: concatenating the segments gives
     * back the input, minus four characters per pair actually formed.
     *
     * @dataProvider grammarProvider
     */
    public function testConcatenatingSegmentsRestoresTheInput(?string $input): void
    {
        $segments = $this->parser->parse($input);

        $concatenated = implode('', array_map(
            static fn (TextSegment $segment): string => $segment->text,
            $segments,
        ));

        $pairs = count(array_filter(
            $segments,
            static fn (TextSegment $segment): bool => $segment->highlighted,
        ));

        self::assertSame(mb_strlen((string) $input) - 4 * $pairs, mb_strlen($concatenated));
    }

    /**
     * Runs of bare markers are the shapes most likely to trip a scanner into an infinite loop or
     * an out-of-range read. None of them may throw, and none may lose a character.
     */
    public function testRunsOfBareMarkersAreSurvivable(): void
    {
        foreach (['*', '**', '***', '****', '*****', '** **', str_repeat('**', 50)] as $input) {
            $segments = $this->parser->parse($input);

            $concatenated = implode('', array_map(
                static fn (TextSegment $segment): string => $segment->text,
                $segments,
            ));

            $pairs = count(array_filter(
                $segments,
                static fn (TextSegment $segment): bool => $segment->highlighted,
            ));

            self::assertSame(
                mb_strlen($input) - 4 * $pairs,
                mb_strlen($concatenated),
                sprintf('entrée « %s »', $input),
            );
        }
    }
}
