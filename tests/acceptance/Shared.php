<?php

namespace NeoTransposerTests\Acceptance;

use AcceptanceTester;
use Faker\Factory;

class Shared
{
    public static function removeDebugBar(AcceptanceTester $I): void
    {
        $I->executeJS('document.querySelectorAll(".phpdebugbar").forEach(e => e.remove())');
    }

    /**
     * Wait until no Zepto AJAX request is in flight.
     *
     * The feedback buttons report to the server via AJAX, and the click handler
     * updates the DOM before the request resolves. Navigating away right after
     * clicking therefore aborts the request and the feedback is never recorded,
     * which made the tests that depend on accumulated feedback flaky.
     */
    public static function waitForAjax(AcceptanceTester $I): void
    {
        $I->waitForJS('return window.$ && $.active === 0', 10);
    }

    public static function givenASpanishNewUserWithManualRangeInBookPage(AcceptanceTester $I): void
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

    public static function givenASpanishNewUserInVoicePage(AcceptanceTester $I): void
    {
        $faker = Factory::create();
        $I->amOnPage('/es/login');
        $I->fillField('email', $faker->email());
        $I->click('sent');
        $I->seeElement('.page-user-voice');
    }

    public static function givenASpanishNewUserInWizardLowestPage(AcceptanceTester $I): void
    {
        self::givenASpanishNewUserInVoicePage($I);
        $I->amOnPage('/es/wizard');
        self::removeDebugBar($I);
        $I->click('.gender-selection a[data-show="sub-female"]');
        $I->click('#sub-female li:nth-child(1) a');
        $I->click('#submit');
    }

    public static function whenIGoToNthSongAndClickButton(AcceptanceTester $I, int $songIndex, string $clickElement): void
    {
        $I->amOnPage('/cantos-camino-neocatecumenal');
        $I->click('.song-index li:nth-child(' . $songIndex . ') a');
        self::removeDebugBar($I);
        $I->click($clickElement);
        self::waitForAjax($I);
    }
}
