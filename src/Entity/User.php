<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Traits\TimestampableEntity;
// ----- 1. VOS USE STATEMENTS (INCHANGÉS) -----
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfiguration;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfigurationInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`app_users`')]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email.')]
#[UniqueEntity(fields: ['username'], message: 'This username is already taken.', ignoreNull: true)]
// ----- 2. VOTRE DÉCLARATION DE CLASSE (INCHANGÉE) -----
class User implements UserInterface, PasswordAuthenticatedUserInterface, TwoFactorInterface
{
    use TimestampableEntity;

    // ...
    // TOUTES VOS PROPRIÉTÉS ET MÉTHODES EXISTANTES RESTENT INCHANGÉES
    // ...
    // J'ai seulement modifié les 3 méthodes requises par l'interface 2FA
    // qui se trouvent à la fin du fichier.
    // ...

    // [SCROLL TO THE BOTTOM OF THE FILE]

    // ==================================================================
    // CORRECTION DES MÉTHODES REQUISES PAR TwoFactorInterface
    // ==================================================================

    /**
     * Doit retourner true si l'authentification 2FA est activée ET qu'une clé secrète existe.
     */
    public function isTotpAuthenticationEnabled(): bool
    {
        // AMÉLIORATION : On s'assure qu'une clé secrète est bien présente.
        return $this->isTwoFactorAuthenticationEnabled && null !== $this->twoFactorAuthenticationSecret;
    }

    /**
     * Doit retourner l'objet de configuration TOTP.
     * C'ÉTAIT ICI LE PROBLÈME.
     */
    public function getTotpAuthenticationConfiguration(): ?TotpConfigurationInterface
    {
        // CORRECTION : Au lieu de retourner `null`, on retourne un nouvel objet de configuration
        // en lui passant la clé secrète de l'utilisateur.
        return new TotpConfiguration($this->twoFactorAuthenticationSecret, TotpConfiguration::ALGORITHM_SHA1, 30, 6);
    }

    /**
     * Doit retourner le nom d'utilisateur à afficher dans l'application d'authentification.
     */
    public function getTotpAuthenticationUsername(): string
    {
        // C'est parfait, on garde votre implémentation.
        return $this->getUserIdentifier();
    }

    /**
     * Cette méthode n'est pas requise par l'interface, mais elle est appelée par votre contrôleur.
     * Pour des raisons de clarté, il est préférable de la supprimer et d'utiliser directement
     * setTwoFactorAuthenticationSecret() dans le contrôleur. Mais pour l'instant, nous la laissons
     * pour ne pas casser votre code existant.
     */
    public function setTotpAuthenticationSecret(?string $secret): void
    {
        $this->setTwoFactorAuthenticationSecret($secret);
    }


    // --- TOUT LE RESTE DE VOTRE FICHIER EST CI-DESSOUS, SANS AUCUNE MODIFICATION ---

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    #[Assert\NotBlank(message: 'Please enter an email address.')]
    #[Assert\Email(message: 'The email "{{ value }}" is not a valid email.')]
    private ?string $email = null;

