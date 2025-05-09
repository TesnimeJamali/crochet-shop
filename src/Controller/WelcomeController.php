<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Repository\ImageCarouselRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Product;
class WelcomeController extends AbstractController
{
    #[Route('/', name: 'app_welcome')]
    public function index(ProductRepository $productRepository,ImageCarouselRepository $imageCarouselRepository): Response
    {
        $products = $productRepository->findAll();
        $carouselImages = $imageCarouselRepository->findAll();
        return $this->render('welcome/index.html.twig', [
            'products' => $products,'carouselImages' => $carouselImages
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
