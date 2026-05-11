<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: '`axe_voyage`')]
class AxeVoyage
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER, nullable: false)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private ?int $id = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true, name: 'budgetMax')]
    private ?float $budgetMax = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true, name: 'budgetMin')]
    private ?float $budgetMin = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $destinations = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $duree = null;

    #[ORM\Column(type: Types::STRING, nullable: true, name: 'saisonsPreferees')]
    private ?string $saisonsPreferees = null;

    #[ORM\Column(type: Types::STRING, nullable: true, name: 'typesVoyages')]
    private ?string $typesVoyages = null;

    public function getId(): ?int { return $this->id; }
    public function setId(int $id): static { $this->id = $id; return $this; }
    public function getBudgetMax(): ?float { return $this->budgetMax; }
    public function setBudgetMax(?float $v): static { $this->budgetMax = $v; return $this; }
    public function getBudgetMin(): ?float { return $this->budgetMin; }
    public function setBudgetMin(?float $v): static { $this->budgetMin = $v; return $this; }
    public function getDestinations(): ?string { return $this->destinations; }
    public function setDestinations(?string $v): static { $this->destinations = $v; return $this; }
    public function getDuree(): ?int { return $this->duree; }
    public function setDuree(?int $v): static { $this->duree = $v; return $this; }
    public function getSaisonsPreferees(): ?string { return $this->saisonsPreferees; }
    public function setSaisonsPreferees(?string $v): static { $this->saisonsPreferees = $v; return $this; }
    public function getTypesVoyages(): ?string { return $this->typesVoyages; }
    public function setTypesVoyages(?string $v): static { $this->typesVoyages = $v; return $this; }
}
