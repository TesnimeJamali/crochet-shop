<?php
namespace App\Controller;

use App\Entity\AlerteStock;
use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Coupon;
use App\Entity\Product;
use App\Form\AlerteStockTypeForm;
use App\Repository\CouponRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
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
    public function index(
        SessionInterface $session,
        ProductRepository $productRepository,
        CouponRepository $couponRepository,
        Security $security,
        EntityManagerInterface $em
    ): Response {
        $user = $security->getUser();
        $info_panier = [];
        $total = 0;
        $coupon = null;
        $discount = 0;

        if ($user) {
            $cart = $user->getCart();

            if (!$cart) {
                $cart = new Cart();
                $cart->setUser($user);
                $em->persist($cart);
                $em->flush();
            }

            foreach ($cart->getItems() as $item) {
                $product = $item->getProduct();
                if (!$product) {
                    $em->remove($item);
                    continue;
                }

                $info_panier[] = [
                    "produit" => $product,
                    "quantite" => $item->getQuantity()
                ];
                $total += $product->getPrice() * $item->getQuantity();
            }
            $em->flush();
        } else {
            $panier = $session->get('panier', []);
            foreach ($panier as $id => $quantite) {
                $product = $productRepository->find($id);
                if ($product) {
                    $info_panier[] = [
                        "produit" => $product,
                        "quantite" => $quantite
                    ];
                    $total += $product->getPrice() * $quantite;
                }
            }
        }
        $couponId = $session->get('coupon');
        if ($couponId) {
            $coupon = $couponRepository->find($couponId);
        }
        if ($user && $coupon) {
            $cart->setCoupon($coupon);
            $em->persist($cart);
            $em->flush();
        }
        $totalAvecReduction = $total;
        if ($coupon) {
            if ($coupon->getValidUntil() >= new \DateTime()) {
                $discount = $total * ($coupon->getDiscount() / 100);
                $totalAvecReduction = $total - $discount;
            } else {
                $session->remove('coupon');
                if ($user) {
                    $cart->setCoupon(null);
                    $em->persist($cart);
                    $em->flush();
                }
                $this->addFlash('warning', 'Le coupon a expiré.');
            }
        }
        return $this->render('cart/index.html.twig', [
            'info_panier' => $info_panier,
            'total' => $total,
            'coupon' => $coupon,
            'discount' => $discount,
            'totalAfterDiscount' => $totalAvecReduction,

        ]);
    }


    #[Route('/add/{id}', name: 'add')]
    public function add(
        Product $product,
        SessionInterface $session,
        Request $request,
        Security $security,
        EntityManagerInterface $em
    ): Response {
        $user = $security->getUser();

        if ($user) {
            $cart = $user->getCart();
            if (!$cart) {
                $cart = new Cart();
                $cart->setUser($user);
                $em->persist($cart);
            }

            $existingItem = null;
            foreach ($cart->getItems() as $item) {
                if ($item->getProduct()->getId() === $product->getId()) {
                    $existingItem = $item;
                    break;
                }
            }

            if ($existingItem) {
                if ($existingItem->getQuantity() < $product->getQuantity()) {
                    $existingItem->setQuantity($existingItem->getQuantity() + 1);
                    $em->flush();
                    $this->addFlash('success', sprintf('%s ajouté au panier', $product->getName()));
                } else {
                    $this->addFlash('warning', 'Quantité maximale disponible atteinte');
                }
            } else {
                if ($product->getQuantity() > 0) {
                    $cartItem = new CartItem();
                    $cartItem->setProduct($product);
                    $cartItem->setQuantity(1);
                    $cartItem->setCart($cart);
                    $em->persist($cartItem);
                    $em->flush();
                    $this->addFlash('success', sprintf('%s ajouté au panier', $product->getName()));
                } else {
                    $this->addFlash('warning', 'Produit en rupture de stock');
                }
            }
        } else {
            $panier = $session->get('panier', []);
            $id = $product->getId();

            if (isset($panier[$id])) {
                if ($panier[$id] < $product->getQuantity()) {
                    $panier[$id]++;
                    $this->addFlash('success', sprintf('%s ajouté au panier', $product->getName()));
                } else {
                    $this->addFlash('warning', sprintf('Stock insuffisant pour %s.', $product->getName()));
                }
            } else {
                if ($product->getQuantity() > 0) {
                    $panier[$id] = 1;
                    $this->addFlash('success', sprintf('%s ajouté au panier', $product->getName()));
                } else {
                    $this->addFlash('warning', sprintf('%s est en rupture de stock.', $product->getName()));
                }
            }
            $session->set('panier', $panier);
        }

        return $this->redirect($request->headers->get('referer') ?: $this->generateUrl('app_cart'));
    }

    #[Route('/delete/{id}', name: 'delete')]
    public function delete(
        Product $product,
        SessionInterface $session,
        Security $security,
        EntityManagerInterface $em
    ): Response {
        $user = $security->getUser();

        if ($user) {
            $cart = $user->getCart();
            if ($cart) {
                foreach ($cart->getItems() as $item) {
                    if ($item->getProduct()->getId() === $product->getId()) {
                        $em->remove($item);
                        break;
                    }
                }
                $em->flush();
            }
        } else {
            $panier = $session->get('panier', []);
            unset($panier[$product->getId()]);
            $session->set('panier', $panier);
        }

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/remove/{id}', name: 'remove')]
    public function remove(
        Product $product,
        SessionInterface $session,
        Security $security,
        EntityManagerInterface $em
    ): Response {
        $user = $security->getUser();

        if ($user) {
            $cart = $user->getCart();
            if ($cart) {
                foreach ($cart->getItems() as $item) {
                    if ($item->getProduct()->getId() === $product->getId()) {
                        if ($item->getQuantity() > 1) {
                            $item->setQuantity($item->getQuantity() - 1);
                        } else {
                            $em->remove($item);
                        }
                        break;
                    }
                }
                $em->flush();
            }
        } else {
            $panier = $session->get('panier', []);
            $id = $product->getId();

            if (isset($panier[$id])) {
                if ($panier[$id] > 1) {
                    $panier[$id]--;
                } else {
                    unset($panier[$id]);
                }
                $session->set('panier', $panier);
            }
        }

        return $this->redirectToRoute('app_cart');
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
                ->to(new Address('oumaymadrive@gmail.com', 'Destinataire'))
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
    #[Route('/cart/apply-coupon', name: 'apply_coupon', methods: ['POST'])]
    public function applyCoupon(Request $request, EntityManagerInterface $em ,Security $security): \Symfony\Component\HttpFoundation\RedirectResponse
    {   $user = $security->getUser();
        if ($user) {
            $cart = $user->getCart();
            if ($cart) {
                $couponuser=$cart->getCoupon();
            }
            if($couponuser !== null){
                $this->addFlash('danger', 'Vous ne pouvez appliquer qu’un seul coupon !');
                return $this->redirectToRoute('app_cart');
            }
        }
        else{
            $session = $request->getSession();
            if ($session->has('coupon')) {
                $this->addFlash('danger', 'Vous ne pouvez appliquer qu’un seul coupon !');
                return $this->redirectToRoute('app_cart');
            }
        }
        $code = $request->request->get('code');
        $coupon = $em->getRepository(Coupon::class)->findOneBy(['code' => $code]);
        if (!$coupon || !$coupon->isValid()) {
            $this->addFlash('danger', 'Ce code promo est invalide ou expiré.');
        } else {
            $this->addFlash('success', 'Code promo appliqué : -' . $coupon->getDiscount() . '%');
            $session = $request->getSession();
            $session->set('coupon', $coupon->getId());
        }
        return $this->redirectToRoute('app_cart');
    }

    #[Route('/clear', name: 'cart_clear')]
    public function clear(
        SessionInterface $session,
        Security $security,
        EntityManagerInterface $em
    ): Response {
        $user = $security->getUser();

        if ($user) {
            $cart = $user->getCart();
            if ($cart) {
                foreach ($cart->getItems() as $item) {
                    $em->remove($item);
                }
                $em->flush();
            }
        } else {
            $session->remove('panier');
        }

        $session->remove('coupon');
        return $this->redirectToRoute('app_cart');
    }
}
