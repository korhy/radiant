<?php

declare(strict_types=1);

namespace App\Tests\Service\Motus;

use App\Service\Motus\MotusService;
use PHPUnit\Framework\TestCase;

/**
 * The two-pass logic of checkGuess() is where Wordle clones get it wrong: a
 * letter repeated in the guess may only be flagged as many times as it really
 * occurs in the word.
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
        // SIROP vs PORTS: R lands right, T is absent, P/O/S are misplaced.
        self::assertSame(
            ['present', 'present', 'correct', 'absent', 'present'],
            $this->states('PORTS', 'SIROP')
        );
    }

    /**
     * The case naive implementations get wrong: the well-placed S consumes the
     * only S of the word, so the second S of the guess must be ABSENT, not
     * "present".
     */
    public function testDuplicateInGuessDoesNotOverConsumeASingleLetter(): void
    {
        self::assertSame(
            ['correct', 'present', 'absent', 'present', 'absent'],
            $this->states('SOSIE', 'SIROP')
        );
    }

    /**
     * Mirror of the previous case: the word does hold two S, so both S of the
     * guess must be flagged.
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
