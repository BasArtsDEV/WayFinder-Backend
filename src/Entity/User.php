<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\MediaType;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\Model\Schema;
use App\Controller\GetAvatarController;
use App\Controller\GetBannerController;
use App\Repository\UserRepository;
use App\State\MeProvider;
use App\State\UserRegistrationProcessor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[ApiResource(
    operations: [
        new Get(
            normalizationContext: ['groups' => ['user:read']],
            security: 'true'
        ),
        new Get(
            uriTemplate: '/me',
            openapi: new Operation(
                summary: 'Retrieves the connected user',
                description: 'Retrieves the connected user',
            ),
            normalizationContext: ['groups' => ['user:me']],
            security: "is_granted('ROLE_USER')",
            provider: MeProvider::class,
        ),
        new Get(
            uriTemplate: '/users/{id}/avatar',
            formats: [
                'png' => 'image/png',
            ],
            controller: GetAvatarController::class,
            openapi: new Operation(
                responses: [
                    '200' => new Response(
                        description: 'The user avatar',
                        content: new \ArrayObject([
                            'image/png' => new MediaType(
                                schema: new Schema([
                                    'type' => 'string',
                                    'format' => 'binary',
                                ])
                            ),
                        ])
                    ),
                    '404' => new Response(
                        description: 'User does not exist'
                    ),
                ],
                summary: 'Retrieves a user avatar',
                description: 'Retrieves the PNG image corresponding to a user avatar',
            ),
        ),
        new Get(
            uriTemplate: '/users/{id}/banner',
            formats: [
                'png' => 'image/png',
            ],
            controller: GetBannerController::class,
            openapi: new Operation(
                responses: [
                    '200' => new Response(
                        description: 'The user banner',
                        content: new \ArrayObject([
                            'image/png' => new MediaType(
                                schema: new Schema([
                                    'type' => 'string',
                                    'format' => 'binary',
                                ])
                            ),
                        ])
                    ),
                    '404' => new Response(
                        description: 'User does not exist'
                    ),
                ],
                summary: 'Retrieves a user banner',
                description: 'Retrieves the PNG image corresponding to a user banner',
            ),
        ),
        new Patch(
            normalizationContext: ['groups' => ['user:read', 'user:me']],
            denormalizationContext: ['groups' => ['user:write']],
            security: "object == user and is_granted('ROLE_USER')"
        ),
        new Post(
            uriTemplate: '/register',
            normalizationContext: ['groups' => ['user:read']],
            denormalizationContext: ['groups' => ['user:create']],
            validationContext: ['groups' => ['Default', 'Registration']],
            processor: UserRegistrationProcessor::class,
        ),
    ]
)]
#[UniqueEntity('email')]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const int MAX_TEAMS = 5;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read', 'user:me'])]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Groups(['user:read', 'user:write', 'user:me', 'user:create'])]
    #[Assert\NotBlank]
    #[Assert\Email(
        message: 'L\'email "{{ value }}" n\'est pas une adresse email valide.'
    )]
    private string $email;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    #[Groups(['user:write', 'user:create'])]
    #[ApiProperty(readable: false, writable: true)]
    #[Assert\Regex(
        pattern: '/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/',
        message: 'Le mot de passe doit contenir au moins 8 caractères, une majuscule, un chiffre et un symbole',
        groups: ['Registration']
    )]
    private string $password;

    #[ORM\Column(length: 30)]
    #[Groups(['user:read', 'user:write', 'user:me', 'user:create'])]
    #[Assert\NotBlank]
    #[Assert\Regex(
        pattern: '/^[\w\d_-]+$/',
        message: 'Le pseudo ne peut contenir que des lettres, chiffres, _ ou -'
    )]
    #[ApiProperty(example: 'monusername')]
    private string $username;

    #[ORM\Column(length: 60)]
    #[Groups(['user:read', 'user:write', 'user:me', 'user:create'])]
    #[Assert\NotBlank]
    #[Assert\Regex(
        pattern: '/^[\w\d\s-]+$/',
        message: 'Le prénom ne peut pas contenir <, >, &, "'
    )]
    #[ApiProperty(example: 'Jean')]
    private string $firstName;

    #[ORM\Column(length: 60)]
    #[Groups(['user:read', 'user:write', 'user:me', 'user:create'])]
    #[Assert\NotBlank]
    #[Assert\Regex(
        pattern: '/^[\w\d\s-]+$/',
        message: 'Le nom ne peut pas contenir <, >, &, "'
    )]
    #[ApiProperty(example: 'Dupont')]
    private string $lastName;

    /**
     * @var string|resource|null
     */
    #[ORM\Column(type: Types::BLOB, nullable: true)]
    private $avatar;

    /**
     * @var string|null
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private $banner;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Groups(['user:read', 'user:me'])]
    #[ApiProperty(
        description: "Date d'inscription de l'utilisateur",
        writable: false
    )]
    private \DateTimeImmutable $createdAt;

    /**
     * @var Collection<int, Team>
     */
    #[ORM\OneToMany(targetEntity: Team::class, mappedBy: 'leader')]
    private Collection $ledTeams;

    /**
     * @var Collection<int, Team>
     */
    #[ORM\ManyToMany(targetEntity: Team::class, inversedBy: 'users')]
    #[Assert\Count(
        max: self::MAX_TEAMS,
        maxMessage: 'Un utilisateur ne peut appartenir qu\'à '.self::MAX_TEAMS.' équipes.'
    )]
    private Collection $teams;

    /**
     * @var Collection<int, Enrollment>
     */
    #[ORM\OneToMany(targetEntity: Enrollment::class, mappedBy: 'user')]
    private Collection $enrollments;

    #[ORM\Column]
    private bool $isVerified = false;

    public function __construct()
    {
        $this->teams = new ArrayCollection();
        $this->ledTeams = new ArrayCollection();
        $this->enrollments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string|resource|null
     */
    public function getAvatar()
    {
        return $this->avatar;
    }

    /**
     * @param string|resource|null $avatar
     */
    public function setAvatar($avatar): static
    {
        $this->avatar = $avatar;

        return $this;
    }

    public function getBanner(): ?string
    {
        return $this->banner;
    }

    public function setBanner(?string $banner): static
    {
        $this->banner = $banner;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    #[ORM\PrePersist]
    public function setCreatedDateValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    /**
     * @return Collection<int, Team>
     */
    public function getTeams(): Collection
    {
        return $this->teams;
    }

    public function addTeam(Team $team): static
    {
        if (!$this->teams->contains($team)) {
            if (count($this->teams) >= self::MAX_TEAMS) {
                throw new \LogicException('Un utilisateur ne peut appartenir qu\'à '.self::MAX_TEAMS.' équipes.');
            }

            $this->teams->add($team);
            $team->addUser($this);
        }

        return $this;
    }

    public function removeTeam(Team $team): static
    {
        if ($this->teams->removeElement($team)) {
            $team->removeUser($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Team>
     */
    public function getLedTeams(): Collection
    {
        return $this->ledTeams;
    }

    public function addLedTeam(Team $ledTeam): static
    {
        if (!$this->ledTeams->contains($ledTeam)) {
            $this->ledTeams->add($ledTeam);
            $ledTeam->setLeader($this);
        }

        return $this;
    }

    public function removeLedTeam(Team $ledTeam): static
    {
        if ($this->ledTeams->removeElement($ledTeam)) {
            if ($ledTeam->getLeader() === $this) {
                $ledTeam->setLeader(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Enrollment>
     */
    public function getEnrollments(): Collection
    {
        return $this->enrollments;
    }

    public function addEnrollment(Enrollment $enrollment): static
    {
        if (!$this->enrollments->contains($enrollment)) {
            $this->enrollments->add($enrollment);
            $enrollment->setUser($this);
        }

        return $this;
    }

    public function removeEnrollment(Enrollment $enrollment): static
    {
        $this->enrollments->removeElement($enrollment);

        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }
}
