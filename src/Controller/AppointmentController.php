<?php

namespace App\Controller;

use App\Entity\Animal;
use App\Entity\Appointment;
use App\Entity\Structure;
use App\Entity\User;
use App\Form\AppointmentType;
use App\Repository\AnimalRepository;
use App\Repository\AppointmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
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
    public function new(Request $request, EntityManagerInterface $em, AnimalRepository $animalRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $isPro = $this->isGranted('ROLE_PRO');
        $isStructure = $this->isGranted('ROLE_STRUCTURE');

        $appointment = new Appointment();

        if ($isPro) {
            $defaultDuration = $user->getDefaultConsultationDuration() ?? 60;
            $appointment->setDuration($defaultDuration);

            $defaultNotes = $user->getDefaultAppointmentNotes();
            if ($defaultNotes) {
                $appointment->setPublicNotes($defaultNotes);
            }
        }

        $presetDate = $request->query->get('date');
        if ($presetDate) {
            try {
                $appointment->setScheduledAt(new \DateTimeImmutable($presetDate));
            } catch (\Exception) {
            }
        }

        $presetAnimalId = $request->query->get('animal');
        $presetAnimal = null;
        if ($presetAnimalId) {
            $presetAnimal = $animalRepository->find((int) $presetAnimalId);
            if ($presetAnimal && $isPro) {
                $appointment->addAnimal($presetAnimal);
            }
        }

        $form = $this->createForm(AppointmentType::class, $appointment, [
            'user' => $user,
            'is_pro' => $isPro,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $appointment->setCreatedBy($user);

            $newAnimalMode = $request->request->get('new_animal_mode') === '1';
            if ($newAnimalMode) {
                $newAnimal = $this->createAnimalFromRequest($request, $em, $user, $isPro, $isStructure);
                if ($newAnimal) {
                    $appointment->addAnimal($newAnimal);
                    $appointment->setAnimal($newAnimal);
                }

                if ($isStructure) {
                    $proId = $request->request->get('new_animal_pro_id');
                    if ($proId) {
                        $pro = $em->getRepository(User::class)->find((int) $proId);
                        if ($pro && $pro->getAccountType() === 'PRO') {
                            $appointment->setSharedWithProfessional($pro);
                        }
                    }
                }
            } elseif (!$isPro && $form->has('animal')) {
                $animal = $form->get('animal')->getData();
                if ($animal) {
                    $appointment->addAnimal($animal);
                    $appointment->setAnimal($animal);
                }
            }

            $em->persist($appointment);
            $em->flush();

            $this->addFlash('success', 'Rendez-vous créé avec succès.');

            return $this->redirectToRoute('app_appointment_show', [
                'id' => $appointment->getId(),
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->render('appointment/new.html.twig', [
            'form' => $form,
            'is_pro' => $isPro,
            'is_structure' => $isStructure,
        ]);
    }

    private function createAnimalFromRequest(Request $request, EntityManagerInterface $em, User $user, bool $isPro, bool $isStructure): ?Animal
    {
        $name = trim($request->request->get('new_animal_name', ''));
        if (!$name) {
            return null;
        }

        $animal = new Animal();
        $animal->setName($name);
        $animal->setSpecies('Cheval');
        $animal->setGender($request->request->get('new_animal_gender', 'Hongre'));

        $birthDateStr = $request->request->get('new_animal_birth_date', '');
        if ($birthDateStr) {
            try {
                $animal->setBirthDate(new \DateTimeImmutable($birthDateStr));
            } catch (\Exception) {
            }
        }

        $sire = trim($request->request->get('new_animal_sire', ''));
        if ($sire) {
            $animal->setIdentificationNumber($sire);
        }

        $microchip = trim($request->request->get('new_animal_microchip', ''));
        if ($microchip) {
            $animal->setMicrochipNumber($microchip);
        }

        $ownerId = $request->request->get('new_animal_owner_id');
        if ($ownerId) {
            $owner = $em->getRepository(User::class)->find((int) $ownerId);
            if ($owner) {
                $animal->setOwner($owner);
            }
        }

        if (!$animal->getOwner()) {
            $animal->setOwner($user);
        }

        $structureId = $request->request->get('new_animal_structure_id');
        if ($structureId) {
            $structure = $em->getRepository(Structure::class)->find((int) $structureId);
            if ($structure) {
                $animal->setStructure($structure);
            }
        }

        $em->persist($animal);
        $em->flush();
        $animal->generateSlug();
        $em->flush();

        return $animal;
    }

    #[Route('/{id}', name: 'app_appointment_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Appointment $appointment): Response
    {
        return $this->render('appointment/show.html.twig', [
            'appointment' => $appointment,
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_appointment_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Appointment $appointment, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $isPro = $this->isGranted('ROLE_PRO');

        $form = $this->createForm(AppointmentType::class, $appointment, [
            'user' => $user,
            'is_pro' => $isPro,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$isPro && $form->has('animal')) {
                $animal = $form->get('animal')->getData();
                if ($animal) {
                    foreach ($appointment->getAnimals() as $a) {
                        $appointment->removeAnimal($a);
                    }
                    $appointment->addAnimal($animal);
                    $appointment->setAnimal($animal);
                }
            }

            $em->flush();
            $this->addFlash('success', 'Rendez-vous modifié.');

            return $this->redirectToRoute('app_appointment_show', [
                'id' => $appointment->getId(),
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->render('appointment/edit.html.twig', [
            'form' => $form,
            'appointment' => $appointment,
            'is_pro' => $isPro,
        ]);
    }

    #[Route('/{id}/annuler', name: 'app_appointment_cancel', methods: ['POST'])]
    public function cancel(Request $request, Appointment $appointment, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('cancel' . $appointment->getId(), $request->getPayload()->getString('_token'))) {
            $appointment->setStatus('CANCELLED');
            $em->flush();
            $this->addFlash('success', 'Rendez-vous annulé.');
        }

        return $this->redirectToRoute('app_appointment_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/confirmer', name: 'app_appointment_confirm', methods: ['POST'])]
    public function confirm(Request $request, Appointment $appointment, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($appointment->getCreatedBy() === $user && $this->isGranted('ROLE_PRO')) {
            $this->addFlash('danger', 'Seul le client peut confirmer ce rendez-vous.');
            return $this->redirectToRoute('app_appointment_show', ['id' => $appointment->getId()], Response::HTTP_SEE_OTHER);
        }

        if ($this->isCsrfTokenValid('confirm' . $appointment->getId(), $request->getPayload()->getString('_token'))) {
            $appointment->setStatus('CONFIRMED');
            $em->flush();
            $this->addFlash('success', 'Rendez-vous confirmé.');
        }

        $redirect = $request->query->get('redirect');
        if ($redirect === 'home') {
            return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
        }

        return $this->redirectToRoute('app_appointment_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/api/client/{id}/animals', name: 'app_appointment_client_animals', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function clientAnimals(User $client): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_PRO');

        $ownerAddress = implode(', ', array_filter([
            $client->getAddress(),
            trim(($client->getPostalCode() ?? '') . ' ' . ($client->getCity() ?? '')),
        ]));

        $animals = [];
        foreach ($client->getAnimals() as $animal) {
            $livingAddress = implode(', ', array_filter([
                $animal->getLivingPlaceName(),
                $animal->getLivingPlaceStreet(),
                trim(($animal->getLivingPlacePostalCode() ?? '') . ' ' . ($animal->getLivingPlaceCity() ?? '')),
            ]));

            $structure = $animal->getStructure();
            $structureAddress = $structure
                ? implode(', ', array_filter([
                    $structure->getName(),
                    $structure->getStreet(),
                    trim(($structure->getPostalCode() ?? '') . ' ' . ($structure->getCity() ?? '')),
                ]))
                : '';

            $animals[] = [
                'id' => $animal->getId(),
                'name' => $animal->getName(),
                'photo' => $animal->getPhoto(),
                'livingPlaceName' => $animal->getLivingPlaceName(),
                'structureName' => $structure?->getName(),
                'address' => $livingAddress,
                'structureAddress' => $structureAddress,
            ];
        }

        return new JsonResponse(['animals' => $animals, 'ownerAddress' => $ownerAddress]);
    }

    #[Route('/api/appointment/search', name: 'app_appointment_search', methods: ['GET'])]
    public function searchClientsAndAnimals(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_PRO');

        /** @var User $user */
        $user = $this->getUser();
        $query = mb_strtolower(trim($request->query->get('q', '')));

        if (strlen($query) < 2) {
            return new JsonResponse(['clients' => []]);
        }

        $clients = $em->getRepository(User::class)->createQueryBuilder('u')
            ->innerJoin('u.animals', 'a')
            ->where('u.id != :self')
            ->andWhere(
                'LOWER(u.firstName) LIKE :q OR LOWER(u.lastName) LIKE :q '
                . 'OR LOWER(a.name) LIKE :q '
                . 'OR LOWER(a.livingPlaceName) LIKE :q '
                . 'OR LOWER(a.livingPlaceCity) LIKE :q'
            )
            ->setParameter('self', $user->getId())
            ->setParameter('q', '%' . $query . '%')
            ->groupBy('u.id')
            ->orderBy('u.lastName', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        $results = [];
        foreach ($clients as $client) {
            $animals = [];
            foreach ($client->getAnimals() as $animal) {
                $structure = $animal->getStructure();
                $animals[] = [
                    'id' => $animal->getId(),
                    'name' => $animal->getName(),
                    'livingPlaceName' => $animal->getLivingPlaceName(),
                    'structureName' => $structure?->getName(),
                ];
            }
            $results[] = [
                'id' => $client->getId(),
                'name' => $client->getFullName(),
                'email' => $client->getEmail(),
                'animals' => $animals,
            ];
        }

        return new JsonResponse(['clients' => $results]);
    }

    #[Route('/api/search-owners', name: 'app_appointment_search_owners', methods: ['GET'])]
    public function searchOwners(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $query = mb_strtolower(trim($request->query->get('q', '')));
        if (strlen($query) < 2) {
            return new JsonResponse(['owners' => []]);
        }

        $owners = $em->getRepository(User::class)->createQueryBuilder('u')
            ->where('LOWER(u.firstName) LIKE :q OR LOWER(u.lastName) LIKE :q OR LOWER(u.email) LIKE :q')
            ->setParameter('q', '%' . $query . '%')
            ->orderBy('u.lastName', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        $results = [];
        foreach ($owners as $owner) {
            $results[] = [
                'id' => $owner->getId(),
                'name' => $owner->getFullName(),
                'email' => $owner->getEmail(),
                'accountType' => $owner->getAccountType(),
            ];
        }

        return new JsonResponse(['owners' => $results]);
    }

    #[Route('/api/search-structures', name: 'app_appointment_search_structures', methods: ['GET'])]
    public function searchStructures(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $query = mb_strtolower(trim($request->query->get('q', '')));
        if (strlen($query) < 2) {
            return new JsonResponse(['structures' => []]);
        }

        $structures = $em->getRepository(Structure::class)->createQueryBuilder('s')
            ->where('LOWER(s.name) LIKE :q OR LOWER(s.city) LIKE :q')
            ->setParameter('q', '%' . $query . '%')
            ->orderBy('s.name', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        $results = [];
        foreach ($structures as $structure) {
            $results[] = [
                'id' => $structure->getId(),
                'name' => $structure->getName(),
                'street' => $structure->getStreet(),
                'postalCode' => $structure->getPostalCode(),
                'city' => $structure->getCity(),
                'type' => $structure->getType(),
            ];
        }

        return new JsonResponse(['structures' => $results]);
    }

    #[Route('/api/search-professionals', name: 'app_appointment_search_professionals', methods: ['GET'])]
    public function searchProfessionals(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $query = mb_strtolower(trim($request->query->get('q', '')));
        if (strlen($query) < 2) {
            return new JsonResponse(['professionals' => []]);
        }

        /** @var User $user */
        $user = $this->getUser();

        $pros = $em->getRepository(User::class)->createQueryBuilder('u')
            ->where('u.accountType = :pro')
            ->andWhere('u.id != :self')
            ->andWhere('LOWER(u.firstName) LIKE :q OR LOWER(u.lastName) LIKE :q OR LOWER(u.specialty) LIKE :q')
            ->setParameter('pro', 'PRO')
            ->setParameter('self', $user->getId())
            ->setParameter('q', '%' . $query . '%')
            ->orderBy('u.lastName', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        $results = [];
        foreach ($pros as $pro) {
            $results[] = [
                'id' => $pro->getId(),
                'name' => $pro->getFullName(),
                'specialty' => $pro->getSpecialty(),
            ];
        }

        return new JsonResponse(['professionals' => $results]);
    }
}
