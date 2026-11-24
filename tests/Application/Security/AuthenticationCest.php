<?php

declare(strict_types=1);

namespace App\Tests\Application\Security;

use App\Entity\User;
use App\Factory\UserFactory;
use App\Tests\Support\ApplicationTester;

final class AuthenticationCest
{
    public function userCanLogin(ApplicationTester $I): void
    {
        $user = UserFactory::createOne();
        $I->amOnPage('/login');
        $I->seeResponseCodeIsSuccessful();

        $I->fillField(['name' => '_username'], $user->getEmail());
        $I->fillField(['name' => '_password'], $user->getPassword());
        $I->click('.btn-login-submit');

        $userInstance = $I->grabEntityFromRepository(User::class, ['email' => $user->getEmail()]);
        $I->amLoggedInAs($userInstance);
    }

    public function authenticatedUserCanLogout(ApplicationTester $I): void
    {
        $user = UserFactory::createOne();
        $userInstance = $I->grabEntityFromRepository(User::class, ['email' => $user->getEmail()]);
        $I->amLoggedInAs($userInstance);

        $I->amOnPage('/logout');
        $I->seeResponseCodeIsSuccessful();
        $I->dontSeeAuthentication();
    }

    public function loginWithWrongCredentialsFails(ApplicationTester $I): void
    {
        $user = UserFactory::createOne();
        $I->amOnPage('/login');
        $I->seeResponseCodeIsSuccessful();

        $I->fillField(['name' => '_username'], $user->getEmail());
        $I->fillField(['name' => '_password'], 'ABC78d');
        $I->click('.btn-login-submit');

        $I->dontSeeAuthentication();
    }

    public function anonymousUserCanCreateAnAccount(ApplicationTester $I): void
    {
        $I->dontSeeAuthentication();
        $I->amOnPage('/register');
        $I->seeResponseCodeIsSuccessful();

        $I->fillField(['name' => 'registration_form[username]'], 'myfirstaccount99');
        $I->fillField(['name' => 'registration_form[email]'], 'test.test@example.com');
        $I->fillField(['name' => 'registration_form[firstname]'], 'Toto');
        $I->fillField(['name' => 'registration_form[lastname]'], 'Titi');

        $password = 'Azerty01!';
        $I->fillField(['name' => 'registration_form[password][first]'], $password);
        $I->fillField(['name' => 'registration_form[password][second]'], $password);
        $I->click('.btn-login-submit');

        $I->seeAuthentication();
    }
}
