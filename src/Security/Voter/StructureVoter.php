<?php

namespace App\Security\Voter;

use App\Entity\Structure;
use App\Entity\StructureMembership;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class StructureVoter extends Voter
{
    public const VIEW = 'STRUCTURE_VIEW';
    public const EDIT = 'STRUCTURE_EDIT';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof Structure
            && in_array($attribute, [self::VIEW, self::EDIT], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var Structure $structure */
        $structure = $subject;

        return match ($attribute) {
            self::VIEW => $this->isMember($structure, $user),
            self::EDIT => $this->isManager($structure, $user),
            default => false,
        };
    }

    private function isMember(Structure $structure, User $user): bool
    {
        foreach ($structure->getMemberships() as $membership) {
            if ($membership->getUser() === $user) {
                return true;
            }
        }

        return false;
    }

    private function isManager(Structure $structure, User $user): bool
    {
        foreach ($structure->getMemberships() as $membership) {
            if ($membership->getUser() === $user && $membership->getRole() === StructureMembership::ROLE_MANAGER) {
                return true;
            }
        }

        return false;
    }
}
