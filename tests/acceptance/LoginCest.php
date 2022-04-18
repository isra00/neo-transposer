<?php

namespace NeoTransposerTests\Acceptance;

use AcceptanceTester;
use Faker\Factory;

class LoginCest
{
    protected function givenASpanishNewUserWithManualRangeInBookPage(AcceptanceTester $I)
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

    public function existingUserShouldLoginAndSeeBookPage(AcceptanceTester $I)
    {
        $I->amOnPage('/es/login');
        $I->fillField('email','isra00@gmail.com');
        $I->click('sent');
        $I->seeElement('.page-book');
    }

    public function newUserWithManualRangeShouldSeeEncourageFeedbackBanner(AcceptanceTester $I)
    {
        $this->givenASpanishNewUserWithManualRangeInBookPage($I);
        $I->seeElement('.encourage-feedback');
    }

    public function newUserWithManualRangeShouldNotSeeEncourageFeedbackBannerAfterReportingFeedback3Times(AcceptanceTester $I)
    {
        $this->givenASpanishNewUserWithManualRangeInBookPage($I);

        $I->click('.song-index li:nth-child(1) a');
        $I->click('#feedback-yes');

        $I->amOnPage('/cantos-camino-neocatecumenal');
        $I->click('.song-index li:nth-child(2) a');
        $I->click('#feedback-yes');

        $I->amOnPage('/cantos-camino-neocatecumenal');
        $I->click('.song-index li:nth-child(3) a');
        $I->click('#feedback-yes');

        $I->amOnPage('/cantos-camino-neocatecumenal');
        $I->dontSeeElement('.encourage-feedback');
    }
}
