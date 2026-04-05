<?php

namespace NeoTransposerTests\Acceptance;

use AcceptanceTester;
use Faker\Factory;

class LoginCest
{
    private const ERROR_MSG_ES = 'Ese e-mail no tiene buena pinta. Por favor, revísalo.';

    public function existingUserShouldLoginAndSeeBookPage(AcceptanceTester $I)
    {
        $faker = Factory::create();
        $email = $faker->email();

        // Register a new user and set voice range
        $I->amOnPage('/es/login');
        $I->fillField('email', $email);
        $I->click('sent');
        $I->click('#i-know');
        $I->selectOption("form select[name=lowest_note]", 'A1');
        $I->selectOption("form select[name=highest_note]", 'E3');
        $I->click('form button');

        // Log back in with the same email
        $I->amOnPage('/es/login');
        $I->fillField('email', $email);
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
