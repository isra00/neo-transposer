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

    /**
     * When the feedback says which transposition worked, the transpose song page
     * marks that one, and only that one, with the check sign.
     */
    public function userShouldSeeFeedbackTickNextToTheTranspositionTheyReportedAsWorking(AcceptanceTester $I)
    {
        Shared::givenASpanishNewUserWithManualRangeInBookPage($I);
        Shared::whenIGoToNthSongAndClickButton($I, 1, '#feedback-yes');

        //Answering "Yes" opens the dialog asking which transposition worked.
        $I->click('#transpositions-feedback li:nth-child(2) .detailed-fb-choice');
        Shared::waitForAjax($I);

        $I->reloadPage();

        //Only the first transpositions-list holds two tables, so nth-child(2) is the second centered one.
        $I->seeElement('.transpositions-list table.transposition:nth-child(2) .feedback.green');
        $I->dontSeeElement('.transpositions-list table.transposition:nth-child(1) .feedback.green');
    }

    /**
     * The feedback row keeps the transposition the user once reported, so reporting
     * the song as not working afterwards must take the check sign away.
     */
    public function userShouldNotSeeTheFeedbackTickAfterReportingTheSongAsNotWorking(AcceptanceTester $I)
    {
        Shared::givenASpanishNewUserWithManualRangeInBookPage($I);
        Shared::whenIGoToNthSongAndClickButton($I, 1, '#feedback-yes');

        $I->click('#transpositions-feedback li:nth-child(2) .detailed-fb-choice');
        Shared::waitForAjax($I);

        //The answer buttons are hidden after answering, so reload to change the answer.
        $I->reloadPage();
        Shared::removeDebugBar($I);
        $I->click('#feedback-no');
        Shared::waitForAjax($I);

        $I->reloadPage();
        $I->dontSeeElement('.transposition .feedback.green');
    }

    /**
     * The teaser transposes for a standard voice, so there is no feedback of the
     * visitor's own to mark.
     */
    public function userShouldNotSeeAnyFeedbackTickWhenNotLoggedIn(AcceptanceTester $I)
    {
        $I->amOnPage('/transpose/gracias-a-yahveh');
        $I->dontSeeElement('.transposition .feedback.green');
    }
}
