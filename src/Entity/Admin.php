<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: '`admin`')]
class Admin
{
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $type = null;

    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER, nullable: false)]
    private int $id;

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;
        return $this;
    }

}
