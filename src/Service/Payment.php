<?php

namespace App\Service;

use App\Entity\Cart;
use App\Entity\Coupon;
use App\Entity\Order;
use App\Entity\OrderDetail;
use App\Entity\User;
use App\Form\AdressForm;
use App\Form\ConfirmAddressForm;
use App\Form\Model\AddressData;
use App\Form\PaymentForm;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class Payment
{
    private SessionInterface $session;
    public function __construct(RequestStack $requestStack,private StripePayment $stripePayment,private LoggerInterface $logger,
    private EntityManagerInterface $entityManager,private OrderRepository $orderRepository,private Security $security,
    private ProductRepository $pRepository, private UrlGeneratorInterface $urlGenerator, private FormFactoryInterface $formFactory, private Environment $twig)
    {
        $this->session = $requestStack->getSession();
    }

    public function getAdressForm(FormInterface $form): string
    {

        $firstName = $form->get("first_name")->getData();
        $lastName = $form->get("last_name")->getData();
        //email
        $address = $form->get("address")->getData();
        $governorate = $form->get("governorate")->getData();
        $ZIPCode = $form->get("ZIPCode")->getData();
        return ($firstName . " " . $lastName . "\n" . $governorate . "\n" . $address . "\n" . $ZIPCode);

    }

    public function getProductsFromCart()
    {

        $user=$this->security->getUser();
        $products = [];
        if($user){
            $panier=$this->entityManager->getRepository(Cart::class)->findOneBy(['user' => $user->getId()]);
            $panierItems =$panier->getItems();
            foreach ($panierItems as $item){
                $product=$this->pRepository->find($item->getProduct()->getId());
                $quantite=$item->getQuantity();
                $products[]=["produit" => $product, "quantite" => $quantite];
            }
        }
        else{
            $panier = $this->session->get("panier");
            foreach ($panier as $produitId => $quantite) {
                $produit = $this->pRepository->find($produitId);
                $products[] = ["produit" => $produit, "quantite" => $quantite];
            }
        }
        return $products;

    }

    public function getCartDetails()
    {
        $products = $this->getProductsFromCart();
        $totalPanier = 0;
        $totalQuantity = 0;
        foreach ($products as $item) {
            $totalPanier += $item['produit']->getPrice() * $item['quantite'];
            $totalQuantity += $item['quantite'];
        }
        return ['totalAmount' => $totalPanier, 'totalQuantity' => $totalQuantity, 'discount'=>20];
    }

    public function handleAdressForm($addressF,Order $order,FormInterface $form){
            $this->session->set("address",$addressF);
            $phone = $form->get("phone")->getData();
            $email = $form->get("email")->getData();
            $order->setPhone($phone);
            $order->setShippingAdress($this->getAdressForm($form));
            $this->session->set("finalAddress",$this->getAdressForm($form));
            $this->session->set("order",$order);
            $url = $this->urlGenerator->generate('app_order_new', ['step' => 3]);
            return new RedirectResponse($url);

    }
    public function lastTouches(Order $order,$step,$form){
        $cart=$this->getCartDetails();
        $shipping=20;
        $valur=(float)$cart["totalAmount"]+$shipping-(float)$cart["discount"];
        $exemple = [
            'items' => [
                [
                    'label' => "Sous-total (".$cart["totalQuantity"]." articles)",
                    'value' => "".$cart["totalAmount"]." TND",
                ],
                [
                    'label' => 'Livraison',
                    'value' => $shipping.' DT',
                ],
                [
                    'label' => 'Réduction',
                    'value' => '-'.(float)$cart["discount"].' DT',
                    'value_class' => 'text-danger'
                ]
            ],
            'total' =>$valur.' DT'
        ];

        $html = $this->twig->render('order/new.html.twig', [
            'step' => $step,
            'form' => $form->createView(),
            'orderDetailsDisplay' => $exemple,
            'address' => $this->session->get("finalAddress"),
            'info_panier' => $this->getProductsFromCart()
        ]);

        return new Response($html);
    }

    public function stripePaymentMethod(Order $order,$discount){

        $products = $this->getProductsFromCart();
        foreach($products as $product){
            $line_items[]=[
                'price_data' => [
                    'currency' => 'EUR',
                    'unit_amount' => (int) round($product['produit']->getPrice() * 0.3 * 100),
                    'product_data' => [
                        'name' => $product['produit']->getName(),
                        'description' => $product['produit']->getDescription(),
                    ],
                ],
                'quantity' => $product['quantite'],
            ];

        }
        return $this->stripePayment->startPayment($line_items,$order);

    }

    public function stripeCheckout($header,$body){
        $event =$this->stripePayment->handle($header,$body);
        if($event->type=='checkout.session.completed'){
            $session = $event->data->object;
            $order=$this->orderRepository->findOneBy(['sessionId' => $session->id]);
            if($order){
                $name  = $session->shipping->name ?? null;
                if (isset($session->shipping)) {
                    $address = $session->shipping->address;
                } elseif (isset($session->collected_information['shipping_details']['address'])) {
                    $address = (object)$session->collected_information['shipping_details']['address'];
                }
                else{
                    $address=null;
                }
                if ($address) {
                    $addressStr = $name . ', ';
                    $addressStr .= $address->line1 ?? '';

                    if (!empty($address->line2)) {
                        $addressStr .= ', ' . $address->line2;
                    }

                    if (isset($address->postal_code)) {
                        $addressStr .= ', ' . $address->postal_code;
                    }

                    $addressStr .= ' ' . $address->city ?? '';
                    $addressStr .= ', ' . $address->country ?? '';

                    $order->setShippingAdress($addressStr);
                    $order->setPaymentIntent($session->payment_intent);
                    $this->entityManager->flush();

                    return "Order added to the database and set to pending.";
                } else {
                    return $session;
                }
            }

            return "oupss  session";

        }
        elseif ($event->type=='payment_intent.succeeded'){
            $paymentIntent = $event->data->object;

            $paymentIntentId = $paymentIntent->id;

            $order = $this->orderRepository->findOneBy(['paymentIntentId' => $paymentIntentId]);
            if ($order) {
                $order->setStatus('paid');
                $this->entityManager->flush();
                //decremante the stock
                return "order updated ans set to paid";
            }

            return "oups payment";

        }
    }

    public function addingToBDD($orderSession){
        $order=$orderSession;
        $products = $this->getProductsFromCart();
        foreach ($products as $itemP) {
            $product = $this->pRepository->find($itemP["produit"]->getId());
            $item = new OrderDetail();
            $item->setProduct($product);
            $item->setQuantity($itemP["quantite"]);
            $item->setPrice($product->getPrice() * $itemP["quantite"]);
            $item->setOrder($order);
            $this->entityManager->persist($product);
            $this->entityManager->persist($item);
            $order->addOrderDetail($item);
        }
        $cart = $this->getCartDetails();
        $order->setCreatedAt(new \DateTime());
        $order->setStatus('pending');
        $user=$this->security->getUser();
        if($user){
            $order->setUser($this->entityManager->getRepository(User::class)->find($user->getId()));
        }
        $this->entityManager->persist($order);
        $this->session->remove("order");
        if($user){
            $this->logger->info("user exist ");
            $panier=$this->entityManager->getRepository(Cart::class)->findOneBy(['user' => $user->getId()]);
            if ($panier) {
                $this->logger->info("panier exist ".$panier->getId());
                foreach ($panier->getItems() as $item) {
                    $this->entityManager->remove($item);
                }
                $this->entityManager->flush();

            }

        }
        $this->entityManager->flush();
        $this->session->remove("panier");
        $this->entityManager->flush();
        $this->session->getFlashBag()->add("sucess","payment is pending");
    }
    public function managePayment(Request $request,int $step)
    {

        if ($step > 1 && !$this->session->has('order')) {
            $url = $this->urlGenerator->generate('app_order_new', ['step' => 1]);
            return new RedirectResponse($url);
        }
        if ($step > 2 && !$this->session->has('address')) {
            $url = $this->urlGenerator->generate('app_order_new', ['step' => 2]);
            return new RedirectResponse($url);
        }

        if($this->session->has("order")){
            $order = $this->session->get("order");
        }
        else{
            $order = new Order();
            $cart = $this->getCartDetails();
            $order->setTotalAmount($cart["totalAmount"]);
            $order->setTotalQuantity($cart["totalQuantity"]);

        }

        $discount=0;
        if($this->session->get("coupon")){
            $coupon = $this->entityManager->getRepository(Coupon::class)->findOneBy(['id' => $this->session->get("coupon")]);
            $discount=$coupon->getDiscount();
        }
        switch ($step) {
            case 1:
                $form = $this->formFactory->create(PaymentForm::class, $order);

                $form->handleRequest($request);
                if($form->isSubmitted() && $form->isValid()){
                    $payment = $form->get("paymentMethod")->getData();
                    $order->setPaymentMethod($payment);
                    $this->session->set("order",$order);
                    if($payment=="stripe"){
                        $stripeUrl=$this->stripePaymentMethod($order,$discount);
                        $this->addingToBDD($order);
                        return new RedirectResponse($stripeUrl);
                    }
                    elseif ($payment=="cash"){
                        $url = $this->urlGenerator->generate('app_order_new', ['step' => 2]);
                        return new RedirectResponse($url);
                    }
                }
            break;
            case 2:
                if(!$this->session->has('address'))
                {$addressF=new AddressData();
                }
                else{
                    $addressF=$this->session->get("address");
                }
                $user=$this->security->getUser();
                if($user){
                    $addressF->email=$user->getEmail();
                }
                $form = $this->formFactory->create(AdressForm::class, $addressF);
                $form->handleRequest($request);
                if ($form->isSubmitted() && $form->isValid() ) {
                    return $this->handleAdressForm($addressF, $order, $form);
                }
                break;

            case 3:
                $form = $this->formFactory->create(ConfirmAddressForm::class, $order);
                $form->handleRequest($request);
                if ($form->isSubmitted() && $form->isValid() ) {
                    $this->session->set("order",$order);
                    $url = $this->urlGenerator->generate('app_order_new', ['step' => 4]);
                    return new RedirectResponse($url);

                }
                break;

            case 4:

                $this->addingToBDD($order);
                $url = $this->urlGenerator->generate('app_order_index', []);
                return new RedirectResponse($url);


                break;
            default:
                throw new NotFoundHttpException('pas de step');
        }

        return $this->lastTouches($order,$step,$form,$discount);

    }




}