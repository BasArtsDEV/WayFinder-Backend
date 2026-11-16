<?php

namespace App\Service;

use App\Entity\Riddle;
use App\Entity\RiddleGPS;
use App\Entity\RiddleQCM;
use App\Entity\RiddleQRCode;
use App\Entity\RiddleText;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

readonly class AnswerValidatorService
{
    public function __construct(private float $gpsToleranceMeters = 50.0)
    {
    }

    public function isCorrect(Riddle $riddle, string $value): bool
    {
        return match (true) {
            $riddle instanceof RiddleText => $this->normalizeText($value) === $this->normalizeText($riddle->getExpectedAnswer()),
            $riddle instanceof RiddleQRCode => trim($value) === $riddle->getUrl(),
            $riddle instanceof RiddleQCM => $this->validateChoices($riddle, $value),
            $riddle instanceof RiddleGPS => $this->validateCoordinates($riddle, $value),
            default => throw new UnprocessableEntityHttpException('Type d\'énigme non pris en charge.'),
        };
    }

    private function normalizeText(string $value): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', $value)));
    }

    private function validateChoices(RiddleQCM $riddle, string $value): bool
    {
        $selected = json_decode($value, true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new UnprocessableEntityHttpException('Le format JSON de la réponse est invalide.');
        }

        if (is_int($selected)) {
            $selected = [$selected];
        }

        if (!is_array($selected) || [] === $selected) {
            throw new UnprocessableEntityHttpException('La réponse doit contenir au moins un choix.');
        }

        $available = [];
        $expected = [];
        foreach ($riddle->getChoices() as $choice) {
            $available[] = $choice->getId();
            if ($choice->isRight()) {
                $expected[] = $choice->getId();
            }
        }

        foreach ($selected as $id) {
            if (!is_int($id) || !in_array($id, $available, true)) {
                throw new UnprocessableEntityHttpException('Un choix sélectionné est invalide.');
            }
        }

        $selected = array_values(array_unique($selected));
        sort($selected);
        sort($expected);

        return $selected === $expected;
    }

    private function validateCoordinates(RiddleGPS $riddle, string $value): bool
    {
        $coordinates = json_decode($value, true);
        if (JSON_ERROR_NONE !== json_last_error() || !is_array($coordinates)) {
            throw new UnprocessableEntityHttpException('Les coordonnées GPS sont obligatoires.');
        }

        $latitude = $coordinates['latitude'] ?? null;
        $longitude = $coordinates['longitude'] ?? null;

        if (!is_numeric($latitude) || !is_numeric($longitude) || abs((float) $latitude) > 90 || abs((float) $longitude) > 180) {
            throw new UnprocessableEntityHttpException('Les coordonnées GPS sont invalides.');
        }

        $earthRadius = 6371000;
        $latDelta = deg2rad((float) $latitude - $riddle->getLatitude());
        $lonDelta = deg2rad((float) $longitude - $riddle->getLongitude());
        $a = sin($latDelta / 2) * sin($latDelta / 2)
            + cos(deg2rad($riddle->getLatitude())) * cos(deg2rad((float) $latitude)) * sin($lonDelta / 2) * sin($lonDelta / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return ($earthRadius * $c) <= $this->gpsToleranceMeters;
    }
}
