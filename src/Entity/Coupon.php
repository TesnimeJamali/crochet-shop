<?php

namespace App\Entity;

use App\Repository\CouponRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CouponRepository::class)]
class Coupon
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    #[ORM\Column(type: 'string', unique: true)]
    private ?string $code;

    #[ORM\Column(type: 'float')]
    private ?float $discount;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTime $validUntil;

    public function isValid(): bool
    {
        return new \DateTime() <= $this->validUntil;
    }


    public function getId(): ?int
    {
        return $this->id;
    }
    public function getCode(): ?string{
        return $this->code;
    }
    public function setCode(string $code): self{
        $this->code = $code;
        return $this;
    }
    public function getDiscount(): ?float{
        return $this->discount;
    }
    public function setDiscount(float $discount): self{
        $this->discount = $discount;
        return $this;
    }
    public function getValidUntil(): ?\DateTime{
        return $this->validUntil;
    }
    public function setValidUntil(\DateTime $validUntil): self{
        $this->validUntil = $validUntil;
        return $this;
    }

}
