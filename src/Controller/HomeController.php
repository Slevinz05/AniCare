<?php

namespace App\Controller;

use App\Entity\StructureMembership;
use App\Entity\User;
use App\Repository\AnimalRepository;
use App\Repository\AnimalShareRepository;
use App\Repository\AppointmentRepository;
use App\Repository\HealthBookEntryRepository;
use App\Repository\StructureMembershipRepository;
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
    public function index(
        Request $request,
        AnimalRepository $animalRepository,
        HealthBookEntryRepository $healthBookRepository,
        AppointmentRepository $appointmentRepository,
    ): Response {
        $user = $this->getUser();
        $animals = [];
        $upcomingReminders = [];

        $ownerAppointments = [];
        if ($user instanceof User) {
            $animals = $animalRepository->findAccessibleAnimals($user);
            $upcomingReminders = $healthBookRepository->findUpcomingRemindersByOwner($user, 5);
            if (!$this->isGranted('ROLE_PRO')) {
                $ownerAppointments = $appointmentRepository->findUpcomingByUser($user);
            }
        }

        $proData = [];
        if ($user instanceof User && $this->isGranted('ROLE_PRO')) {
            $view = $request->query->get('agenda', 'week');
            $now = new \DateTimeImmutable();

            if ($view === 'day') {
                $start = $now->setTime(0, 0);
                $end = $now->setTime(23, 59, 59);
            } elseif ($view === 'month') {
                $start = $now->modify('first day of this month')->setTime(0, 0);
                $end = $now->modify('last day of this month')->setTime(23, 59, 59);
            } else {
                $start = $now->modify('monday this week')->setTime(0, 0);
                $end = $now->modify('sunday this week')->setTime(23, 59, 59);
            }

            $proData = [
                'agenda_view' => $view,
                'agenda_start' => $start,
                'agenda_end' => $end,
                'agenda_appointments' => $appointmentRepository->findByPeriodAndUser($start, $end, $user),
                'agenda_entries' => $healthBookRepository->findByMonthAndUser($start, $end, $user),
                'upcoming_appointments' => $appointmentRepository->findUpcomingByUser($user),
                'drafts' => $healthBookRepository->findDraftsByVeterinarian($user),
                'todays_reminders' => $healthBookRepository->findTodaysRemindersByVeterinarian($user),
            ];
        }

        return $this->render('home/index.html.twig', [
            'animals' => $animals,
            'upcoming_reminders' => $upcomingReminders,
            'owner_appointments' => $ownerAppointments,
            'pro' => $proData,
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
    public function directory(Request $request, UserRepository $userRepository, StructureMembershipRepository $membershipRepo): Response
    {
        $specialty = $request->query->get('specialty');
        $department = $request->query->get('department');
        $query = $request->query->get('q');

        $hasFilters = $specialty || $department || $query;

        $professionals = $hasFilters
            ? $userRepository->findProfessionalsFiltered($specialty, $department, $query)
            : $userRepository->findProfessionals();

        $structureProIds = [];
        /** @var User|null $user */
        $user = $this->getUser();
        if ($user) {
            $managerMemberships = $membershipRepo->findBy(['user' => $user, 'role' => StructureMembership::ROLE_MANAGER]);
            foreach ($managerMemberships as $membership) {
                $structure = $membership->getStructure();
                foreach ($structure->getAnimals() as $animal) {
                    foreach ($animal->getAnimalShares() as $share) {
                        $pro = $userRepository->findOneBy(['email' => $share->getSharedWithEmail(), 'accountType' => 'PRO']);
                        if ($pro) {
                            $structureProIds[$pro->getId()] = true;
                        }
                    }
                }
                foreach ($structure->getMemberships() as $m) {
                    if ($m->getRole() === StructureMembership::ROLE_PRO) {
                        $structureProIds[$m->getUser()->getId()] = true;
                    }
                }
            }
        }

        return $this->render('home/directory.html.twig', [
            'professionals' => $professionals,
            'current_specialty' => $specialty,
            'current_department' => $department,
            'current_query' => $query,
            'structure_pro_ids' => array_keys($structureProIds),
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
    public function repertoireReseau(Request $request, AnimalShareRepository $animalShareRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_PRO');

        /** @var User $user */
        $user = $this->getUser();
        $query = $request->query->get('q');

        $shares = $animalShareRepository->searchSharedWithEmail($user->getEmail(), $query);

        $clients = [];
        $structures = [];
        foreach ($shares as $share) {
            $animal = $share->getAnimal();
            $owner = $animal->getOwner();
            if (!$owner) {
                continue;
            }

            if (!isset($clients[$owner->getId()])) {
                $clients[$owner->getId()] = [
                    'user' => $owner,
                    'animals' => [],
                    'sharedSince' => $share->getCreatedAt(),
                ];
            }
            $clients[$owner->getId()]['animals'][] = $animal;

            $lpName = $animal->getLivingPlaceName();
            if ($lpName) {
                $key = mb_strtolower($lpName);
                if (!isset($structures[$key])) {
                    $structures[$key] = [
                        'name' => $lpName,
                        'city' => $animal->getLivingPlaceCity(),
                        'postalCode' => $animal->getLivingPlacePostalCode(),
                        'managerName' => trim(($animal->getLivingPlaceManagerFirstName() ?? '') . ' ' . ($animal->getLivingPlaceManagerLastName() ?? '')),
                        'managerPhone' => $animal->getLivingPlaceManagerPhone(),
                        'animals' => [],
                    ];
                }
                $structures[$key]['animals'][] = $animal;
            }
        }

        return $this->render('repertoire/reseau.html.twig', [
            'clients' => array_values($clients),
            'structures' => array_values($structures),
            'query' => $query,
        ]);
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
