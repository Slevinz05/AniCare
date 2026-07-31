<?php

namespace App\Controller\Api;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class ChatbotController extends AbstractController
{
    public function __construct(
        #[Autowire('%env(CHATBOT_API_KEY)%')]
        private readonly string $chatbotApiKey,
    ) {
    }

    private function isValidKey(Request $request): bool
    {
        $header = $request->headers->get('X-Chatbot-Key');

        return $header && hash_equals($this->chatbotApiKey, $header);
    }

    #[Route('/api/professionals/chatbot', name: 'api_professionals_chatbot', methods: ['GET'])]
    public function search(Request $request, UserRepository $userRepository): JsonResponse
    {
        if (!$this->isValidKey($request)) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $specialty = $request->query->get('specialty');
        $department = $request->query->get('department');
        $query = $request->query->get('q');

        $professionals = $userRepository->findProfessionalsFiltered($specialty, $department, $query);

        $results = array_map(fn($pro) => [
            'id' => $pro->getId(),
            'fullName' => $pro->getFullName(),
            'email' => $pro->getEmail(),
            'phone' => $pro->getPhone(),
            'specialty' => $pro->getSpecialty(),
            'city' => $pro->getCity(),
            'postalCode' => $pro->getPostalCode(),
            'department' => $pro->getDepartment(),
            'bio' => $pro->getBio(),
            'interventionDepartments' => $pro->getInterventionDepartments(),
            'experienceYears' => $pro->getExperienceYears(),
            'profileUrl' => '/annuaire/' . $pro->getId(),
        ], $professionals);

        return $this->json($results);
    }

    #[Route('/api/professionals/specialties', name: 'api_professionals_specialties', methods: ['GET'])]
    public function specialties(Request $request): JsonResponse
    {
        if (!$this->isValidKey($request)) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        return $this->json([
            'Veterinaire',
            'Dentiste',
            'Osteopathe',
            'Marechal-ferrant',
            'Shiatsu',
            'Massotherapeute',
            'Physiotherapeute',
            'Nutritionniste',
            'Comportementaliste',
            'Autre',
        ]);
    }
}
