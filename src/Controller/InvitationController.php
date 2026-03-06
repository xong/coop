<?php

namespace App\Controller;

use App\Entity\OrganizationMember;
use App\Entity\ProjectMember;
use App\Repository\OrganizationInvitationRepository;
use App\Repository\ProjectInvitationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/invite')]
class InvitationController extends AbstractController
{
    #[Route('/accept/{token}', name: 'app_invitation_accept')]
    public function accept(
        string $token,
        OrganizationInvitationRepository $orgInvRepo,
        ProjectInvitationRepository $projInvRepo,
        EntityManagerInterface $em,
    ): Response {
        // Try organization invitation
        $orgInv = $orgInvRepo->findByToken($token);
        if ($orgInv) {
            if ($orgInv->isExpired() || $orgInv->isAccepted()) {
                $this->addFlash('error', 'Diese Einladung ist abgelaufen oder wurde bereits verwendet.');
                return $this->redirectToRoute('app_dashboard');
            }

            $user = $this->getUser();
            if (!$user) {
                return $this->redirectToRoute('app_login');
            }

            $org = $orgInv->getOrganization();
            if (!$org->isMember($user)) {
                $member = new OrganizationMember();
                $member->setOrganization($org);
                $member->setUser($user);
                $member->setRole($orgInv->getRole());
                $member->setInvitedBy($orgInv->getInvitedBy());
                $em->persist($member);
            }

            $orgInv->setAcceptedAt(new \DateTimeImmutable());
            $em->flush();

            $this->addFlash('success', 'Du bist jetzt Mitglied von "' . $org->getName() . '".');
            return $this->redirectToRoute('app_organization_show', ['slug' => $org->getSlug()]);
        }

        // Try project invitation
        $projInv = $projInvRepo->findByToken($token);
        if ($projInv) {
            if ($projInv->isExpired() || $projInv->isAccepted()) {
                $this->addFlash('error', 'Diese Einladung ist abgelaufen oder wurde bereits verwendet.');
                return $this->redirectToRoute('app_dashboard');
            }

            $user = $this->getUser();
            if (!$user) {
                return $this->redirectToRoute('app_login');
            }

            $project = $projInv->getProject();
            if (!$project->isMember($user)) {
                $member = new ProjectMember();
                $member->setProject($project);
                $member->setUser($user);
                $member->setRole($projInv->getRole());
                $em->persist($member);
            }

            $projInv->setAcceptedAt(new \DateTimeImmutable());
            $em->flush();

            $org = $project->getOrganization();
            $this->addFlash('success', 'Du bist jetzt Mitglied des Projekts "' . $project->getName() . '".');
            return $this->redirectToRoute('app_project_show', [
                'slug' => $org->getSlug(),
                'projectSlug' => $project->getSlug(),
            ]);
        }

        $this->addFlash('error', 'Einladung nicht gefunden.');
        return $this->redirectToRoute('app_dashboard');
    }
}
