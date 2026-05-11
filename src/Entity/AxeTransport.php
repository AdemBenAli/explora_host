<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: '`axe_transport`')]
class AxeTransport
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER, nullable: false)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, nullable: true, name: 'accepteEscale')]
    private ?string $accepteEscale = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true, name: 'budgetMax')]
    private ?float $budgetMax = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true, name: 'budgetMin')]
    private ?float $budgetMin = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $classe = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true, name: 'toleranceTemps')]
    private ?int $toleranceTemps = null;

    #[ORM\Column(type: Types::STRING, nullable: true, name: 'typeTransport')]
    private ?string $typeTransport = null;

    public function getId(): ?int { return $this->id; }
    public function setId(int $id): static { $this->id = $id; return $this; }
    public function getAccepteEscale(): ?string { return $this->accepteEscale; }
    public function setAccepteEscale(?string $v): static { $this->accepteEscale = $v; return $this; }
    public function getBudgetMax(): ?float { return $this->budgetMax; }
    public function setBudgetMax(?float $v): static { $this->budgetMax = $v; return $this; }
    public function getBudgetMin(): ?float { return $this->budgetMin; }
    public function setBudgetMin(?float $v): static { $this->budgetMin = $v; return $this; }
    public function getClasse(): ?string { return $this->classe; }
    public function setClasse(?string $v): static { $this->classe = $v; return $this; }
    public function getToleranceTemps(): ?int { return $this->toleranceTemps; }
    public function setToleranceTemps(?int $v): static { $this->toleranceTemps = $v; return $this; }
    public function getTypeTransport(): ?string { return $this->typeTransport; }
    public function setTypeTransport(?string $v): static { $this->typeTransport = $v; return $this; }
}
