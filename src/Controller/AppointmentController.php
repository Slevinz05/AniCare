<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\User;
use App\Form\AppointmentType;
use App\Repository\AppointmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/rendez-vous')]
#[IsGranted('ROLE_USER')]
final class AppointmentController extends AbstractController
{
    #[Route('', name: 'app_appointment_index', methods: ['GET'])]
    public function index(AppointmentRepository $repository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('appointment/index.html.twig', [
            'appointments' => $repository->findUpcomingByUser($user),
        ]);
    }

    #[Route('/nouveau', name: 'app_appointment_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $appointment = new Appointment();
        $form = $this->createForm(AppointmentType::class, $appointment, ['user' => $user]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->denyAccessUnlessGranted('ANIMAL_VIEW', $appointment->getAnimal());

            $appointment->setCreatedBy($user);

            $em->persist($appointment);
            $em->flush();

            return $this->redirectToRoute('app_appointment_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('appointment/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/annuler', name: 'app_appointment_cancel', methods: ['POST'])]
    public function cancel(Request $request, Appointment $appointment, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ANIMAL_VIEW', $appointment->getAnimal());

        if ($this->isCsrfTokenValid('cancel' . $appointment->getId(), $request->getPayload()->getString('_token'))) {
            $appointment->setStatus('CANCELLED');
            $em->flush();
        }

        return $this->redirectToRoute('app_appointment_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/confirmer', name: 'app_appointment_confirm', methods: ['POST'])]
    public function confirm(Request $request, Appointment $appointment, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ANIMAL_VIEW', $appointment->getAnimal());

        if ($this->isCsrfTokenValid('confirm' . $appointment->getId(), $request->getPayload()->getString('_token'))) {
            $appointment->setStatus('CONFIRMED');
            $em->flush();
        }

        return $this->redirectToRoute('app_appointment_index', [], Response::HTTP_SEE_OTHER);
    }
}
