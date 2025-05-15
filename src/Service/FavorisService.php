<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;

class FavorisService
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function toggle(User $user, Product $product): bool
    {
        $favoris = $user->getFavoris();

        if ($favoris->contains($product)) {
            $favoris->removeElement($product);
            $isAdded = false;
        } else {
            $favoris->add($product);
            $isAdded = true;
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $isAdded;
    }
}