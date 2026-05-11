<?php

namespace App\Entity;

use App\Repository\AvisActiviteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AvisActiviteRepository::class)]
#[ORM\Table(name: "avis_activite")]
class AvisActivite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "idAvis", type: "integer")]
    private int $idAvis;

    #[ORM\ManyToOne(targetEntity: Activite::class)]
    #[ORM\JoinColumn(name: 'idActivite', referencedColumnName: 'idActivite', onDelete: 'CASCADE')]
    private ?Activite $activite = null;

    #[ORM\Column(name: 'idvoyageur', type: 'integer')]
    private int $idVoyageur;

    #[ORM\Column(name: 'nomVoyageur', type: 'string', length: 255)]
    private string $nomVoyageur;

    #[ORM\Column(name: 'note', type: 'integer')]
    private int $note;

    #[ORM\Column(name: 'commentaire', type: 'text')]
    private string $commentaire;

    #[ORM\Column(name: 'dateAvis', type: 'datetime')]
    private \DateTimeInterface $dateAvis;

    public function __construct()
    {
        $this->dateAvis = new \DateTime();
    }

    public function getIdAvis(): int
    {
        return $this->idAvis;
    }

    public function getActivite(): ?Activite
    {
        return $this->activite;
    }

    public function setActivite(?Activite $activite): self
    {
        $this->activite = $activite;
        return $this;
    }

    public function getIdVoyageur(): int
    {
        return $this->idVoyageur;
    }

    public function setIdVoyageur(int $id): self
    {
        $this->idVoyageur = $id;
        return $this;
    }

    public function getNomVoyageur(): string
    {
        return $this->nomVoyageur;
    }

    public function setNomVoyageur(string $nom): self
    {
        $this->nomVoyageur = $nom;
        return $this;
    }

    public function getNote(): int
    {
        return $this->note;
    }

    public function setNote(int $note): self
    {
        $this->note = $note;
        return $this;
    }

    public function getCommentaire(): string
    {
        return $this->commentaire;
    }

    public function setCommentaire(string $commentaire): self
    {
        $this->commentaire = $commentaire;
        return $this;
    }

    public function getDateAvis(): \DateTimeInterface
    {
        return $this->dateAvis;
    }

    public function setDateAvis(\DateTimeInterface $date): self
    {
        $this->dateAvis = $date;
        return $this;
    }

    /** Retourne les étoiles sous forme de ★☆☆☆☆ */
    public function getEtoiles(): string
    {
        $s = '';
        for ($i = 1; $i <= 5; $i++) {
            $s .= $i <= $this->note ? '★' : '☆';
        }
        return $s;
    }
}