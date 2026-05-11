<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Auto-generated entity for table 'promo_code'
 * Generated: 2026-04-06 16:08:09
 */
#[ORM\Entity]
#[ORM\Table(name: 'promo_code')]
class PromoCode
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'code', type: 'string', nullable: false)]
    private ?string $code = null;

    #[ORM\Column(name: 'created_at', type: 'datetime', nullable: true)]
    private ?\DateTime $createdAt = null;

    #[ORM\Column(name: 'discount_percent', type: 'integer', nullable: false)]
    private ?int $discountPercent = null;

    #[ORM\Column(name: 'panier_id', type: 'integer', nullable: true)]
    private ?int $panierId = null;

    #[ORM\Column(name: 'is_used', type: 'boolean', nullable: false)]
    private ?bool $isUsed = null;

    #[ORM\Column(name: 'used_at', type: 'datetime', nullable: true)]
    private ?\DateTime $usedAt = null;

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

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): self
    {
        $this->code = $code;
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

    public function getDiscountPercent(): ?int
    {
        return $this->discountPercent;
    }

    public function setDiscountPercent(?int $discountPercent): self
    {
        $this->discountPercent = $discountPercent;
        return $this;
    }

    public function getPanierId(): ?int
    {
        return $this->panierId;
    }

    public function setPanierId(?int $panierId): self
    {
        $this->panierId = $panierId;
        return $this;
    }

    public function getIsUsed(): ?bool
    {
        return $this->isUsed;
    }

    public function setIsUsed(?bool $isUsed): self
    {
        $this->isUsed = $isUsed;
        return $this;
    }

    public function getUsedAt(): ?\DateTime
    {
        return $this->usedAt;
    }

    public function setUsedAt(?\DateTime $usedAt): self
    {
        $this->usedAt = $usedAt;
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

    public function isUsed(): ?bool
    {
        return $this->isUsed;
    }

}