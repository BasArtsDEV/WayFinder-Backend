<?php

namespace App\Tests\Application\TreasureHunt;

use App\Entity\TreasureHunt;
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
        $treasureHunt = $this->createDraftHunt('AB12CD');
        $id = $treasureHunt->getId();

        $I->amLoggedInAs(UserFactory::createOne());
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
