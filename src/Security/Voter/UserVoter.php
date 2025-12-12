<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class UserVoter extends Voter
{
    public const EDIT = 'USER_EDIT';
    public const DELETE = 'USER_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::DELETE])
            && $subject instanceof User;
    }

    protected function voteOnAttribute(string $attribute, mixed $targetUser, TokenInterface $token): bool
    {
        $currentUser = $token->getUser();

        if (!$currentUser instanceof User) {
            return false;
        }

        // Admin can always edit/delete any user
        if ($currentUser->isAdmin()) {
            return true;
        }

        // Staff can edit other staff users, but never admin users
        if ($currentUser->isStaff()) {
            // Never allow staff to edit/delete admin users
            if ($targetUser->isAdmin()) {
                return false;
            }
            
            // Staff can edit/delete other staff users (including themselves)
            if ($targetUser->isStaff()) {
                return true;
            }
            
            // Staff can also edit/delete client users
            return true;
        }

        // Users can edit themselves (for profile editing)
        if ($currentUser === $targetUser) {
            return $attribute === self::EDIT;
        }

        // Default: deny access
        return false;
    }
}

