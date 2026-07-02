<?php

namespace App\Command;

use App\Repository\HealthBookEntryRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;

#[AsCommand(
    name: 'app:send-medical-reminders',
    description: 'Envoie les e-mails de rappel pour les soins médicaux à venir et en retard.',
)]
class SendMedicalReminderCommand extends Command
{
    public function __construct(
        private readonly HealthBookEntryRepository $healthBookRepository,
        private readonly MailerInterface $mailer,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $emailCount = 0;

        $upcomingEntries = $this->healthBookRepository->findUpcomingReminders(7);
        foreach ($upcomingEntries as $entry) {
            $this->sendReminder($entry, 'Rappel à venir');
            $emailCount++;
        }

        $overdueEntries = $this->healthBookRepository->findOverdueReminders();
        foreach ($overdueEntries as $entry) {
            $this->sendReminder($entry, 'Rappel en retard');
            $emailCount++;
        }

        $io->success(sprintf('%d e-mail(s) de rappel envoyé(s).', $emailCount));

        return Command::SUCCESS;
    }

    private function sendReminder(object $entry, string $urgency): void
    {
        $animal = $entry->getAnimal();
        $owner = $animal?->getOwner();

        if (!$owner?->getUserIdentifier()) {
            return;
        }

        $email = (new TemplatedEmail())
            ->from('ne-pas-repondre@anicare.com')
            ->to($owner->getUserIdentifier())
            ->subject(sprintf('%s : %s pour %s', $urgency, $entry->getTitle(), $animal->getName()))
            ->htmlTemplate('emails/medical_reminder.html.twig')
            ->context([
                'owner' => $owner,
                'animal' => $animal,
                'entry' => $entry,
                'urgency' => $urgency,
            ]);

        $this->mailer->send($email);
    }
}
