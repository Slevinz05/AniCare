<?php

namespace App\Security\Voter;

use App\Entity\Animal;
use App\Entity\StructureMembership;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class AnimalVoter extends Voter
{
    public const VIEW = 'ANIMAL_VIEW';
    public const EDIT = 'ANIMAL_EDIT';
    public const EDIT_HEALTH = 'ANIMAL_EDIT_HEALTH';
    public const DELETE = 'ANIMAL_DELETE';
    public const REQUEST_DELETE = 'ANIMAL_REQUEST_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof Animal
            && in_array($attribute, [self::VIEW, self::EDIT, self::EDIT_HEALTH, self::DELETE, self::REQUEST_DELETE], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var Animal $animal */
        $animal = $subject;

        return match ($attribute) {
            self::VIEW => $this->canView($animal, $user),
            self::EDIT => $this->canEdit($animal, $user),
            self::EDIT_HEALTH => $this->canEditHealth($animal, $user),
            self::DELETE => $animal->getOwner() === $user,
            self::REQUEST_DELETE => $this->isStructureManager($animal, $user),
            default => false,
        };
    }

    private function canView(Animal $animal, User $user): bool
    {
        if ($animal->getOwner() === $user) {
            return true;
        }

        if ($this->hasShare($animal, $user)) {
            return true;
        }

        return $this->hasStructureAccess($animal, $user);
    }

    private function canEdit(Animal $animal, User $user): bool
    {
        if ($animal->getOwner() === $user) {
            return true;
        }

        if ($this->hasWriteShare($animal, $user)) {
            return true;
        }

        return $this->hasStructureAccess($animal, $user, [StructureMembership::ROLE_MANAGER, StructureMembership::ROLE_PRO]);
    }

    private function canEditHealth(Animal $animal, User $user): bool
    {
        if ($animal->getOwner() === $user) {
            return true;
        }

        if ($this->hasWriteShare($animal, $user)) {
            return true;
        }

        return $this->hasStructureAccess($animal, $user, [StructureMembership::ROLE_PRO]);
    }

    private function hasShare(Animal $animal, User $user): bool
    {
        foreach ($animal->getAnimalShares() as $share) {
            if ($share->getSharedWithEmail() === $user->getEmail()) {
                return true;
            }
        }

        return false;
    }

    private function hasWriteShare(Animal $animal, User $user): bool
    {
        foreach ($animal->getAnimalShares() as $share) {
            if ($share->getSharedWithEmail() === $user->getEmail()
                && $share->getPermissionLevel() === 'WRITE') {
                return true;
            }
        }

        return false;
    }

    private function hasStructureAccess(Animal $animal, User $user, ?array $roles = null): bool
    {
        $structure = $animal->getStructure();
        if (!$structure) {
            return false;
        }

        foreach ($structure->getMemberships() as $membership) {
            if ($membership->getUser() === $user) {
                if ($roles === null || in_array($membership->getRole(), $roles, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function isStructureManager(Animal $animal, User $user): bool
    {
        return $this->hasStructureAccess($animal, $user, [StructureMembership::ROLE_MANAGER]);
    }
}
