<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Auto-generated entity for table 'produit_panier'
 * Generated: 2026-04-06 16:08:09
 */
#[ORM\Entity]
#[ORM\Table(name: 'produit_panier')]
class ProduitPanier
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'date_ajout', type: 'datetime', nullable: true)]
    private ?\DateTime $dateAjout = null;

    #[ORM\Column(name: 'panier_id', type: 'integer', nullable: false)]
    private ?int $panierId = null;

    #[ORM\Column(name: 'prix_total_ligne', type: 'decimal', nullable: false)]
    private ?string $prixTotalLigne = null;

    #[ORM\Column(name: 'prix_unitaire', type: 'decimal', nullable: false)]
    private ?string $prixUnitaire = null;

    #[ORM\Column(name: 'produit_id', type: 'integer', nullable: false)]
    private ?int $produitId = null;

    #[ORM\Column(name: 'quantite', type: 'integer', nullable: false)]
    private ?int $quantite = null;

    #[ORM\Column(name: 'type_produit', type: 'string', nullable: false)]
    private ?string $typeProduit = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getDateAjout(): ?\DateTime
    {
        return $this->dateAjout;
    }

    public function setDateAjout(?\DateTime $dateAjout): self
    {
        $this->dateAjout = $dateAjout;
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

    public function getPrixTotalLigne(): ?string
    {
        return $this->prixTotalLigne;
    }

    public function setPrixTotalLigne(?string $prixTotalLigne): self
    {
        $this->prixTotalLigne = $prixTotalLigne;
        return $this;
    }

    public function getPrixUnitaire(): ?string
    {
        return $this->prixUnitaire;
    }

    public function setPrixUnitaire(?string $prixUnitaire): self
    {
        $this->prixUnitaire = $prixUnitaire;
        return $this;
    }

    public function getProduitId(): ?int
    {
        return $this->produitId;
    }

    public function setProduitId(?int $produitId): self
    {
        $this->produitId = $produitId;
        return $this;
    }

    public function getQuantite(): ?int
    {
        return $this->quantite;
    }

    public function setQuantite(?int $quantite): self
    {
        $this->quantite = $quantite;
        return $this;
    }

    public function getTypeProduit(): ?string
    {
        return $this->typeProduit;
    }

    public function setTypeProduit(?string $typeProduit): self
    {
        $this->typeProduit = $typeProduit;
        return $this;
    }

}