<?php

namespace App\Controller;

use App\Repository\AnimalRepository;
use App\Repository\HealthBookEntryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(AnimalRepository $animalRepository, HealthBookEntryRepository $healthBookRepository): Response
    {
        $user = $this->getUser();
        $animals = [];
        $upcomingReminders = [];

        if ($user) {
            if ($this->isGranted('ROLE_PRO')) {
                $animals = $animalRepository->findBy([], ['id' => 'DESC'], 5);
            } else {
                $animals = $animalRepository->findBy(['owner' => $user], ['name' => 'ASC']);
                $upcomingReminders = $healthBookRepository->findUpcomingRemindersByOwner($user, 3);
            }
        }

        return $this->render('home/index.html.twig', [
            'animals' => $animals,
            'upcoming_reminders' => $upcomingReminders,
        ]);
    }

    // ─── AJOUTE CETTE ROUTE POUR LA PAGE À PROPOS ───────────────────────────
    #[Route('/a-propos', name: 'app_about', methods: ['GET'])]
    public function about(): Response
    {
        return $this->render('home/about.html.twig', [
            'project_name' => 'AniCare'
        ]);
    }

    // ─── AJOUTE CETTE ROUTE POUR LA PAGE DE CONTACT ─────────────────────────
    #[Route('/contact', name: 'app_contact', methods: ['GET'])]
    public function contact(): Response
    {
        return $this->render('home/contact.html.twig');
    }
}