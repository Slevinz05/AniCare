<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\AnimalRepository;
use App\Repository\AnimalShareRepository;
use App\Repository\HealthBookEntryRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
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
    public function directory(Request $request, UserRepository $userRepository): Response
    {
        $specialty = $request->query->get('specialty');
        $department = $request->query->get('department');
        $query = $request->query->get('q');

        $hasFilters = $specialty || $department || $query;

        $professionals = $hasFilters
            ? $userRepository->findProfessionalsFiltered($specialty, $department, $query)
            : $userRepository->findProfessionals();

        return $this->render('home/directory.html.twig', [
            'professionals' => $professionals,
            'current_specialty' => $specialty,
            'current_department' => $department,
            'current_query' => $query,
        ]);
    }

    #[Route('/annuaire/{id}', name: 'app_professional_show', methods: ['GET'])]
    public function showProfessional(User $professional, HealthBookEntryRepository $healthBookEntryRepository): Response
    {
        if ($professional->getAccountType() !== 'PRO') {
            throw $this->createNotFoundException();
        }

        $recentConsultations = $healthBookEntryRepository->findBy(
            ['veterinarian' => $professional],
            ['date' => 'DESC'],
            5,
        );

        return $this->render('home/professional_show.html.twig', [
            'professional' => $professional,
            'recent_consultations' => $recentConsultations,
        ]);
    }

    #[Route('/annuaire/{id}/photo/{fileName}', name: 'app_professional_photo', methods: ['GET'])]
    public function professionalPhoto(
        User $professional,
        string $fileName,
        #[Autowire('%kernel.project_dir%/var/uploads/profiles')] string $uploadsDir,
    ): BinaryFileResponse {
        if ($professional->getAccountType() !== 'PRO') {
            throw $this->createNotFoundException();
        }

        $photos = $professional->getProfilePhotos() ?? [];
        $found = false;
        $originalName = $fileName;

        foreach ($photos as $photo) {
            if (($photo['fileName'] ?? '') === $fileName) {
                $found = true;
                $originalName = $photo['originalName'] ?? $fileName;
                break;
            }
        }

        if (!$found) {
            throw $this->createNotFoundException();
        }

        $filePath = $uploadsDir . '/' . basename($fileName);

        if (!is_file($filePath)) {
            throw $this->createNotFoundException();
        }

        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $originalName);

        return $response;
    }

    #[Route('/repertoire', name: 'app_repertoire_reseau', methods: ['GET'])]
    public function repertoireReseau(AnimalShareRepository $animalShareRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_PRO');

        /** @var User $user */
        $user = $this->getUser();
        $shares = $animalShareRepository->findSharedWithEmail($user->getEmail());

        $clients = [];
        foreach ($shares as $share) {
            $owner = $share->getAnimal()->getOwner();
            if ($owner && !isset($clients[$owner->getId()])) {
                $clients[$owner->getId()] = [
                    'user' => $owner,
                    'animals' => [],
                    'sharedSince' => $share->getCreatedAt(),
                ];
            }
            if ($owner) {
                $clients[$owner->getId()]['animals'][] = $share->getAnimal();
            }
        }

        return $this->render('repertoire/reseau.html.twig', [
            'clients' => array_values($clients),
        ]);
    }

    #[Route('/repertoire/structures', name: 'app_repertoire_structures', methods: ['GET'])]
    public function repertoireStructures(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_PRO');

        return $this->render('repertoire/structures.html.twig');
    }

    #[Route('/factures', name: 'app_factures', methods: ['GET'])]
    public function factures(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_PRO');

        return $this->render('factures/index.html.twig');
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
