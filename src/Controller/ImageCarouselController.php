<?php

namespace App\Controller;

use App\Entity\ImageCarousel;
use App\Form\ImageCarouselForm;
use App\Repository\ImageCarouselRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/image/carousel')]
final class ImageCarouselController extends AbstractController
{
    #[Route(name: 'app_image_carousel_index', methods: ['GET'])]
    public function index(ImageCarouselRepository $imageCarouselRepository): Response
    {
        return $this->render('image_carousel/index.html.twig', [
            'image_carousels' => $imageCarouselRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_image_carousel_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $imageCarousel = new ImageCarousel();
        $form = $this->createForm(ImageCarouselForm::class, $imageCarousel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($imageCarousel);
            $entityManager->flush();

            return $this->redirectToRoute('app_image_carousel_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('image_carousel/new.html.twig', [
            'image_carousel' => $imageCarousel,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_image_carousel_show', methods: ['GET'])]
    public function show(ImageCarousel $imageCarousel): Response
    {
        return $this->render('image_carousel/show.html.twig', [
            'image_carousel' => $imageCarousel,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_image_carousel_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ImageCarousel $imageCarousel, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ImageCarouselForm::class, $imageCarousel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_image_carousel_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('image_carousel/edit.html.twig', [
            'image_carousel' => $imageCarousel,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_image_carousel_delete', methods: ['POST'])]
    public function delete(Request $request, ImageCarousel $imageCarousel, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$imageCarousel->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($imageCarousel);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_image_carousel_index', [], Response::HTTP_SEE_OTHER);
    }
}
