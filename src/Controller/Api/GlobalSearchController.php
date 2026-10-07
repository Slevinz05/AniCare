<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\AnimalRepository;
use App\Repository\StructureRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_PRO')]
final class GlobalSearchController extends AbstractController
{
    #[Route('/api/search', name: 'api_global_search', methods: ['GET'])]
    public function search(
        Request $request,
        AnimalRepository $animalRepo,
        UserRepository $userRepo,
        StructureRepository $structureRepo,
    ): JsonResponse {
        $query = trim($request->query->get('q', ''));

        if (mb_strlen($query) < 2) {
            return $this->json(['animals' => [], 'users' => [], 'structures' => []]);
        }

        /** @var User $pro */
        $pro = $this->getUser();

        $animals = $animalRepo->findByProHistory($pro, $query);
        $animalResults = array_map(fn($a) => [
            'id' => $a->getId(),
            'name' => $a->getName(),
            'slug' => $a->getSlug(),
            'breed' => $a->getBreed(),
            'owner' => $a->getOwner()?->getFullName(),
        ], array_slice($animals, 0, 8));

        $users = $userRepo->searchAll($query, 8);
        $userResults = array_map(fn($u) => [
            'id' => $u->getId(),
            'fullName' => $u->getFullName(),
            'accountType' => $u->getAccountType(),
            'city' => $u->getCity(),
        ], $users);

        $structures = $structureRepo->search($query);
        $structureResults = array_map(fn($s) => [
            'id' => $s->getId(),
            'name' => $s->getName(),
            'city' => $s->getCity(),
            'postalCode' => $s->getPostalCode(),
        ], array_slice($structures, 0, 6));

        return $this->json([
            'animals' => $animalResults,
            'users' => $userResults,
            'structures' => $structureResults,
        ]);
    }
}
