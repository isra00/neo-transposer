<?php

namespace NeoTransposerTests\Acceptance;

use AcceptanceTester;

class WizardCest
{
    public function wizardSeeMaleSubOptionsWhenClickingMaleVoice(AcceptanceTester $I)
    {
        Shared::givenASpanishNewUserInVoicePage($I);
        $I->amOnPage('/es/wizard');
        Shared::removeDebugBar($I);
        $I->click('.gender-selection a[data-show="sub-male"]');
        $I->seeElement('#sub-male');
    }

    public function newUserWizardLowestHighestHappyPath(AcceptanceTester $I)
    {
        Shared::givenASpanishNewUserInWizardLowestPage($I);

        $I->click('#yes');
        Shared::removeDebugBar($I);
        $I->click('#no');

        Shared::removeDebugBar($I);
        $I->click('#no');

        $I->seeElement('.song-index');
        $I->seeInCurrentUrl('cantos-camino-neocatecumenal');
    }

    public function noButtonShouldBeDisabledOnFirstLowestAttempt(AcceptanceTester $I)
    {
        Shared::givenASpanishNewUserInWizardLowestPage($I);

        // On first visit, No button should be type="button" (JS-only, not a real submit)
        $I->seeElement('#no[type="button"]');

        // After clicking Yes once, No button should become type="submit"
        $I->click('#yes');
        $I->seeElement('#no[type="submit"]');
    }

    public function userShouldBeAbleToRestartWizardFromLowestPage(AcceptanceTester $I)
    {
        Shared::givenASpanishNewUserInWizardLowestPage($I);
        Shared::removeDebugBar($I);
        $I->click('.wizard-nav a:first-child');
        $I->seeElement('.gender-selection');
    }
}
