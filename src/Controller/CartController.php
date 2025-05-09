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
        return $this->redirectToRoute('app_cart');

    }
    #[Route('/delete/{id}', name: 'delete')]
    public function delete(Product $product, SessionInterface $session): Response
    {
        $id = $product->getId();
        $panier = $session->get('panier', []);
        if (array_key_exists($id, $panier)) {
            unset($panier[$id]);
            $session->set('panier', $panier);
            return $this->redirectToRoute('app_cart');
        } else{
            $session->set('panier', $panier);
            return $this->redirectToRoute('app_cart');
        }


    }


}
