<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Auto-generated entity for table 'panier'
 * Generated: 2026-04-06 16:08:09
 */
#[ORM\Entity]
#[ORM\Table(name: 'panier')]
class Panier
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'code_promo', type: 'string', nullable: true)]
    private ?string $codePromo = null;

    #[ORM\Column(name: 'date_creation', type: 'datetime', nullable: true)]
    private ?\DateTime $dateCreation = null;

    #[ORM\Column(name: 'date_modification', type: 'datetime', nullable: true)]
    private ?\DateTime $dateModification = null;

    #[ORM\Column(name: 'montant_reduction', type: 'decimal', nullable: true)]
    private ?string $montantReduction = null;

    #[ORM\Column(name: 'montant_ttc', type: 'decimal', nullable: true)]
    private ?string $montantTtc = null;

    #[ORM\Column(name: 'montant_tva', type: 'decimal', nullable: true)]
    private ?string $montantTva = null;

    #[ORM\Column(name: 'montant_total_ht', type: 'decimal', nullable: true)]
    private ?string $montantTotalHt = null;

    #[ORM\Column(name: 'statut', type: 'string', nullable: false)]
    private ?string $statut = null;

    #[ORM\Column(name: 'user_id', type: 'integer', nullable: false)]
    private ?int $userId = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getCodePromo(): ?string
    {
        return $this->codePromo;
    }

    public function setCodePromo(?string $codePromo): self
    {
        $this->codePromo = $codePromo;
        return $this;
    }

    public function getDateCreation(): ?\DateTime
    {
        return $this->dateCreation;
    }

    public function setDateCreation(?\DateTime $dateCreation): self
    {
        $this->dateCreation = $dateCreation;
        return $this;
    }

    public function getDateModification(): ?\DateTime
    {
        return $this->dateModification;
    }

    public function setDateModification(?\DateTime $dateModification): self
    {
        $this->dateModification = $dateModification;
        return $this;
    }

    public function getMontantReduction(): ?string
    {
        return $this->montantReduction;
    }

    public function setMontantReduction(?string $montantReduction): self
    {
        $this->montantReduction = $montantReduction;
        return $this;
    }

    public function getMontantTtc(): ?string
    {
        return $this->montantTtc;
    }

    public function setMontantTtc(?string $montantTtc): self
    {
        $this->montantTtc = $montantTtc;
        return $this;
    }

    public function getMontantTva(): ?string
    {
        return $this->montantTva;
    }

    public function setMontantTva(?string $montantTva): self
    {
        $this->montantTva = $montantTva;
        return $this;
    }

    public function getMontantTotalHt(): ?string
    {
        return $this->montantTotalHt;
    }

    public function setMontantTotalHt(?string $montantTotalHt): self
    {
        $this->montantTotalHt = $montantTotalHt;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(?string $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

}