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
        Shared::clickAndWaitForNewPage($I, 'sent');
        $I->click('#i-know');
        $I->selectOption('form select[name=lowest_note]', 'A1');
        $I->selectOption('form select[name=highest_note]', 'E3');
        Shared::clickAndWaitForNewPage($I, 'form button');
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

        Shared::clickAndWaitForNewPage($I, '.standard-voice-selection a[href*="female"]');
        Shared::clickAndWaitForNewPage($I, '.song-index li a');
        $I->see('Mi → La +1 oct');
    }
}
