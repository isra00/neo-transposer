<?php

namespace NeoTransposerTests\Acceptance;

use AcceptanceTester;
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

    public function newUserShouldSeeUnhappyWarningAfterReporting5NegativeFeedbacks(AcceptanceTester $I)
    {
        $this->givenASpanishNewUserWithManualRangeInBookPage($I);

        Shared::whenIGoToNthSongAndClickButton($I, 1, '#feedback-no');
        Shared::whenIGoToNthSongAndClickButton($I, 2, '#feedback-no');
        Shared::whenIGoToNthSongAndClickButton($I, 3, '#feedback-no');
        Shared::whenIGoToNthSongAndClickButton($I, 4, '#feedback-no');
        Shared::whenIGoToNthSongAndClickButton($I, 5, '#feedback-no');

        $I->amOnPage('/cantos-camino-neocatecumenal');
        $I->seeElement('.unhappy-warning');
    }

    public function unhappyUserShouldHaveStandardRangeAssignedAfterTakingActionStandardRange(AcceptanceTester $I)
    {
        $this->givenASpanishNewUserWithManualRangeInBookPage($I);

        Shared::whenIGoToNthSongAndClickButton($I, 1, '#feedback-no');
        Shared::whenIGoToNthSongAndClickButton($I, 2, '#feedback-no');
        Shared::whenIGoToNthSongAndClickButton($I, 3, '#feedback-no');
        Shared::whenIGoToNthSongAndClickButton($I, 4, '#feedback-no');
        Shared::whenIGoToNthSongAndClickButton($I, 5, '#feedback-no');

        $I->amOnPage('/cantos-camino-neocatecumenal');

        $I->click('.standard-voice-selection a[href*="female"]');
        $I->click('.song-index li a');
        $I->see('Mi → La +1 oct');
    }
}
