<?php

namespace App\Entity;

use App\Repository\ReservationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
#[ORM\Table(name: 'reservation')]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_reservation', type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Hebergement::class)]
    #[ORM\JoinColumn(name: 'id_hebergement', referencedColumnName: 'id_hebergement', nullable: false)]
    private ?Hebergement $hebergement = null;

    #[ORM\Column(name: 'nom_client', length: 255)]
    private ?string $nomClient = null;

    #[ORM\Column(name: 'email_client', length: 255, nullable: true)]
    private ?string $emailClient = null;

    #[ORM\Column(name: 'date_checkin', type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateCheckin = null;

    #[ORM\Column(name: 'date_checkout', type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateCheckout = null;

    #[ORM\Column(name: 'statut', length: 50)]
    private ?string $statut = 'CONFIRMED';

    #[ORM\Column(name: 'prix_total', type: Types::FLOAT)]
    private ?float $prixTotal = 0.0;

    #[ORM\Column(name: 'date_reservation', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateReservation = null;

    #[ORM\Column(name: 'guests_count', type: Types::INTEGER)]
    private ?int $guestsCount = 1;

    #[ORM\Column(name: 'rooms_count', type: Types::INTEGER)]
    private ?int $roomsCount = 1;

    #[ORM\Column(name: 'occupancy', length: 50)]
    private ?string $occupancy = 'DOUBLE';

    #[ORM\Column(name: 'room_type', length: 100, nullable: true)]
    private ?string $roomType = null;

    /**
     * @var Collection<int, ReservationGuest>
     */
    #[ORM\OneToMany(mappedBy: 'reservation', targetEntity: ReservationGuest::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $reservationGuests;

    public function __construct()
    {
        $this->reservationGuests = new ArrayCollection();
        $this->dateReservation = new \DateTime();
        $this->statut = 'CONFIRMED';
        $this->guestsCount = 1;
        $this->roomsCount = 1;
        $this->occupancy = 'DOUBLE';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHebergement(): ?Hebergement
    {
        return $this->hebergement;
    }

    public function setHebergement(?Hebergement $hebergement): static
    {
        $this->hebergement = $hebergement;
        return $this;
    }

    public function getNomClient(): ?string
    {
        return $this->nomClient;
    }

    public function setNomClient(string $nomClient): static
    {
        $this->nomClient = $nomClient;
        return $this;
    }

    public function getEmailClient(): ?string
    {
        return $this->emailClient;
    }

    public function setEmailClient(?string $emailClient): static
    {
        $this->emailClient = $emailClient;
        return $this;
    }

    public function getDateCheckin(): ?\DateTimeInterface
    {
        return $this->dateCheckin;
    }

    public function setDateCheckin(\DateTimeInterface $dateCheckin): static
    {
        $this->dateCheckin = $dateCheckin;
        return $this;
    }

    public function getDateCheckout(): ?\DateTimeInterface
    {
        return $this->dateCheckout;
    }

    public function setDateCheckout(\DateTimeInterface $dateCheckout): static
    {
        $this->dateCheckout = $dateCheckout;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getPrixTotal(): ?float
    {
        return $this->prixTotal;
    }

    public function setPrixTotal(float $prixTotal): static
    {
        $this->prixTotal = $prixTotal;
        return $this;
    }

    public function getDateReservation(): ?\DateTimeInterface
    {
        return $this->dateReservation;
    }

    public function setDateReservation(\DateTimeInterface $dateReservation): static
    {
        $this->dateReservation = $dateReservation;
        return $this;
    }

    public function getGuestsCount(): ?int
    {
        return $this->guestsCount;
    }

    public function setGuestsCount(int $guestsCount): static
    {
        $this->guestsCount = $guestsCount;
        return $this;
    }

    public function getRoomsCount(): ?int
    {
        return $this->roomsCount;
    }

    public function setRoomsCount(int $roomsCount): static
    {
        $this->roomsCount = $roomsCount;
        return $this;
    }

    public function getOccupancy(): ?string
    {
        return $this->occupancy;
    }

    public function setOccupancy(string $occupancy): static
    {
        $this->occupancy = $occupancy;
        return $this;
    }

    public function getRoomType(): ?string
    {
        return $this->roomType;
    }

    public function setRoomType(?string $roomType): static
    {
        $this->roomType = $roomType;
        return $this;
    }

    /**
     * @return Collection<int, ReservationGuest>
     */
    public function getReservationGuests(): Collection
    {
        return $this->reservationGuests;
    }

    public function addReservationGuest(ReservationGuest $reservationGuest): static
    {
        if (!$this->reservationGuests->contains($reservationGuest)) {
            $this->reservationGuests->add($reservationGuest);
            $reservationGuest->setReservation($this);
        }

        return $this;
    }

    public function removeReservationGuest(ReservationGuest $reservationGuest): static
    {
        if ($this->reservationGuests->removeElement($reservationGuest)) {
            if ($reservationGuest->getReservation() === $this) {
                $reservationGuest->setReservation(null);
            }
        }

        return $this;
    }
}