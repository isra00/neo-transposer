<?php

namespace NeoTransposerTests\Acceptance;

use AcceptanceTester;
use Faker\Factory;

class Shared
{
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

    public static function whenIGoToNthSongAndClickButton(AcceptanceTester $I, int $songIndex, string $clickElement): void
    {
        $I->amOnPage('/cantos-camino-neocatecumenal');
        $I->click('.song-index li:nth-child(' . $songIndex . ') a');
        $I->click($clickElement);
    }
}