<?php

namespace App\Tests\Application\Game;

use App\Entity\Enrollment;
use App\Entity\RiddleText;
use App\Entity\Team;
use App\Entity\TreasureHunt;
use App\Entity\User;
use App\Enum\EnrollmentStatus;
use App\Factory\EnrollmentFactory;
use App\Factory\LocationFactory;
use App\Factory\RiddleTextFactory;
use App\Factory\TeamFactory;
use App\Factory\TreasureHuntFactory;
use App\Factory\UserFactory;
use App\Tests\Support\ApplicationTester;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class LeaderboardCest
{
    private function loginAs(ApplicationTester $I, User $user): void
    {
        /** @var JWTTokenManagerInterface $jwtManager */
        $jwtManager = $I->grabService('lexik_jwt_authentication.jwt_manager');
        $I->haveHttpHeader('Authorization', 'Bearer '.$jwtManager->create($user));
    }

    private function createHunt(): TreasureHunt
    {
        LocationFactory::createOne();
        TeamFactory::createOne();

        return TreasureHuntFactory::createOne(['duration' => 3600, 'published' => true]);
    }

    private function createFinishedEnrollment(User $user, TreasureHunt $treasureHunt, int $score, int $countdown, ?RiddleText $riddle = null, ?Team $team = null): Enrollment
    {
        return EnrollmentFactory::createOne([
            'countdown' => $countdown,
            'finishedAt' => new \DateTimeImmutable(),
            'riddle' => $riddle,
            'scoreTotal' => $score,
            'status' => EnrollmentStatus::FINISHED,
            'team' => $team,
            'treasureHunt' => $treasureHunt,
            'user' => $user,
        ]);
    }

    public function leaderboardSortsFinishedCompetitorsByFinalScore(ApplicationTester $I): void
    {
        $treasureHunt = $this->createHunt();
        $riddle = RiddleTextFactory::createOne(['index' => 1, 'treasureHunt' => $treasureHunt]);
        $player = UserFactory::createOne();
        $slowPlayer = UserFactory::createOne();
        $member = UserFactory::createOne();
        $mate = UserFactory::createOne();
        $team = TeamFactory::createOne(['role' => 'participant', 'leader' => $member]);

        $this->createFinishedEnrollment($player, $treasureHunt, 10, 600);
        $this->createFinishedEnrollment($slowPlayer, $treasureHunt, 5, 3600, $riddle);
        $this->createFinishedEnrollment($member, $treasureHunt, 20, 1000, null, $team);
        $this->createFinishedEnrollment($mate, $treasureHunt, 0, 3600, $riddle, $team);
        EnrollmentFactory::createOne([
            'riddle' => $riddle,
            'scoreTotal' => 50,
            'status' => EnrollmentStatus::IN_PROGRESS,
            'treasureHunt' => $treasureHunt,
            'user' => UserFactory::createOne(),
        ]);

        $this->loginAs($I, $player);
        $I->sendGet('/api/treasure_hunts/'.$treasureHunt->getId().'/leaderboard');

        $I->seeResponseCodeIsSuccessful();
        $entries = $I->grabDataFromResponseByJsonPath('$.entries')[0];
        $I->assertCount(3, $entries);
        $I->assertSame(['rank' => 1, 'name' => $player->getUsername(), 'score' => 3010, 'isCurrentUser' => true], array_intersect_key($entries[0], array_flip(['rank', 'name', 'score', 'isCurrentUser'])));
        $I->assertSame(['rank' => 2, 'name' => $team->getName(), 'score' => 1310, 'timedOut' => false], array_intersect_key($entries[1], array_flip(['rank', 'name', 'score', 'timedOut'])));
        $I->assertSame(['rank' => 3, 'score' => 5, 'timedOut' => true, 'isCurrentUser' => false], array_intersect_key($entries[2], array_flip(['rank', 'score', 'timedOut', 'isCurrentUser'])));
    }

    public function finishedGameShowsLeaderboardRank(ApplicationTester $I): void
    {
        $treasureHunt = $this->createHunt();
        $riddle = RiddleTextFactory::createOne(['index' => 1, 'treasureHunt' => $treasureHunt]);
        $player = UserFactory::createOne();
        $this->createFinishedEnrollment(UserFactory::createOne(), $treasureHunt, 10, 600);
        $enrollment = $this->createFinishedEnrollment($player, $treasureHunt, 5, 3600, $riddle);

        $this->loginAs($I, $player);
        $I->sendGet('/api/enrollments/'.$enrollment->getId().'/game');

        $I->seeResponseContainsJson(['score' => 5, 'rank' => 2, 'competitorsCount' => 2, 'timedOut' => true]);
    }

    public function unpublishedHuntHasNoLeaderboard(ApplicationTester $I): void
    {
        TeamFactory::createOne();
        $treasureHunt = TreasureHuntFactory::createOne(['published' => false]);

        $I->sendGet('/api/treasure_hunts/'.$treasureHunt->getId().'/leaderboard');

        $I->seeResponseCodeIs(404);
    }
}
