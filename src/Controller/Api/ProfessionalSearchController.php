<?php

namespace App\Controller\Api;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class ProfessionalSearchController extends AbstractController
{
    #[Route('/api/professionals/search', name: 'api_professionals_search', methods: ['GET'])]
    public function search(Request $request, UserRepository $userRepository): JsonResponse
    {
        $query = trim($request->query->get('q', ''));

        if (strlen($query) < 2) {
            return $this->json([]);
        }

        $professionals = $userRepository->searchProfessionals($query);

        $results = array_map(fn($pro) => [
            'id' => $pro->getId(),
            'fullName' => $pro->getFullName(),
            'email' => $pro->getEmail(),
            'phone' => $pro->getPhone(),
            'city' => $pro->getCity(),
        ], $professionals);

        return $this->json($results);
    }
}
