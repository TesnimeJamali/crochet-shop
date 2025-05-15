<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\Request; // ✅ THIS is the correct import
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Product;
#[Route('/shop')]
class ShopController extends AbstractController
{
    #[Route('/', name: 'shop')]
    public function index(Request $request, ProductRepository $productRepository): Response
    {
        $search = $request->query->get('q');

        $products = $search
            ? $productRepository->createQueryBuilder('p')
                ->where('p.name LIKE :search')
                ->setParameter('search', '%' . $search . '%')
                ->getQuery()
                ->getResult()
            : $productRepository->findAll();

        return $this->render('shop/index.html.twig', [
            'products' => $products,
            'search' => $search,
        ]);
    }
    #[Route(path: '/product/{id}', name: 'shop_product_details')]
    public function showDetails(Product $product): Response
    {
        return $this->render('product/details.html.twig', [
            'product' => $product,
        ]);
    }
}
