<?php

namespace App\Entity;

use App\Repository\HebergementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: HebergementRepository::class)]
#[ORM\Table(name: 'hebergement')]
#[Vich\Uploadable]
class Hebergement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_hebergement', type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(name: 'nom', length: 255)]
    #[Assert\NotBlank(message: 'Le nom de l’hébergement est obligatoire.')]
    #[Assert\Regex(
        pattern: '/^(?=(?:.*\p{L}){2,}).{2,}$/u',
        message: 'Le nom de l’hébergement doit contenir au moins 2 lettres.'
    )]
    private ?string $nom = null;

    #[ORM\Column(name: 'type', length: 100)]
    #[Assert\NotBlank(message: 'Le type est obligatoire.')]
    private ?string $type = null;

    #[ORM\Column(name: 'localisation', length: 255)]
    #[Assert\NotBlank(message: 'La localisation est obligatoire.')]
    #[Assert\Length(
        min: 2,
        minMessage: 'La localisation doit contenir au moins 2 caractères.'
    )]
    private ?string $localisation = null;

    #[ORM\Column(name: 'pays', length: 100, nullable: true)]
    private ?string $pays = null;

    #[ORM\Column(name: 'description', type: Types::TEXT, nullable: true)]
    #[Assert\NotBlank(message: 'La description est obligatoire.')]
    #[Assert\Length(
        min: 10,
        minMessage: 'La description doit contenir au moins 10 caractères.'
    )]
    private ?string $description = null;

    #[ORM\Column(name: 'prix_par_nuit', type: Types::FLOAT, nullable: true)]
    #[Assert\NotNull(message: 'Le prix par nuit est obligatoire.')]
    #[Assert\PositiveOrZero(message: 'Le prix par nuit ne peut pas être négatif.')]
    private ?float $prixParNuit = null;

    #[ORM\Column(name: 'capacite', type: Types::INTEGER, nullable: true)]
    #[Assert\NotNull(message: 'La capacité est obligatoire.')]
    #[Assert\Positive(message: 'La capacité doit être supérieure à 0.')]
    private ?int $capacite = null;

    #[ORM\Column(name: 'latitude', type: Types::FLOAT, nullable: true)]
    private ?float $latitude = null;

    #[ORM\Column(name: 'longitude', type: Types::FLOAT, nullable: true)]
    private ?float $longitude = null;

    #[ORM\Column(name: 'note_moyenne', type: Types::FLOAT, nullable: true)]
    private ?float $noteMoyenne = null;

    #[ORM\Column(name: 'date_creation', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(name: 'image_path', length: 255, nullable: true)]
    private ?string $imagePath = null;

    #[Vich\UploadableField(mapping: 'hebergement_images', fileNameProperty: 'imagePath')]
    private ?File $imageFile = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(name: 'special_couple', type: Types::BOOLEAN, options: ['default' => false])]
    private ?bool $specialCouple = false;

    #[ORM\Column(name: 'under18_allowed', type: Types::BOOLEAN, options: ['default' => false])]
    private ?bool $under18Allowed = false;

    #[ORM\Column(name: 'sea_view', type: Types::BOOLEAN, options: ['default' => false])]
    private ?bool $seaView = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getLocalisation(): ?string
    {
        return $this->localisation;
    }

    public function setLocalisation(string $localisation): static
    {
        $this->localisation = $localisation;
        return $this;
    }

    public function getPays(): ?string
    {
        return $this->pays;
    }

    public function setPays(?string $pays): static
    {
        $this->pays = $pays;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getPrixParNuit(): ?float
    {
        return $this->prixParNuit;
    }

    public function setPrixParNuit(?float $prixParNuit): static
    {
        $this->prixParNuit = $prixParNuit;
        return $this;
    }

    public function getCapacite(): ?int
    {
        return $this->capacite;
    }

    public function setCapacite(?int $capacite): static
    {
        $this->capacite = $capacite;
        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): static
    {
        $this->latitude = $latitude;
        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): static
    {
        $this->longitude = $longitude;
        return $this;
    }

    public function getNoteMoyenne(): ?float
    {
        return $this->noteMoyenne;
    }

    public function setNoteMoyenne(?float $noteMoyenne): static
    {
        $this->noteMoyenne = $noteMoyenne;
        return $this;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(?\DateTimeInterface $dateCreation): static
    {
        $this->dateCreation = $dateCreation;
        return $this;
    }

    public function getImagePath(): ?string
    {
        return $this->imagePath;
    }

    public function setImagePath(?string $imagePath): static
    {
        $this->imagePath = $imagePath;
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->imagePath;
    }

    public function setImage(?string $image): static
    {
        $this->imagePath = $image;
        return $this;
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function setImageFile(?File $imageFile = null): void
    {
        $this->imageFile = $imageFile;

        if ($imageFile !== null) {
            $this->updatedAt = new \DateTime();
        }
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function isSpecialCouple(): ?bool
    {
        return $this->specialCouple;
    }

    public function getSpecialCouple(): ?bool
    {
        return $this->specialCouple;
    }

    public function setSpecialCouple(bool $specialCouple): static
    {
        $this->specialCouple = $specialCouple;
        return $this;
    }

    public function isUnder18Allowed(): ?bool
    {
        return $this->under18Allowed;
    }

    public function getUnder18Allowed(): ?bool
    {
        return $this->under18Allowed;
    }

    public function setUnder18Allowed(bool $under18Allowed): static
    {
        $this->under18Allowed = $under18Allowed;
        return $this;
    }

    public function isSeaView(): ?bool
    {
        return $this->seaView;
    }

    public function getSeaView(): ?bool
    {
        return $this->seaView;
    }

    public function setSeaView(bool $seaView): static
    {
        $this->seaView = $seaView;
        return $this;
    }
}