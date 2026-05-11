<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Enum\StatutBillet;
use App\Entity\Transport;

#[ORM\Entity]
#[ORM\Table(name: "billet")]
class Billet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "integer")]
    private int $userId;

    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $voyageId = null;

    #[ORM\Column(type: "integer")]
    private int $nombrePlaces;

    #[ORM\Column(type: "decimal", precision: 10, scale: 2)]
    private ?string $prixTotal = null;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $dateReservation;

    #[ORM\Column(type: "string", length: 20, enumType: StatutBillet::class)]
    private StatutBillet $statut;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $qrCode = null;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\ManyToOne(targetEntity: Transport::class, fetch: "EAGER")]
    #[ORM\JoinColumn(nullable: false)]
    private Transport $transport;

    // Constructeurs
    public function __construct(?Transport $transport = null, ?int $userId = null, ?int $nombrePlaces = null)
    {
        $this->dateReservation = new \DateTime();
        $this->statut = StatutBillet::EN_ATTENTE;

        if ($transport !== null) {
            $this->transport = $transport;
        }

        if ($userId !== null) {
            $this->userId = $userId;
        }

        if ($nombrePlaces !== null) {
            $this->nombrePlaces = $nombrePlaces;
            if ($transport !== null) {
                $this->prixTotal = $transport->getPrix() * $nombrePlaces;
            }
        }
    }

    // Méthodes utilitaires
    public function calculerPrixTotal(): void
    {
        if ($this->transport && $this->nombrePlaces) {
            $this->prixTotal = $this->transport->getPrix() * $this->nombrePlaces;
        }
    }

    public function estModifiable(): bool
    {
        return $this->statut === StatutBillet::EN_ATTENTE || $this->statut === StatutBillet::CONFIRME;
    }

    public function estAnnulable(): bool
    {
        return $this->statut !== StatutBillet::ANNULE;
    }

    public function confirmer(): void
    {
        $this->statut = StatutBillet::CONFIRME;
    }

    public function annuler(): void
    {
        if ($this->statut !== StatutBillet::ANNULE) {
            $this->statut = StatutBillet::ANNULE;
            // Libérer les places
            if ($this->transport) {
                $this->transport->libererPlaces($this->nombrePlaces);
            }
        }
    }

    // Getters et Setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getVoyageId(): ?int
    {
        return $this->voyageId;
    }

    public function setVoyageId(?int $voyageId): self
    {
        $this->voyageId = $voyageId;
        return $this;
    }

    public function getNombrePlaces(): int
    {
        return $this->nombrePlaces;
    }

    public function setNombrePlaces(int $nombrePlaces): self
    {
        $this->nombrePlaces = $nombrePlaces;
        return $this;
    }

    public function getPrixTotal(): ?float
    {
        return $this->prixTotal;
    }

    public function setPrixTotal(?float $prixTotal): self
    {
        $this->prixTotal = $prixTotal;
        return $this;
    }

    public function getDateReservation(): ?\DateTimeInterface
    {
        return $this->dateReservation;
    }

    public function setDateReservation(\DateTimeInterface $dateReservation): self
    {
        $this->dateReservation = $dateReservation;
        return $this;
    }

    public function getStatut(): StatutBillet
    {
        return $this->statut;
    }

    public function setStatut(StatutBillet $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    public function getQrCode(): ?string
    {
        return $this->qrCode;
    }

    public function setQrCode(?string $qrCode): self
    {
        $this->qrCode = $qrCode;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getTransport(): ?Transport
    {
        return $this->transport;
    }

    public function setTransport(?Transport $transport): self
    {
        $this->transport = $transport;
        return $this;
    }

    public function __toString(): string
    {
        return sprintf("Billet #%d - %s → %s (%d places) - %.2f DT [%s]",
            $this->id,
            $this->transport ? $this->transport->getOrigine() : "N/A",
            $this->transport ? $this->transport->getDestination() : "N/A",
            $this->nombrePlaces,
            $this->prixTotal,
            $this->statut->value
        );
    }
}
