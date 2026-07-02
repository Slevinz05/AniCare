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
    description: 'Envoie un e-mail de rappel aux propriétaires pour les soins médicaux de la semaine.',
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

        $targetDate = new \DateTimeImmutable('+7 days');
        $upcomingEntries = $this->healthBookRepository->findByDate($targetDate);

        $emailCount = 0;

        foreach ($upcomingEntries as $entry) {
            $animal = $entry->getAnimal();
            $owner = $animal?->getOwner();

            if ($owner && $owner->getUserIdentifier()) {
                $email = (new TemplatedEmail())
                    ->from('ne-pas-repondre@anicare.com')
                    ->to($owner->getUserIdentifier())
                    ->subject(sprintf('Rappel de soin pour %s', $animal->getName()))
                    ->htmlTemplate('emails/medical_reminder.html.twig')
                    ->context([
                        'owner' => $owner,
                        'animal' => $animal,
                        'entry' => $entry,
                    ]);

                $this->mailer->send($email);
                $emailCount++;
            }
        }

        $io->success(sprintf('%d e-mail(s) de rappel ont été envoyés avec succès.', $emailCount));

        return Command::SUCCESS;
    }
}
