<?php

namespace App\Controller;

use App\Entity\HealthBookEntry;
use App\Form\HealthBookEntryType;
use App\Repository\HealthBookEntryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/health/book/entry')]
final class HealthBookEntryController extends AbstractController
{
    #[Route(name: 'app_health_book_entry_index', methods: ['GET'])]
    public function index(HealthBookEntryRepository $healthBookEntryRepository): Response
    {
        return $this->render('health_book_entry/index.html.twig', [
            'health_book_entries' => $healthBookEntryRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_health_book_entry_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $healthBookEntry = new HealthBookEntry();
        $form = $this->createForm(HealthBookEntryType::class, $healthBookEntry);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($healthBookEntry);
            $entityManager->flush();

            return $this->redirectToRoute('app_health_book_entry_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('health_book_entry/new.html.twig', [
            'health_book_entry' => $healthBookEntry,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_health_book_entry_show', methods: ['GET'])]
    public function show(HealthBookEntry $healthBookEntry): Response
    {
        return $this->render('health_book_entry/show.html.twig', [
            'health_book_entry' => $healthBookEntry,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_health_book_entry_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, HealthBookEntry $healthBookEntry, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(HealthBookEntryType::class, $healthBookEntry);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_health_book_entry_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('health_book_entry/edit.html.twig', [
            'health_book_entry' => $healthBookEntry,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_health_book_entry_delete', methods: ['POST'])]
    public function delete(Request $request, HealthBookEntry $healthBookEntry, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$healthBookEntry->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($healthBookEntry);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_health_book_entry_index', [], Response::HTTP_SEE_OTHER);
    }
}
