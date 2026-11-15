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

        Shared::clickAndWaitForNewPage($I, '#yes');
        Shared::removeDebugBar($I);
        Shared::clickAndWaitForNewPage($I, '#no');

        Shared::removeDebugBar($I);
        Shared::clickAndWaitForNewPage($I, '#no');

        $I->seeElement('.song-index');
        $I->seeInCurrentUrl('cantos-camino-neocatecumenal');
    }

    public function noButtonShouldBeDisabledOnFirstLowestAttempt(AcceptanceTester $I)
    {
        Shared::givenASpanishNewUserInWizardLowestPage($I);

        // On first visit, No button should be type="button" (JS-only, not a real submit)
        $I->seeElement('#no[type="button"]');

        // After clicking Yes once, No button should become type="submit"
        Shared::clickAndWaitForNewPage($I, '#yes');
        $I->seeElement('#no[type="submit"]');
    }

    public function userShouldBeAbleToRestartWizardFromLowestPage(AcceptanceTester $I)
    {
        Shared::givenASpanishNewUserInWizardLowestPage($I);
        Shared::removeDebugBar($I);
        Shared::clickAndWaitForNewPage($I, '.wizard-nav a:first-child');
        $I->seeElement('.gender-selection');
    }
}
