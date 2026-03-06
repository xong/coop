<?php

namespace App\Controller;

use App\Entity\Project;
use App\Entity\ProjectInvitation;
use App\Entity\ProjectMember;
use App\Form\InviteFormType;
use App\Form\ProjectFormType;
use App\Repository\OrganizationRepository;
use App\Repository\ProjectInvitationRepository;
use App\Repository\ProjectMemberRepository;
use App\Repository\ProjectRepository;
use App\Security\Voter\OrganizationVoter;
use App\Security\Voter\ProjectVoter;
use App\Service\SlugService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/organizations/{slug}/projects')]
class ProjectController extends AbstractController
{
    public function __construct(
        private OrganizationRepository $orgRepo,
        private ProjectRepository $projectRepo,
        private ProjectMemberRepository $memberRepo,
        private ProjectInvitationRepository $invRepo,
        private EntityManagerInterface $em,
        private SlugService $slugService,
    ) {}

    #[Route('', name: 'app_projects')]
    public function index(string $slug): Response
    {
        $org = $this->getOrg($slug);
        $this->denyAccessUnlessGranted(OrganizationVoter::VIEW, $org);

        $projects = $this->projectRepo->findVisibleByOrganization($org, $this->getUser());

        return $this->render('project/list.html.twig', [
            'organization' => $org,
            'projects' => $projects,
        ]);
    }

    #[Route('/new', name: 'app_project_new')]
    public function new(string $slug, Request $request): Response
    {
        $org = $this->getOrg($slug);
        $this->denyAccessUnlessGranted(OrganizationVoter::VIEW, $org);

        $project = new Project();
        $form = $this->createForm(ProjectFormType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            $project->setOrganization($org);
            $project->setCreatedBy($user);

            $projectSlug = $this->slugService->uniqueSlug(
                $project->getName(),
                fn($s) => $this->projectRepo->findOneByOrganizationAndSlug($org, $s) !== null
            );
            $project->setSlug($projectSlug);

            $this->em->persist($project);

            // Add creator as admin
            $member = new ProjectMember();
            $member->setProject($project);
            $member->setUser($user);
            $member->setRole(ProjectMember::ROLE_ADMIN);
            $this->em->persist($member);

            $this->em->flush();

            $this->addFlash('success', 'Projekt "' . $project->getName() . '" wurde erstellt.');
            return $this->redirectToRoute('app_project_show', ['slug' => $slug, 'projectSlug' => $project->getSlug()]);
        }

        return $this->render('project/new.html.twig', [
            'form' => $form,
            'organization' => $org,
        ]);
    }

    #[Route('/{projectSlug}', name: 'app_project_show')]
    public function show(string $slug, string $projectSlug): Response
    {
        $org = $this->getOrg($slug);
        $project = $this->getProject($org, $projectSlug);
        $this->denyAccessUnlessGranted(ProjectVoter::VIEW, $project);

        return $this->render('project/show.html.twig', [
            'organization' => $org,
            'project' => $project,
        ]);
    }

    #[Route('/{projectSlug}/edit', name: 'app_project_edit')]
    public function edit(string $slug, string $projectSlug, Request $request): Response
    {
        $org = $this->getOrg($slug);
        $project = $this->getProject($org, $projectSlug);
        $this->denyAccessUnlessGranted(ProjectVoter::EDIT, $project);

        $form = $this->createForm(ProjectFormType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $this->addFlash('success', 'Projekt aktualisiert.');
            return $this->redirectToRoute('app_project_show', ['slug' => $slug, 'projectSlug' => $project->getSlug()]);
        }

        return $this->render('project/edit.html.twig', [
            'form' => $form,
            'organization' => $org,
            'project' => $project,
        ]);
    }

    #[Route('/{projectSlug}/members', name: 'app_project_members')]
    public function members(string $slug, string $projectSlug): Response
    {
        $org = $this->getOrg($slug);
        $project = $this->getProject($org, $projectSlug);
        $this->denyAccessUnlessGranted(ProjectVoter::VIEW, $project);

        return $this->render('project/members.html.twig', [
            'organization' => $org,
            'project' => $project,
        ]);
    }

