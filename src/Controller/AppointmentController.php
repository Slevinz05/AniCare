<?php

namespace App\Controller;

use App\Entity\Animal;
use App\Entity\AnimalReferent;
use App\Entity\Appointment;
use App\Entity\Structure;
use App\Entity\User;
use App\Form\AppointmentType;
use App\Repository\AnimalRepository;
use App\Repository\AppointmentRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
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
    public function new(Request $request, EntityManagerInterface $em, AnimalRepository $animalRepository, UserPasswordHasherInterface $passwordHasher): Response
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
            $newAnimalsCreated = [];

            $newAnimalMode = $request->request->get('new_animal_mode') === '1';
            $multiAnimalCount = (int) $request->request->get('multi_animal_count', '0');

            if ($newAnimalMode && $multiAnimalCount > 0) {
                $newAnimalsData = $request->request->all('new_animals');
                foreach ($newAnimalsData as $animalData) {
                    $newAnimal = $this->createAnimalFromArray($animalData, $em, $user, $isPro, $passwordHasher);
                    if ($newAnimal) {
                        $appointment->addAnimal($newAnimal);
                        if (empty($newAnimalsCreated)) {
                            $appointment->setAnimal($newAnimal);
                        }
                        $newAnimalsCreated[] = $newAnimal;
                    }
                }
            } elseif ($newAnimalMode) {
                $newAnimal = $this->createAnimalFromRequest($request, $em, $user, $isPro, $isStructure, $passwordHasher);
                if ($newAnimal) {
                    $appointment->addAnimal($newAnimal);
                    $appointment->setAnimal($newAnimal);
                    $newAnimalsCreated[] = $newAnimal;
                }

                if ($isStructure) {
                    $proId = $request->request->get('new_animal_pro_id');
                    if ($proId) {
                        $pro = $em->getRepository(User::class)->find((int) $proId);
                        if ($pro && $pro->hasProSpace()) {
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

            foreach ($newAnimalsCreated as $createdAnimal) {
                $createdAnimal->generateCompleteToken();
            }

            $appointment->syncStatusWithClient();
            $em->persist($appointment);
            $em->flush();

            foreach ($newAnimalsCreated as $createdAnimal) {
                $createdAnimal->ensureSlug();
            }
            if (!empty($newAnimalsCreated)) {
                $em->flush();
            }

            $this->addFlash('success', 'Rendez-vous créé avec succès.');

            $params = ['id' => $appointment->getId()];
            if (!empty($newAnimalsCreated)) {
                $params['complete_animals'] = implode(',', array_map(
                    fn(Animal $a) => $a->getId() . ':' . $a->getCompleteToken(),
                    $newAnimalsCreated
                ));
            }

            return $this->redirectToRoute('app_appointment_show', $params, Response::HTTP_SEE_OTHER);
        }

        return $this->render('appointment/new.html.twig', [
            'form' => $form,
            'is_pro' => $isPro,
            'is_structure' => $isStructure,
        ]);
    }

    private function createAnimalFromArray(array $data, EntityManagerInterface $em, User $user, bool $isPro, UserPasswordHasherInterface $passwordHasher): ?Animal
    {
        $name = trim($data['name'] ?? '');
        if (!$name) {
            return null;
        }

        $animal = new Animal();
        $animal->setName($name);
        $animal->setSpecies('Cheval');
        $animal->setGender($data['gender'] ?? 'Hongre');

        $birthDateStr = $data['birth_date'] ?? '';
        if ($birthDateStr) {
            try {
                $animal->setBirthDate(new \DateTimeImmutable($birthDateStr));
            } catch (\Exception) {
            }
        }

        $sire = trim($data['sire'] ?? '');
        if ($sire) {
            $animal->setIdentificationNumber($sire);
        }

        $microchip = trim($data['microchip'] ?? '');
        if ($microchip) {
            $animal->setMicrochipNumber($microchip);
        }

        if ($isPro) {
            $animal->setCreatedByPro($user);
        }

        $referentUser = null;

        $referentId = $data['referent_id'] ?? '';
        if ($referentId) {
            $referentUser = $em->getRepository(User::class)->find((int) $referentId);
        }

        $refEmail = trim($data['referent_email'] ?? '');
        $refFirstName = trim($data['referent_firstname'] ?? '');
        $refLastName = trim($data['referent_lastname'] ?? '');

        if (!$referentUser && $refEmail && $refFirstName && $refLastName) {
            $existingRef = $em->getRepository(User::class)->findOneBy(['email' => $refEmail]);
            if ($existingRef) {
                $referentUser = $existingRef;
            } else {
                $referentUser = new User();
                $referentUser->setFirstName($refFirstName);
                $referentUser->setLastName($refLastName);
                $referentUser->setEmail($refEmail);
                $refPhone = trim($data['referent_phone'] ?? '');
                if ($refPhone) {
                    $referentUser->setPhone($refPhone);
                }
                $referentUser->setPassword($passwordHasher->hashPassword($referentUser, bin2hex(random_bytes(8))));
                $referentUser->setRoles(['ROLE_USER']);
                $referentUser->setAccountType('Propriétaire');
                $referentUser->setActivationToken(bin2hex(random_bytes(32)));
                $em->persist($referentUser);
            }
        }

        $animal->setOwner($referentUser ?? $user);

        if ($referentUser) {
            $referent = new AnimalReferent();
            $referent->setAnimal($animal);
            $referent->setUser($referentUser);
            $referent->setRole('principal');
            $referent->setStatus('active');
            $em->persist($referent);
        }

        $structureId = $data['structure_id'] ?? '';
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

    private function createAnimalFromRequest(Request $request, EntityManagerInterface $em, User $user, bool $isPro, bool $isStructure, UserPasswordHasherInterface $passwordHasher): ?Animal
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

        if ($isPro) {
            $animal->setCreatedByPro($user);
        }

        $referentUser = null;

        $referentId = $request->request->get('new_animal_referent_id');
        if ($referentId) {
            $referentUser = $em->getRepository(User::class)->find((int) $referentId);
        }

        $refEmail = trim($request->request->get('new_animal_referent_email', ''));
        $refFirstName = trim($request->request->get('new_animal_referent_firstname', ''));
        $refLastName = trim($request->request->get('new_animal_referent_lastname', ''));

        if (!$referentUser && $refEmail && $refFirstName && $refLastName) {
            $existingRef = $em->getRepository(User::class)->findOneBy(['email' => $refEmail]);
            if ($existingRef) {
                $referentUser = $existingRef;
            } else {
                $referentUser = new User();
                $referentUser->setFirstName($refFirstName);
                $referentUser->setLastName($refLastName);
                $referentUser->setEmail($refEmail);
                $refPhone = trim($request->request->get('new_animal_referent_phone', ''));
                if ($refPhone) {
                    $referentUser->setPhone($refPhone);
                }
                $referentUser->setPassword($passwordHasher->hashPassword($referentUser, bin2hex(random_bytes(8))));
                $referentUser->setRoles(['ROLE_USER']);
                $referentUser->setAccountType('Propriétaire');
                $referentUser->setActivationToken(bin2hex(random_bytes(32)));
                $em->persist($referentUser);
            }
        }

        $animal->setOwner($referentUser ?? $user);

        if ($referentUser) {
            $referent = new AnimalReferent();
            $referent->setAnimal($animal);
            $referent->setUser($referentUser);
            $referent->setRole('principal');
            $referent->setStatus('active');
            $em->persist($referent);
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
    public function show(Appointment $appointment, Request $request, AnimalRepository $animalRepository): Response
    {
        $completeAnimals = [];
        $completeTokens = [];
        $completeAnimalsParam = $request->query->get('complete_animals', '');
        if ($completeAnimalsParam) {
            $entries = explode(',', $completeAnimalsParam);
            foreach ($entries as $entry) {
                $parts = explode(':', $entry, 2);
                $id = (int) $parts[0];
                $token = $parts[1] ?? null;
                if ($id <= 0) {
                    continue;
                }
                $animal = $animalRepository->find($id);
                if ($animal) {
                    $completeAnimals[] = $animal;
                    if ($token) {
                        $completeTokens[$animal->getId()] = $token;
                    }
                }
            }
        }

        return $this->render('appointment/show.html.twig', [
            'appointment' => $appointment,
            'complete_animals' => $completeAnimals,
            'complete_tokens' => $completeTokens,
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_appointment_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Appointment $appointment, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $isPro = $this->isGranted('ROLE_PRO');

        $previousClient = $appointment->getClient();

        $form = $this->createForm(AppointmentType::class, $appointment, [
            'user' => $user,
            'is_pro' => $isPro,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newAnimalsCreated = [];

            if ($isPro) {
                $newAnimalMode = $request->request->get('new_animal_mode') === '1';
                $multiAnimalCount = (int) $request->request->get('multi_animal_count', '0');

                if ($newAnimalMode && $multiAnimalCount > 0) {
                    $newAnimalsData = $request->request->all('new_animals');
                    foreach ($newAnimalsData as $animalData) {
                        $newAnimal = $this->createAnimalFromArray($animalData, $em, $user, $isPro, $passwordHasher);
                        if ($newAnimal) {
                            $appointment->addAnimal($newAnimal);
                            $newAnimalsCreated[] = $newAnimal;
                        }
                    }
                }
            } elseif ($form->has('animal')) {
                $animal = $form->get('animal')->getData();
                if ($animal) {
                    foreach ($appointment->getAnimals() as $a) {
                        $appointment->removeAnimal($a);
                    }
                    $appointment->addAnimal($animal);
                    $appointment->setAnimal($animal);
                }
            }

            foreach ($newAnimalsCreated as $createdAnimal) {
                $createdAnimal->generateCompleteToken();
            }

            $appointment->syncStatusWithClient($previousClient);
            $em->flush();

            foreach ($newAnimalsCreated as $createdAnimal) {
                $createdAnimal->ensureSlug();
            }
            if (!empty($newAnimalsCreated)) {
                $em->flush();
            }

            $this->addFlash('success', 'Rendez-vous modifié.');

            $params = ['id' => $appointment->getId()];
            if (!empty($newAnimalsCreated)) {
                $params['complete_animals'] = implode(',', array_map(
                    fn(Animal $a) => $a->getId() . ':' . $a->getCompleteToken(),
                    $newAnimalsCreated
                ));
            }

            return $this->redirectToRoute('app_appointment_show', $params, Response::HTTP_SEE_OTHER);
        }

        $existingAnimalsJson = [];
        if ($isPro) {
            foreach ($appointment->getAnimals() as $animal) {
                $existingAnimalsJson[] = [
                    'id' => $animal->getId(),
                    'name' => $animal->getName(),
                    'ownerName' => $animal->getOwner()?->getFullName() ?? '',
                    'structureName' => $animal->getStructure()?->getName() ?? '',
                ];
            }
        }

        return $this->render('appointment/edit.html.twig', [
            'form' => $form,
            'appointment' => $appointment,
            'is_pro' => $isPro,
            'existing_animals_json' => json_encode($existingAnimalsJson),
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
    public function clientAnimals(User $client, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_PRO');

        $ownerAddress = implode(', ', array_filter([
            $client->getAddress(),
            trim(($client->getPostalCode() ?? '') . ' ' . ($client->getCity() ?? '')),
        ]));

        $allAnimals = $em->getRepository(Animal::class)->createQueryBuilder('a')
            ->leftJoin('a.referents', 'r')
            ->where('a.owner = :user')
            ->orWhere('r.user = :user AND r.status = :active')
            ->setParameter('user', $client)
            ->setParameter('active', 'active')
            ->groupBy('a.id')
            ->orderBy('a.name', 'ASC')
            ->getQuery()
            ->getResult();

        $animals = [];
        foreach ($allAnimals as $animal) {
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
                'ownerName' => $animal->getOwner()?->getFullName(),
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
            ->leftJoin('u.animals', 'a')
            ->leftJoin('u.animalReferents', 'ar', 'WITH', 'ar.status = :active')
            ->leftJoin('ar.animal', 'ra')
            ->where(
                'LOWER(u.firstName) LIKE :q OR LOWER(u.lastName) LIKE :q '
                . 'OR LOWER(a.name) LIKE :q '
                . 'OR LOWER(a.livingPlaceName) LIKE :q '
                . 'OR LOWER(a.livingPlaceCity) LIKE :q '
                . 'OR LOWER(ra.name) LIKE :q'
            )
            ->andWhere('a.id IS NOT NULL OR ra.id IS NOT NULL')
            ->setParameter('q', '%' . $query . '%')
            ->setParameter('active', 'active')
            ->groupBy('u.id')
            ->orderBy('u.lastName', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        $results = [];
        foreach ($clients as $client) {
            $clientAnimals = $em->getRepository(Animal::class)->createQueryBuilder('a')
                ->leftJoin('a.referents', 'r')
                ->where('a.owner = :client')
                ->orWhere('r.user = :client AND r.status = :activeStatus')
                ->setParameter('client', $client)
                ->setParameter('activeStatus', 'active')
                ->groupBy('a.id')
                ->orderBy('a.name', 'ASC')
                ->getQuery()
                ->getResult();

            $animals = [];
            foreach ($clientAnimals as $animal) {
                $structure = $animal->getStructure();
                $animals[] = [
                    'id' => $animal->getId(),
                    'name' => $animal->getName(),
                    'livingPlaceName' => $animal->getLivingPlaceName(),
                    'structureName' => $structure?->getName(),
                ];
            }
            $clientAddress = implode(', ', array_filter([
                $client->getAddress(),
                trim(($client->getPostalCode() ?? '') . ' ' . ($client->getCity() ?? '')),
            ]));

            $results[] = [
                'id' => $client->getId(),
                'name' => $client->getFullName(),
                'email' => $client->getEmail(),
                'address' => $clientAddress ?: null,
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
    public function searchProfessionals(Request $request, UserRepository $userRepository): JsonResponse
    {
        $query = mb_strtolower(trim($request->query->get('q', '')));
        if (strlen($query) < 2) {
            return new JsonResponse(['professionals' => []]);
        }

        /** @var User $user */
        $user = $this->getUser();

        $pros = $userRepository->createProfessionalQueryBuilder()
            ->andWhere('u.id != :self')
            ->andWhere('LOWER(u.firstName) LIKE :q OR LOWER(u.lastName) LIKE :q OR LOWER(u.specialty) LIKE :q')
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
