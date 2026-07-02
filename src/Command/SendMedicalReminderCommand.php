<?php

namespace App\Command;

use App\Repository\HealthBookEntryRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(
    name: 'app:send-medical-reminders',
    description: 'Envoie un e-mail de rappel aux propriétaires pour les soins médicaux de la semaine.',
)]
class SendMedicalReminderCommand extends Command
{
    private HealthBookEntryRepository $healthBookRepository;
    private MailerInterface $mailer;

    public function __construct(HealthBookEntryRepository $healthBookRepository, MailerInterface $mailer)
    {
        $this->healthBookRepository = $healthBookRepository;
        $this->mailer = $mailer;

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // 1. On récupère les rappels prévus par exemple dans exactement 7 jours
        $targetDate = new \DateTimeImmutable('+7 days');
        // On va créer une méthode spécifique dans le Repository (voir Étape B)
        $upcomingEntries = $this->healthBookRepository->findByDate($targetDate);

        $emailCount = 0;

        foreach ($upcomingEntries as $entry) {
            $animal = $entry->getAnimal();
            $owner = $animal ? $animal->getOwner() : null;

            if ($owner && $owner->getUserIdentifier()) {
                // 2. Création et envoi de l'e-mail
                $email = (new Email())
                    ->from('ne-pas-repondre@anicare.com')
                    ->to($owner->getUserIdentifier()) // L'adresse email de l'utilisateur
                    ->subject(sprintf('⚠️ Rappel de soin pour %s', $animal->getName()))
                    ->html(sprintf(
                        '<p>Bonjour %s,</p>
                        <p>Ceci est un rappel automatique d\'<b>AniCare</b>.</p>
                        <p>Le soin ou vaccin suivant est programmé pour <b>%s</b> le %s :</p>
                        <ul>
                            <li><b>Activité :</b> %s</li>
                            <li><b>Type :</b> %s</li>
                        </ul>
                        <p>Prenez soin de vos compagnons !</p>',
                        $owner->getUserIdentifier(),
                        $animal->getName(),
                        $entry->getDate()->format('d/m/Y'),
                        $entry->getTitle(),
                        $entry->getType()
                    ));

                $this->mailer->send($email);
                $emailCount++;
            }
        }

        $io->success(sprintf('%d e-mail(s) de rappel ont été envoyés avec succès.', $emailCount));

        return Command::SUCCESS;
    }
}
