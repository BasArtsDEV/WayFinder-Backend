<?php

namespace App\Tests\Application\TreasureHunt;

use App\Entity\Team;
use App\Entity\TreasureHunt;
use App\Entity\User;
use App\Factory\LocationFactory;
use App\Factory\RiddleTextFactory;
use App\Factory\TeamFactory;
use App\Factory\TreasureHuntFactory;
use App\Factory\UserFactory;
use App\Tests\Support\ApplicationTester;

class PublishCest
{
    private function createDraftHunt(?string $invitationCode): TreasureHunt
    {
        LocationFactory::createOne();
        TeamFactory::createOne();

        $treasureHunt = TreasureHuntFactory::createOne(['invitationCode' => $invitationCode, 'published' => false]);
        RiddleTextFactory::createOne(['index' => 1, 'treasureHunt' => $treasureHunt]);

        return $treasureHunt;
    }

    public function invitationCodeIsKeptWhenRepublishing(ApplicationTester $I): void
    {
        LocationFactory::createOne();
        $user = UserFactory::createOne();
        $team = TeamFactory::createOne([
            'leader' => $user,
            'users' => [$user],
        ]);
        $teamInstance = $I->grabEntityFromRepository(Team::class, ['id' => $team->getId()]);
        $treasureHunt = TreasureHuntFactory::createOne([
            'team' => $teamInstance,
            'published' => false,
            'invitationCode' => 'AB12CD',
        ]);
        RiddleTextFactory::createOne(['treasureHunt' => $treasureHunt]);
        $id = $treasureHunt->getId();

        $userInstance = $I->grabEntityFromRepository(User::class, ['email' => $user->getEmail()]);
        $I->amLoggedInAs($userInstance);

        $I->amOnPage('/treasure_hunt/'.$id);
        $I->click('Publier');
        $I->seeInRepository(TreasureHunt::class, ['id' => $id, 'published' => true, 'invitationCode' => 'AB12CD']);

        $I->click('Dépublier');
        $I->click('Publier');
        $I->seeInRepository(TreasureHunt::class, ['id' => $id, 'published' => true, 'invitationCode' => 'AB12CD']);
    }

    public function invitationCodeIsGeneratedOnFirstPublish(ApplicationTester $I): void
    {
        $treasureHunt = $this->createDraftHunt(null);
        $id = $treasureHunt->getId();

        $I->amLoggedInAs(UserFactory::createOne());
        $I->amOnPage('/treasure_hunt/'.$id);
        $I->click('Publier');

        /** @var TreasureHunt $published */
        $published = $I->grabEntityFromRepository(TreasureHunt::class, ['id' => $id]);
        $I->assertNotNull($published->getInvitationCode());
    }
}
