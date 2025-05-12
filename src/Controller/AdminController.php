<?php
namespace App\Controller;
use App\Controller\ImageCarouselController;
use App\Entity\Product;
use App\Entity\ImageCarousel;
use App\Form\ProductType;
use App\Form\ImageCarouselForm;
use App\Form\ImageCarouselTypeForm;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\ImageCarouselRepository;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use App\Repository\AlerteStockRepository;


#[Route('/admin')]
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
            // Handle file upload and setting image path
            $em->persist($product);
            $em->flush();
            return $this->redirectToRoute('admin_products');
        }

        return $this->render('admin/new.html.twig', ['form' => $form->createView()]);
    }

    #[Route('/{id}/edit', name: 'admin_product_edit')]
    public function edit(
        Product $product,
        Request $request,
        EntityManagerInterface $em,
        AlerteStockRepository $alerteStockRepository,
        MailerInterface $mailer
    ): Response {
        $oldQuantity = $product->getQuantity();

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newQuantity = $product->getQuantity();

            if ($oldQuantity == 0 && $newQuantity > 0) {
                $alertes = $alerteStockRepository->findBy(['product' => $product]);

                foreach ($alertes as $alerte) {
                    $email = (new Email())
                        ->from(new Address('projetweb521@gmail.com', 'Crochet Shop'))
                        ->to(new Address($alerte->getEmail(), 'Destinataire'))
                        ->subject('Produit à nouveau en stock !')
                        ->html("
                        <p>Bonjour,</p>
                        <p>Le produit <strong>{$product->getName()}</strong> est à nouveau disponible.</p>
                        <p><a href='https://crochetdor.com/produit/{$product->getId()}'>Voir le produit</a></p>
                    ");

                    $mailer->send($email);
                    $em->remove($alerte);
                }
            }

            $em->flush();
            $this->addFlash('success', 'Produit modifié avec succès.');
            return $this->redirectToRoute('admin_products');
        }

        return $this->render('admin/edit.html.twig', [
            'form' => $form->createView(),
            'product' => $product,
        ]);
    }



    #[Route('/{id}/delete', name: 'admin_product_delete')]
    public function delete(Product $product, EntityManagerInterface $em): Response
    {
        $em->remove($product);
        $em->flush();
        return $this->redirectToRoute('admin_products');
    }
    #[Route('/carousel/upload', name: 'admin_carousel_upload')]
    public function uploadCarousel(Request $request, EntityManagerInterface $em, ImageCarouselRepository $repo): Response
    {
        $carouselImage = new ImageCarousel();
        $form = $this->createForm(ImageCarouselTypeForm::class, $carouselImage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($carouselImage);
            $em->flush();
            $this->addFlash('success', 'Image ajoutée au carrousel.');
            return $this->redirectToRoute('admin_carousel_upload');
        }

        // ✅ Fetch all existing carousel images from DB
        $carouselImages = $repo->findAll();

        return $this->render('admin/carousel_upload.html.twig', [
            'form' => $form->createView(),
            'carouselImages' => $carouselImages, // ✅ Pass list
        ]);
    }
    #[Route('/carousel/{id}/delete', name: 'admin_carousel_delete', methods: ['POST'])]
    public function deleteCarouselImage(
        ImageCarousel $image,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $image->getId(), $request->request->get('_token'))) {
            // Optional: remove the image file from the server
            $imagePath = $this->getParameter('carousel_directory') . '/' . $image->getImageName();
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }

            $em->remove($image);
            $em->flush();

            $this->addFlash('success', 'Image supprimée du carrousel.');
        }

        return $this->redirectToRoute('admin_carousel_upload');
    }
}
