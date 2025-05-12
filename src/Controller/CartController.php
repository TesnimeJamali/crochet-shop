<?php
namespace App\Controller;

use App\Entity\AlerteStock;
use App\Entity\Product;
use App\Form\AlerteStockTypeForm;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;



final class CartController extends AbstractController
{
    #[Route('/cart', name: 'app_cart')]
    public function index(SessionInterface $session, ProductRepository $productRepository): Response
    {
        $panier = $session->get('panier', []);
        $total = $session->get('total', 0);
        $info_panier = $session->get('info_panier', []);
        foreach ($panier as $id => $quantite) {
            $product = $productRepository->find($id);
            $info_panier[] = [
                "produit" => $product,
                "quantite" => $quantite
            ];
            $total = $total + ($product->getPrice()) * $quantite;
        }
        return $this->render('cart/index.html.twig', [
            'controller_name' => 'CartController', "info_panier" => $info_panier, "total" => $total,
        ]);
    }

    #[Route('/add/{id}', name: 'add')]
    public function add(Product $product, SessionInterface $session, Request $request): Response
    {
        $panier = $session->get('panier', []);
        $id = $product->getId();

        if (isset($panier[$id])) {
            if ($panier[$id] < $product->getQuantity()) {
                $panier[$id]++;
                $this->addFlash('success', sprintf('%s a été ajouté au panier.', $product->getName()));
            } else {
                $this->addFlash('warning', sprintf('Stock insuffisant pour %s.', $product->getName()));
            }
        } else {
            if ($product->getQuantity() > 0) {
                $panier[$id] = 1;
                $this->addFlash('success', sprintf('%s a été ajouté au panier.', $product->getName()));
            } else {
                $this->addFlash('warning', sprintf('%s est en rupture de stock.', $product->getName()));
            }
        }

        $session->set('panier', $panier);
        $referer = $request->headers->get('referer');

        return $this->redirect($referer ?: $this->generateUrl('app_cart'));
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
        } else {
            $session->set('panier', $panier);
            return $this->redirectToRoute('app_cart');
        }


    }

    #[Route('/remove/{id}', name: 'remove')]
    public function remove(Product $product, SessionInterface $session): Response
    {
        $id = $product->getId();
        $panier = $session->get('panier', []);
        if (array_key_exists($id, $panier)) {
            if ($panier[$id] > 1) {
                $panier[$id]--;
                $session->set('panier', $panier);
                return $this->redirectToRoute('app_cart');
            } else {
                $session->set('panier', $panier);
                return $this->redirectToRoute('delete', ['id' => $id]);
            }

        } else {
            $session->set('panier', $panier);
            return $this->redirectToRoute('app_cart');
        }


    }

    #[Route('/alerte/{id}', name: 'alerte_stock')]
    public function alerteStock(Product $product, Request $request, EntityManagerInterface $em): Response
    {
        $alerte = new AlerteStock();
        $alerte->setProduct($product);
        $form = $this->createForm(AlerteStockTypeForm::class, $alerte);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($alerte);
            $em->flush();
            $this->addFlash('success', 'Vous serez averti dès que le produit est à nouveau en stock.');
            return $this->redirectToRoute('app_cart');
        }
        return $this->render('cart/alerte.html.twig', [
            'form' => $form->createView(),
            'product' => $product,
        ]);
    }


    /**
     * @throws TransportExceptionInterface
     */
    #[Route('/test-mail')]
    public function testMail(MailerInterface $mailer): Response
    {
        try {
            $email = (new Email())
                ->from(new Address('projetweb521@gmail.com', 'Crochet Shop'))
                ->to(new Address('allagasmii@gmail.com', 'Destinataire'))
                ->subject('Test de la route')
                ->text('Ceci est un test depuis la route');

            $mailer->send($email);

            return new Response('Email envoyé. Vérifiez vos spams !');

        } catch (TransportExceptionInterface $e) {
            return new Response('ERREUR SMTP : ' . $e->getMessage());
        } catch (\Exception $e) {
            return new Response('ERREUR Générale : ' . $e->getMessage());
        }
    }
}
