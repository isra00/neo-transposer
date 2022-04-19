<?php

namespace NeoTransposerTests\Acceptance;

use AcceptanceTester;
use Faker\Factory;

class EncourageFeedbackCest
{
    public function newUserWithManualRangeShouldSeeEncourageFeedbackBanner(AcceptanceTester $I)
    {
        Shared::givenASpanishNewUserWithManualRangeInBookPage($I);
        $I->seeElement('.encourage-feedback');
    }

    public function newUserWithManualRangeShouldNotSeeEncourageFeedbackBannerAfterReportingFeedback3Times(AcceptanceTester $I)
    {
        Shared::givenASpanishNewUserWithManualRangeInBookPage($I);

        Shared::whenIGoToNthSongAndClickButton($I, 1, '#feedback-yes');
        Shared::whenIGoToNthSongAndClickButton($I, 2, '#feedback-yes');
        Shared::whenIGoToNthSongAndClickButton($I, 3, '#feedback-yes');

        $I->amOnPage('/cantos-camino-neocatecumenal');
        $I->dontSeeElement('.encourage-feedback');
    }
}
