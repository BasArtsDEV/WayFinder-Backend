<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\Notification;
use App\Factory\LocationFactory;
use App\Factory\RiddleTextFactory;
use App\Factory\TeamFactory;
use App\Factory\TreasureHuntFactory;
use App\Factory\UserFactory;
use App\Tests\Support\ApplicationTester;
use Codeception\Attribute\DataProvider;
use Codeception\Attribute\Group;
use Codeception\Example;

use function Zenstruck\Foundry\Persistence\save;

final class AvailabilityCest
{
    /** @return array<array<string, string>> */
    public function _URLProvider(): array
    {
        return [
            ['url' => '/login'],
            ['url' => '/logout'],

            ['url' => '/register'],
            ['url' => '/verify/email'],
            ['url' => '/verify/email/resend'],

            ['url' => '/reset-password'],
            ['url' => '/reset-password/check-email'],
            ['url' => '/reset-password/reset/token'],

            ['url' => '/profil'],
            ['url' => '/user/1/avatar'],
            ['url' => '/profil/avatar/delete'],
            ['url' => '/profil/user/delete'],

            ['url' => '/settings'],

            ['url' => '/notification'],
            ['url' => '/team/1/request-join'],
            ['url' => '/notification/delete/1'],
            ['url' => '/notification/delete-all'],

            ['url' => '/team'],
            ['url' => '/team/new'],
            ['url' => '/team/1'],
            ['url' => '/team/1/banner'],
            ['url' => '/team/1/edit'],
            ['url' => '/team/1/invite'],

            ['url' => '/treasure_hunt'],
            ['url' => '/treasure_hunt/new'],
            ['url' => '/treasure_hunt/1'],
            ['url' => '/treasure_hunt/1/qrcodes'],
            ['url' => '/treasure_hunt/1/edit'],

            ['url' => '/treasure-hunt/1/new/text'],
            ['url' => '/treasure-hunt/1/new/qcm'],
            ['url' => '/treasure-hunt/1/new/gps'],
            ['url' => '/treasure-hunt/1/new/qrcode'],
            ['url' => '/riddle/1/edit'],
        ];
    }

    /**
     * @param Example<string, string> $example
     */
    #[DataProvider('_URLProvider')]
    #[Group('available')]
    public function pageIsAvailable(ApplicationTester $I, Example $example): void
    {
        LocationFactory::createOne();

        $user = UserFactory::createOne(['isVerified' => true]);
        $team = TeamFactory::createOne(['leader' => $user, 'users' => [$user]]);
        $treasureHunt = TreasureHuntFactory::createOne([
            'team' => $team,
            'published' => true,
            'listed' => true,
        ]);
        RiddleTextFactory::createOne(['treasureHunt' => $treasureHunt]);

        $notification = new Notification();
        $notification->setType(Notification::TYPE_TEAM_JOIN_REQUEST);
        $notification->setTitle('Demande à rejoindre l\'équipe');
        $notification->setDescription('Notification de test.');
        $notification->setTargetUser($user);
        $notification->setTargetTeam($team);
        $notification->addReceiveNotif($user);
        save($notification);

        $I->amOnPage($example['url']);
        $I->seeResponseCodeIsSuccessful();
    }
}
