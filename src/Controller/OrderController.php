<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderDetail;
use App\Form\AdressForm;
use App\Form\ConfirmAddressForm;
use App\Form\Model\AddressData;
use App\Form\OrderForm;
use App\Form\PaymentForm;
use App\Repository\OrderDetailRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Service\Payment;
use App\Service\StripePayment;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/order')]
final class OrderController extends AbstractController
{
    #[Route(name: 'app_order_index', methods: ['GET'])]
    public function index(OrderRepository $orderRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        return $this->render('order/index.html.twig');
    }

    #[Route('/filtered/{status}?paid',name: 'app_order_filtered', methods: ['GET'])]
    public function filter(OrderRepository $orderRepository,$status): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

         /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $orders = $orderRepository->findBy(['user' => $user->getId() ,'status' => $status], ['createdAt' => 'DESC']);
        $stat=$status=="paid"?"payée":($status=="pending"?"en attente":"annulée");
        return $this->render('order/ordersTable.html.twig', [
            'orders' => $orders,
            'status' => $stat,
        ]);
    }


    #[Route('/topSelled/{limit<\d+>?10}',name: 'app_order_topselled', methods: ['GET'])]
    public function topSelled(OrderDetailRepository $orderDetailRepository,int $limit): Response
    {
        return $this->render('order/topSelledClient.html.twig', [
            'products' => $orderDetailRepository->findTopSellingProducts($limit),
        ]);
    }

    #[isGranted('ROLE_ADMIN')]
    #[Route('/topSelledAdmin',name: 'app_order_topselledAdmin', methods: ['GET'])]
    public function topSelledAdmin(OrderDetailRepository $orderDetailRepository,Request $request): Response
    {

        $limit=$request->query->get('limit');
        $products=$orderDetailRepository->findTopSellingProducts($limit);
        return $this->render('admin/topSelledAdmin.html.twig', [
            'products' => $products,
            'current_limit' => $limit
        ]);
    }

    #[Route('/annulerCmd/{order}' , name: 'app_annuler_cmd')]
    public function annulerCmd(Order $order,EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if ($order) {
            $order->setStatus('canceled');
            $entityManager->persist($order);
            $entityManager->flush();
        }
        return $this->redirectToRoute('app_order_index');
    }




    #[Route('/newOrder/{step<\d>?1}', name: 'app_order_new', methods: ['GET', 'POST'])]
    public function new(Request $request,$step,Payment $payment,Security $security): Response
    {

        return $payment->managePayment($request,$step);
    }

    #[Route('/checkout/stripe', name: 'app_checkout', methods: ['GET','POST'])]
    public function checkout(Request $request,Payment $payment,LoggerInterface $logger)
    {
        $body = $request->getContent();
        $header = $request->headers->get('stripe-signature');
        $payment->stripeCheckout($header,$body);
        return new Response('Webhook handled', Response::HTTP_OK);

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
