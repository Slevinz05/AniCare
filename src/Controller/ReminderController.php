<?php

namespace App\Controller;

use App\Entity\Reminder;
use App\Form\ReminderType;
use App\Repository\ReminderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/rappels')]
#[IsGranted('ROLE_USER')]
class ReminderController extends AbstractController
{
    #[Route('/', name: 'app_reminder_index', methods: ['GET'])]
    public function index(ReminderRepository $reminderRepository): Response
    {
        $user = $this->getUser();
        $reminders = $reminderRepository->findActiveByUser($user);
        $overdueCount = count($reminderRepository->findOverdueByUser($user));
        $todayCount = count($reminderRepository->findTodayByUser($user));

        return $this->render('reminder/index.html.twig', [
            'reminders' => $reminders,
            'overdue_count' => $overdueCount,
            'today_count' => $todayCount,
        ]);
    }

    #[Route('/nouveau', name: 'app_reminder_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $reminder = new Reminder();
        $form = $this->createForm(ReminderType::class, $reminder);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $reminder->setOwner($reminder->getAnimal()->getOwner());
            $reminder->setCreatedBy($this->getUser());
            $reminder->computeNextOccurrence();
            $em->persist($reminder);
            $em->flush();

            $this->addFlash('success', 'Rappel "' . $reminder->getTitle() . '" créé avec succès.');
            return $this->redirectToRoute('app_reminder_index');
        }

        return $this->render('reminder/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_reminder_show', methods: ['GET'])]
    public function show(Reminder $reminder): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $user = $this->getUser();
        if ($reminder->getOwner() !== $user && $reminder->getCreatedBy() !== $user) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('reminder/show.html.twig', [
            'reminder' => $reminder,
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_reminder_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Reminder $reminder, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if ($reminder->getOwner() !== $user && $reminder->getCreatedBy() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(ReminderType::class, $reminder);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $reminder->computeNextOccurrence();
            $em->flush();

            $this->addFlash('success', 'Rappel modifié avec succès.');
            return $this->redirectToRoute('app_reminder_index');
        }

        return $this->render('reminder/edit.html.twig', [
            'reminder' => $reminder,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/toggle', name: 'app_reminder_toggle', methods: ['POST'])]
    public function toggle(Request $request, Reminder $reminder, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if ($reminder->getOwner() !== $user && $reminder->getCreatedBy() !== $user) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('toggle' . $reminder->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $reminder->setActive(!$reminder->isActive());
        if ($reminder->isActive()) {
            $reminder->computeNextOccurrence();
        }
        $em->flush();

        $action = $reminder->isActive() ? 'réactivé' : 'désactivé';
        $this->addFlash('success', "Rappel {$action}.");
        return $this->redirectToRoute('app_reminder_index');
    }

    #[Route('/{id}/supprimer', name: 'app_reminder_delete', methods: ['POST'])]
    public function delete(Request $request, Reminder $reminder, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if ($reminder->getOwner() !== $user && $reminder->getCreatedBy() !== $user) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('delete' . $reminder->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $em->remove($reminder);
        $em->flush();

        $this->addFlash('success', 'Rappel supprimé.');
        return $this->redirectToRoute('app_reminder_index');
    }
}
