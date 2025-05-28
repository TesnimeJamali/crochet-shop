<?php

namespace App\Controller;

use App\Entity\Product;
use App\Service\FavorisService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[isGranted('ROLE_USER')]
final class FavorisController extends AbstractController
{
    #[Route('/favoris', name: 'app_favoris')]
    public function index(): Response
    {
        $user = $this->getUser();
        $favoris = $user->getFavoris();

        return $this->render('favoris/index.html.twig', [
            'favoris' => $favoris,
        ]);
    }

    #[Route('/favoris/toggle/{id}', name: 'favoris_toggle', methods: ['POST'])]
    public function toggle(Product $product, Request $request, FavorisService $favorisService): JsonResponse
    {
        // Debug : log la requête
        error_log("Toggle favoris pour produit: ".$product->getId());

        try {
            $data = json_decode($request->getContent(), true);
            if (!$this->isCsrfTokenValid('favoris', $data['_token'] ?? '')) {
                throw new \Exception('Token CSRF invalide');
            }

            $user = $this->getUser();
            if (!$user) {
                return new JsonResponse([
                    'status' => 'unauthenticated',
                    'loginUrl' => $this->generateUrl('app_login')
                ], 401);
            }

            $isAdded = $favorisService->toggle($user, $product);

            return new JsonResponse([
                'status' => $isAdded ? 'added' : 'removed',
                'productId' => $product->getId()
            ]);

        } catch (\Exception $e) {
            error_log("Erreur dans toggle favoris: ".$e->getMessage());
            return new JsonResponse([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString() // Only in dev
            ], 500);
        }
    }
}
