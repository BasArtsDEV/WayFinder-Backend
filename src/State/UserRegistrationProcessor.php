<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

/**
 * @implements ProcessorInterface<User, User|void>
 */
readonly class UserRegistrationProcessor implements ProcessorInterface
{
    public function __construct(
        /** @var ProcessorInterface<User, User|void> */
        private ProcessorInterface $persistProcessor,
        private UserPasswordHasherInterface $passwordHasher,
        private VerifyEmailHelperInterface $verifyEmailHelper,
        private MailerInterface $mailer,
    ) {
    }

    /**
     * @param User $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): User
    {
        $data->setPassword(
            $this->passwordHasher->hashPassword($data, $data->getPassword())
        );

        $data->setRoles(['ROLE_USER']);

        /** @var User $result */
        $result = $this->persistProcessor->process($data, $operation, $uriVariables, $context);

        // make the signaature for the api
        $signatureComponents = $this->verifyEmailHelper->generateSignature(
            'api_verify_email',
            (string) $result->getId(),
            $result->getEmail(),
            ['id' => (string) $result->getId()]
        );

        // extract the parameters (?expires=...&signature=...)
        $queryString = parse_url($signatureComponents->getSignedUrl(), PHP_URL_QUERY);

        // Link to the frontend React
        $frontendUrl = 'http://localhost:5173/verify-email?'.$queryString;

        // Sending the email
        $email = new TemplatedEmail()
            ->from(new Address('noreply@wayfinder.com', 'Support WayFinder'))
            ->to($result->getEmail())
            ->subject('Confirmez votre inscription')
            ->htmlTemplate('registration/confirmation_email.html.twig')
            ->context([
                'signedUrl' => $frontendUrl,
                'expiresAtMessageKey' => $signatureComponents->getExpirationMessageKey(),
                'expiresAtMessageData' => $signatureComponents->getExpirationMessageData(),
            ]);

        $this->mailer->send($email);

        return $result;
    }
}
