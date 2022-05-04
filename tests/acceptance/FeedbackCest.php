<?php

namespace NeoTransposerTests\Acceptance;

use AcceptanceTester;

class FeedbackCest
{
    public function newUserShouldSeeFeedbackTickForASongOnBookPageAfterReportingFeedbackOnThatSong(AcceptanceTester $I)
    {
        Shared::givenASpanishNewUserWithManualRangeInBookPage($I);
        Shared::whenIGoToNthSongAndClickButton($I, 1, '#feedback-yes');
        $I->amOnPage('/cantos-camino-neocatecumenal');
        $I->seeElement('.song-index li:nth-child(1) a .green');
    }
}