    #[Route('/{projectSlug}/invite', name: 'app_project_invite')]
    public function invite(string $slug, string $projectSlug, Request $request): Response
    {
        $org = $this->getOrg($slug);
        $project = $this->getProject($org, $projectSlug);
        $this->denyAccessUnlessGranted(ProjectVoter::INVITE, $project);

        $form = $this->createForm(InviteFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $email = $data['email'];

            $existingUser = $this->em->getRepository(\App\Entity\User::class)->findOneBy(['email' => $email]);
            if ($existingUser && $project->isMember($existingUser)) {
                $this->addFlash('warning', 'Dieser Benutzer ist bereits Mitglied.');
                return $this->redirectToRoute('app_project_invite', ['slug' => $slug, 'projectSlug' => $projectSlug]);
            }

            $invitation = new ProjectInvitation();
            $invitation->setProject($project);
            $invitation->setEmail($email);
            $invitation->setRole($data['role']);
            $invitation->setInvitedBy($this->getUser());
            $this->em->persist($invitation);
            $this->em->flush();

            $this->addFlash('success', "Einladung an {$email} gespeichert. Einladungslink: " .
                $this->generateUrl('app_invitation_accept', ['token' => $invitation->getToken()], UrlGeneratorInterface::ABSOLUTE_URL));

            return $this->redirectToRoute('app_project_members', ['slug' => $slug, 'projectSlug' => $projectSlug]);
        }

        return $this->render('project/invite.html.twig', [
            'form' => $form,
            'organization' => $org,
            'project' => $project,
        ]);
    }

    #[Route('/{projectSlug}/members/{userId}/remove', name: 'app_project_member_remove', methods: ['POST'])]
    public function removeMember(string $slug, string $projectSlug, int $userId, Request $request): Response
    {
        $org = $this->getOrg($slug);
        $project = $this->getProject($org, $projectSlug);
        $this->denyAccessUnlessGranted(ProjectVoter::MANAGE_MEMBERS, $project);

        $member = $this->memberRepo->findOneBy(['project' => $project, 'user' => $userId]);
        if (!$member || $member->getUser() === $project->getCreatedBy()) {
            $this->addFlash('error', 'Dieses Mitglied kann nicht entfernt werden.');
            return $this->redirectToRoute('app_project_members', ['slug' => $slug, 'projectSlug' => $projectSlug]);
        }

        if ($this->isCsrfTokenValid('remove_project_member_' . $userId, $request->getPayload()->get('_token'))) {
            $this->memberRepo->remove($member, true);
            $this->addFlash('success', 'Mitglied entfernt.');
        }

        return $this->redirectToRoute('app_project_members', ['slug' => $slug, 'projectSlug' => $projectSlug]);
    }

    #[Route('/{projectSlug}/members/{userId}/role', name: 'app_project_member_role', methods: ['POST'])]
    public function changeRole(string $slug, string $projectSlug, int $userId, Request $request): Response
    {
        $org = $this->getOrg($slug);
        $project = $this->getProject($org, $projectSlug);
        $this->denyAccessUnlessGranted(ProjectVoter::MANAGE_MEMBERS, $project);

        $member = $this->memberRepo->findOneBy(['project' => $project, 'user' => $userId]);
        if (!$member) throw $this->createNotFoundException();

        if ($this->isCsrfTokenValid('role_project_member_' . $userId, $request->getPayload()->get('_token'))) {
            $newRole = $member->getRole() === ProjectMember::ROLE_ADMIN ? ProjectMember::ROLE_MEMBER : ProjectMember::ROLE_ADMIN;
            $member->setRole($newRole);
            $this->em->flush();
            $this->addFlash('success', 'Rolle geandert.');
        }

        return $this->redirectToRoute('app_project_members', ['slug' => $slug, 'projectSlug' => $projectSlug]);
    }

    private function getOrg(string $slug): \App\Entity\Organization
    {
        $org = $this->orgRepo->findOneBySlug($slug);
        if (!$org) throw $this->createNotFoundException('Organisation nicht gefunden.');
        return $org;
    }

    private function getProject(\App\Entity\Organization $org, string $projectSlug): Project
    {
        $project = $this->projectRepo->findOneByOrganizationAndSlug($org, $projectSlug);
        if (!$project) throw $this->createNotFoundException('Projekt nicht gefunden.');
        return $project;
    }
}
