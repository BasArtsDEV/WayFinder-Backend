<?php

namespace App\Tests\Application\Performance;

use App\Entity\Riddle;
use App\Entity\Team;
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

class DatabasePerformanceCest
{
    private function loginApiAs(ApplicationTester $I, User $user): void
    {
        /** @var JWTTokenManagerInterface $jwtManager */
        $jwtManager = $I->grabService('lexik_jwt_authentication.jwt_manager');
        $I->haveHttpHeader('Authorization', 'Bearer '.$jwtManager->create($user));
    }

    public function creatorSeesOnlyCreatorTeamTreasureHuntsOrderedByCreationDate(ApplicationTester $I): void
    {
        LocationFactory::createOne();
        $user = UserFactory::createOne();
        $creatorTeam = TeamFactory::createOne(['role' => Team::ROLE_CREATOR, 'leader' => $user, 'users' => [$user]]);
        $participantTeam = TeamFactory::createOne(['role' => Team::ROLE_PARTICIPANT, 'leader' => $user, 'users' => [$user]]);

        $olderHunt = TreasureHuntFactory::createOne([
            'title' => 'Older Hunt',
            'team' => $creatorTeam,
            'creationDate' => new \DateTimeImmutable('2026-01-01'),
        ]);
        $newerHunt = TreasureHuntFactory::createOne([
            'title' => 'Newer Hunt',
            'team' => $creatorTeam,
            'creationDate' => new \DateTimeImmutable('2026-06-01'),
        ]);
        TreasureHuntFactory::createOne([
            'title' => 'Participant Team Hunt',
            'team' => $participantTeam,
        ]);

        EnrollmentFactory::createOne([
            'treasureHunt' => $olderHunt,
            'user' => UserFactory::createOne(),
            'nbStars' => 5,
            'feedback' => 'Super !',
        ]);
        EnrollmentFactory::createOne([
            'treasureHunt' => $newerHunt,
            'user' => UserFactory::createOne(),
            'nbStars' => 4,
            'feedback' => 'Bien',
        ]);

        $I->amLoggedInAs($user);
        $I->amOnPage('/treasure_hunt');

        $I->seeResponseCodeIsSuccessful();
        $I->see('Newer Hunt');
        $I->see('Older Hunt');
        $I->dontSee('Participant Team Hunt');
    }

    public function treasureHuntShowRendersQcmChoicesWithoutError(ApplicationTester $I): void
    {
        LocationFactory::createOne();
        $user = UserFactory::createOne();
        $team = TeamFactory::createOne(['role' => Team::ROLE_CREATOR, 'leader' => $user, 'users' => [$user]]);
        $hunt = TreasureHuntFactory::createOne(['team' => $team]);

        $qcm1 = RiddleQCMFactory::createOne(['index' => 1, 'treasureHunt' => $hunt]);
        $qcm2 = RiddleQCMFactory::createOne(['index' => 2, 'treasureHunt' => $hunt]);
        ChoiceFactory::createOne(['riddleQCM' => $qcm1, 'choice' => 'Choix Alpha', 'isRight' => true]);
        ChoiceFactory::createOne(['riddleQCM' => $qcm2, 'choice' => 'Choix Beta', 'isRight' => false]);

        $I->amLoggedInAs($user);
        $I->amOnPage('/treasure_hunt/'.$hunt->getId());

        $I->seeResponseCodeIsSuccessful();
        $I->see('Choix Alpha');
        $I->see('Choix Beta');
    }

    public function reorderAllUpdatesRiddleIndexesInBatch(ApplicationTester $I): void
    {
        LocationFactory::createOne();
        $user = UserFactory::createOne();
        $team = TeamFactory::createOne(['role' => Team::ROLE_CREATOR, 'leader' => $user, 'users' => [$user]]);
        $hunt = TreasureHuntFactory::createOne(['team' => $team]);

        $riddle1 = RiddleTextFactory::createOne(['index' => 1, 'treasureHunt' => $hunt]);
        $riddle2 = RiddleTextFactory::createOne(['index' => 2, 'treasureHunt' => $hunt]);

        $I->amLoggedInAs($user);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPost('/riddle/reorder-all', json_encode([
            'orders' => [
                ['id' => $riddle1->getId(), 'index' => 2],
                ['id' => $riddle2->getId(), 'index' => 1],
            ],
        ], JSON_THROW_ON_ERROR));

        $I->seeResponseCodeIsSuccessful();
        $I->seeInRepository(Riddle::class, ['id' => $riddle1->getId(), 'index' => 2]);
        $I->seeInRepository(Riddle::class, ['id' => $riddle2->getId(), 'index' => 1]);
    }

    public function leaderboardPreservesCompetitionRankingForTiedScores(ApplicationTester $I): void
    {
        LocationFactory::createOne();
        TeamFactory::createOne();
        $hunt = TreasureHuntFactory::createOne(['duration' => 3600, 'published' => true]);

        $playerA = UserFactory::createOne();
        $playerB = UserFactory::createOne();
        $playerC = UserFactory::createOne();

        EnrollmentFactory::createOne([
            'countdown' => 600,
            'finishedAt' => new \DateTimeImmutable('2026-01-01 10:00:00'),
            'scoreTotal' => 100,
            'status' => EnrollmentStatus::FINISHED,
            'treasureHunt' => $hunt,
            'user' => $playerA,
        ]);
        EnrollmentFactory::createOne([
            'countdown' => 600,
            'finishedAt' => new \DateTimeImmutable('2026-01-01 10:05:00'),
            'scoreTotal' => 100,
            'status' => EnrollmentStatus::FINISHED,
            'treasureHunt' => $hunt,
            'user' => $playerB,
        ]);
        EnrollmentFactory::createOne([
            'countdown' => 1200,
            'finishedAt' => new \DateTimeImmutable('2026-01-01 10:01:00'),
            'scoreTotal' => 50,
            'status' => EnrollmentStatus::FINISHED,
            'treasureHunt' => $hunt,
            'user' => $playerC,
        ]);

        $this->loginApiAs($I, $playerA);
        $I->sendGet('/api/treasure_hunts/'.$hunt->getId().'/leaderboard');

        $I->seeResponseCodeIsSuccessful();
        $entries = $I->grabDataFromResponseByJsonPath('$.entries')[0];
        $I->assertCount(3, $entries);
        $I->assertSame(1, $entries[0]['rank']);
        $I->assertSame(1, $entries[1]['rank']);
        $I->assertSame(3, $entries[2]['rank']);
    }

    public function profileAndNotificationPagesRenderSuccessfully(ApplicationTester $I): void
    {
        $user = UserFactory::createOne();
        $member = UserFactory::createOne();
        $team = TeamFactory::createOne(['role' => Team::ROLE_CREATOR, 'leader' => $user, 'users' => [$user, $member]]);

        $I->amLoggedInAs($user);
        $I->amOnPage('/profil');
        $I->seeResponseCodeIsSuccessful();
        $I->see($team->getName());

        $I->amOnPage('/notification');
        $I->seeResponseCodeIsSuccessful();
    }
}
