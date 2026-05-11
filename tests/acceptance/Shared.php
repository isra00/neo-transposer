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
    }
}
