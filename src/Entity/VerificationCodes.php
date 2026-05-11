<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: '`verification_codes`')]
class VerificationCodes
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, nullable: false)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, nullable: false)]
    private ?string $email = null;

    #[ORM\Column(type: Types::STRING, nullable: false)]
    private ?string $code = null;

    #[ORM\Column(type: Types::STRING, nullable: false, name: 'type', columnDefinition: 'VARCHAR(255) NOT NULL')]
    private ?string $type = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: false, name: 'expiration_time')]
    private ?\DateTimeInterface $expirationTime = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: false, name: 'created_at')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true, name: 'is_used')]
    private ?bool $isUsed = null;

    public function getId(): ?int { return $this->id; }
    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): static { $this->email = $email; return $this; }
    public function getCode(): ?string { return $this->code; }
    public function setCode(string $code): static { $this->code = $code; return $this; }
    public function getType(): ?string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }
    public function getExpirationTime(): ?\DateTimeInterface { return $this->expirationTime; }
    public function setExpirationTime(\DateTimeInterface $t): static { $this->expirationTime = $t; return $this; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(\DateTimeInterface $t): static { $this->createdAt = $t; return $this; }
    public function getIsUsed(): ?bool { return $this->isUsed; }
    public function setIsUsed(?bool $v): static { $this->isUsed = $v; return $this; }
}
