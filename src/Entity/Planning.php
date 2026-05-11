<?php

namespace App\Entity;

use App\Repository\PlanningRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlanningRepository::class)]
#[ORM\Table(name: 'planning')]
class Planning
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_planning')]
    private ?int $idPlanning = null;

    #[ORM\Column(name: 'id_voyageur')]
    private int $idVoyageur;

    #[ORM\ManyToOne(targetEntity: Activite::class)]
    #[ORM\JoinColumn(name: 'id_activite', referencedColumnName: 'idActivite')]
    private Activite $activite;

    #[ORM\Column(name: 'date_activite', type: 'date')]
    private \DateTimeInterface $date;

    #[ORM\Column(name: 'heure_debut', type: 'time', nullable: true)]
    private ?\DateTimeInterface $heureDebut = null;

    #[ORM\Column(name: 'heure_fin', type: 'time', nullable: true)]
    private ?\DateTimeInterface $heureFin = null;

    #[ORM\Column(name: 'nombre_places', type: 'integer', options: ['default' => 1])]
    private int $nombrePlaces = 1;

    // ── Getters / Setters ────────────────────────────────────────────────

    public function getIdPlanning(): ?int { return $this->idPlanning; }

    public function getIdVoyageur(): int { return $this->idVoyageur; }
    public function setIdVoyageur(int $v): static { $this->idVoyageur = $v; return $this; }

    public function getActivite(): Activite { return $this->activite; }
    public function setActivite(Activite $a): static { $this->activite = $a; return $this; }

    public function getDate(): \DateTimeInterface { return $this->date; }
    public function setDate(\DateTimeInterface $d): static { $this->date = $d; return $this; }

    public function getHeureDebut(): ?\DateTimeInterface { return $this->heureDebut; }
    public function setHeureDebut(?\DateTimeInterface $h): static { $this->heureDebut = $h; return $this; }

    public function getHeureFin(): ?\DateTimeInterface { return $this->heureFin; }
    public function setHeureFin(?\DateTimeInterface $h): static { $this->heureFin = $h; return $this; }

    public function getNombrePlaces(): int { return $this->nombrePlaces; }
    public function setNombrePlaces(int $n): static { $this->nombrePlaces = $n; return $this; }

    /** Clé unique pour la suppression par session */
    public function getKey(): string
    {
        return $this->idVoyageur . '_' . $this->activite->getIdActivite() . '_' . $this->date->format('Y-m-d');
    }
}