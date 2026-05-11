<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: '`axe_hebergement`')]
class AxeHebergement
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER, nullable: false)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, nullable: true, name: 'accepteColocation')]
    private ?string $accepteColocation = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true, name: 'budgetMax')]
    private ?float $budgetMax = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true, name: 'budgetMin')]
    private ?float $budgetMin = null;

    #[ORM\Column(type: Types::STRING, nullable: true, name: 'categorieHotel')]
    private ?string $categorieHotel = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true, name: 'nombreDeChambre')]
    private ?int $nombreDeChambre = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $services = null;

    #[ORM\Column(type: Types::STRING, nullable: true, name: 'typeHebergement')]
    private ?string $typeHebergement = null;

    public function getId(): ?int { return $this->id; }
    public function setId(int $id): static { $this->id = $id; return $this; }
    public function getAccepteColocation(): ?string { return $this->accepteColocation; }
    public function setAccepteColocation(?string $v): static { $this->accepteColocation = $v; return $this; }
    public function getBudgetMax(): ?float { return $this->budgetMax; }
    public function setBudgetMax(?float $v): static { $this->budgetMax = $v; return $this; }
    public function getBudgetMin(): ?float { return $this->budgetMin; }
    public function setBudgetMin(?float $v): static { $this->budgetMin = $v; return $this; }
    public function getCategorieHotel(): ?string { return $this->categorieHotel; }
    public function setCategorieHotel(?string $v): static { $this->categorieHotel = $v; return $this; }
    public function getNombreDeChambre(): ?int { return $this->nombreDeChambre; }
    public function setNombreDeChambre(?int $v): static { $this->nombreDeChambre = $v; return $this; }
    public function getServices(): ?string { return $this->services; }
    public function setServices(?string $v): static { $this->services = $v; return $this; }
    public function getTypeHebergement(): ?string { return $this->typeHebergement; }
    public function setTypeHebergement(?string $v): static { $this->typeHebergement = $v; return $this; }
}
