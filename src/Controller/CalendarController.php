<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\AppointmentRepository;
use App\Repository\HealthBookEntryRepository;
use App\Repository\ReminderRepository;
use App\Entity\StructureMembership;
use App\Repository\StructureMembershipRepository;
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
        StructureMembershipRepository $membershipRepo,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $structureIds = array_map(
            fn($m) => $m->getStructure()->getId(),
            $membershipRepo->findBy(['user' => $user, 'role' => StructureMembership::ROLE_MANAGER])
        );

        $view = $request->query->get('view', 'month');
        $month = (int) $request->query->get('month', date('n'));
        $year = (int) $request->query->get('year', date('Y'));
        $day = (int) $request->query->get('day', date('j'));

        $todayStart = new \DateTimeImmutable('today 00:00:00');
        $todayEnd = new \DateTimeImmutable('today 23:59:59');
        $todayEvents = $this->mergeEvents($healthRepo, $appointmentRepo, $reminderRepo, $todayStart, $todayEnd, $user, $structureIds);

        $pendingAppointments = $appointmentRepo->findByPeriodAndUser(
            new \DateTimeImmutable('today 00:00:00'),
            new \DateTimeImmutable('+30 days 23:59:59'),
            $user,
            $structureIds
        );
        $pendingCount = 0;
        foreach ($pendingAppointments as $apt) {
            if ($apt->getStatus() === 'PENDING' && $apt->getCreatedBy() !== $user) {
                $pendingCount++;
            }
        }

        // Les RDV créés par l'utilisateur lui-même ne sont pas des notifications pour lui
        $notifEvents = array_values(array_filter(
            $todayEvents,
            fn(array $e) => !($e['type'] === 'appointment' && $e['createdByMe'])
        ));

        $extra = [
            'todayEvents' => $todayEvents,
            'notifEvents' => $notifEvents,
            'todayDate' => $todayStart,
            'todayDayName' => self::DAYS_FR[((int) $todayStart->format('N')) - 1],
            'pendingAppointmentsCount' => $pendingCount,
        ];

        if ($view === 'day') {
            return $this->dayView($user, $year, $month, $day, $healthRepo, $appointmentRepo, $reminderRepo, $structureIds, $extra);
        }

        if ($view === 'week') {
            return $this->weekView($user, $year, $month, $day, $healthRepo, $appointmentRepo, $reminderRepo, $structureIds, $extra);
        }

        return $this->monthView($user, $year, $month, $healthRepo, $appointmentRepo, $reminderRepo, $structureIds, $extra);
    }

    private function monthView(User $user, int $year, int $month, HealthBookEntryRepository $healthRepo, AppointmentRepository $appointmentRepo, ReminderRepository $reminderRepo, array $structureIds = [], array $extra = []): Response
    {
        $firstDayOfMonth = new \DateTime("$year-$month-01");
        $daysInMonth = (int) $firstDayOfMonth->format('t');
        $startOfWeekDay = (int) $firstDayOfMonth->format('N');

        $startPeriod = new \DateTimeImmutable("$year-$month-01 00:00:00");
        $endPeriod = $startPeriod->modify('last day of this month')->setTime(23, 59, 59);

        $events = $this->mergeEvents($healthRepo, $appointmentRepo, $reminderRepo, $startPeriod, $endPeriod, $user, $structureIds);

        $eventsByDay = [];
        foreach ($events as $event) {
            $dayKey = (int) $event['date']->format('j');
            $eventsByDay[$dayKey][] = $event;
        }

        $prevMonth = $month - 1 === 0 ? 12 : $month - 1;
        $prevYear = $month - 1 === 0 ? $year - 1 : $year;
        $nextMonth = $month + 1 === 13 ? 1 : $month + 1;
        $nextYear = $month + 1 === 13 ? $year + 1 : $year;

        return $this->render('calendar/index.html.twig', array_merge([
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
        ], $extra));
    }

    private function weekView(User $user, int $year, int $month, int $day, HealthBookEntryRepository $healthRepo, AppointmentRepository $appointmentRepo, ReminderRepository $reminderRepo, array $structureIds = [], array $extra = []): Response
    {
        $current = new \DateTimeImmutable("$year-$month-$day");
        $dayOfWeek = (int) $current->format('N');
        $monday = $current->modify('-' . ($dayOfWeek - 1) . ' days');
        $sunday = $monday->modify('+6 days')->setTime(23, 59, 59);

        $events = $this->mergeEvents($healthRepo, $appointmentRepo, $reminderRepo, $monday, $sunday, $user, $structureIds);

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

        return $this->render('calendar/index.html.twig', array_merge([
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
        ], $extra));
    }

    private function dayView(User $user, int $year, int $month, int $day, HealthBookEntryRepository $healthRepo, AppointmentRepository $appointmentRepo, ReminderRepository $reminderRepo, array $structureIds = [], array $extra = []): Response
    {
        $current = new \DateTimeImmutable("$year-$month-$day");
        $startOfDay = $current->setTime(0, 0, 0);
        $endOfDay = $current->setTime(23, 59, 59);

        $events = $this->mergeEvents($healthRepo, $appointmentRepo, $reminderRepo, $startOfDay, $endOfDay, $user, $structureIds);

        $prevDay = $current->modify('-1 day');
        $nextDay = $current->modify('+1 day');

        return $this->render('calendar/index.html.twig', array_merge([
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
        ], $extra));
    }

    private function mergeEvents(HealthBookEntryRepository $healthRepo, AppointmentRepository $appointmentRepo, ReminderRepository $reminderRepo, \DateTimeImmutable $start, \DateTimeImmutable $end, User $user, array $structureIds = []): array
    {
        $events = [];

        foreach ($healthRepo->findByMonthAndUser($start, $end, $user, $structureIds) as $entry) {
            $animal = $entry->getAnimal();
            $owner = $animal?->getOwner();
            $locationParts = array_filter([
                $animal?->getLivingPlaceName(),
                $animal?->getLivingPlaceCity(),
            ]);

            $events[] = [
                'type' => 'consultation',
                'title' => $entry->getTitle(),
                'date' => $entry->getDate(),
                'endAt' => null,
                'duration' => null,
                'animal' => $animal?->getName() ?? 'Cheval',
                'professional' => $entry->getVeterinarianName(),
                'link' => 'app_health_book_entry_show',
                'linkParams' => ['id' => $entry->getId()],
                'id' => $entry->getId(),
                'status' => null,
                'clientName' => $owner?->getFullName(),
                'location' => $locationParts ? implode(', ', $locationParts) : null,
            ];
        }

        foreach ($appointmentRepo->findByPeriodAndUser($start, $end, $user, $structureIds) as $appointment) {
            $duration = $appointment->getDuration();
            $endAt = $duration ? $appointment->getScheduledAt()->modify("+{$duration} minutes") : null;

            $client = $appointment->getClient();
            $firstAnimal = $appointment->getAnimals()->isEmpty() ? $appointment->getAnimal() : $appointment->getAnimals()->first();
            $locationParts = array_filter([
                $appointment->getLocation(),
                $firstAnimal?->getLivingPlaceName(),
                $firstAnimal?->getLivingPlaceCity(),
            ]);

            $events[] = [
                'type' => 'appointment',
                'title' => $appointment->getReason(),
                'date' => $appointment->getScheduledAt(),
                'endAt' => $endAt,
                'duration' => $duration,
                'animal' => $appointment->getAnimals()->count() > 1
                    ? $appointment->getAnimals()->count() . ' chevaux'
                    : ($appointment->getAnimals()->isEmpty()
                        ? ($appointment->getAnimal()?->getName() ?? $appointment->getEventTypeShortLabel())
                        : $appointment->getAnimals()->first()->getName()),
                'hasAnimal' => $firstAnimal !== null,
                'typeLabel' => $appointment->getEventTypeShortLabel(),
                'createdByMe' => $appointment->getCreatedBy() === $user,
                'professional' => null,
                'link' => 'app_appointment_show',
                'linkParams' => ['id' => $appointment->getId()],
                'id' => $appointment->getId(),
                'status' => $appointment->getStatus(),
                'clientName' => $client?->getFullName(),
                'location' => $locationParts ? implode(', ', $locationParts) : null,
            ];
        }

        foreach ($reminderRepo->findByPeriodAndUser($start, $end, $user) as $reminder) {
            $animal = $reminder->getAnimal();
            $owner = $animal?->getOwner();

            $events[] = [
                'type' => 'reminder',
                'title' => $reminder->getTitle(),
                'date' => $reminder->getNextOccurrence() ?? $reminder->getScheduledAt(),
                'endAt' => null,
                'duration' => null,
                'animal' => $animal?->getName() ?? 'Cheval',
                'professional' => null,
                'link' => $animal ? 'app_animal_show' : 'app_calendar',
                'linkParams' => $animal ? ['slug' => $animal->getSlug()] : [],
                'id' => $animal?->getId() ?? 0,
                'status' => $reminder->getRecurrence(),
                'clientName' => $owner?->getFullName(),
                'location' => null,
            ];
        }

        usort($events, fn(array $a, array $b) => $a['date'] <=> $b['date']);

        return $events;
    }
}
