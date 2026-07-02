<?php

namespace App\Controller;

use App\Entity\Animal;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class EmergencyCardController extends AbstractController
{
    #[Route('/emergency/{id}', name: 'app_emergency_card', methods: ['GET'])]
    public function show(Animal $animal): Response
    {
        $this->denyAccessUnlessGranted('ANIMAL_VIEW', $animal);

        return $this->render('emergency/show.html.twig', [
            'animal' => $animal,
            'allergies' => $animal->getAllergies(),
            'active_treatments' => $animal->getActiveTreatments(),
            'overdue_reminders' => $animal->getOverdueReminders(),
        ]);
    }
}
