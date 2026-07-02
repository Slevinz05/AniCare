<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\AnimalRepository;
use App\Repository\HealthBookEntryRepository;
use App\Repository\UserRepository;
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

        if ($user instanceof User) {
            $animals = $animalRepository->findAccessibleAnimals($user);
            $upcomingReminders = $healthBookRepository->findUpcomingRemindersByOwner($user, 3);
        }

        return $this->render('home/index.html.twig', [
            'animals' => $animals,
            'upcoming_reminders' => $upcomingReminders,
        ]);
    }

    #[Route('/a-propos', name: 'app_about', methods: ['GET'])]
    public function about(): Response
    {
        return $this->render('home/about.html.twig', [
            'project_name' => 'AniCare'
        ]);
    }

    #[Route('/contact', name: 'app_contact', methods: ['GET'])]
    public function contact(): Response
    {
        return $this->render('home/contact.html.twig');
    }

    #[Route('/annuaire', name: 'app_directory', methods: ['GET'])]
    public function directory(UserRepository $userRepository): Response
    {
        return $this->render('home/directory.html.twig', [
            'professionals' => $userRepository->findProfessionals(),
        ]);
    }

    #[Route('/mentions-legales', name: 'app_legal', methods: ['GET'])]
    public function legal(): Response
    {
        return $this->render('home/legal.html.twig');
    }

    #[Route('/conditions-generales', name: 'app_terms', methods: ['GET'])]
    public function terms(): Response
    {
        return $this->render('home/terms.html.twig');
    }

    #[Route('/politique-de-confidentialite', name: 'app_privacy', methods: ['GET'])]
    public function privacy(): Response
    {
        return $this->render('home/privacy.html.twig');
    }
}
