<?php

namespace NeoTransposerTests\Acceptance;

use AcceptanceTester;
use Faker\Factory;

class TransposeSongCest
{
    // tests
    public function userWithDefinedVoiceRangeShouldGetExactTranspositionsForAGivenSong(AcceptanceTester $I)
    {
        $faker = Factory::create();
        $I->amOnPage('/es/login');
        $I->fillField('email', $faker->email());
        $I->click('sent');
        $I->click('#i-know');
        $I->selectOption("form select[name=lowest_note]", 'A1');
        $I->selectOption("form select[name=highest_note]", 'E3');
        $I->click('form button');

        $I->amOnPage('/transpose/gracias-a-yahveh');

        $I->see('La-', '//table[1]/thead/tr/th/span[1]');
        $I->see('con capo 4', '//table[1]/thead/tr/th/span[2]');
        $I->see('Si-', '//table[2]/thead/tr/th/span[1]');
        $I->see('con capo 2', '//table[2]/thead/tr/th/span[2]');
    }

    public function userInTransposeSongShouldSeeVoiceChartWhenClickedOnShowChart(AcceptanceTester $I)
    {
        Shared::givenASpanishNewUserWithManualRangeInBookPage($I);

        $I->amOnPage('/transpose/gracias-a-yahveh');
        $I->scrollTo('#show-voice-chart');
        $I->click('#show-voice-chart');

        $I->seeElement('table.voicechart');
    }
}
