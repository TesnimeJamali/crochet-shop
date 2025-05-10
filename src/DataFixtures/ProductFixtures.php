<?php

namespace App\DataFixtures;

use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ProductFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $product = new Product();
            $product->setName('Produit ' . $i);
            $product->setDescription('Description du produit ' . $i);
            $product->setPrice(mt_rand(10, 100));
            $product->setImageName('default.jpg');
            $product->setQuantity(mt_rand(1, 10));
            $manager->persist($product);
        }

        $manager->flush();
    }
}
