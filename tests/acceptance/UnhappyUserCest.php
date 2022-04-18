<?php

use Faker\Factory;

class UnhappyUserCest
{
    protected function givenASpanishNewUserWithManualRangeInBookPage(AcceptanceTester $I): void
    {
        $faker = Factory::create();
        $I->amOnPage('/es/login');
        $I->fillField('email', $faker->email());
        $I->click('sent');
        $I->click('#i-know');
        $I->selectOption("form select[name=lowest_note]", 'A1');
        $I->selectOption("form select[name=highest_note]", 'E3');
        $I->click('form button');
    }

    protected function whenIGoToNthSongAndClickButton(AcceptanceTester $I, int $songIndex, string $clickElement): void
    {
        $I->amOnPage('/cantos-camino-neocatecumenal');
        $I->click('.song-index li:nth-child(' . $songIndex . ') a');
        $I->click($clickElement);
    }

    public function newUserShouldSeeUnhappyWarningAfterReporting5NegativeFeedbacks(AcceptanceTester $I)
    {
        $this->givenASpanishNewUserWithManualRangeInBookPage($I);

        $this->whenIGoToNthSongAndClickButton($I, 1, '#feedback-no');
        $this->whenIGoToNthSongAndClickButton($I, 2, '#feedback-no');
        $this->whenIGoToNthSongAndClickButton($I, 3, '#feedback-no');
        $this->whenIGoToNthSongAndClickButton($I, 4, '#feedback-no');
        $this->whenIGoToNthSongAndClickButton($I, 5, '#feedback-no');

        $I->amOnPage('/cantos-camino-neocatecumenal');
        $I->seeElement('.unhappy-warning');
    }

    public function unhappyUserShouldHaveStandardRangeAssignedAfterTakingActionStandardRange(AcceptanceTester $I)
    {
        $this->givenASpanishNewUserWithManualRangeInBookPage($I);

        $this->whenIGoToNthSongAndClickButton($I, 1, '#feedback-no');
        $this->whenIGoToNthSongAndClickButton($I, 2, '#feedback-no');
        $this->whenIGoToNthSongAndClickButton($I, 3, '#feedback-no');
        $this->whenIGoToNthSongAndClickButton($I, 4, '#feedback-no');
        $this->whenIGoToNthSongAndClickButton($I, 5, '#feedback-no');

        $I->amOnPage('/cantos-camino-neocatecumenal');

        $I->click('.standard-voice-selection a[href*="female"]');
        $I->click('.song-index li a');
        $I->see('Mi → La +1 oct');
    }
}
