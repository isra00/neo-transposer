<?php

namespace NeoTransposerTests\Acceptance;

use AcceptanceTester;

/**
 * Songs are inserted very seldom, and the insertion writes to two tables (song and
 * song_chord) within one transaction. This covers that path end to end: if the chords
 * were lost, or the song row were left orphaned, the transposition page would not print
 * the original chords.
 */
class InsertSongCest
{
    private const CHORDS = ['C', 'G', 'Am', 'F'];

    public function _before(AcceptanceTester $I)
    {
        $I->haveHttpHeader('X-Admin-Bypass', 'local-dev');
    }

    public function insertedSongShouldBeShownCorrectlyInTransposeSongPage(AcceptanceTester $I)
    {
        $title = 'Test song ' . uniqid();

        $this->whenIFillTheInsertSongForm($I, $title);
        $I->click('#submit');
        $I->see('Song inserted', '.notification.success');

        Shared::givenASpanishNewUserWithManualRangeInBookPage($I);

        $I->amOnPage('/transpose/' . str_replace(' ', '-', strtolower($title)));
        Shared::removeDebugBar($I);
        $I->see($title, 'h1');

        //The page prints several transpositions, all with the same original chords, except
        //the "as-book" one, which prints none.
        $firstTranspositionOriginalChords =
            '(//table[contains(@class, "transposition") and not(contains(@class, "as-book"))])[1]'
            . '//td[@class="original"]';

        //One cell per original chord: proves the song_chord rows were persisted too.
        $I->seeNumberOfElements($firstTranspositionOriginalChords, count(self::CHORDS));

        foreach (self::CHORDS as $chord) {
            $I->see($chord, $firstTranspositionOriginalChords);
        }
    }

    /**
     * A repeated chord violates song_chord's UNIQUE(id_song, chord), so the chord insert
     * fails after the song row has already been written. That is exactly the partial write
     * the transaction exists to prevent: no song must be left behind.
     */
    public function aFailedChordInsertShouldLeaveNoSongBehind(AcceptanceTester $I)
    {
        $title = 'Test song ' . uniqid();

        $this->whenIFillTheInsertSongForm($I, $title);

        //Duplicate chords are only rejected by the form's own JS, which runs on keyup;
        //setting the value directly is what any client that skips it would send, and the
        //point of the test is that the server survives it.
        $I->executeJS('document.querySelector(\'input[name="chords[3]"]\').value = "C"');
        $I->click('#submit');
        $I->dontSee('Song inserted');

        Shared::givenASpanishNewUserWithManualRangeInBookPage($I);

        $I->amOnPage('/transpose/' . str_replace(' ', '-', strtolower($title)));
        $I->seeElement('.error-page');
    }

    private function whenIFillTheInsertSongForm(AcceptanceTester $I, string $title): void
    {
        $I->amOnPage('/admin/insert-song');
        Shared::removeDebugBar($I);

        $I->selectOption('#book', 'English');
        $I->fillField('#title', $title);
        $I->fillField('#lowest_note', 'C2');
        $I->fillField('#highest_note', 'C3');
        $I->fillField('#people_lowest_note', 'C2');
        $I->fillField('#people_highest_note', 'C3');

        foreach (self::CHORDS as $position => $chord) {
            $I->fillField('input[name="chords[' . $position . ']"]', $chord);
        }
    }
}
