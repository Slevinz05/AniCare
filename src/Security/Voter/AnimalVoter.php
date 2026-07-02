<?php

namespace App\Security\Voter;

use App\Entity\Animal;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class AnimalVoter extends Voter
{
    public const VIEW = 'ANIMAL_VIEW';
    public const EDIT = 'ANIMAL_EDIT';
    public const DELETE = 'ANIMAL_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof Animal
            && in_array($attribute, [self::VIEW, self::EDIT, self::DELETE], true);
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
            self::DELETE => $animal->getOwner() === $user,
            default => false,
        };
    }

    private function canView(Animal $animal, User $user): bool
    {
        if ($animal->getOwner() === $user) {
            return true;
        }

        return $this->hasShare($animal, $user);
    }

    private function canEdit(Animal $animal, User $user): bool
    {
        if ($animal->getOwner() === $user) {
            return true;
        }

        return $this->hasWriteShare($animal, $user);
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
}
