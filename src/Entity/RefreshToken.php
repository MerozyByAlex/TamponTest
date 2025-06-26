<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gesdinet\JWTRefreshTokenBundle\Entity\RefreshToken as BaseRefreshToken;
use App\Entity\User; // Importez votre entité User
use Symfony\Component\Validator\Constraints as Assert; // Pour la validation si nécessaire

#[ORM\Entity]
#[ORM\Table(name: 'refresh_tokens')]
class RefreshToken extends BaseRefreshToken
{
    /**
     * The user associated with this refresh token.
     * Using a direct relationship allows for cascade operations (e.g., deleting tokens when a user is deleted)
     * and easier querying if needed.
     */
    #[ORM\ManyToOne(targetEntity: User::class)] // Pas d'inversedBy nécessaire ici car User ne connaîtra pas forcément ses refresh tokens
    #[ORM\JoinColumn(name: "user_id", referencedColumnName: "id", nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull] // Ensure the user is always set if the relationship is mandatory
    protected ?User $user = null; // Note: 'protected' pour correspondre au style de la classe parente, ou 'private'

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static // Le type de retour 'static' est PHP 8.0+
    {
        $this->user = $user;
        return $this;
    }

    // Le champ $username (hérité) stockera toujours UserInterface::getUserIdentifier()
    // Le champ $refreshToken (hérité) a déjà un index unique défini dans la classe parente du bundle
}