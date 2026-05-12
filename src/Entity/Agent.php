<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: '`agent`')]
class Agent
{
    #[ORM\Column(type: Types::STRING, nullable: false, name: 'adresseAgence')]
    private ?string $adresseAgence = null;

    #[ORM\Column(type: Types::STRING, nullable: true, name: 'codePostalAgence')]
    private ?string $codePostalAgence = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: false, name: 'dateEnregistrement')]
    private ?\DateTimeInterface $dateEnregistrement = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: false, name: 'dateSoumission')]
    private ?\DateTimeInterface $dateSoumission = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, name: 'dateSuspension')]
    private ?\DateTimeInterface $dateSuspension = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, name: 'dateValidation')]
    private ?\DateTimeInterface $dateValidation = null;

    #[ORM\Column(type: Types::STRING, nullable: true, name: 'descriptionAgence')]
    private ?string $descriptionAgence = null;

    #[ORM\Column(type: Types::STRING, nullable: true, name: 'docAssuranceUrl')]
    private ?string $docAssuranceUrl = null;

    #[ORM\Column(type: Types::STRING, nullable: false, name: 'docJustificatifAdresseUrl')]
    private ?string $docJustificatifAdresseUrl = null;

    #[ORM\Column(type: Types::STRING, nullable: false, name: 'docLicenceAgenceUrl')]
    private ?string $docLicenceAgenceUrl = null;

    #[ORM\Column(type: Types::STRING, nullable: false, name: 'docMatriculeFiscalUrl')]
    private ?string $docMatriculeFiscalUrl = null;

    #[ORM\Column(type: Types::STRING, nullable: false, name: 'docPieceIdentiteRectoUrl')]
    private ?string $docPieceIdentiteRectoUrl = null;

    #[ORM\Column(type: Types::STRING, nullable: false, name: 'docPieceIdentiteVersoUrl')]
    private ?string $docPieceIdentiteVersoUrl = null;

    #[ORM\Column(type: Types::STRING, nullable: false, name: 'docRegistreCommerceUrl')]
    private ?string $docRegistreCommerceUrl = null;

    #[ORM\Column(type: Types::STRING, nullable: false, name: 'docRibOuReleveBancaireUrl')]
    private ?string $docRibOuReleveBancaireUrl = null;

    #[ORM\Column(type: Types::STRING, nullable: false, name: 'emailAgence')]
    private ?string $emailAgence = null;

    #[ORM\Column(type: Types::STRING, nullable: false, name: 'estSuspendu')]
    private ?string $estSuspendu = null;

    #[ORM\Column(type: Types::STRING, nullable: true, name: 'logoUrl')]
    private ?string $logoUrl = null;

    #[ORM\Column(type: Types::STRING, nullable: false, name: 'nomAgence')]
    private ?string $nomAgence = null;

    #[ORM\Column(type: Types::STRING, nullable: false, name: 'nomLegalAgence')]
    private ?string $nomLegalAgence = null;

    #[ORM\Column(type: Types::STRING, nullable: true, name: 'notesAdmin')]
    private ?string $notesAdmin = null;

    #[ORM\Column(type: Types::STRING, nullable: false, name: 'numeroFiscal')]
    private ?string $numeroFiscal = null;

    #[ORM\Column(type: Types::STRING, nullable: false, name: 'numeroLicenceAgence')]
    private ?string $numeroLicenceAgence = null;

    #[ORM\Column(type: Types::STRING, nullable: false, name: 'numeroRegistreCommerce')]
    private ?string $numeroRegistreCommerce = null;

    #[ORM\Column(type: Types::STRING, nullable: false, name: 'paysAgence')]
    private ?string $paysAgence = null;

    #[ORM\Column(type: Types::STRING, nullable: true, name: 'raisonRefus')]
    private ?string $raisonRefus = null;

    #[ORM\Column(type: Types::STRING, nullable: true, name: 'raisonSuspension')]
    private ?string $raisonSuspension = null;

    #[ORM\Column(type: Types::STRING, nullable: true, name: 'siteWebUrl')]
    private ?string $siteWebUrl = null;

    #[ORM\Column(type: Types::STRING, nullable: false, name: 'statutVerification')]
    private ?string $statutVerification = null;

    #[ORM\Column(type: Types::STRING, nullable: false, name: 'telephoneAgence')]
    private ?string $telephoneAgence = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true, name: 'valideParAdminId')]
    private ?int $valideParAdminId = null;

    #[ORM\Column(type: Types::STRING, nullable: false, name: 'villeAgence')]
    private ?string $villeAgence = null;

    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER, nullable: false)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private ?int $id = null;

    public function getAdresseAgence(): ?string
    {
        return $this->adresseAgence;
    }

    public function setAdresseAgence(string $adresseAgence): static
    {
        $this->adresseAgence = $adresseAgence;
        return $this;
    }

    public function getCodePostalAgence(): ?string
    {
        return $this->codePostalAgence;
    }

    public function setCodePostalAgence(?string $codePostalAgence): static
    {
        $this->codePostalAgence = $codePostalAgence;
        return $this;
    }

    public function getDateEnregistrement(): \DateTimeInterface
    {
        return $this->dateEnregistrement;
    }

    public function setDateEnregistrement(\DateTimeInterface $dateEnregistrement): static
    {
        $this->dateEnregistrement = $dateEnregistrement;
        return $this;
    }

    public function getDateSoumission(): \DateTimeInterface
    {
        return $this->dateSoumission;
    }

    public function setDateSoumission(\DateTimeInterface $dateSoumission): static
    {
        $this->dateSoumission = $dateSoumission;
        return $this;
    }

    public function getDateSuspension(): ?\DateTimeInterface
    {
        return $this->dateSuspension;
    }

    public function setDateSuspension(?\DateTimeInterface $dateSuspension): static
    {
        $this->dateSuspension = $dateSuspension;
        return $this;
    }

    public function getDateValidation(): ?\DateTimeInterface
    {
        return $this->dateValidation;
    }

    public function setDateValidation(?\DateTimeInterface $dateValidation): static
    {
        $this->dateValidation = $dateValidation;
        return $this;
    }

    public function getDescriptionAgence(): ?string
    {
        return $this->descriptionAgence;
    }

    public function setDescriptionAgence(?string $descriptionAgence): static
    {
        $this->descriptionAgence = $descriptionAgence;
        return $this;
    }

    public function getDocAssuranceUrl(): ?string
    {
        return $this->docAssuranceUrl;
    }

    public function setDocAssuranceUrl(?string $docAssuranceUrl): static
    {
        $this->docAssuranceUrl = $docAssuranceUrl;
        return $this;
    }

    public function getDocJustificatifAdresseUrl(): ?string
    {
        return $this->docJustificatifAdresseUrl;
    }

    public function setDocJustificatifAdresseUrl(string $docJustificatifAdresseUrl): static
    {
        $this->docJustificatifAdresseUrl = $docJustificatifAdresseUrl;
        return $this;
    }

    public function getDocLicenceAgenceUrl(): ?string
    {
        return $this->docLicenceAgenceUrl;
    }

    public function setDocLicenceAgenceUrl(string $docLicenceAgenceUrl): static
    {
        $this->docLicenceAgenceUrl = $docLicenceAgenceUrl;
        return $this;
    }

    public function getDocMatriculeFiscalUrl(): ?string
    {
        return $this->docMatriculeFiscalUrl;
    }

    public function setDocMatriculeFiscalUrl(string $docMatriculeFiscalUrl): static
    {
        $this->docMatriculeFiscalUrl = $docMatriculeFiscalUrl;
        return $this;
    }

    public function getDocPieceIdentiteRectoUrl(): ?string
    {
        return $this->docPieceIdentiteRectoUrl;
    }

    public function setDocPieceIdentiteRectoUrl(string $docPieceIdentiteRectoUrl): static
    {
        $this->docPieceIdentiteRectoUrl = $docPieceIdentiteRectoUrl;
        return $this;
    }

    public function getDocPieceIdentiteVersoUrl(): ?string
    {
        return $this->docPieceIdentiteVersoUrl;
    }

    public function setDocPieceIdentiteVersoUrl(string $docPieceIdentiteVersoUrl): static
    {
        $this->docPieceIdentiteVersoUrl = $docPieceIdentiteVersoUrl;
        return $this;
    }

    public function getDocRegistreCommerceUrl(): ?string
    {
        return $this->docRegistreCommerceUrl;
    }

    public function setDocRegistreCommerceUrl(string $docRegistreCommerceUrl): static
    {
        $this->docRegistreCommerceUrl = $docRegistreCommerceUrl;
        return $this;
    }

    public function getDocRibOuReleveBancaireUrl(): ?string
    {
        return $this->docRibOuReleveBancaireUrl;
    }

    public function setDocRibOuReleveBancaireUrl(string $docRibOuReleveBancaireUrl): static
    {
        $this->docRibOuReleveBancaireUrl = $docRibOuReleveBancaireUrl;
        return $this;
    }

    public function getEmailAgence(): ?string
    {
        return $this->emailAgence;
    }

    public function setEmailAgence(string $emailAgence): static
    {
        $this->emailAgence = $emailAgence;
        return $this;
    }

    public function getEstSuspendu(): ?string
    {
        return $this->estSuspendu;
    }

    public function setEstSuspendu(string $estSuspendu): static
    {
        $this->estSuspendu = $estSuspendu;
        return $this;
    }

    public function getLogoUrl(): ?string
    {
        return $this->logoUrl;
    }

    public function setLogoUrl(?string $logoUrl): static
    {
        $this->logoUrl = $logoUrl;
        return $this;
    }

    public function getNomAgence(): ?string
    {
        return $this->nomAgence;
    }

    public function setNomAgence(string $nomAgence): static
    {
        $this->nomAgence = $nomAgence;
        return $this;
    }

    public function getNomLegalAgence(): ?string
    {
        return $this->nomLegalAgence;
    }

    public function setNomLegalAgence(string $nomLegalAgence): static
    {
        $this->nomLegalAgence = $nomLegalAgence;
        return $this;
    }

    public function getNotesAdmin(): ?string
    {
        return $this->notesAdmin;
    }

    public function setNotesAdmin(?string $notesAdmin): static
    {
        $this->notesAdmin = $notesAdmin;
        return $this;
    }

    public function getNumeroFiscal(): ?string
    {
        return $this->numeroFiscal;
    }

    public function setNumeroFiscal(string $numeroFiscal): static
    {
        $this->numeroFiscal = $numeroFiscal;
        return $this;
    }

    public function getNumeroLicenceAgence(): ?string
    {
        return $this->numeroLicenceAgence;
    }

    public function setNumeroLicenceAgence(string $numeroLicenceAgence): static
    {
        $this->numeroLicenceAgence = $numeroLicenceAgence;
        return $this;
    }

    public function getNumeroRegistreCommerce(): ?string
    {
        return $this->numeroRegistreCommerce;
    }

    public function setNumeroRegistreCommerce(string $numeroRegistreCommerce): static
    {
        $this->numeroRegistreCommerce = $numeroRegistreCommerce;
        return $this;
    }

    public function getPaysAgence(): ?string
    {
        return $this->paysAgence;
    }

    public function setPaysAgence(string $paysAgence): static
    {
        $this->paysAgence = $paysAgence;
        return $this;
    }

    public function getRaisonRefus(): ?string
    {
        return $this->raisonRefus;
    }

    public function setRaisonRefus(?string $raisonRefus): static
    {
        $this->raisonRefus = $raisonRefus;
        return $this;
    }

    public function getRaisonSuspension(): ?string
    {
        return $this->raisonSuspension;
    }

    public function setRaisonSuspension(?string $raisonSuspension): static
    {
        $this->raisonSuspension = $raisonSuspension;
        return $this;
    }

    public function getSiteWebUrl(): ?string
    {
        return $this->siteWebUrl;
    }

    public function setSiteWebUrl(?string $siteWebUrl): static
    {
        $this->siteWebUrl = $siteWebUrl;
        return $this;
    }

    public function getStatutVerification(): ?string
    {
        return $this->statutVerification;
    }

    public function setStatutVerification(string $statutVerification): static
    {
        $this->statutVerification = $statutVerification;
        return $this;
    }

    public function getTelephoneAgence(): ?string
    {
        return $this->telephoneAgence;
    }

    public function setTelephoneAgence(string $telephoneAgence): static
    {
        $this->telephoneAgence = $telephoneAgence;
        return $this;
    }

    public function getValideParAdminId(): ?int
    {
        return $this->valideParAdminId;
    }

    public function setValideParAdminId(?int $valideParAdminId): static
    {
        $this->valideParAdminId = $valideParAdminId;
        return $this;
    }

    public function getVilleAgence(): ?string
    {
        return $this->villeAgence;
    }

    public function setVilleAgence(string $villeAgence): static
    {
        $this->villeAgence = $villeAgence;
        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;
        return $this;
    }

}
