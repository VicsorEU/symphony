<?php
namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[ORM\UniqueConstraint(name: 'uniq_login_pass', columns: ['login', 'pass'])]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type:'string', length:8)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 8)]
    private ?string $login = null;

    #[ORM\Column(type:'string', length:8)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 8)]
    private ?string $phone = null;

    #[ORM\Column(type:'string', length:8)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 8)]
    private ?string $pass = null;

    public function getId(): ?int { return $this->id; }
    public function getLogin(): ?string { return $this->login; }
    public function setLogin(string $login): self { $this->login = $login; return $this; }

    public function getPhone(): ?string { return $this->phone; }
    public function setPhone(string $phone): self { $this->phone = $phone; return $this; }

    public function getPass(): ?string { return $this->pass; }
    public function setPass(string $pass): self { $this->pass = $pass; return $this; }
}
