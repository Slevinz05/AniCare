<?php

namespace App\Controller;

use App\Repository\HealthBookEntryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')] // 🔒 Seuls les utilisateurs connectés ont accès au calendrier
final class CalendarController extends AbstractController
{
    #[Route('/calendar', name: 'app_calendar', methods: ['GET'])]
    public function index(Request $request, HealthBookEntryRepository $repository): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // 1. Gestion du mois et de l'année (courants par défaut)
        $month = (int) $request->query->get('month', date('n'));
        $year = (int) $request->query->get('year', date('Y'));

        // 2. Calcul des dates clés du mois
        $firstDayOfMonth = new \DateTime("$year-$month-01");
        $daysInMonth = (int) $firstDayOfMonth->format('t');
        $startOfWeekDay = (int) $firstDayOfMonth->format('N'); // 1 (Lundi) à 7 (Dimanche)

        // 3. Récupération des activités médicales du mois sélectionné (FILTRÉ PAR ACCÈS)
        $startPeriod = new \DateTimeImmutable("$year-$month-01 00:00:00");
        $endPeriod = $startPeriod->modify('last day of this month')->setTime(23, 59, 59);
        
        // 🔑 Nouvelle requête Doctrine avec jointure sur le propriétaire et les partages de l'animal
        $entries = $repository->createQueryBuilder('h')
            ->join('h.animal', 'a') // Jointure vers l'animal rattaché à l'acte
            ->leftJoin('a.animalShares', 's') // Jointure vers les partages de cet animal
            ->where('h.date BETWEEN :start AND :end')
            ->andWhere(
                $repository->createQueryBuilder('x')->expr()->orX(
                    'a.owner = :user',               // Condition 1 : L'utilisateur est le propriétaire
                    's.sharedWithEmail = :email'     // Condition 2 : L'animal lui est partagé par email
                )
            )
            ->setParameter('start', $startPeriod)
            ->setParameter('end', $endPeriod)
            ->setParameter('user', $user)
            ->setParameter('email', $user->getEmail())
            ->orderBy('h.date', 'ASC')
            ->getQuery()
            ->getResult();

        // 4. Indexation des activités par jour du mois pour un accès rapide en Twig
        $entriesByDay = [];
        foreach ($entries as $entry) {
            if ($entry->getDate()) {
                $dayKey = (int) $entry->getDate()->format('j');
                $entriesByDay[$dayKey][] = $entry;
            }
        }

        // 5. Calcul des mois précédent / suivant pour la navigation
        $prevMonth = $month - 1 === 0 ? 12 : $month - 1;
        $prevYear = $month - 1 === 0 ? $year - 1 : $year;
        $nextMonth = $month + 1 === 13 ? 1 : $month + 1;
        $nextYear = $month + 1 === 13 ? $year + 1 : $year;

        // Traduction française des mois
        $monthsFr = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril', 5 => 'Mai', 6 => 'Juin',
            7 => 'Juillet', 8 => 'Août', 9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];

        return $this->render('calendar/index.html.twig', [
            'currentMonthName' => $monthsFr[$month],
            'currentMonth' => $month,
            'currentYear' => $year,
            'daysInMonth' => $daysInMonth,
            'startOfWeekDay' => $startOfWeekDay,
            'entriesByDay' => $entriesByDay,
            'prevMonth' => $prevMonth,
            'prevYear' => $prevYear,
            'nextMonth' => $nextMonth,
            'nextYear' => $nextYear,
        ]);
    }
}