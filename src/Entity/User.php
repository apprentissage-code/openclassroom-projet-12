<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
  #[ORM\Id]
  #[ORM\GeneratedValue]
  #[ORM\Column]
  private ?int $id = null;

  #[ORM\Column(length: 255)]
  private ?string $login = null;

  #[ORM\Column(length: 255)]
  private ?string $password = null;

  #[ORM\Column(length: 255)]
  private ?string $postcode = null;

  #[ORM\Column]
  private array $roles = [];

  public function getId(): ?int
  {
    return $this->id;
  }

  public function getUserIdentifier(): string
  {
    return (string) $this->login;
  }

  public function getLogin(): ?string
  {
    return $this->login;
  }

  public function setLogin(string $login): static
  {
    $this->login = $login;

    return $this;
  }

  public function getPassword(): ?string
  {
    return $this->password;
  }

  public function setPassword(string $password): static
  {
    $this->password = $password;

    return $this;
  }

  public function getPostcode(): ?int
  {
    return $this->postcode;
  }

  public function setPostcode(?int $postcode): static
  {
    $this->postcode = $postcode;

    return $this;
  }

  public function getRoles(): array
  {
    $roles = $this->roles;

    // garantit au moins un rôle
    $roles[] = 'ROLE_USER';

    return array_unique($roles);
  }

  public function setRoles(array $roles): static
  {
    $this->roles = $roles;

    return $this;
  }

  /**
   * @see UserInterface
   */
  public function eraseCredentials(): void
  {
    // si tu stockes des données sensibles temporaires sur l'utilisateur,
    // nettoie-les ici
  }
}
