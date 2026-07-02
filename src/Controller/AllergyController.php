<?php

namespace App\Controller;

use App\Entity\Allergy;
use App\Entity\Animal;
use App\Form\AllergyType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/allergy')]
#[IsGranted('ROLE_USER')]
final class AllergyController extends AbstractController
{
    #[Route('/animal/{id}', name: 'app_allergy_index', methods: ['GET'])]
    public function index(Animal $animal): Response
    {
        $this->denyAccessUnlessGranted('ANIMAL_VIEW', $animal);

        return $this->render('allergy/index.html.twig', [
            'animal' => $animal,
            'allergies' => $animal->getAllergies(),
        ]);
    }

    #[Route('/animal/{id}/new', name: 'app_allergy_new', methods: ['GET', 'POST'])]
    public function new(Request $request, Animal $animal, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ANIMAL_EDIT', $animal);

        $allergy = new Allergy();

        $form = $this->createForm(AllergyType::class, $allergy);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $allergy->setAnimal($animal);

            $em->persist($allergy);
            $em->flush();

            return $this->redirectToRoute('app_allergy_index', ['id' => $animal->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('allergy/new.html.twig', [
            'animal' => $animal,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_allergy_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Allergy $allergy, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ANIMAL_EDIT', $allergy->getAnimal());

        $form = $this->createForm(AllergyType::class, $allergy);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            return $this->redirectToRoute('app_allergy_index', ['id' => $allergy->getAnimal()->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('allergy/edit.html.twig', [
            'allergy' => $allergy,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_allergy_delete', methods: ['POST'])]
    public function delete(Request $request, Allergy $allergy, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ANIMAL_DELETE', $allergy->getAnimal());

        $animalId = $allergy->getAnimal()->getId();

        if ($this->isCsrfTokenValid('delete' . $allergy->getId(), $request->getPayload()->getString('_token'))) {
            $em->remove($allergy);
            $em->flush();
        }

        return $this->redirectToRoute('app_allergy_index', ['id' => $animalId], Response::HTTP_SEE_OTHER);
    }
}
