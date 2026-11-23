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
        $I->click('.publish_btn');
        $I->seeInRepository(TreasureHunt::class, ['id' => $id, 'published' => true, 'invitationCode' => 'AB12CD']);

        $I->click('.unpublish_btn');
        $I->click('.publish_btn');
        $I->seeInRepository(TreasureHunt::class, ['id' => $id, 'published' => true, 'invitationCode' => 'AB12CD']);
    }

    public function invitationCodeIsGeneratedOnFirstPublish(ApplicationTester $I): void
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
        $I->click('.publish_btn');

        /** @var TreasureHunt $published */
        $published = $I->grabEntityFromRepository(TreasureHunt::class, ['id' => $id]);
        $I->assertNotNull($published->getInvitationCode());
    }
}
