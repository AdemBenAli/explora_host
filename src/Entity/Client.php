<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\ClientRepository::class)]
#[ORM\Table(name: '`client`')]
class Client
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER, nullable: false)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $adresse = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $badge = null;

    #[ORM\Column(type: Types::STRING, nullable: true, name: 'paysResidence')]
    private ?string $paysResidence = null;

    #[ORM\Column(type: Types::INTEGER, nullable: false, name: 'scoreFidelite')]
    private ?int $scoreFidelite = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $ville = null;

    public function getId(): ?int { return $this->id; }
    public function setId(int $id): static { $this->id = $id; return $this; }
    public function getAdresse(): ?string { return $this->adresse; }
    public function setAdresse(?string $v): static { $this->adresse = $v; return $this; }
    public function getBadge(): ?string { return $this->badge; }
    public function setBadge(?string $v): static { $this->badge = $v; return $this; }
    public function getPaysResidence(): ?string { return $this->paysResidence; }
    public function setPaysResidence(?string $v): static { $this->paysResidence = $v; return $this; }
    public function getScoreFidelite(): ?int { return $this->scoreFidelite; }
    public function setScoreFidelite(int $v): static { $this->scoreFidelite = $v; return $this; }
    public function getVille(): ?string { return $this->ville; }
    public function setVille(?string $v): static { $this->ville = $v; return $this; }
}
