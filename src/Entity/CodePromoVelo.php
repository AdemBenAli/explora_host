<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CodePromoVelo
 *
 * @ORM\Table(name="code_promo_velo", uniqueConstraints={@ORM\UniqueConstraint(name="unique_trajet", columns={"user_id", "origine", "destination"}), @ORM\UniqueConstraint(name="code", columns={"code"})}, indexes={@ORM\Index(name="idx_user", columns={"user_id"}), @ORM\Index(name="idx_code", columns={"code"})})
 * @ORM\Entity
 */
class CodePromoVelo
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="user_id", type="integer", nullable=false)
     */
    private $userId;

    /**
     * @var string
     *
     * @ORM\Column(name="origine", type="string", length=50, nullable=false)
     */
    private $origine;

    /**
     * @var string
     *
     * @ORM\Column(name="destination", type="string", length=50, nullable=false)
     */
    private $destination;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=20, nullable=false)
     */
    private $code;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="date_creation", type="datetime", nullable=true, options={"default"="current_timestamp()"})
     */
    private $dateCreation = 'current_timestamp()';

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="date_utilisation", type="datetime", nullable=true, options={"default"="NULL"})
     */
    private $dateUtilisation = 'NULL';

    /**
     * @var bool|null
     *
     * @ORM\Column(name="utilise", type="boolean", nullable=true)
     */
    private $utilise = '0';


}
