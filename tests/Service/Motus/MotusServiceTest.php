<?php

declare(strict_types=1);

namespace App\Tests\Service\Motus;

use App\Service\Motus\MotusService;
use PHPUnit\Framework\TestCase;

/**
 * La logique en deux passes de checkGuess() est le point où les clones de Wordle
 * se trompent : une lettre doublée dans la proposition ne doit être signalée que
 * autant de fois qu'elle apparaît réellement dans le mot.
 */
final class MotusServiceTest extends TestCase
{
    private MotusService $service;

    protected function setUp(): void
    {
        $this->service = new MotusService();
    }

    /**
     * @return list<string>
     */
    private function states(string $guess, string $word): array
    {
        return array_column($this->service->checkGuess($guess, $word), 'state');
    }

    public function testExactMatchIsFullyCorrect(): void
    {
        self::assertSame(
            ['correct', 'correct', 'correct', 'correct', 'correct'],
            $this->states('SIROP', 'SIROP')
        );
    }

    public function testNoSharedLetterIsFullyAbsent(): void
    {
        self::assertSame(
            ['absent', 'absent', 'absent', 'absent', 'absent'],
            $this->states('MUTED', 'SIROP')
        );
    }

    public function testMisplacedLettersArePresent(): void
    {
        // SIROP vs PORTS : R tombe juste, T est absent, P/O/S sont mal placés.
        self::assertSame(
            ['present', 'present', 'correct', 'absent', 'present'],
            $this->states('PORTS', 'SIROP')
        );
    }

    /**
     * Le cas qui casse les implémentations naïves : le S bien placé consomme
     * l'unique S du mot, donc le second S de la proposition doit être ABSENT
     * et non « présent ».
     */
    public function testDuplicateInGuessDoesNotOverConsumeASingleLetter(): void
    {
        self::assertSame(
            ['correct', 'present', 'absent', 'present', 'absent'],
            $this->states('SOSIE', 'SIROP')
        );
    }

    /**
     * Symétrique du précédent : le mot contient bien deux S, les deux S de la
     * proposition doivent donc être signalés tous les deux.
     */
    public function testDuplicateInWordIsReportedTwice(): void
    {
        self::assertSame(
            ['present', 'absent', 'absent', 'correct', 'correct'],
            $this->states('SALES', 'ROSES')
        );
    }

    public function testComparisonIsCaseInsensitive(): void
    {
        self::assertSame(
            $this->states('SIROP', 'SIROP'),
            $this->states('sirop', 'sirop')
        );
    }

    public function testResultKeepsTheGuessLettersUppercased(): void
    {
        $result = $this->service->checkGuess('sirop', 'SIROP');

        self::assertSame(['S', 'I', 'R', 'O', 'P'], array_column($result, 'letter'));
    }

    public function testResultHasOneEntryPerLetterOfTheWord(): void
    {
        self::assertCount(8, $this->service->checkGuess('BOUSSOLE', 'BOUSSOLE'));
    }

    public function testWordOfTheDayIsStableWithinTheSameDay(): void
    {
        self::assertSame($this->service->getWordOfTheDay(), $this->service->getWordOfTheDay());
    }

    public function testWordOfTheDayRespectsTheAnnouncedLengthRange(): void
    {
        $word = $this->service->getWordOfTheDay();

        self::assertGreaterThanOrEqual(5, mb_strlen($word));
        self::assertLessThanOrEqual(8, mb_strlen($word));
        self::assertSame(mb_strtoupper($word), $word, 'Le mot du jour doit être en majuscules.');
    }
}
