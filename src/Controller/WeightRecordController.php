<?php

namespace App\Controller;

use App\Entity\Animal;
use App\Entity\WeightRecord;
use App\Form\WeightRecordType;
use App\Repository\WeightRecordRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/poids')]
#[IsGranted('ROLE_USER')]
final class WeightRecordController extends AbstractController
{
    #[Route('/cheval/{id}', name: 'app_weight_record_index', methods: ['GET'])]
    public function index(Animal $animal, WeightRecordRepository $repository): Response
    {
        $this->denyAccessUnlessGranted('ANIMAL_VIEW', $animal);

        $records = $repository->findByAnimalOrdered($animal);

        return $this->render('weight_record/index.html.twig', [
            'animal' => $animal,
            'records' => $records,
        ]);
    }

    #[Route('/cheval/{id}/donnees.json', name: 'app_weight_record_chart_data', methods: ['GET'])]
    public function chartData(Animal $animal, WeightRecordRepository $repository): JsonResponse
    {
        $this->denyAccessUnlessGranted('ANIMAL_VIEW', $animal);

        $records = $repository->findByAnimalOrdered($animal);

        $data = array_map(fn(WeightRecord $r) => [
            'date' => $r->getRecordedAt()->format('Y-m-d'),
            'weight' => $r->getWeight(),
        ], $records);

        return $this->json($data);
    }

    #[Route('/cheval/{id}/ajouter', name: 'app_weight_record_new', methods: ['GET', 'POST'])]
    public function new(Request $request, Animal $animal, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ANIMAL_EDIT', $animal);

        $record = new WeightRecord();
        $record->setRecordedAt(new \DateTimeImmutable());

        $form = $this->createForm(WeightRecordType::class, $record);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $record->setAnimal($animal);

            $animal->setWeight($record->getWeight());

            $em->persist($record);
            $em->flush();

            return $this->redirectToRoute('app_weight_record_index', ['id' => $animal->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('weight_record/new.html.twig', [
            'animal' => $animal,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'app_weight_record_delete', methods: ['POST'])]
    public function delete(Request $request, WeightRecord $record, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ANIMAL_DELETE', $record->getAnimal());

        $animalId = $record->getAnimal()->getId();

        if ($this->isCsrfTokenValid('delete' . $record->getId(), $request->getPayload()->getString('_token'))) {
            $em->remove($record);
            $em->flush();
        }

        return $this->redirectToRoute('app_weight_record_index', ['id' => $animalId], Response::HTTP_SEE_OTHER);
    }
}
