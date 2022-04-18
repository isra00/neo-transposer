<?php

namespace NeoTransposerTests\Acceptance;

use AcceptanceTester;
use Faker\Factory;

class LoginCest
{

    protected function newUserWithManualRangeInSpanish(AcceptanceTester $I)
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

    public function LoginSuccessfullyWithExistingUserSpanish(AcceptanceTester $I)
    {
        $I->amOnPage('/es/login');
        $I->fillField('email','isra00@gmail.com');
        $I->click('sent');
        $I->see('Como oveja');
    }

    public function newUserWithManualRangeShouldSeeEncourageFeedbackBanner(AcceptanceTester $I)
    {
        $this->newUserWithManualRangeInSpanish($I);
        $I->seeElement('.encourage-feedback');
    }

    /*public function newUserWithManualRangeShouldNotSeeEncourageFeedbackBannerAfterFeedback(AcceptanceTester $I)
    {
        $this->newUserWithManualRangeInSpanish($I);
        $I->seeElement('.encourage-feedback');
        $I->click('.song-index li:nth-child(1) a');

        $I->click('#feedback-yes');

        $I->amOnPage('/cantos-camino-neocatecumenal');
        $I->click('.song-index li:nth-child(2) a');
        $I->click('#feedback-yes');

        $I->amOnPage('/cantos-camino-neocatecumenal');
        $I->see('Todo listo');
    }*/
}
