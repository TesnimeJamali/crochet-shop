<?php
namespace App\Controller;


use App\Controller\ImageCarouselController;
use App\Entity\Cart;
use App\Entity\Coupon;
use App\Entity\Order;
use App\Entity\Product;
use App\Entity\ImageCarousel;
use App\Entity\User;
use App\Form\AddCouponFormType;
use App\Form\ProductType;
use App\Form\ImageCarouselTypeForm;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\ReviewRepository;
use App\Repository\UserRepository;
use App\Service\Payment;
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
use Symfony\Component\Security\Http\Attribute\IsGranted; // Import IsGranted
use Symfony\Component\Security\Core\Exception\AccessDeniedException; // Import AccessDeniedException
use Symfony\Component\Form\FormError;


#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'admin_products')]
    public function index(ProductRepository $productRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $products = $productRepository->findAll();
        return $this->render('admin/products.html.twig', ['products' => $products]);
    }

    #[Route('/new', name: 'admin_product_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
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
    #[Route('/reviews', name: 'admin_reviews')]
    public function showReviews(ReviewRepository $reviewRepo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $reviews = $reviewRepo->findBy([], ['createdAt' => 'DESC']);
        return $this->render('admin/reviews.html.twig', [
            'reviews' => $reviews,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_product_edit')]
    public function edit(
        Product $product,
        Request $request,
        EntityManagerInterface $em,
        AlerteStockRepository $alerteStockRepository,
        MailerInterface $mailer
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

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
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $em->remove($product);
        $em->flush();
        return $this->redirectToRoute('admin_products');
    }
    #[Route('/carousel/upload', name: 'admin_carousel_upload')]
    public function uploadCarousel(Request $request, EntityManagerInterface $em, ImageCarouselRepository $repo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $carouselImage = new ImageCarousel();
        $form = $this->createForm(ImageCarouselTypeForm::class, $carouselImage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($carouselImage);
            $em->flush();
            $this->addFlash('success', 'Image ajoutée au carrousel.');
            return $this->redirectToRoute('admin_carousel_upload');
        }

        $carouselImages = $repo->findAll();

        return $this->render('admin/carousel_upload.html.twig', [
            'form' => $form->createView(),
            'carouselImages' => $carouselImages,
        ]);
    }
    #[Route('/carousel/{id}/delete', name: 'admin_carousel_delete', methods: ['POST'])]
    public function deleteCarouselImage(
        ImageCarousel $image,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
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
    #[Route('/coupon', name: 'ajout_coupon')]
    public function coupon(Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $coupon = new Coupon();
        $form = $this->createForm(AddCouponFormType::class, $coupon);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($coupon);
            $em->flush();
            $this->addFlash('success', 'coupon ajouté avec succés.');
            return $this->redirectToRoute('admin_products');
        }

        return $this->render('admin/coupon.html.twig', ['form' => $form->createView()]);
    }
    #[Route('/admin/coupon/delete', name: 'admin_coupon_delete')]
    public function deleteByCode(Request $request, EntityManagerInterface $em): Response
    {
        $code = $request->query->get('code');
        if ($code) {
            $coupon = $em->getRepository(Coupon::class)->findOneBy(['code' => $code]);
            if (!$coupon) {
                $this->addFlash('erreurcoupon', "Aucun coupon trouvé avec le code '$code'.");
            } else {
                $usedInCarts = $em->getRepository(Cart::class)->count(['coupon' => $coupon]);
                if ($usedInCarts > 0) {
                    $this->addFlash('erreurcoupon', "Impossible de supprimer : ce coupon est utilisé dans $usedInCarts panier(s).");
                } else {
                    $em->remove($coupon);
                    $em->flush();
                    $this->addFlash('suppressioncoupon', "Le coupon '$code' a été supprimé avec succès.");
                }
            }
            return $this->redirectToRoute('admin_coupon_delete');
        }
        return $this->render('admin/coupon/delete.html.twig');
    }


    #[Route('/clients', name: 'app_clients')]
    public function nosClients(Request $request, UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $allUsers = $userRepository->findAll();

        $clients = array_filter($allUsers, function(User $user) {
            return in_array('ROLE_USER', $user->getRoles()) && count($user->getRoles()) === 1;
        });
        return $this->render('admin/nosClients.html.twig', ['clients'=>$clients]);
    }
    #[Route('/OrdersValidat', name: 'app_orders_admin')]
    public function OrdersValidation(OrderRepository $orderRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $orders = $orderRepository->findBy(['status' => 'pending', 'paymentMethod'=>'cash'], ['createdAt' => 'ASC']);
        return $this->render('order/ordersTableValid.html.twig', [
            'orders' => $orders
        ]);

    }
    #[Route('/annulerCmdAdmin/{order}' , name: 'app_annuler_cmd_admin')]
    public function annulerCmd(Order $order,EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');


        if ($order) {
            $order->setStatus('canceled');
            $entityManager->persist($order);
            $entityManager->flush();
        }
        return $this->redirectToRoute('app_orders_admin');
    }

    #[Route('/validerCmdAdmin/{order}' , name: 'app_valider_cmd_admin')]
    public function validerCmd(Order $order,EntityManagerInterface $entityManager,Payment $payment): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');


        if ($order) {
            $order->setStatus('paid');
            $entityManager->persist($order);
            $entityManager->flush();
            $payment->gererStock($order);
        }
        return $this->redirectToRoute('app_orders_admin');
    }
    #[Route('/reviews/delete/{id}', name: 'review_delete', methods: ['POST'])]
    public function deleterev($id, Request $request, EntityManagerInterface $em, ReviewRepository $reviewRepository): Response
    {
        $review = $reviewRepository->find($id);
        if (!$review) {
            $this->addFlash('error', 'Avis non trouvé.');
            return $this->redirectToRoute('admin_reviews');
        }

        if ($this->isCsrfTokenValid('delete'.$id, $request->request->get('_token'))) {
            $em->remove($review);
            $em->flush();
            $this->addFlash('success', 'Avis supprimé avec succès.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('admin_reviews');
    }

    #[Route('/reviews/delete_all', name: 'review_delete_all', methods: ['POST'])]
    public function deleteAll(Request $request, EntityManagerInterface $em, ReviewRepository $reviewRepository): Response
    {
        if ($this->isCsrfTokenValid('delete_all', $request->request->get('_token'))) {
            $reviews = $reviewRepository->findAll();

            foreach ($reviews as $review) {
                $em->remove($review);
            }
            $em->flush();

            $this->addFlash('success', 'Tous les avis ont été supprimés.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('admin_reviews');
    }
}
