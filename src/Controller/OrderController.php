<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderDetail;
use App\Form\AdressForm;
use App\Form\ConfirmAddressForm;
use App\Form\Model\AddressData;
use App\Form\OrderForm;
use App\Form\PaymentForm;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/order')]
final class OrderController extends AbstractController
{
    #[Route(name: 'app_order_index', methods: ['GET'])]
    public function index(OrderRepository $orderRepository): Response
    {
        return $this->render('order/index.html.twig');
    }

    #[Route('/filtered/{status}?paid',name: 'app_order_filtered', methods: ['GET'])]
    public function filter(OrderRepository $orderRepository,$status): Response
    {
        return $this->render('order/ordersTable.html.twig', [
            'orders' => $orderRepository->findBy(['status'=>$status],['createdAt'=>'DESC']),
            'status' => $status,
        ]);
    }


    #[Route('/topSelled/{howMany<\d+>}?10',name: 'app_order_top', methods: ['GET'])]
    public function topSelled(OrderRepository $orderRepository,$status): Response
    {
        return $this->render('order/ordersTable.html.twig', [
            'orders' => $orderRepository->findBy(),
            'status' => $status,
        ]);
    }

    #[Route('/newOrder/{step<\d>?1}', name: 'app_order_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager,SessionInterface $session,$step,ProductRepository $pRepository): Response
    {

        if ($step > 1 && !$session->has('address')) {
            return $this->redirectToRoute('app_order_new', ['step' => 1]);
        }
        if ($step > 2 && !$session->has('order')) {
            return $this->redirectToRoute('app_order_new', ['step' => 2]);
        }

        //check the session
        if($session->has("order")){
            $order = $session->get("order");
        }
        else{
            $order = new Order();
            $panier=$session->get("panier");
            $totalPanier=0;
            $totalQuantity=0;
            foreach ($panier as $produitId => $quantite) {
                $produit=$pRepository->find($produitId);
                $item=new OrderDetail();
                $item->setProduct($produit);
                $item->setQuantity($quantite);
                $item->setPrice($produit->getPrice() * $quantite);
                $item->setOrder($order);
                $order->addOrderDetail($item);

                $totalPanier+=$produit->getPrice() * $quantite;
                $totalQuantity+=$quantite;
            }
            $order->setTotalAmount($totalPanier);
            $order->setTotalQuantity($totalQuantity);


            //check if user is authenticated
            //if yes
            //get his email
            //$order->setUser($email);
            //if no ->okk
        }



       switch ($step) {
          case 1:
              if(!$session->has('address'))
              {$addressF=new AddressData();}
              else{
                  $addressF=$session->get("address");
              }
               $form = $this->createForm(AdressForm::class, $addressF);
              $form->handleRequest($request);

              if ($form->isSubmitted() && $form->isValid() ) {
                  $session->set("address",$addressF);
                  $firstName = $form->get("first_name")->getData();
                  $lastName = $form->get("last_name")->getData();
                  //email
                  $address = $form->get("address")->getData();
                  $governorate = $form->get("governorate")->getData();
                  $ZIPCode = $form->get("ZIPCode")->getData();
                  $phone = $form->get("phone")->getData();
                  $email = $form->get("email")->getData();

                  $order->setPhone($phone);
                  $finalAddress=$firstName." ".$lastName."\n".$governorate."\n".$address."\n".$ZIPCode."\n".$phone;
                  $session->set("finalAddress",$finalAddress);
                  $order->setShippingAdress($finalAddress);
                  $session->set("order",$order);
                  return $this->redirectToRoute('app_order_new', ['step' => 2]);

              }
              break;


            case 2:
                $form = $this->createForm(ConfirmAddressForm::class, $order);
                $form->handleRequest($request);
                if ($form->isSubmitted() && $form->isValid() ) {
                    $session->set("order",$order);
                    return $this->redirectToRoute('app_order_new', ['step' => 3]);

                }
               break;
            case 3:
               $form = $this->createForm(PaymentForm::class, $order);
               $formFlouci = $this->createForm(PaymentForm::class, $order);
               $formStripe = $this->createForm(PaymentForm::class, $order);

                $form->handleRequest($request);
                $formFlouci->handleRequest($request);
                $formStripe->handleRequest($request);

                if (($formStripe->isSubmitted() && $formStripe->isValid()) || ($formFlouci->isSubmitted() && $formFlouci->isValid()) ) {
                    //if c stripe -> deal with stripe sinon deal with flouci
                    //by dealing: send the needed data to them then once they respond will just use another route
                    $panier = $session->get('panier');
                    $order = new Order();

                    $totalAmount = 0;
                    $totalQuantity = 0;

                    foreach ($panier as $productId => $quantity) {
                        $product = $pRepository->find($productId);

                        $item = new OrderDetail();
                        $item->setProduct($product);
                        $item->setQuantity($quantity);
                        $item->setPrice($product->getPrice() * $quantity);
                        $item->setOrder($order);

                        $entityManager->persist($item);

                        $order->addOrderDetail($item);
                        $totalAmount += $item->getPrice();
                        $totalQuantity += $quantity;
                    }

                    $order->setTotalAmount($totalAmount);
                    $order->setTotalQuantity($totalQuantity);
                    $order->setShippingAdress($session->get('finalAddress'));
                    $order->setCreatedAt(new \DateTime());
                    $order->setStatus('pending');
                    $order->setPaymentMethod("paypal");
                    // Enfin, persist l'ordre lui-même
                    $entityManager->persist($order);
                    $entityManager->flush();
                    $session->remove("order");
                    $session->remove("panier");
                    $this->addFlash("sucess","payment is pending");
                    return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);

                }
               break;
            default:
                throw $this->createNotFoundException();
        }
        $text="".$order->getTotalAmount()." TND";
        $exemple = [
            'items' => [
                [
                    'label' => "Subtotal (".$order->getTotalQuantity()." item)",
                    'value' => $text,
                ],
                [
                    'label' => 'Shipping',
                    'value' => '29.36TND',
                    'tooltip' => 'Standard shipping (3-5 business days)'
                ],
                [
                    'label' => 'Additional discount(s)',
                    'value' => '-4.72 TND',
                    'value_class' => 'text-danger'
                ]
            ],
            'total' => '40.39'
        ];

        $panier = $session->get('panier', []);
        $total=$session->get('total', 0);
        foreach ($panier as $id => $quantite) {
            $product = $pRepository->find($id);
            $info_panier[] = [
                "produit" => $product,
                "quantite" => $quantite
            ];
            $total = $total + ($product->getPrice()) * $quantite;
        }
        $finalAddress=$session->get("finalAddress");
        return $this->render('order/new.html.twig', [
            'step' => $step,
            'form' => $form,
            'orderDetailsDisplay'=>$exemple,
            'address'=>$finalAddress,
            'info_panier'=>$info_panier
        ]);
    }

    #[Route('/{id}', name: 'app_order_show', methods: ['GET'])]
    public function show(Order $order): Response
    {
        return $this->render('order/show.html.twig', [
            'order' => $order,
        ]);
    }



    #[Route('/{id}', name: 'app_order_delete', methods: ['POST'])]
    public function delete(Request $request, Order $order, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$order->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($order);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
    }
}
