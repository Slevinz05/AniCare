<?php

namespace App\Controller\Api;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class UserSearchController extends AbstractController
{
    #[Route('/api/users/search', name: 'api_users_search', methods: ['GET'])]
    public function search(Request $request, UserRepository $userRepository): JsonResponse
    {
        $query = trim($request->query->get('q', ''));

        if (strlen($query) < 2) {
            return $this->json([]);
        }

        $users = $userRepository->searchAll($query);

        $results = array_map(fn($u) => [
            'id' => $u->getId(),
            'fullName' => $u->getFullName(),
            'email' => $u->getEmail(),
            'phone' => $u->getPhone(),
            'lastName' => $u->getLastName(),
            'firstName' => $u->getFirstName(),
            'accountType' => $u->getAccountType(),
            'isPro' => $u->hasProSpace(),
            'city' => $u->getCity(),
        ], $users);

        return $this->json($results);
    }
}
