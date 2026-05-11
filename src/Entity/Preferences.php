<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: '`preferences`')]
class Preferences
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, nullable: false)]
    private ?int $id = null;

    #[ORM\Column(type: Types::INTEGER, nullable: false, name: 'clientId')]
    private ?int $clientId = null;

    public function getId(): ?int { return $this->id; }
    public function getClientId(): ?int { return $this->clientId; }
    public function setClientId(int $clientId): static { $this->clientId = $clientId; return $this; }
}
