<?php

namespace App\Entity;

use App\Repository\AvisRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AvisRepository::class)]
#[ORM\Table(name: 'avis')]
class Avis
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_avis', type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Hebergement::class)]
    #[ORM\JoinColumn(name: 'id_hebergement', referencedColumnName: 'id_hebergement', nullable: false, onDelete: 'CASCADE')]
    private ?Hebergement $hebergement = null;

    #[ORM\Column(name: 'nom_auteur', length: 255)]
    private ?string $nomAuteur = 'Guest';

    #[ORM\Column(name: 'note', type: Types::INTEGER)]
    private ?int $note = 0;

    #[ORM\Column(name: 'commentaire', type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

    #[ORM\Column(name: 'date_avis', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateAvis = null;

    public function __construct()
    {
        $this->dateAvis = new \DateTime();
        $this->nomAuteur = 'Guest';
        $this->note = 0;
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

    public function getNomAuteur(): ?string
    {
        return $this->nomAuteur;
    }

    public function setNomAuteur(?string $nomAuteur): static
    {
        $this->nomAuteur = ($nomAuteur === null || trim($nomAuteur) === '') ? 'Guest' : trim($nomAuteur);
        return $this;
    }

    public function getNote(): ?int
    {
        return $this->note;
    }

    public function setNote(int $note): static
    {
        $this->note = max(1, min(5, $note));
        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
    {
        $this->commentaire = $commentaire;
        return $this;
    }

    public function getDateAvis(): ?\DateTimeInterface
    {
        return $this->dateAvis;
    }

    public function setDateAvis(\DateTimeInterface $dateAvis): static
    {
        $this->dateAvis = $dateAvis;
        return $this;
    }
}