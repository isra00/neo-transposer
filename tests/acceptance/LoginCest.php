<?php

namespace NeoTransposerTests\Acceptance;

use AcceptanceTester;
use Faker\Factory;

class LoginCest
{

    /*public function existingUserShouldLoginAndSeeBookPage(AcceptanceTester $I)
    {
        $I->amOnPage('/es/login');
        $I->fillField('email','isra00@gmail.com');
        $I->click('sent');
        $I->seeElement('.page-book');
    }*/

    public function newUserShouldSeeUserVoicePage(AcceptanceTester $I)
    {
        $faker = Factory::create();
        $I->amOnPage('/es/login');
        $I->fillField('email', $faker->email());
        $I->click('sent');
        $I->seeElement('.page-user-voice');
    }

    /*public function shouldRejectEmailWithoutAtSymbol(AcceptanceTester $I)
    {
        $I->amOnPage('/es/login');
        $I->fillField('email', 'invalidemail');
        $I->click('sent');
        $I->see('That e-mail doesn\'t look good. Please, re-type it.');
        $I->seeElement('.page-login');
    }

    public function shouldRejectEmailWithoutDomain(AcceptanceTester $I)
    {
        $I->amOnPage('/es/login');
        $I->fillField('email', 'test@');
        $I->click('sent');
        $I->see('That e-mail doesn\'t look good. Please, re-type it.');
        $I->seeElement('.page-login');
    }

    public function shouldRejectEmailWithoutLocalPart(AcceptanceTester $I)
    {
        $I->amOnPage('/es/login');
        $I->fillField('email', '@example.com');
        $I->click('sent');
        $I->see('That e-mail doesn\'t look good. Please, re-type it.');
        $I->seeElement('.page-login');
    }

    public function shouldRejectEmailWithSpaces(AcceptanceTester $I)
    {
        $I->amOnPage('/es/login');
        $I->fillField('email', 'test @example.com');
        $I->click('sent');
        $I->see('That e-mail doesn\'t look good. Please, re-type it.');
        $I->seeElement('.page-login');
    }

    public function shouldRejectEmptyEmail(AcceptanceTester $I)
    {
        $I->amOnPage('/es/login');
        $I->fillField('email', '');
        $I->click('sent');
        $I->see('That e-mail doesn\'t look good. Please, re-type it.');
        $I->seeElement('.page-login');
    }

    public function shouldRejectEmailWithInvalidCharacters(AcceptanceTester $I)
    {
        $I->amOnPage('/es/login');
        $I->fillField('email', 'test<>@example.com');
        $I->click('sent');
        $I->see('That e-mail doesn\'t look good. Please, re-type it.');
        $I->seeElement('.page-login');
    }

    public function shouldRejectEmailWithoutTopLevelDomain(AcceptanceTester $I)
    {
        $I->amOnPage('/es/login');
        $I->fillField('email', 'test@domain');
        $I->click('sent');
        $I->see('That e-mail doesn\'t look good. Please, re-type it.');
        $I->seeElement('.page-login');
    }*/

    public function shouldAcceptValidEmail(AcceptanceTester $I)
    {
        $faker = Factory::create();
        $I->amOnPage('/es/login');
        $I->fillField('email', $faker->email());
        $I->click('sent');
        $I->dontSee('That e-mail doesn\'t look good. Please, re-type it.');
        $I->seeElement('.page-user-voice');
    }
}
