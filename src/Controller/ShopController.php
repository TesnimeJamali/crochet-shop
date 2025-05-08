<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Product;
class ShopController extends AbstractController
{
    #[Route('/shop', name: 'shop')]
    public function index(ProductRepository $productRepository): Response
    {
        $products = $productRepository->findAll();

        return $this->render('shop/index.html.twig', [
            'products' => $products,
        ]);
    }
    #[Route(path: '/product/{id}', name: 'product_details')]
    public function showDetails(Product $product): Response
    {
        return $this->render('product/details.html.twig', [
            'product' => $product,
        ]);
    }
}
