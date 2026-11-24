<?php

declare(strict_types=1);

namespace App\Tests\Application\Security;

use App\Entity\User;
use App\Factory\UserFactory;
use App\Tests\Support\ApplicationTester;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

final class AuthenticationCest
{
    public function userCanLogin(ApplicationTester $I): void
    {
        $user = UserFactory::createOne();
        $userEmail = $user->getEmail();
        $I->amOnPage('/login');
        $I->seeResponseCodeIsSuccessful();

        $I->fillField(['name' => '_username'], $userEmail);
        $I->fillField(['name' => '_password'], $user->getPassword());
        $I->click('.btn-login-submit');

        $userInstance = $I->grabEntityFromRepository(User::class, ['email' => $userEmail]);
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

    public function userCanChangePassword(ApplicationTester $I): void
    {
        $user = UserFactory::createOne();
        $oldHashedPassword = $user->getPassword();
        $userEmail = $user->getEmail();
        $userInstance = $I->grabEntityFromRepository(User::class, ['email' => $userEmail]);
        $I->amLoggedInAs($userInstance);
        $I->amOnPage('/settings');
        $I->seeResponseCodeIsSuccessful();

        $I->click('.change__password__btn');
        $I->seeCurrentUrlEquals('/reset-password');
        $I->seeElement('input', ['name' => 'reset_password_request_form[email]']);
        $I->fillField(['name' => 'reset_password_request_form[email]'], $userEmail);
        $I->stopFollowingRedirects();
        $I->click('.btn-login-submit');
        $I->seeResponseCodeIs(302);

        $I->assertQueuedEmailCount(1);
        $event = $I->getMailerEvent();
        $I->assertNotNull($event);
        $I->assertEmailIsQueued($event);

        $message = $event->getMessage();
        $I->assertInstanceOf(TemplatedEmail::class, $message);
        $I->assertSame($userEmail, $message->getTo()[0]->getAddress());
        $I->assertSame('no-reply@wayfinder.com', $message->getFrom()[0]->getAddress());
        $I->assertSame('Your password reset request', $message->getSubject());

        $matched = preg_match('#/reset-password/reset/[A-Za-z0-9]+#', (string) $message->getHtmlBody(), $matches);
        $I->assertSame(1, $matched, 'Lien de réinitialisation introuvable dans le mail.');

        $I->followRedirect();
        $I->startFollowingRedirects();
        $I->seeCurrentUrlEquals('/reset-password/check-email');
        $I->seeResponseCodeIsSuccessful();

        $I->amOnPage($matches[0]);
        $I->seeCurrentUrlEquals('/reset-password/reset');
        $I->seeResponseCodeIsSuccessful();

        $newPassword = 'gdfj!GFD7897!';
        $I->fillField(['name' => 'change_password_form[plainPassword][first]'], $newPassword);
        $I->fillField(['name' => 'change_password_form[plainPassword][second]'], $newPassword);
        $I->click('.btn-login-submit');

        $updatedUser = $I->grabEntityFromRepository(User::class, ['email' => $userEmail]);
        $I->assertNotSame(
            $oldHashedPassword,
            $updatedUser->getPassword(),
            "Les mots de passe sont identiques, le mot de passe n'est pas modifié"
        );
    }
}
