<?php

namespace App\Security\Voter;

use App\Entity\Order;
use App\Entity\User;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class OrderVoter extends Voter
{
    public const EDIT = 'ORDER_EDIT';
    public const DELETE = 'ORDER_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::DELETE])
            && $subject instanceof Order;
    }

    protected function voteOnAttribute(string $attribute, mixed $order, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        // Admin always allowed
        if ($user->isAdmin()) {
            return true;
        }

        // Staff can modify orders created by other staff, but not admin-created orders
        if ($user->isStaff()) {
            $createdBy = $order->getCreatedBy();
            
            // Never allow staff to modify admin-created orders
            if ($createdBy && $createdBy->isAdmin()) {
                return false;
            }
            
            // Staff can modify orders created by themselves or other staff
            return true;
        }

        return false;
    }
}
