<?php

namespace App\Tests\Controller\User;

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
            'firstname' => 'string',
            'lastname' => 'string',
            'createdAt' => 'string',
            'dateNais' => 'string',
        ];
    }
}
