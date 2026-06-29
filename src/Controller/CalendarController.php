<?php

namespace App\Controller;

use App\Repository\HealthBookEntryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CalendarController extends AbstractController
{
    #[Route('/calendar', name: 'app_calendar', methods: ['GET'])]
    public function index(Request $request, HealthBookEntryRepository $repository): Response
    {
        // 1. Gestion du mois et de l'année (courants par défaut)
        $month = (int) $request->query->get('month', date('n'));
        $year = (int) $request->query->get('year', date('Y'));

        // 2. Calcul des dates clés du mois
        $firstDayOfMonth = new \DateTime("$year-$month-01");
        $daysInMonth = (int) $firstDayOfMonth->format('t');
        $startOfWeekDay = (int) $firstDayOfMonth->format('N'); // 1 (Lundi) à 7 (Dimanche)

        // 3. Récupération des activités médicales du mois sélectionné
        $startPeriod = new \DateTimeImmutable("$year-$month-01 00:00:00");
        $endPeriod = $startPeriod->modify('last day of this month')->setTime(23, 59, 59);
        
        $entries = $repository->createQueryBuilder('h')
            ->andWhere('h.date BETWEEN :start AND :end')
            ->setParameter('start', $startPeriod)
            ->setParameter('end', $endPeriod)
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