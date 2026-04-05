<?php

namespace NeoTransposerTests\Acceptance;

use AcceptanceTester;
use Faker\Factory;

class LoginCest
{
    private const ERROR_MSG_ES = 'Ese e-mail no tiene buena pinta. Por favor, revísalo.';

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

    public function shouldRejectEmailWithoutTopLevelDomain(AcceptanceTester $I)
    {
        $I->amOnPage('/es/login');
        $I->fillField('email', 'test@domain');
        $I->click('sent');
        $I->see(self::ERROR_MSG_ES);
        $I->seeElement('.page-login');
    }

    public function shouldAcceptValidEmail(AcceptanceTester $I)
    {
        $faker = Factory::create();
        $I->amOnPage('/es/login');
        $I->fillField('email', $faker->email());
        $I->click('sent');
        $I->dontSee(self::ERROR_MSG_ES);
        $I->seeElement('.page-user-voice');
    }
}
