<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: '`axe_activite`')]
class AxeActivite
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER, nullable: false)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, nullable: true, name: 'avecGroupe')]
    private ?string $avecGroupe = null;

    #[ORM\Column(type: Types::STRING, nullable: true, name: 'avecGuide')]
    private ?string $avecGuide = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true, name: 'budgetMax')]
    private ?float $budgetMax = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true, name: 'budgetMin')]
    private ?float $budgetMin = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $niveau = null;

    #[ORM\Column(type: Types::STRING, nullable: true, name: 'typesActivite')]
    private ?string $typesActivite = null;

    public function getId(): ?int { return $this->id; }
    public function setId(int $id): static { $this->id = $id; return $this; }
    public function getAvecGroupe(): ?string { return $this->avecGroupe; }
    public function setAvecGroupe(?string $v): static { $this->avecGroupe = $v; return $this; }
    public function getAvecGuide(): ?string { return $this->avecGuide; }
    public function setAvecGuide(?string $v): static { $this->avecGuide = $v; return $this; }
    public function getBudgetMax(): ?float { return $this->budgetMax; }
    public function setBudgetMax(?float $v): static { $this->budgetMax = $v; return $this; }
    public function getBudgetMin(): ?float { return $this->budgetMin; }
    public function setBudgetMin(?float $v): static { $this->budgetMin = $v; return $this; }
    public function getNiveau(): ?string { return $this->niveau; }
    public function setNiveau(?string $v): static { $this->niveau = $v; return $this; }
    public function getTypesActivite(): ?string { return $this->typesActivite; }
    public function setTypesActivite(?string $v): static { $this->typesActivite = $v; return $this; }
}
