<?php

namespace App\Tests\Application\User;

use App\Factory\UserFactory;
use App\Tests\Support\ApplicationTester;

class LoginCest
{
    public function loginRedirectsToTreasureHunts(ApplicationTester $I): void
    {
        UserFactory::createOne(['email' => 'login@example.com']);

        $I->amOnPage('/login');
        $I->submitForm('form', ['_username' => 'login@example.com', '_password' => 'mdp']);

        $I->seeCurrentUrlEquals('/treasure_hunt');
    }

    public function loggedInUserIsRedirectedFromLogin(ApplicationTester $I): void
    {
        $I->amLoggedInAs(UserFactory::createOne());
        $I->amOnPage('/login');

        $I->seeCurrentUrlEquals('/treasure_hunt');
    }
}
