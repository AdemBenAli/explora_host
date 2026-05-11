<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Enum\TypeTransport;

#[ORM\Entity]
#[ORM\Table(name: "transport")]
class Transport
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string", length: 20, enumType: TypeTransport::class)]
    private TypeTransport $type;

    #[ORM\Column(type: "string", length: 100)]
    private string $origine;

    #[ORM\Column(type: "string", length: 100)]
    private string $destination;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $dateDepart;

    #[ORM\Column(type: "date", nullable: true)]
    private ?\DateTimeInterface $dateArrivee = null;

    #[ORM\Column(type: "time")]
    private \DateTimeInterface $heureDepart;

    #[ORM\Column(type: "time", nullable: true)]
    private ?\DateTimeInterface $heureArrivee = null;

    #[ORM\Column(type: "decimal", precision: 10, scale: 2)]
    private string $prix;

    #[ORM\Column(type: "integer")]
    private int $placesDisponibles;

    #[ORM\Column(type: "string", length: 100, nullable: true)]
    private ?string $compagnie = null;

    #[ORM\Column(type: "string", length: 50, nullable: true)]
    private ?string $numeroVol = null;

    #[ORM\Column(type: "string", length: 500, nullable: true)]
    private ?string $imageUrl = null;

    #[ORM\Column(type: "string", length: 20, options: ["default" => "FLUIDE"])]
    private string $etatTrafic = 'FLUIDE';

    #[ORM\Column(type: "string", length: 500, nullable: true)]
    private ?string $imageTraficUrl = null;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?\DateTimeInterface $derniereAnalyse = null;

    #[ORM\Column(type: "decimal", precision: 5, scale: 2, nullable: true)]
    private ?string $scoreConfiance = null;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: "decimal", precision: 10, scale: 2, nullable: true)]
    private ?string $prixOriginal = null;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?\DateTimeInterface $derniereMajPrix = null;

    #[ORM\Column(type: "float", nullable: true)]
    private ?float $distanceKm = null;

    #[ORM\Column(type: "float", nullable: true)]
    private ?float $emissionsKgCo2 = null;

    #[ORM\Column(type: "string", length: 50, nullable: true)]
    private ?string $categorieEcologique = null;

    // ======================
    // GETTERS & SETTERS
    // ======================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): TypeTransport
    {
        return $this->type;
    }

    public function setType(TypeTransport $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getOrigine(): string
    {
        return $this->origine;
    }

    public function setOrigine(string $origine): self
    {
        $this->origine = $origine;
        return $this;
    }

    public function getDestination(): string
    {
        return $this->destination;
    }

    public function setDestination(string $destination): self
    {
        $this->destination = $destination;
        return $this;
    }

    public function getDateDepart(): \DateTimeInterface
    {
        return $this->dateDepart;
    }

    public function setDateDepart(\DateTimeInterface $dateDepart): self
    {
        $this->dateDepart = $dateDepart;
        return $this;
    }

    public function getDateArrivee(): ?\DateTimeInterface
    {
        return $this->dateArrivee;
    }

    public function setDateArrivee(?\DateTimeInterface $dateArrivee): self
    {
        $this->dateArrivee = $dateArrivee;
        return $this;
    }

    public function getHeureDepart(): \DateTimeInterface
    {
        return $this->heureDepart;
    }

    public function setHeureDepart(\DateTimeInterface $heureDepart): self
    {
        $this->heureDepart = $heureDepart;
        return $this;
    }

    public function getHeureArrivee(): ?\DateTimeInterface
    {
        return $this->heureArrivee;
    }

    public function setHeureArrivee(?\DateTimeInterface $heureArrivee): self
    {
        $this->heureArrivee = $heureArrivee;
        return $this;
    }

    public function getPrix(): string
    {
        return $this->prix;
    }

    public function setPrix(string $prix): self
    {
        $this->prix = $prix;
        return $this;
    }

    public function getPlacesDisponibles(): int
    {
        return $this->placesDisponibles;
    }

    public function setPlacesDisponibles(int $placesDisponibles): self
    {
        $this->placesDisponibles = $placesDisponibles;
        return $this;
    }

    public function getCompagnie(): ?string
    {
        return $this->compagnie;
    }

    public function setCompagnie(?string $compagnie): self
    {
        $this->compagnie = $compagnie;
        return $this;
    }

    public function getNumeroVol(): ?string
    {
        return $this->numeroVol;
    }

    public function setNumeroVol(?string $numeroVol): self
    {
        $this->numeroVol = $numeroVol;
        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): self
    {
        $this->imageUrl = $imageUrl;
        return $this;
    }

    public function getEtatTrafic(): string
    {
        return $this->etatTrafic;
    }

    public function setEtatTrafic(string $etatTrafic): self
    {
        $this->etatTrafic = $etatTrafic;
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getImageTraficUrl(): ?string
    {
        return $this->imageTraficUrl;
    }

    public function setImageTraficUrl(?string $imageTraficUrl): self
    {
        $this->imageTraficUrl = $imageTraficUrl;
        return $this;
    }

    public function getDerniereAnalyse(): ?\DateTimeInterface
    {
        return $this->derniereAnalyse;
    }

    public function setDerniereAnalyse(?\DateTimeInterface $derniereAnalyse): self
    {
        $this->derniereAnalyse = $derniereAnalyse;
        return $this;
    }

    public function getScoreConfiance(): ?string
    {
        return $this->scoreConfiance;
    }

    public function setScoreConfiance(?string $scoreConfiance): self
    {
        $this->scoreConfiance = $scoreConfiance;
        return $this;
    }

    public function getPrixOriginal(): ?string
    {
        return $this->prixOriginal;
    }

    public function setPrixOriginal(?string $prixOriginal): self
    {
        $this->prixOriginal = $prixOriginal;
        return $this;
    }

    public function getDerniereMajPrix(): ?\DateTimeInterface
    {
        return $this->derniereMajPrix;
    }

    public function setDerniereMajPrix(?\DateTimeInterface $derniereMajPrix): self
    {
        $this->derniereMajPrix = $derniereMajPrix;
        return $this;
    }

    public function getDistanceKm(): ?float
    {
        return $this->distanceKm;
    }

    public function setDistanceKm(?float $distanceKm): self
    {
        $this->distanceKm = $distanceKm;
        return $this;
    }

    public function getEmissionsKgCo2(): ?float
    {
        return $this->emissionsKgCo2;
    }

    public function setEmissionsKgCo2(?float $emissionsKgCo2): self
    {
        $this->emissionsKgCo2 = $emissionsKgCo2;
        return $this;
    }

    public function getCategorieEcologique(): ?string
    {
        return $this->categorieEcologique;
    }

    public function setCategorieEcologique(?string $categorieEcologique): self
    {
        $this->categorieEcologique = $categorieEcologique;
        return $this;
    }

    // ======================
    // BUSINESS METHODS
    // ======================

    public function reserverPlaces(int $nombre): void
    {
        if ($this->placesDisponibles >= $nombre) {
            $this->placesDisponibles -= $nombre;
        } else {
            throw new \Exception('Pas assez de places disponibles');
        }
    }

    public function libererPlaces(int $nombre): void
    {
        $this->placesDisponibles += $nombre;
    }
}