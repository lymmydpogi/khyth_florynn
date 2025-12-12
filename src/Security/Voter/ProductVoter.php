<?php

namespace App\Security\Voter;

use App\Entity\Product;
use App\Entity\User;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class ProductVoter extends Voter
{
    public const EDIT = 'PRODUCT_EDIT';
    public const DELETE = 'PRODUCT_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::DELETE])
            && $subject instanceof Product;
    }

    protected function voteOnAttribute(string $attribute, mixed $product, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        // Admin always allowed
        if ($user->isAdmin()) {
            return true;
        }

        // Staff can modify products created by other staff, but not admin-created products
        if ($user->isStaff()) {
            $createdBy = $product->getCreatedBy();
            
            // Never allow staff to modify admin-created products
            if ($createdBy && $createdBy->isAdmin()) {
                return false;
            }
            
            // Staff can modify products created by themselves or other staff
            return true;
        }

        return false;
    }
}


