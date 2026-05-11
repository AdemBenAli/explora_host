<?php

namespace App\Entity;

use App\Repository\VoyageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: VoyageRepository::class)]
#[ORM\Table(name: 'voyage')]
class Voyage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $idVoyage = null;

    #[ORM\Column(name: 'nom', type: 'string', length: 255)]
    private string $nom = '';

    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'date_depart', type: 'date', nullable: true)]
    private ?\DateTimeInterface $dateDepart = null;

    #[ORM\Column(name: 'date_retour', type: 'date', nullable: true)]
    private ?\DateTimeInterface $dateRetour = null;

    // prix column does not exist in DB; prix_unitaire is mapped below
    private float $prix = 0.0;

    // villeDepart / villeArrivee do not exist in DB table
    private string $villeDepart = '';
    private string $villeArrivee = '';

    #[ORM\Column(name: 'image_url', type: 'string', length: 255, nullable: true)]
    private ?string $imageUrl = null;

    // ── Extra columns added for my work (voyage management feature) ──────────
    #[ORM\Column(name: 'destination', type: 'string', length: 150, nullable: true)]
    private ?string $destination = null;

    #[ORM\Column(name: 'disponibilite', type: 'integer', nullable: true)]
    private ?int $disponibilite = 1;

    #[ORM\Column(name: 'duree_jours', type: 'integer', nullable: true)]
    private ?int $dureeJours = null;

    #[ORM\Column(name: 'prix_unitaire', type: 'float', nullable: true)]
    private ?float $budgetTotal = null;

    #[ORM\ManyToMany(targetEntity: Activite::class, inversedBy: 'voyages')]
    #[ORM\JoinTable(
        name: 'activite_voyage',
        joinColumns: [new ORM\JoinColumn(name: 'idVoyage', referencedColumnName: 'id')],
        inverseJoinColumns: [new ORM\JoinColumn(name: 'idActivite', referencedColumnName: 'idActivite')]
    )]
    private Collection $activites;

    public function __construct()
    {
        $this->activites = new ArrayCollection();
    }

    // ── Group work getters/setters ───────────────────────────────────────────

    public function getIdVoyage(): ?int { return $this->idVoyage; }

    /** Alias for getId() - used by my work */
    public function getId(): ?int { return $this->idVoyage; }

    public function getNom(): string { return $this->nom; }
    public function setNom(string $nom): self { $this->nom = $nom; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $d): self { $this->description = $d; return $this; }

    public function getDateDepart(): ?\DateTimeInterface { return $this->dateDepart; }
    public function setDateDepart(?\DateTimeInterface $d): self { $this->dateDepart = $d; return $this; }

    public function getDateRetour(): ?\DateTimeInterface { return $this->dateRetour; }
    public function setDateRetour(?\DateTimeInterface $d): self { $this->dateRetour = $d; return $this; }

    public function getPrix(): float { return $this->prix; }
    public function setPrix(float $prix): self { $this->prix = $prix; return $this; }

    public function getVilleDepart(): string { return $this->villeDepart; }
    public function setVilleDepart(string $v): self { $this->villeDepart = $v; return $this; }

    public function getVilleArrivee(): string { return $this->villeArrivee; }
    public function setVilleArrivee(string $v): self { $this->villeArrivee = $v; return $this; }

    public function getImageUrl(): ?string { return $this->imageUrl; }
    public function setImageUrl(?string $url): self { $this->imageUrl = $url; return $this; }

    public function getActivites(): Collection { return $this->activites; }
    public function addActivite(Activite $a): self
    {
        if (!$this->activites->contains($a)) {
            $this->activites->add($a);
        }
        return $this;
    }
    public function removeActivite(Activite $a): self
    {
        $this->activites->removeElement($a);
        return $this;
    }

    // ── My work getters/setters ──────────────────────────────────────────────

    /** titre maps to nom column (same DB field) */
    public function getTitre(): string { return $this->nom; }
    public function setTitre(string $titre): static { $this->nom = trim($titre); return $this; }

    public function getDestination(): string { return $this->destination ?? $this->villeArrivee; }
    public function setDestination(string $destination): static { $this->destination = trim($destination); return $this; }

    public function getDisponibilite(): int { return $this->disponibilite ?? 1; }
    public function setDisponibilite(int $disponibilite): static { $this->disponibilite = $disponibilite; return $this; }

    public function getDureeJours(): int
    {
        if ($this->dureeJours !== null && $this->dureeJours > 0) {
            return $this->dureeJours;
        }
        if ($this->dateDepart !== null && $this->dateRetour !== null) {
            return (int) $this->dateDepart->diff($this->dateRetour)->days;
        }
        return 0;
    }
    public function setDureeJours(int $dureeJours): static { $this->dureeJours = $dureeJours; return $this; }

    public function getBudgetTotal(): float { return $this->budgetTotal ?? $this->prix; }
    public function setBudgetTotal(float $budgetTotal): static { $this->budgetTotal = $budgetTotal; $this->prix = $budgetTotal; return $this; }

    /** Alias for getDateDepart() */
    public function getDateDebut(): ?\DateTimeInterface { return $this->dateDepart; }
    public function setDateDebut(?\DateTimeInterface $d): static { $this->dateDepart = $d; return $this; }

    /** Alias for getDateRetour() */
    public function getDateFin(): ?\DateTimeInterface { return $this->dateRetour; }
    public function setDateFin(?\DateTimeInterface $d): static { $this->dateRetour = $d; return $this; }

    public function estEnCours(): bool
    {
        if ($this->dateDepart === null || $this->dateRetour === null) return false;
        $today = new \DateTimeImmutable('today');
        return $today >= $this->dateDepart && $today <= $this->dateRetour;
    }

    public function estAVenir(): bool
    {
        if ($this->dateDepart === null) return false;
        return new \DateTimeImmutable('today') < $this->dateDepart;
    }

    public function estTermine(): bool
    {
        if ($this->dateRetour === null) return false;
        return new \DateTimeImmutable('today') > $this->dateRetour;
    }

    public function getStatut(): string
    {
        if ($this->estEnCours()) return 'En cours';
        if ($this->estAVenir()) return 'A venir';
        return 'Termine';
    }

    public function getDuree(): int { return $this->getDureeJours(); }
    public function getName(): string { return $this->getNom(); }
    public function getDuration(): int { return $this->getDureeJours(); }
    public function isIncludesFlight(): bool { return true; }
    public function isIncludesHotel(): bool { return true; }
    public function isIncludesMeals(): bool { return true; }

    public function __toString(): string
    {
        return $this->nom . ' (' . $this->villeDepart . ' → ' . $this->villeArrivee . ')';
    }
}
