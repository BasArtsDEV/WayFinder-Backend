<?php

declare(strict_types=1);

namespace App\Tests\Application;

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

            ['url' => '/api/verify-email'],

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

            ['url' => '/api/teams/1/treasure_hunts/1/score'],

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

            ['url' => '/api/users/1/avatar'],
            ['url' => '/api/users/1/banner'],
        ];
    }
}
