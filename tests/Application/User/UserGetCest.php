<?php

namespace App\Tests\Application\User;

use App\Entity\User;
use App\Factory\UserFactory;
use App\Tests\Support\ApplicationTester;

class UserGetCest
{
    /**
     * @return array<string, string>
     */
    protected static function expectedProperties(): array
    {
        return [
            'id' => 'integer',
            'email' => 'string',
            'username' => 'string',
            'firstName' => 'string',
            'lastName' => 'string',
            'createdAt' => 'string',
            'timezone' => 'string',
            'isVerified' => 'boolean',
            'hasAvatar' => 'boolean',
            'hasBanner' => 'boolean',
        ];
    }

    public function anonymousUserGetSimpleUserElement(ApplicationTester $I): void
    {
        $data = [
            'email' => 'test0123@gmail.com',
            'firstName' => 'prenom',
            'lastName' => 'nom',
        ];

        UserFactory::createOne($data);
        $I->sendGet('/api/users/1');

        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseIsJson();
        $I->seeResponseIsAnEntity(User::class, '/api/users/1');
        $I->seeResponseIsAnItem(self::expectedProperties(), $data);
    }
}
