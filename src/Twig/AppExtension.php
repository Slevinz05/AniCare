<?php

namespace App\Twig;

use App\Repository\AnimalReferentRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        #[Autowire('%env(bool:STRIPE_ENABLED)%')]
        private readonly bool $stripeEnabled,
        private readonly AnimalReferentRepository $referentRepository,
        private readonly Security $security,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('pending_invitations_count', [$this, 'getPendingInvitationsCount']),
        ];
    }

    public function getPendingInvitationsCount(): int
    {
        $user = $this->security->getUser();
        if (!$user) {
            return 0;
        }

        return $this->referentRepository->countPendingForUser($user);
    }

    public function getGlobals(): array
    {
        return [
            'stripe_enabled' => $this->stripeEnabled,
        ];
    }
}
