<?php

namespace NeoTransposerTests\Acceptance;

use AcceptanceTester;
use Faker\Factory;

class LoginCest
{

    public function existingUserShouldLoginAndSeeBookPage(AcceptanceTester $I)
    {
        $I->amOnPage('/es/login');
        $I->fillField('email','isra00@gmail.com');
        $I->click('sent');
        $I->seeElement('.page-book');
    }

    public function newUserShouldSeeUserVoicePage(AcceptanceTester $I)
    {
        $faker = Factory::create();
        $I->amOnPage('/es/login');
        $I->fillField('email', $faker->email());
        $I->click('sent');
        $I->seeElement('.page-user-voice');
    }
}
