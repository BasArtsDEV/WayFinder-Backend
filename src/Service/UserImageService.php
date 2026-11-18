<?php

declare(strict_types=1);

namespace App\Service;

use ApiPlatform\Validator\Exception\ValidationException;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

readonly class UserImageService
{
    public const string MAX_SIZE = '2M';

    public const array MIME_TYPES = [
        'image/png',
        'image/jpeg',
        'image/webp',
        'image/gif',
    ];

    public function __construct(
        private EntityManagerInterface $em,
        private ValidatorInterface $validator,
    ) {
    }

    public function setAvatar(User $user, ?UploadedFile $file): void
    {
        $this->assertValidImage($file);

        $user->setAvatar(file_get_contents($file->getPathname()));
        $this->em->flush();
    }

    public function removeAvatar(User $user): void
    {
        $user->setAvatar(null);
        $this->em->flush();
    }

    public function setBanner(User $user, ?UploadedFile $file): void
    {
        $this->assertValidImage($file);

        $user->setBanner(sprintf(
            'data:%s;base64,%s',
            $file->getMimeType(),
            base64_encode(file_get_contents($file->getPathname()))
        ));
        $this->em->flush();
    }

    public function removeBanner(User $user): void
    {
        $user->setBanner(null);
        $this->em->flush();
    }

    /**
     * @phpstan-assert UploadedFile $file
     */
    private function assertValidImage(?UploadedFile $file): void
    {
        if (null === $file) {
            throw new BadRequestHttpException('Aucun fichier n\'a été envoyé.');
        }

        $violations = $this->validator->validate($file, new Assert\Image(
            maxSize: self::MAX_SIZE,
            mimeTypes: self::MIME_TYPES,
            maxSizeMessage: 'L\'image est trop volumineuse ({{ size }} {{ suffix }}). La taille maximale autorisée est {{ limit }} {{ suffix }}.',
            mimeTypesMessage: 'Le format de l\'image n\'est pas valide. Formats acceptés : PNG, JPEG, WebP, GIF.',
        ));

        if (count($violations) > 0) {
            throw new ValidationException($violations);
        }
    }
}
