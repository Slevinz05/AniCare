<?php

namespace App\Controller;

use App\Entity\AnimalShare;
use App\Form\AnimalShareType;
use App\Repository\AnimalShareRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/animal/share')]
final class AnimalShareController extends AbstractController
{
    #[Route('', name: 'app_animal_share_index', methods: ['GET'])]
    public function index(AnimalShareRepository $animalShareRepository): Response
    {
        return $this->render('animal_share/index.html.twig', [
            'animal_shares' => $animalShareRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_animal_share_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $animalShare = new AnimalShare();
        $form = $this->createForm(AnimalShareType::class, $animalShare);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($animalShare);
            $entityManager->flush();

            return $this->redirectToRoute('app_animal_share_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('animal_share/new.html.twig', [
            'animal_share' => $animalShare,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_animal_share_show', methods: ['GET'])]
    public function show(AnimalShare $animalShare): Response
    {
        return $this->render('animal_share/show.html.twig', [
            'animal_share' => $animalShare,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_animal_share_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, AnimalShare $animalShare, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AnimalShareType::class, $animalShare);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_animal_share_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('animal_share/edit.html.twig', [
            'animal_share' => $animalShare,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_animal_share_delete', methods: ['POST'])]
    public function delete(Request $request, AnimalShare $animalShare, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $animalShare->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($animalShare);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_animal_share_index', [], Response::HTTP_SEE_OTHER);
    }
}
