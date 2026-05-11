<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: '`coupon`')]
class Coupon
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, nullable: false)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, nullable: false)]
    private ?string $actif = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true, name: 'clientId')]
    private ?int $clientId = null;

    #[ORM\Column(type: Types::STRING, nullable: false)]
    private ?string $code = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: false, name: 'dateCreation')]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: false, name: 'dateExpiration')]
    private ?\DateTimeInterface $dateExpiration = null;

    #[ORM\Column(type: Types::FLOAT, nullable: false, name: 'montantMinimum')]
    private ?float $montantMinimum = null;

    #[ORM\Column(type: Types::FLOAT, nullable: false)]
    private ?float $pourcentage = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(type: Types::FLOAT, nullable: false, name: 'valeurReduction')]
    private ?float $valeurReduction = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function getActif(): string
    {
        return $this->actif;
    }

    public function setActif(string $actif): static
    {
        $this->actif = $actif;
        return $this;
    }

    public function getClientId(): int
    {
        return $this->clientId;
    }

    public function setClientId(int $clientId): static
    {
        $this->clientId = $clientId;
        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;
        return $this;
    }

    public function getDateCreation(): \DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeInterface $dateCreation): static
    {
        $this->dateCreation = $dateCreation;
        return $this;
    }

    public function getDateExpiration(): \DateTimeInterface
    {
        return $this->dateExpiration;
    }

    public function setDateExpiration(\DateTimeInterface $dateExpiration): static
    {
        $this->dateExpiration = $dateExpiration;
        return $this;
    }

    public function getMontantMinimum(): float
    {
        return $this->montantMinimum;
    }

    public function setMontantMinimum(float $montantMinimum): static
    {
        $this->montantMinimum = $montantMinimum;
        return $this;
    }

    public function getPourcentage(): float
    {
        return $this->pourcentage;
    }

    public function setPourcentage(float $pourcentage): static
    {
        $this->pourcentage = $pourcentage;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getValeurReduction(): float
    {
        return $this->valeurReduction;
    }

    public function setValeurReduction(float $valeurReduction): static
    {
        $this->valeurReduction = $valeurReduction;
        return $this;
    }

}
