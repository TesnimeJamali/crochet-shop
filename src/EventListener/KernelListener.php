<?php

namespace App\EventListener;

use App\Entity\Product;
use App\Service\NotificationService;
use App\Repository\AlerteStockRepository;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class KernelListener
{
    public function __construct(NotificationService $notifier, AlerteStockRepository $repo)
    {
        Product::setNotifier($notifier);
        Product::setAlerteRepo($repo);
    }

    public function onKernelRequest(RequestEvent $event) {}
}
