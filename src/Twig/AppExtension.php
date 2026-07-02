<?php

namespace App\Twig;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class AppExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        #[Autowire('%env(bool:STRIPE_ENABLED)%')]
        private readonly bool $stripeEnabled,
    ) {
    }

    public function getGlobals(): array
    {
        return [
            'stripe_enabled' => $this->stripeEnabled,
        ];
    }
}
