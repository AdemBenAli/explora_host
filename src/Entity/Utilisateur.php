<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\UtilisateurRepository::class)]
#[ORM\Table(name: '`utilisateur`')]
class Utilisateur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, nullable: false)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $bio = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: false, name: 'dateCreation')]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(type: Types::STRING, nullable: false)]
    private ?string $email = null;

    #[ORM\Column(type: Types::STRING, nullable: false, name: 'estVerifie')]
    private ?string $estVerifie = null;

    #[ORM\Column(type: Types::STRING, nullable: false, name: 'motDePasse')]
    private ?string $motDePasse = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $nationalite = null;

    #[ORM\Column(type: Types::STRING, nullable: false)]
    private ?string $nom = null;

    #[ORM\Column(type: Types::STRING, nullable: true, name: 'photoDeProfil')]
    private ?string $photoDeProfil = null;

    #[ORM\Column(type: Types::STRING, nullable: false)]
    private ?string $prenom = null;

    #[ORM\Column(type: Types::STRING, nullable: false)]
    private ?string $role = null;

    #[ORM\Column(type: Types::STRING, nullable: false)]
    private ?string $statut = null;

    #[ORM\Column(type: Types::INTEGER, nullable: false)]
    private ?int $telephone = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $adresse = null;

    #[ORM\Column(type: Types::STRING, nullable: true, name: 'codePostale')]
    private ?string $codePostale = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, name: 'dateNaissance')]
    private ?\DateTimeInterface $dateNaissance = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $pays = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $ville = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): static
    {
        $this->bio = $bio;
        return $this;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeInterface $dateCreation): static
    {
        $this->dateCreation = $dateCreation;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getEstVerifie(): ?string
    {
        return $this->estVerifie;
    }

    public function setEstVerifie(string $estVerifie): static
    {
        $this->estVerifie = $estVerifie;
        return $this;
    }

    public function getMotDePasse(): ?string
    {
        return $this->motDePasse;
    }

    public function setMotDePasse(string $motDePasse): static
    {
        $this->motDePasse = $motDePasse;
        return $this;
    }

    public function getNationalite(): ?string
    {
        return $this->nationalite;
    }

    public function setNationalite(?string $nationalite): static
    {
        $this->nationalite = $nationalite;
        return $this;
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

    public function getPhotoDeProfil(): ?string
    {
        return $this->photoDeProfil;
    }

    public function setPhotoDeProfil(?string $photoDeProfil): static
    {
        $this->photoDeProfil = $photoDeProfil;
        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;
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

    public function getTelephone(): ?int
    {
        return $this->telephone;
    }

    public function setTelephone(?int $telephone): static
    {
        $this->telephone = $telephone;
        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(?string $adresse): static
    {
        $this->adresse = $adresse;
        return $this;
    }

    public function getCodePostale(): ?string
    {
        return $this->codePostale;
    }

    public function setCodePostale(?string $codePostale): static
    {
        $this->codePostale = $codePostale;
        return $this;
    }

    public function getDateNaissance(): ?\DateTimeInterface
    {
        return $this->dateNaissance;
    }

    public function setDateNaissance(?\DateTimeInterface $dateNaissance): static
    {
        $this->dateNaissance = $dateNaissance;
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

    public function getVille(): ?string
    {
        return $this->ville;
    }

    public function setVille(?string $ville): static
    {
        $this->ville = $ville;
        return $this;
    }

}
