<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\AppointmentRepository;
use App\Repository\HealthBookEntryRepository;
use App\Repository\ReminderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class CalendarController extends AbstractController
{
    private const MONTHS_FR = [
        1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril', 5 => 'Mai', 6 => 'Juin',
        7 => 'Juillet', 8 => 'Août', 9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
    ];

    private const DAYS_FR = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];

    #[Route('/calendrier', name: 'app_calendar', methods: ['GET'])]
    public function index(
        Request $request,
        HealthBookEntryRepository $healthRepo,
        AppointmentRepository $appointmentRepo,
        ReminderRepository $reminderRepo,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $view = $request->query->get('view', 'month');
        $month = (int) $request->query->get('month', date('n'));
        $year = (int) $request->query->get('year', date('Y'));
        $day = (int) $request->query->get('day', date('j'));

        if ($view === 'day') {
            return $this->dayView($user, $year, $month, $day, $healthRepo, $appointmentRepo, $reminderRepo);
        }

        if ($view === 'week') {
            return $this->weekView($user, $year, $month, $day, $healthRepo, $appointmentRepo, $reminderRepo);
        }

        return $this->monthView($user, $year, $month, $healthRepo, $appointmentRepo, $reminderRepo);
    }

    private function monthView(User $user, int $year, int $month, HealthBookEntryRepository $healthRepo, AppointmentRepository $appointmentRepo, ReminderRepository $reminderRepo): Response
    {
        $firstDayOfMonth = new \DateTime("$year-$month-01");
        $daysInMonth = (int) $firstDayOfMonth->format('t');
        $startOfWeekDay = (int) $firstDayOfMonth->format('N');

        $startPeriod = new \DateTimeImmutable("$year-$month-01 00:00:00");
        $endPeriod = $startPeriod->modify('last day of this month')->setTime(23, 59, 59);

        $events = $this->mergeEvents($healthRepo, $appointmentRepo, $reminderRepo, $startPeriod, $endPeriod, $user);

        $eventsByDay = [];
        foreach ($events as $event) {
            $dayKey = (int) $event['date']->format('j');
            $eventsByDay[$dayKey][] = $event;
        }

        $prevMonth = $month - 1 === 0 ? 12 : $month - 1;
        $prevYear = $month - 1 === 0 ? $year - 1 : $year;
        $nextMonth = $month + 1 === 13 ? 1 : $month + 1;
        $nextYear = $month + 1 === 13 ? $year + 1 : $year;

        return $this->render('calendar/index.html.twig', [
            'view' => 'month',
            'currentMonthName' => self::MONTHS_FR[$month],
            'currentMonth' => $month,
            'currentYear' => $year,
            'currentDay' => (int) date('j'),
            'daysInMonth' => $daysInMonth,
            'startOfWeekDay' => $startOfWeekDay,
            'eventsByDay' => $eventsByDay,
            'prevMonth' => $prevMonth,
            'prevYear' => $prevYear,
            'nextMonth' => $nextMonth,
            'nextYear' => $nextYear,
        ]);
    }

    private function weekView(User $user, int $year, int $month, int $day, HealthBookEntryRepository $healthRepo, AppointmentRepository $appointmentRepo, ReminderRepository $reminderRepo): Response
    {
        $current = new \DateTimeImmutable("$year-$month-$day");
        $dayOfWeek = (int) $current->format('N');
        $monday = $current->modify('-' . ($dayOfWeek - 1) . ' days');
        $sunday = $monday->modify('+6 days')->setTime(23, 59, 59);

        $events = $this->mergeEvents($healthRepo, $appointmentRepo, $reminderRepo, $monday, $sunday, $user);

        $weekDays = [];
        for ($i = 0; $i < 7; $i++) {
            $d = $monday->modify("+$i days");
            $dayNum = (int) $d->format('j');
            $weekDays[] = [
                'date' => $d,
                'dayName' => self::DAYS_FR[$i],
                'dayNum' => $dayNum,
                'monthNum' => (int) $d->format('n'),
                'isToday' => $d->format('Y-m-d') === date('Y-m-d'),
                'events' => [],
            ];
        }

        foreach ($events as $event) {
            $idx = ((int) $event['date']->format('N')) - 1;
            if (isset($weekDays[$idx])) {
                $weekDays[$idx]['events'][] = $event;
            }
        }

        $prevWeek = $monday->modify('-7 days');
        $nextWeek = $monday->modify('+7 days');

        return $this->render('calendar/index.html.twig', [
            'view' => 'week',
            'weekDays' => $weekDays,
            'monday' => $monday,
            'sunday' => $sunday,
            'currentMonth' => $month,
            'currentYear' => $year,
            'currentDay' => $day,
            'currentMonthName' => self::MONTHS_FR[(int) $monday->format('n')],
            'prevWeek' => $prevWeek,
            'nextWeek' => $nextWeek,
        ]);
    }

    private function dayView(User $user, int $year, int $month, int $day, HealthBookEntryRepository $healthRepo, AppointmentRepository $appointmentRepo, ReminderRepository $reminderRepo): Response
    {
        $current = new \DateTimeImmutable("$year-$month-$day");
        $startOfDay = $current->setTime(0, 0, 0);
        $endOfDay = $current->setTime(23, 59, 59);

        $events = $this->mergeEvents($healthRepo, $appointmentRepo, $reminderRepo, $startOfDay, $endOfDay, $user);

        $prevDay = $current->modify('-1 day');
        $nextDay = $current->modify('+1 day');

        return $this->render('calendar/index.html.twig', [
            'view' => 'day',
            'events' => $events,
            'currentDate' => $current,
            'currentMonth' => $month,
            'currentYear' => $year,
            'currentDay' => $day,
            'currentMonthName' => self::MONTHS_FR[$month],
            'dayName' => self::DAYS_FR[((int) $current->format('N')) - 1],
            'prevDay' => $prevDay,
            'nextDay' => $nextDay,
        ]);
    }

    private function mergeEvents(HealthBookEntryRepository $healthRepo, AppointmentRepository $appointmentRepo, ReminderRepository $reminderRepo, \DateTimeImmutable $start, \DateTimeImmutable $end, User $user): array
    {
        $events = [];

        foreach ($healthRepo->findByMonthAndUser($start, $end, $user) as $entry) {
            $events[] = [
                'type' => 'consultation',
                'title' => $entry->getTitle(),
                'date' => $entry->getDate(),
                'animal' => $entry->getAnimal()?->getName() ?? 'Cheval',
                'professional' => $entry->getVeterinarianName(),
                'link' => 'app_health_book_entry_show',
                'id' => $entry->getId(),
                'status' => null,
            ];
        }

        foreach ($appointmentRepo->findByPeriodAndUser($start, $end, $user) as $appointment) {
            $events[] = [
                'type' => 'appointment',
                'title' => $appointment->getReason(),
                'date' => $appointment->getScheduledAt(),
                'animal' => $appointment->getAnimal()?->getName() ?? 'Cheval',
                'professional' => null,
                'link' => 'app_appointment_index',
                'id' => $appointment->getId(),
                'status' => $appointment->getStatus(),
            ];
        }

        foreach ($reminderRepo->findByPeriodAndUser($start, $end, $user) as $reminder) {
            $events[] = [
                'type' => 'reminder',
                'title' => $reminder->getTitle(),
                'date' => $reminder->getNextOccurrence() ?? $reminder->getScheduledAt(),
                'animal' => $reminder->getAnimal()?->getName() ?? 'Cheval',
                'professional' => null,
                'link' => 'app_animal_show',
                'id' => $reminder->getAnimal()?->getId() ?? 0,
                'status' => $reminder->getRecurrence(),
            ];
        }

        usort($events, fn(array $a, array $b) => $a['date'] <=> $b['date']);

        return $events;
    }
}
