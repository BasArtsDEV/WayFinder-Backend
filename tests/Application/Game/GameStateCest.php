<?php

namespace App\Tests\Application\Game;

use App\Entity\Enrollment;
use App\Entity\RiddleText;
use App\Entity\TreasureHunt;
use App\Entity\User;
use App\Enum\EnrollmentStatus;
use App\Factory\ChoiceFactory;
use App\Factory\EnrollmentFactory;
use App\Factory\LocationFactory;
use App\Factory\RiddleQCMFactory;
use App\Factory\RiddleTextFactory;
use App\Factory\TeamFactory;
use App\Factory\TreasureHuntFactory;
use App\Factory\UserFactory;
use App\Tests\Support\ApplicationTester;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class GameStateCest
{
    private function loginAs(ApplicationTester $I, User $user): void
    {
        /** @var JWTTokenManagerInterface $jwtManager */
        $jwtManager = $I->grabService('lexik_jwt_authentication.jwt_manager');
        $I->haveHttpHeader('Authorization', 'Bearer '.$jwtManager->create($user));
    }

    private function createHunt(int $duration = 3600): TreasureHunt
    {
        LocationFactory::createOne();
        TeamFactory::createOne();

        return TreasureHuntFactory::createOne([
            'duration' => $duration,
            'listed' => true,
            'published' => true,
        ]);
    }

    private function createEnrollment(User $user, TreasureHunt $treasureHunt, RiddleText $riddle): Enrollment
    {
        return EnrollmentFactory::createOne([
            'countdown' => 0,
            'lastResumedAt' => new \DateTimeImmutable(),
            'riddle' => $riddle,
            'scoreTotal' => 0,
            'status' => EnrollmentStatus::IN_PROGRESS,
            'treasureHunt' => $treasureHunt,
            'user' => $user,
        ]);
    }

    public function playerGetsCurrentRiddleWithoutAnswer(ApplicationTester $I): void
    {
        $user = UserFactory::createOne();
        $treasureHunt = $this->createHunt();
        $riddle = RiddleTextFactory::createOne(['index' => 1, 'treasureHunt' => $treasureHunt, 'expectedAnswer' => 'secret']);
        RiddleTextFactory::createOne(['index' => 2, 'treasureHunt' => $treasureHunt]);
        $enrollment = $this->createEnrollment($user, $treasureHunt, $riddle);

        $this->loginAs($I, $user);
        $I->sendGet('/api/enrollments/'.$enrollment->getId().'/game');

        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseContainsJson([
            'status' => 'in_progress',
            'step' => 1,
            'totalSteps' => 2,
            'riddle' => ['type' => 'text', 'question' => $riddle->getQuestion()],
        ]);
        $I->dontSeeResponseContains('secret');
        $I->dontSeeResponseContains('expectedAnswer');
    }

    public function qcmRiddleHidesRightChoices(ApplicationTester $I): void
    {
        $user = UserFactory::createOne();
        $treasureHunt = $this->createHunt();
        $riddle = RiddleQCMFactory::createOne(['index' => 1, 'treasureHunt' => $treasureHunt]);
        ChoiceFactory::createOne(['riddleQCM' => $riddle, 'isRight' => true]);
        ChoiceFactory::createOne(['riddleQCM' => $riddle, 'isRight' => false]);
        $enrollment = EnrollmentFactory::createOne([
            'countdown' => 0,
            'lastResumedAt' => new \DateTimeImmutable(),
            'riddle' => $riddle,
            'status' => EnrollmentStatus::IN_PROGRESS,
            'treasureHunt' => $treasureHunt,
            'user' => $user,
        ]);

        $this->loginAs($I, $user);
        $I->sendGet('/api/enrollments/'.$enrollment->getId().'/game');

        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseContainsJson(['riddle' => ['type' => 'qcm', 'answersCount' => 1]]);
        $I->dontSeeResponseContains('isRight');
    }

    public function startingThenPausingAndResumingTheGame(ApplicationTester $I): void
    {
        $user = UserFactory::createOne();
        $treasureHunt = $this->createHunt();
        $riddle = RiddleTextFactory::createOne(['index' => 1, 'treasureHunt' => $treasureHunt]);
        $enrollment = EnrollmentFactory::createOne([
            'countdown' => 0,
            'riddle' => null,
            'status' => EnrollmentStatus::NOT_STARTED,
            'treasureHunt' => $treasureHunt,
            'user' => $user,
        ]);
        $uri = '/api/enrollments/'.$enrollment->getId();

        $this->loginAs($I, $user);
        $I->haveHttpHeader('Content-Type', 'application/merge-patch+json');

        $I->sendPatch($uri, json_encode(['status' => 'in_progress']));
        $I->seeResponseCodeIsSuccessful();
        $I->sendGet($uri.'/game');
        $I->seeResponseContainsJson(['status' => 'in_progress', 'step' => 1, 'riddle' => ['iri' => '/api/riddle_texts/'.$riddle->getId()]]);

        $I->sendPatch($uri, json_encode(['status' => 'paused']));
        $I->seeResponseCodeIsSuccessful();
        $I->sendGet($uri.'/game');
        $I->seeResponseContainsJson(['status' => 'paused', 'step' => 1]);

        $I->sendPatch($uri, json_encode(['status' => 'in_progress']));
        $I->seeResponseCodeIsSuccessful();
        $I->sendGet($uri.'/game');
        $I->seeResponseContainsJson(['status' => 'in_progress', 'step' => 1]);
    }

    public function otherUserCannotSeeTheGame(ApplicationTester $I): void
    {
        $user = UserFactory::createOne();
        $other = UserFactory::createOne();
        $treasureHunt = $this->createHunt();
        $riddle = RiddleTextFactory::createOne(['index' => 1, 'treasureHunt' => $treasureHunt]);
        $enrollment = $this->createEnrollment($user, $treasureHunt, $riddle);

        $this->loginAs($I, $other);
        $I->sendGet('/api/enrollments/'.$enrollment->getId().'/game');

        $I->seeResponseCodeIs(403);
    }

    public function wrongAnswerAppliesPenaltyAndRightAnswerGoesToNextRiddle(ApplicationTester $I): void
    {
        $user = UserFactory::createOne();
        $treasureHunt = $this->createHunt();
        $first = RiddleTextFactory::createOne(['index' => 1, 'treasureHunt' => $treasureHunt, 'expectedAnswer' => 'Paris', 'secondsPenalty' => 60, 'score' => 10]);
        RiddleTextFactory::createOne(['index' => 2, 'treasureHunt' => $treasureHunt]);
        $enrollment = $this->createEnrollment($user, $treasureHunt, $first);

        $this->loginAs($I, $user);
        $I->haveHttpHeader('Content-Type', 'application/ld+json');
        $I->sendPost('/api/submissions', [
            'enrollment' => '/api/enrollments/'.$enrollment->getId(),
            'riddle' => '/api/riddle_texts/'.$first->getId(),
            'submittedValue' => 'Londres',
        ]);
        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseContainsJson(['isRight' => false, 'appliedPenalty' => 60]);

        $I->sendPost('/api/submissions', [
            'enrollment' => '/api/enrollments/'.$enrollment->getId(),
            'riddle' => '/api/riddle_texts/'.$first->getId(),
            'submittedValue' => ' paris ',
        ]);
        $I->seeResponseContainsJson(['isRight' => true]);

        $I->sendGet('/api/enrollments/'.$enrollment->getId().'/game');
        $I->seeResponseContainsJson(['step' => 2, 'score' => 10, 'rank' => 1]);
        $elapsedTime = $I->grabDataFromResponseByJsonPath('$.elapsedTime')[0];
        $I->assertGreaterThanOrEqual(60, $elapsedTime);
    }

    public function gameEndsWhenTimeIsUp(ApplicationTester $I): void
    {
        $user = UserFactory::createOne();
        $treasureHunt = $this->createHunt(120);
        $riddle = RiddleTextFactory::createOne(['index' => 1, 'treasureHunt' => $treasureHunt]);
        $enrollment = EnrollmentFactory::createOne([
            'countdown' => 100,
            'lastResumedAt' => new \DateTimeImmutable('-1 minute'),
            'riddle' => $riddle,
            'status' => EnrollmentStatus::IN_PROGRESS,
            'treasureHunt' => $treasureHunt,
            'user' => $user,
        ]);

        $this->loginAs($I, $user);
        $I->sendGet('/api/enrollments/'.$enrollment->getId().'/game');

        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseContainsJson(['status' => 'finished', 'timedOut' => true, 'remainingTime' => 0, 'elapsedTime' => 120]);
        $I->dontSeeResponseJsonMatchesJsonPath('$.riddle');
    }

    public function teamScoreAddsMembersScores(ApplicationTester $I): void
    {
        $user = UserFactory::createOne();
        $mate = UserFactory::createOne();
        $treasureHunt = $this->createHunt();
        $riddle = RiddleTextFactory::createOne(['index' => 1, 'treasureHunt' => $treasureHunt]);
        $team = TeamFactory::createOne(['role' => 'participant', 'leader' => $user]);
        $enrollment = EnrollmentFactory::createOne([
            'countdown' => 0,
            'lastResumedAt' => new \DateTimeImmutable(),
            'riddle' => $riddle,
            'scoreTotal' => 5,
            'status' => EnrollmentStatus::IN_PROGRESS,
            'team' => $team,
            'treasureHunt' => $treasureHunt,
            'user' => $user,
        ]);
        EnrollmentFactory::createOne([
            'riddle' => null,
            'scoreTotal' => 7,
            'status' => EnrollmentStatus::FINISHED,
            'team' => $team,
            'treasureHunt' => $treasureHunt,
            'user' => $mate,
        ]);

        $this->loginAs($I, $user);
        $I->sendGet('/api/enrollments/'.$enrollment->getId().'/game');

        $I->seeResponseContainsJson(['score' => 12, 'teamName' => $team->getName()]);
    }

    public function huntIsFoundByCodeEvenWhenNotListed(ApplicationTester $I): void
    {
        $user = UserFactory::createOne();
        TeamFactory::createOne();
        TreasureHuntFactory::createOne(['invitationCode' => 'AB12CD', 'listed' => false, 'published' => true, 'title' => 'Chasse privée']);

        $this->loginAs($I, $user);
        $I->sendGet('/api/treasure_hunts/code/ab12cd');
        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseContainsJson(['title' => 'Chasse privée']);

        $I->sendGet('/api/treasure_hunts');
        $I->dontSeeResponseContains('Chasse privée');
    }

    public function unpublishedHuntIsNotFoundByCode(ApplicationTester $I): void
    {
        $user = UserFactory::createOne();
        TeamFactory::createOne();
        TreasureHuntFactory::createOne(['invitationCode' => 'ZZ99ZZ', 'published' => false]);

        $this->loginAs($I, $user);
        $I->sendGet('/api/treasure_hunts/code/ZZ99ZZ');

        $I->seeResponseCodeIs(404);
    }
}
