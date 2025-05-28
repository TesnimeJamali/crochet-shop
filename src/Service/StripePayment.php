<?php

namespace App\Service;

use App\Entity\Order;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Stripe\Webhook;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class StripePayment
{
    public function __construct(readonly private string $clientSecret,readonly private string $webhookSecret,)
    {
        Stripe::setApiKey($this->clientSecret);
        Stripe::setApiVersion('2025-04-30.basil');

    }

    public function startPayment($panier,Order $order,$discount)
    {
        $session=Session::create([

            'line_items' => $panier,
            'mode' => 'payment',
            'success_url' =>  'http://localhost:8000/order',
            'cancel_url' =>  'http://localhost:8000/',
            'shipping_address_collection' => [
                'allowed_countries'=>['FR']
            ],
            'metadata' => [

            ]
        ]);
        $order->setSessionId($session->id);
        return $session->url;
    }

    public function handle($header,$body)
    {
        try {
            $event=Webhook::constructEvent($body,$header,$this->webhookSecret);
                return $event;
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return "error";
        }
    }
}