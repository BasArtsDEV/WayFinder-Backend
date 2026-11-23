<?php

namespace App\Tests\Application\TreasureHunt;

use App\Entity\Enrollment;
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

class LeaderboardPageCest
{
    private User $creator;

    private function createHunt(): TreasureHunt
    {
        LocationFactory::createOne();
        $this->creator = UserFactory::createOne(['username' => 'creator']);
        $team = TeamFactory::createOne(['leader' => $this->creator]);

        $treasureHunt = TreasureHuntFactory::createOne(['duration' => 3600, 'published' => true, 'team' => $team]);
        RiddleTextFactory::createOne(['index' => 1, 'treasureHunt' => $treasureHunt]);

        return $treasureHunt;
    }

    private function createFinishedEnrollment(TreasureHunt $treasureHunt, string $username, int $score): Enrollment
    {
        return EnrollmentFactory::createOne([
            'countdown' => 600,
            'finishedAt' => new \DateTimeImmutable(),
            'scoreTotal' => $score,
            'status' => EnrollmentStatus::FINISHED,
            'team' => null,
            'treasureHunt' => $treasureHunt,
            'user' => UserFactory::createOne(['username' => $username]),
        ]);
    }

    public function creatorSeesTheLeaderboard(ApplicationTester $I): void
    {
        $treasureHunt = $this->createHunt();
        $this->createFinishedEnrollment($treasureHunt, 'alice', 10);
        $this->createFinishedEnrollment($treasureHunt, 'bob', 20);

        $I->amLoggedInAs($this->creator);
        $I->amOnPage('/treasure_hunt/'.$treasureHunt->getId());
        $I->click('Classement');

        $I->seeCurrentUrlEquals('/treasure_hunt/'.$treasureHunt->getId().'/leaderboard');
        $I->see('3020', '.leaderboard-row:first-child');
        $I->see('bob', '.leaderboard-row:first-child');
        $I->see('alice', '.leaderboard-row:last-child');
    }

    public function otherUsersCannotSeeTheLeaderboard(ApplicationTester $I): void
    {
        $treasureHunt = $this->createHunt();

        $I->amLoggedInAs(UserFactory::createOne());
        $I->amOnPage('/treasure_hunt/'.$treasureHunt->getId().'/leaderboard');

        $I->seeResponseCodeIs(403);
    }

    public function creatorCanRemoveOneEntry(ApplicationTester $I): void
    {
        $treasureHunt = $this->createHunt();
        $removed = $this->createFinishedEnrollment($treasureHunt, 'alice', 10);
        $kept = $this->createFinishedEnrollment($treasureHunt, 'bob', 20);

        $I->amLoggedInAs($this->creator);
        $I->amOnPage('/treasure_hunt/'.$treasureHunt->getId().'/leaderboard');
        $I->click('button[name="key"][value="user-'.$removed->getUser()?->getId().'"]');

        $I->see("L'entrée a été retirée du classement.");
        $I->seeInRepository(Enrollment::class, ['id' => $removed->getId(), 'status' => EnrollmentStatus::NOT_STARTED, 'scoreTotal' => 0]);
        $I->seeInRepository(Enrollment::class, ['id' => $kept->getId(), 'status' => EnrollmentStatus::FINISHED, 'scoreTotal' => 20]);
    }

    public function creatorCanRemoveSelectedEntries(ApplicationTester $I): void
    {
        $treasureHunt = $this->createHunt();
        $first = $this->createFinishedEnrollment($treasureHunt, 'alice', 10);
        $second = $this->createFinishedEnrollment($treasureHunt, 'bob', 20);
        $kept = $this->createFinishedEnrollment($treasureHunt, 'carol', 30);

        $I->amLoggedInAs($this->creator);
        $I->amOnPage('/treasure_hunt/'.$treasureHunt->getId().'/leaderboard');
        $I->checkOption('input[value="user-'.$first->getUser()?->getId().'"]');
        $I->checkOption('input[value="user-'.$second->getUser()?->getId().'"]');
        $I->click('button[form="leaderboard-remove-form"]');

        $I->see('2 entrées ont été retirées du classement.');
        $I->seeInRepository(Enrollment::class, ['id' => $first->getId(), 'status' => EnrollmentStatus::NOT_STARTED]);
        $I->seeInRepository(Enrollment::class, ['id' => $second->getId(), 'status' => EnrollmentStatus::NOT_STARTED]);
        $I->seeInRepository(Enrollment::class, ['id' => $kept->getId(), 'status' => EnrollmentStatus::FINISHED]);
    }

    public function creatorCanResetAllResults(ApplicationTester $I): void
    {
        $treasureHunt = $this->createHunt();
        $enrollment = $this->createFinishedEnrollment($treasureHunt, 'alice', 10);

        $I->amLoggedInAs($this->creator);
        $I->amOnPage('/treasure_hunt/'.$treasureHunt->getId().'/leaderboard');
        $I->click('Tout réinitialiser');

        $I->seeCurrentUrlEquals('/treasure_hunt/'.$treasureHunt->getId().'/leaderboard');
        $I->see("Personne n'a encore terminé cette chasse au trésor.");
        $I->seeInRepository(Enrollment::class, ['id' => $enrollment->getId(), 'status' => EnrollmentStatus::NOT_STARTED, 'scoreTotal' => 0]);
    }

    public function creatorCanExportTheLeaderboard(ApplicationTester $I): void
    {
        $treasureHunt = $this->createHunt();
        $this->createFinishedEnrollment($treasureHunt, 'alice', 10);

        $I->amLoggedInAs($this->creator);
        $I->amOnPage('/treasure_hunt/'.$treasureHunt->getId().'/leaderboard/export');

        $I->seeResponseCodeIsSuccessful();
        $I->seeHttpHeader('Content-Type', 'text/csv; charset=UTF-8');
        $I->see('1;alice;Solo;1;3010;10;3000;600;Non;');
    }
}