    #[ORM\Column(type: 'string', length: 255, unique: true, nullable: true)]
    #[Assert\Length(min: 3, max: 255, minMessage: 'Username must be at least 3 characters long.', maxMessage: 'Username cannot be longer than 255 characters.')]
    #[Assert\Regex(
        pattern: '/^[a-zA-Z0-9_.-]+$/',
        message: 'Username can only contain letters, numbers, and the characters: _ . -'
    )]
    private ?string $username = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column(type: 'json')]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column(type: 'string')]
    private ?string $password = null;

    private ?string $plainPassword = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Length(min: 2, max: 255, groups: ['EmployeeProfile', 'Default', 'CustomerProfile'])]
    #[Assert\NotBlank(message: 'First name is required for this profile.', groups: ['EmployeeProfile', 'CustomerProfile'])]
    private ?string $firstName = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Length(min: 2, max: 255, groups: ['EmployeeProfile', 'Default', 'CustomerProfile'])]
    #[Assert\NotBlank(message: 'Last name is required for this profile.', groups: ['EmployeeProfile', 'CustomerProfile'])]
    private ?string $lastName = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isVerified = false;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isSystemAccount = false;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $agreedToTermsAt = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $passwordResetToken = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $passwordResetTokenExpiresAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $passwordRequestedAt = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $twoFactorAuthenticationSecret = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isTwoFactorAuthenticationEnabled = false;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $failedLoginAttempts = 0;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastFailedLoginAttemptAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $accountLockedUntil = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastLoginAt = null;

    #[ORM\Column(type: 'string', length: 255, unique: true, nullable: true)]
    private ?string $googleId = null;

    #[ORM\Column(type: 'string', length: 255, unique: true, nullable: true)]
    private ?string $appleId = null;

    #[ORM\Column(type: 'string', length: 255, unique: true, nullable: true)]
    private ?string $microsoftId = null;

    #[ORM\OneToOne(mappedBy: 'userAccount', targetEntity: Customer::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private ?Customer $customerProfile = null;

    public function __construct()
    {
        $this->isActive = true;
        $this->isVerified = false;
        $this->isTwoFactorAuthenticationEnabled = false;
        $this->isSystemAccount = false;
        $this->failedLoginAttempts = 0;
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): static
    {
        $this->username = $username;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        if (empty($roles) && !$this->isSystemAccount()) {
             $roles[] = 'ROLE_CUSTOMER';
        }
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function addRole(string $role): static
    {
        $role = strtoupper($role);
        if (!in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
        }
        $this->roles = array_unique($this->roles);
        return $this;
    }

    public function removeRole(string $role): static
    {
        $role = strtoupper($role);
        $this->roles = array_values(array_filter($this->roles, static fn(string $r): bool => $r !== $role));
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): static
    {
        $this->plainPassword = $plainPassword;
        return $this;
    }

    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): static
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): static
    {
        $this->lastName = $lastName;
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

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function isSystemAccount(): bool
    {
        return $this->isSystemAccount;
    }

    public function setIsSystemAccount(bool $isSystemAccount): static
    {
        $this->isSystemAccount = $isSystemAccount;
        return $this;
    }

    public function getAgreedToTermsAt(): ?\DateTimeImmutable
    {
        return $this->agreedToTermsAt;
    }

    public function setAgreedToTermsAt(?\DateTimeImmutable $agreedToTermsAt): static
    {
        $this->agreedToTermsAt = $agreedToTermsAt;
        return $this;
    }

    public function getPasswordResetToken(): ?string
    {
        return $this->passwordResetToken;
    }

    public function setPasswordResetToken(?string $passwordResetToken): static
    {
        $this->passwordResetToken = $passwordResetToken;
        return $this;
    }

    public function getPasswordResetTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->passwordResetTokenExpiresAt;
    }

    public function setPasswordResetTokenExpiresAt(?\DateTimeImmutable $passwordResetTokenExpiresAt): static
    {
        $this->passwordResetTokenExpiresAt = $passwordResetTokenExpiresAt;
        return $this;
    }

    public function getPasswordRequestedAt(): ?\DateTimeImmutable
    {
        return $this->passwordRequestedAt;
    }

    public function setPasswordRequestedAt(?\DateTimeImmutable $passwordRequestedAt): static
    {
        $this->passwordRequestedAt = $passwordRequestedAt;
        return $this;
    }

    public function getTwoFactorAuthenticationSecret(): ?string
    {
        return $this->twoFactorAuthenticationSecret;
    }

    public function setTwoFactorAuthenticationSecret(?string $twoFactorAuthenticationSecret): static
    {
        $this->twoFactorAuthenticationSecret = $twoFactorAuthenticationSecret;
        return $this;
    }

    public function isTwoFactorAuthenticationEnabled(): bool
    {
        return $this->isTwoFactorAuthenticationEnabled;
    }

    public function setIsTwoFactorAuthenticationEnabled(bool $isTwoFactorAuthenticationEnabled): static
    {
        $this->isTwoFactorAuthenticationEnabled = $isTwoFactorAuthenticationEnabled;
        return $this;
    }

    public function getFailedLoginAttempts(): int
    {
        return $this->failedLoginAttempts;
    }

    public function setFailedLoginAttempts(int $failedLoginAttempts): static
    {
        $this->failedLoginAttempts = $failedLoginAttempts;
        return $this;
    }

    public function incrementFailedLoginAttempts(): static
    {
        $this->failedLoginAttempts++;
        return $this;
    }

    public function getLastFailedLoginAttemptAt(): ?\DateTimeImmutable
    {
        return $this->lastFailedLoginAttemptAt;
    }

    public function setLastFailedLoginAttemptAt(?\DateTimeImmutable $lastFailedLoginAttemptAt): static
    {
        $this->lastFailedLoginAttemptAt = $lastFailedLoginAttemptAt;
        return $this;
    }

    public function getAccountLockedUntil(): ?\DateTimeImmutable
    {
        return $this->accountLockedUntil;
    }

    public function setAccountLockedUntil(?\DateTimeImmutable $accountLockedUntil): static
    {
        $this->accountLockedUntil = $accountLockedUntil;
        return $this;
    }

    public function getLastLoginAt(): ?\DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?\DateTimeImmutable $lastLoginAt): static
    {
        $this->lastLoginAt = $lastLoginAt;
        return $this;
    }

    public function getGoogleId(): ?string
    {
        return $this->googleId;
    }

    public function setGoogleId(?string $googleId): static
    {
        $this->googleId = $googleId;
        return $this;
    }

    public function getAppleId(): ?string
    {
        return $this->appleId;
    }

    public function setAppleId(?string $appleId): static
    {
        $this->appleId = $appleId;
        return $this;
    }

    public function getMicrosoftId(): ?string
    {
        return $this->microsoftId;
    }

    public function setMicrosoftId(?string $microsoftId): static
    {
        $this->microsoftId = $microsoftId;
        return $this;
    }

    public function getCustomerProfile(): ?Customer
    {
        return $this->customerProfile;
    }

    public function setCustomerProfile(?Customer $customerProfile): static
    {
        if ($this->customerProfile !== $customerProfile) {
            $this->customerProfile = $customerProfile;
            if ($customerProfile && $customerProfile->getUserAccount() !== $this) {
                $customerProfile->setUserAccount($this);
            }
        }
        return $this;
    }

    public function getDisplayName(): string
    {
        if ($this->username !== null && trim($this->username) !== '') {
            return $this->username;
        }
        if ($this->firstName !== null && trim($this->firstName) !== '') {
            return $this->firstName;
        }
        $emailParts = explode('@', $this->email ?? '');
        return $emailParts[0] ?: 'User';
    }
}