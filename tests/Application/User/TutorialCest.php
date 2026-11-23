<?php

namespace App\Tests\Application\User;

use App\Factory\UserFactory;
use App\Tests\Support\ApplicationTester;
use Codeception\Util\HttpCode;

class TutorialCest
{
    public function anonymousUserDoesNotSeeTutorial(ApplicationTester $I): void
    {
        $I->amOnPage('/');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->dontSeeElement('#tutorial-tooltip');
        $I->dontSeeElement('#start-tutorial-btn');
        $I->dontSeeElement('body[data-tutorial-completed]');
    }

    public function firstTimeUserSeesTutorialAsNotCompleted(ApplicationTester $I): void
    {
        $user = UserFactory::createOne(['hasCompletedTutorial' => false]);

        $I->amLoggedInAs($user);
        $I->amOnPage('/');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeElement('#tutorial-tooltip');
        $I->seeElement('#start-tutorial-btn');
        $I->seeElement('body[data-tutorial-completed="false"]');
    }

    public function returningUserSeesTutorialAsCompleted(ApplicationTester $I): void
    {
        $user = UserFactory::createOne(['hasCompletedTutorial' => true]);

        $I->amLoggedInAs($user);
        $I->amOnPage('/');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeElement('#tutorial-tooltip');
        $I->seeElement('#start-tutorial-btn');
        $I->seeElement('body[data-tutorial-completed="true"]');
    }

    public function userCanCompleteTutorialWithValidCsrfToken(ApplicationTester $I): void
    {
        $user = UserFactory::createOne(['hasCompletedTutorial' => false]);

        $I->amLoggedInAs($user);
        $I->amOnPage('/');
        $csrfToken = $I->grabAttributeFrom('body', 'data-tutorial-csrf-token');

        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->haveHttpHeader('X-CSRF-TOKEN', $csrfToken);
        $I->sendPost('/tutorial/complete', json_encode(['_token' => $csrfToken], JSON_THROW_ON_ERROR));

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseContainsJson(['completed' => true]);

        $I->amOnPage('/');
        $I->seeElement('body[data-tutorial-completed="true"]');
    }

    public function userCannotCompleteTutorialWithInvalidCsrfToken(ApplicationTester $I): void
    {
        $user = UserFactory::createOne(['hasCompletedTutorial' => false]);

        $I->amLoggedInAs($user);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->haveHttpHeader('X-CSRF-TOKEN', 'invalid-token');
        $I->sendPost('/tutorial/complete', json_encode(['_token' => 'invalid-token'], JSON_THROW_ON_ERROR));

        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);

        $I->amOnPage('/');
        $I->seeElement('body[data-tutorial-completed="false"]');
    }
}
