<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

final class CartController extends AbstractController
{
    #[Route('/cart', name: 'app_cart')]
    public function index(SessionInterface $session,ProductRepository $productRepository): Response
    {   $panier = $session->get('panier', []);
        $total=$session->get('total', 0);
        foreach ($panier as $id => $quantite) {
            $product = $productRepository->find($id);
            $info_panier[] = [
                "produit" => $product,
                "quantite" => $quantite
            ];
            $total = $total + ($product->getPrice()) * $quantite;
        }

        return $this->render('cart/index.html.twig', [
            'controller_name' => 'CartController',"info_panier"=>$info_panier,"total"=>$total,
        ]);
    }

    #[Route('/add/{id}', name: 'add')]
    public function add(Product $product,SessionInterface $session): Response{
        $panier = $session->get('panier', []);
        $id=$product->getId();
        if(isset($panier[$id])){
            $panier[$id]++;
        }
        else{
            $panier[$id] = 1;
        }
        $session->set('panier', $panier);
        return $this->redirectToRoute('app_cart',['id'=>$id]);

    }

}
