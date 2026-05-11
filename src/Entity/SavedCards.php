<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Auto-generated entity for table 'saved_cards'
 * Generated: 2026-04-06 16:08:09
 */
#[ORM\Entity]
#[ORM\Table(name: 'saved_cards')]
class SavedCards
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'billing_address', type: 'string', nullable: true)]
    private ?string $billingAddress = null;

    #[ORM\Column(name: 'billing_city', type: 'string', nullable: true)]
    private ?string $billingCity = null;

    #[ORM\Column(name: 'billing_country', type: 'string', nullable: true)]
    private ?string $billingCountry = null;

    #[ORM\Column(name: 'billing_postal_code', type: 'string', nullable: true)]
    private ?string $billingPostalCode = null;

    #[ORM\Column(name: 'card_brand', type: 'string', nullable: true)]
    private ?string $cardBrand = null;

    #[ORM\Column(name: 'cardholder_name', type: 'string', nullable: false)]
    private ?string $cardholderName = null;

    #[ORM\Column(name: 'created_at', type: 'datetime', nullable: false)]
    private ?\DateTime $createdAt = null;

    #[ORM\Column(name: 'expiry_month', type: 'integer', nullable: false)]
    private ?int $expiryMonth = null;

    #[ORM\Column(name: 'expiry_year', type: 'integer', nullable: false)]
    private ?int $expiryYear = null;

    #[ORM\Column(name: 'is_default', type: 'boolean', nullable: false)]
    private ?bool $isDefault = null;

    #[ORM\Column(name: 'last_four_digits', type: 'string', nullable: false)]
    private ?string $lastFourDigits = null;

    #[ORM\Column(name: 'stripe_payment_method_id', type: 'string', nullable: true)]
    private ?string $stripePaymentMethodId = null;

    #[ORM\Column(name: 'updated_at', type: 'datetime', nullable: true)]
    private ?\DateTime $updatedAt = null;

    #[ORM\Column(name: 'user_id', type: 'integer', nullable: false)]
    private ?int $userId = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getBillingAddress(): ?string
    {
        return $this->billingAddress;
    }

    public function setBillingAddress(?string $billingAddress): self
    {
        $this->billingAddress = $billingAddress;
        return $this;
    }

    public function getBillingCity(): ?string
    {
        return $this->billingCity;
    }

    public function setBillingCity(?string $billingCity): self
    {
        $this->billingCity = $billingCity;
        return $this;
    }

    public function getBillingCountry(): ?string
    {
        return $this->billingCountry;
    }

    public function setBillingCountry(?string $billingCountry): self
    {
        $this->billingCountry = $billingCountry;
        return $this;
    }

    public function getBillingPostalCode(): ?string
    {
        return $this->billingPostalCode;
    }

    public function setBillingPostalCode(?string $billingPostalCode): self
    {
        $this->billingPostalCode = $billingPostalCode;
        return $this;
    }

    public function getCardBrand(): ?string
    {
        return $this->cardBrand;
    }

    public function setCardBrand(?string $cardBrand): self
    {
        $this->cardBrand = $cardBrand;
        return $this;
    }

    public function getCardholderName(): ?string
    {
        return $this->cardholderName;
    }

    public function setCardholderName(?string $cardholderName): self
    {
        $this->cardholderName = $cardholderName;
        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getExpiryMonth(): ?int
    {
        return $this->expiryMonth;
    }

    public function setExpiryMonth(?int $expiryMonth): self
    {
        $this->expiryMonth = $expiryMonth;
        return $this;
    }

    public function getExpiryYear(): ?int
    {
        return $this->expiryYear;
    }

    public function setExpiryYear(?int $expiryYear): self
    {
        $this->expiryYear = $expiryYear;
        return $this;
    }

    public function getIsDefault(): ?bool
    {
        return $this->isDefault;
    }

    public function setIsDefault(?bool $isDefault): self
    {
        $this->isDefault = $isDefault;
        return $this;
    }

    public function getLastFourDigits(): ?string
    {
        return $this->lastFourDigits;
    }

    public function setLastFourDigits(?string $lastFourDigits): self
    {
        $this->lastFourDigits = $lastFourDigits;
        return $this;
    }

    public function getStripePaymentMethodId(): ?string
    {
        return $this->stripePaymentMethodId;
    }

    public function setStripePaymentMethodId(?string $stripePaymentMethodId): self
    {
        $this->stripePaymentMethodId = $stripePaymentMethodId;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function isDefault(): ?bool
    {
        return $this->isDefault;
    }

}