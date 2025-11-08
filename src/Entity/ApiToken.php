<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'api_tokens')]
#[ORM\UniqueConstraint(name: 'uniq_token', columns: ['token'])]
class ApiToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type:'integer')]
    private ?int $id = null;

    #[ORM\Column(type:'string', length:64)]
    private string $token;

    #[ORM\Column(type:'string', length:16)]
    private string $role; // 'root' | 'user'

    #[ORM\Column(type:'integer', nullable:true)]
    private ?int $userId = null;

    public function getId(): ?int { return $this->id; }
    public function getToken(): string { return $this->token; }
    public function setToken(string $t): self { $this->token = $t; return $this; }

    public function getRole(): string { return $this->role; }
    public function setRole(string $r): self { $this->role = $r; return $this; }

    public function getUserId(): ?int { return $this->userId; }
    public function setUserId(?int $id): self { $this->userId = $id; return $this; }
}
