<?php

namespace App\Security\Voter;

use App\Entity\Organization;
use App\Entity\OrganizationMember;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class OrganizationVoter extends Voter
{
    const VIEW = 'ORG_VIEW';
    const EDIT = 'ORG_EDIT';
    const MANAGE_MEMBERS = 'ORG_MANAGE_MEMBERS';
    const INVITE = 'ORG_INVITE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::MANAGE_MEMBERS, self::INVITE])
            && $subject instanceof Organization;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var Organization $organization */
        $organization = $subject;

        return match ($attribute) {
            self::VIEW => $organization->isMember($user),
            self::EDIT, self::MANAGE_MEMBERS, self::INVITE => $organization->isAdmin($user),
            default => false,
        };
    }
}
