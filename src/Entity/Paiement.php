<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Auto-generated entity for table 'paiement'
 * Generated: 2026-04-06 16:08:09
 */
#[ORM\Entity]
#[ORM\Table(name: 'paiement')]
class Paiement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'adresse_facturation', type: 'text', nullable: true)]
    private ?string $adresseFacturation = null;

    #[ORM\Column(name: 'date_paiement', type: 'datetime', nullable: true)]
    private ?\DateTime $datePaiement = null;

    #[ORM\Column(name: 'devise', type: 'string', nullable: true)]
    private ?string $devise = null;

    #[ORM\Column(name: 'methode_paiement', type: 'string', nullable: false)]
    private ?string $methodePaiement = null;

    #[ORM\Column(name: 'montant_paye', type: 'decimal', nullable: false)]
    private ?string $montantPaye = null;

    #[ORM\Column(name: 'panier_id', type: 'integer', nullable: false)]
    private ?int $panierId = null;

    #[ORM\Column(name: 'reference_transaction', type: 'string', nullable: true)]
    private ?string $referenceTransaction = null;

    #[ORM\Column(name: 'statut', type: 'string', nullable: false)]
    private ?string $statut = null;

    #[ORM\Column(name: 'token_securise', type: 'string', nullable: true)]
    private ?string $tokenSecurise = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getAdresseFacturation(): ?string
    {
        return $this->adresseFacturation;
    }

    public function setAdresseFacturation(?string $adresseFacturation): self
    {
        $this->adresseFacturation = $adresseFacturation;
        return $this;
    }

    public function getDatePaiement(): ?\DateTime
    {
        return $this->datePaiement;
    }

    public function setDatePaiement(?\DateTime $datePaiement): self
    {
        $this->datePaiement = $datePaiement;
        return $this;
    }

    public function getDevise(): ?string
    {
        return $this->devise;
    }

    public function setDevise(?string $devise): self
    {
        $this->devise = $devise;
        return $this;
    }

    public function getMethodePaiement(): ?string
    {
        return $this->methodePaiement;
    }

    public function setMethodePaiement(?string $methodePaiement): self
    {
        $this->methodePaiement = $methodePaiement;
        return $this;
    }

    public function getMontantPaye(): ?string
    {
        return $this->montantPaye;
    }

    public function setMontantPaye(?string $montantPaye): self
    {
        $this->montantPaye = $montantPaye;
        return $this;
    }

    public function getPanierId(): ?int
    {
        return $this->panierId;
    }

    public function setPanierId(?int $panierId): self
    {
        $this->panierId = $panierId;
        return $this;
    }

    public function getReferenceTransaction(): ?string
    {
        return $this->referenceTransaction;
    }

    public function setReferenceTransaction(?string $referenceTransaction): self
    {
        $this->referenceTransaction = $referenceTransaction;
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

    public function getTokenSecurise(): ?string
    {
        return $this->tokenSecurise;
    }

    public function setTokenSecurise(?string $tokenSecurise): self
    {
        $this->tokenSecurise = $tokenSecurise;
        return $this;
    }

}