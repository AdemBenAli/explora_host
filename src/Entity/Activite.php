<?php

namespace App\Entity;

use App\Repository\ActiviteRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: ActiviteRepository::class)]
#[ORM\Table(name: 'activite')]
class Activite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'idActivite', type: 'integer')]
    private int $idActivite;

    #[ORM\Column(name: 'nom', type: 'string', length: 255)]
    private string $nom;

    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'categorie', type: 'string', length: 100)]
    private string $categorie;

    #[ORM\Column(name: 'type', type: 'string', length: 100)]
    private string $type;

    #[ORM\Column(name: 'ville', type: 'string', length: 100)]
    private string $ville;

    #[ORM\Column(name: 'lieu', type: 'string', length: 255)]
    private string $lieu;

    #[ORM\Column(name: 'prix', type: 'float')]
    private float $prix;

    #[ORM\Column(name: 'duree', type: 'integer')]
    private int $duree;

    #[ORM\Column(name: 'image', type: 'string', length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(name: 'id_agent', type: 'integer', nullable: true)]
    private ?int $idAgent = null;

    #[ORM\Column(name: 'nombrePlaces', type: 'integer')]
    private int $nombrePlaces;

    #[ORM\Column(name: 'disponible', type: 'boolean')]
    private bool $disponible;

    #[ORM\Column(name: 'date_activite', type: 'date', nullable: true)]
    private ?\DateTimeInterface $dateActivite = null;

    #[ORM\Column(name: 'heure_debut', type: 'time', nullable: true)]
    private ?\DateTimeInterface $heureDebut = null;

    #[ORM\Column(name: 'heure_fin', type: 'time', nullable: true)]
    private ?\DateTimeInterface $heureFin = null;

    #[ORM\ManyToMany(targetEntity: Voyage::class, mappedBy: 'activites')]
    private Collection $voyages;

    public function __construct()
    {
        $this->voyages = new ArrayCollection();
        $this->disponible = false;
    }

    public function getIdActivite(): int { return $this->idActivite; }

    public function getNom(): string { return $this->nom; }
    public function setNom(string $nom): self { $this->nom = $nom; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }

    public function getCategorie(): string { return $this->categorie; }
    public function setCategorie(string $categorie): self { $this->categorie = $categorie; return $this; }

    public function getType(): string { return $this->type; }
    public function setType(string $type): self { $this->type = $type; return $this; }

    public function getVille(): string { return $this->ville; }
    public function setVille(string $ville): self { $this->ville = $ville; return $this; }

    public function getLieu(): string { return $this->lieu; }
    public function setLieu(string $lieu): self { $this->lieu = $lieu; return $this; }

    public function getPrix(): float { return $this->prix; }
    public function setPrix(float $prix): self { $this->prix = $prix; return $this; }

    public function getDuree(): int { return $this->duree; }
    public function setDuree(int $duree): self { $this->duree = $duree; return $this; }

    public function getImage(): ?string { return $this->image; }
    public function setImage(?string $image): self { $this->image = $image; return $this; }

    public function getIdAgent(): ?int { return $this->idAgent; }
    public function setIdAgent(?int $idAgent): self { $this->idAgent = $idAgent; return $this; }

    public function getNombrePlaces(): int { return $this->nombrePlaces; }
    public function setNombrePlaces(int $nombrePlaces): self
    {
        $this->nombrePlaces = $nombrePlaces;
        $this->disponible = $nombrePlaces > 0;
        return $this;
    }

    public function isDisponible(): bool { return $this->disponible; }
    public function setDisponible(bool $disponible): self { $this->disponible = $disponible; return $this; }

    public function getDateActivite(): ?\DateTimeInterface { return $this->dateActivite; }
    public function setDateActivite(?\DateTimeInterface $dateActivite): self { $this->dateActivite = $dateActivite; return $this; }

    public function getHeureDebut(): ?\DateTimeInterface { return $this->heureDebut; }
    public function setHeureDebut(?\DateTimeInterface $heureDebut): self { $this->heureDebut = $heureDebut; return $this; }

    public function getHeureFin(): ?\DateTimeInterface { return $this->heureFin; }
    public function setHeureFin(?\DateTimeInterface $heureFin): self { $this->heureFin = $heureFin; return $this; }

    public function getPeriodeJournee(): string
    {
        if ($this->heureDebut === null) return 'INCONNU';
        $h = (int) $this->heureDebut->format('H');
        if ($h >= 6 && $h < 12) return 'MATIN';
        if ($h >= 12 && $h < 18) return 'APRES_MIDI';
        if ($h >= 18) return 'SOIR';
        return 'NUIT';
    }

    public function getVoyages(): Collection { return $this->voyages; }

    public function addVoyage(Voyage $voyage): self
    {
        if (!$this->voyages->contains($voyage)) {
            $this->voyages->add($voyage);
            $voyage->addActivite($this);
        }
        return $this;
    }

    public function removeVoyage(Voyage $voyage): self
    {
        if ($this->voyages->removeElement($voyage)) {
            $voyage->removeActivite($this);
        }
        return $this;
    }
}