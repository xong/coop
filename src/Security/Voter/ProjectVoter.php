<?php

namespace App\Security\Voter;

use App\Entity\Project;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ProjectVoter extends Voter
{
    const VIEW = 'PROJECT_VIEW';
    const EDIT = 'PROJECT_EDIT';
    const MANAGE_MEMBERS = 'PROJECT_MANAGE_MEMBERS';
    const INVITE = 'PROJECT_INVITE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::MANAGE_MEMBERS, self::INVITE])
            && $subject instanceof Project;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var Project $project */
        $project = $subject;

        // Check if user is member of the organization
        $org = $project->getOrganization();
        if (!$org->isMember($user)) {
            return false;
        }

        return match ($attribute) {
            self::VIEW => $project->isPublic() || $project->isMember($user),
            self::EDIT, self::MANAGE_MEMBERS, self::INVITE => $project->isAdmin($user),
            default => false,
        };
    }
}
