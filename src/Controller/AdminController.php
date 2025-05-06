<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/products')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'admin_products')]
    public function index(ProductRepository $productRepository): Response
    {
        $products = $productRepository->findAll();
        return $this->render('admin/products.html.twig', ['products' => $products]);
    }

    #[Route('/new', name: 'admin_product_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($product);
            $em->flush();
            return $this->redirectToRoute('admin_products');
        }

        return $this->render('admin/new.html.twig', ['form' => $form->createView()]);
    }

    #[Route('/{id}/edit', name: 'admin_product_edit')]
    public function edit(Product $product, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ProductType::class, $product);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('admin_products');
        }

        return $this->render('admin/edit.html.twig', ['form' => $form->createView(), 'product' => $product]);
    }

    #[Route('/{id}/delete', name: 'admin_product_delete')]
    public function delete(Product $product, EntityManagerInterface $em): Response
    {
        $em->remove($product);
        $em->flush();
        return $this->redirectToRoute('admin_products');
    }
}
