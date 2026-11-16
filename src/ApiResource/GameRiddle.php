<?php

namespace App\ApiResource;

/**
 * Current riddle of a game, as seen by a player (without the answers).
 */
final class GameRiddle
{
    public const string TYPE_TEXT = 'text';
    public const string TYPE_QCM = 'qcm';
    public const string TYPE_GPS = 'gps';
    public const string TYPE_QRCODE = 'qrcode';

    public string $iri;

    public string $type;

    public string $title;

    public string $description;

    public ?string $image = null;

    public ?string $imgDescription = null;

    public ?string $question = null;

    public int $secondsPenalty = 0;

    public int $answersCount = 0;

    /**
     * @var list<array{id: int, label: string}>
     */
    public array $choices = [];
}
