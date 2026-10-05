<?php

namespace App\Controller;

use App\Entity\Tournee;
use App\Entity\TourneeStop;
use App\Repository\AppointmentRepository;
use App\Repository\TourneeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/tournees')]
#[IsGranted('ROLE_PRO')]
class TourneeController extends AbstractController
{
    #[Route('/', name: 'app_tournee_index', methods: ['GET'])]
    public function index(TourneeRepository $tourneeRepository): Response
    {
        $user = $this->getUser();
        $tournees = $tourneeRepository->findByUser($user);
        $today = $tourneeRepository->findTodayByUser($user);

        return $this->render('tournee/index.html.twig', [
            'tournees' => $tournees,
            'today' => $today,
        ]);
    }

    #[Route('/nouvelle', name: 'app_tournee_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, AppointmentRepository $appointmentRepository): Response
    {
        if ($request->isMethod('POST')) {
            $date = $request->request->get('date');
            $name = $request->request->get('name');
            $notes = $request->request->get('notes');

            if (!$date) {
                $this->addFlash('danger', 'La date est obligatoire.');
                return $this->redirectToRoute('app_tournee_new');
            }

            $tournee = new Tournee();
            $tournee->setDate(new \DateTimeImmutable($date));
            $tournee->setName($name ?: null);
            $tournee->setNotes($notes ?: null);
            $tournee->setCreatedBy($this->getUser());

            $appointmentIds = $request->request->all('appointments');
            if ($appointmentIds) {
                $position = 0;
                foreach ($appointmentIds as $appointmentId) {
                    $appointment = $appointmentRepository->find($appointmentId);
                    if ($appointment) {
                        $stop = new TourneeStop();
                        $stop->setAppointment($appointment);
                        $stop->setPosition($position++);
                        $tournee->addStop($stop);
                    }
                }
            }

            $em->persist($tournee);
            $em->flush();

            $this->addFlash('success', 'Tournée créée avec succès.');
            return $this->redirectToRoute('app_tournee_show', ['id' => $tournee->getId()]);
        }

        $date = $request->query->get('date', (new \DateTimeImmutable())->format('Y-m-d'));
        $dayStart = new \DateTimeImmutable($date . ' 00:00:00');
        $dayEnd = new \DateTimeImmutable($date . ' 23:59:59');

        $appointments = $appointmentRepository->createQueryBuilder('a')
            ->where('a.createdBy = :user')
            ->andWhere('a.scheduledAt BETWEEN :start AND :end')
            ->andWhere('a.status != :cancelled')
            ->setParameter('user', $this->getUser())
            ->setParameter('start', $dayStart)
            ->setParameter('end', $dayEnd)
            ->setParameter('cancelled', 'CANCELLED')
            ->orderBy('a.scheduledAt', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('tournee/new.html.twig', [
            'date' => $date,
            'appointments' => $appointments,
        ]);
    }

    #[Route('/{id}', name: 'app_tournee_show', methods: ['GET'])]
    public function show(Tournee $tournee): Response
    {
        if ($tournee->getCreatedBy() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('tournee/show.html.twig', [
            'tournee' => $tournee,
        ]);
    }

    #[Route('/{id}/reordonner', name: 'app_tournee_reorder', methods: ['POST'])]
    public function reorder(Request $request, Tournee $tournee, EntityManagerInterface $em): JsonResponse
    {
        if ($tournee->getCreatedBy() !== $this->getUser()) {
            return new JsonResponse(['error' => 'Accès refusé'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $order = $data['order'] ?? [];

        foreach ($tournee->getStops() as $stop) {
            $pos = array_search($stop->getId(), $order);
            if ($pos !== false) {
                $stop->setPosition($pos);
            }
        }

        $em->flush();
        return new JsonResponse(['success' => true]);
    }

    #[Route('/{id}/stop/{stopId}/toggle', name: 'app_tournee_stop_toggle', methods: ['POST'])]
    public function toggleStop(Request $request, Tournee $tournee, int $stopId, EntityManagerInterface $em): Response
    {
        if ($tournee->getCreatedBy() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        foreach ($tournee->getStops() as $stop) {
            if ($stop->getId() === $stopId) {
                $stop->setCompleted(!$stop->isCompleted());
                $em->flush();
                break;
            }
        }

        return $this->redirectToRoute('app_tournee_show', ['id' => $tournee->getId()]);
    }

    #[Route('/{id}/ajouter-etape', name: 'app_tournee_add_stop', methods: ['POST'])]
    public function addStop(Request $request, Tournee $tournee, EntityManagerInterface $em): Response
    {
        if ($tournee->getCreatedBy() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $label = $request->request->get('label');
        $location = $request->request->get('location');

        if ($label) {
            $maxPosition = 0;
            foreach ($tournee->getStops() as $s) {
                if ($s->getPosition() > $maxPosition) {
                    $maxPosition = $s->getPosition();
                }
            }

            $stop = new TourneeStop();
            $stop->setLabel($label);
            $stop->setLocation($location ?: null);
            $stop->setPosition($maxPosition + 1);
            $tournee->addStop($stop);
            $em->flush();

            $this->addFlash('success', 'Étape ajoutée.');
        }

        return $this->redirectToRoute('app_tournee_show', ['id' => $tournee->getId()]);
    }

    #[Route('/{id}/supprimer', name: 'app_tournee_delete', methods: ['POST'])]
    public function delete(Request $request, Tournee $tournee, EntityManagerInterface $em): Response
    {
        if ($tournee->getCreatedBy() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('delete' . $tournee->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $em->remove($tournee);
        $em->flush();

        $this->addFlash('success', 'Tournée supprimée.');
        return $this->redirectToRoute('app_tournee_index');
    }

    #[Route('/api/rdv-jour', name: 'app_tournee_api_appointments', methods: ['GET'])]
    public function apiAppointments(Request $request, AppointmentRepository $appointmentRepository): JsonResponse
    {
        $date = $request->query->get('date', (new \DateTimeImmutable())->format('Y-m-d'));
        $dayStart = new \DateTimeImmutable($date . ' 00:00:00');
        $dayEnd = new \DateTimeImmutable($date . ' 23:59:59');

        $appointments = $appointmentRepository->createQueryBuilder('a')
            ->where('a.createdBy = :user')
            ->andWhere('a.scheduledAt BETWEEN :start AND :end')
            ->andWhere('a.status != :cancelled')
            ->setParameter('user', $this->getUser())
            ->setParameter('start', $dayStart)
            ->setParameter('end', $dayEnd)
            ->setParameter('cancelled', 'CANCELLED')
            ->orderBy('a.scheduledAt', 'ASC')
            ->getQuery()
            ->getResult();

        $data = [];
        foreach ($appointments as $appt) {
            $data[] = [
                'id' => $appt->getId(),
                'reason' => $appt->getReason(),
                'time' => $appt->getScheduledAt()->format('H:i'),
                'location' => $appt->getLocation(),
                'client' => $appt->getClient() ? $appt->getClient()->getFullName() : null,
                'duration' => $appt->getDuration(),
            ];
        }

        return new JsonResponse($data);
    }
}
