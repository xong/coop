<?php

namespace App\Controller;

use App\Entity\Organization;
use App\Entity\OrganizationInvitation;
use App\Entity\OrganizationMember;
use App\Form\InviteFormType;
use App\Form\OrganizationFormType;
use App\Repository\OrganizationInvitationRepository;
use App\Repository\OrganizationMemberRepository;
use App\Repository\OrganizationRepository;
use App\Repository\UserRepository;
use App\Security\Voter\OrganizationVoter;
use App\Service\SlugService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[IsGranted('ROLE_USER')]
#[Route('/organizations')]
class OrganizationController extends AbstractController
{
    public function __construct(
        private OrganizationRepository $orgRepo,
        private OrganizationMemberRepository $memberRepo,
        private OrganizationInvitationRepository $invRepo,
        private EntityManagerInterface $em,
        private SlugService $slugService,
    ) {}

    #[Route('', name: 'app_organizations')]
    public function index(): Response
    {
        $organizations = $this->orgRepo->findByMember($this->getUser());
        return $this->render('organization/list.html.twig', [
            'organizations' => $organizations,
        ]);
    }

    #[Route('/new', name: 'app_organization_new')]
    public function new(Request $request, SluggerInterface $slugger): Response
    {
        $organization = new Organization();
        $form = $this->createForm(OrganizationFormType::class, $organization);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            $organization->setOwner($user);

            $slug = $this->slugService->uniqueSlug(
                $organization->getName(),
                fn($s) => $this->orgRepo->findOneBySlug($s) !== null
            );
            $organization->setSlug($slug);

            $this->handleLogoUpload($form, $organization, $slugger);

            $this->em->persist($organization);

            // Add creator as admin
            $member = new OrganizationMember();
            $member->setOrganization($organization);
            $member->setUser($user);
            $member->setRole(OrganizationMember::ROLE_ADMIN);
            $this->em->persist($member);

            $this->em->flush();

            $this->addFlash('success', 'Organisation "' . $organization->getName() . '" wurde erstellt.');
            return $this->redirectToRoute('app_organization_show', ['slug' => $organization->getSlug()]);
        }

        return $this->render('organization/new.html.twig', ['form' => $form]);
    }

    #[Route('/{slug}', name: 'app_organization_show')]
    public function show(string $slug): Response
    {
        $org = $this->getOrganization($slug);
        $this->denyAccessUnlessGranted(OrganizationVoter::VIEW, $org);

        return $this->render('organization/show.html.twig', ['organization' => $org]);
    }

    #[Route('/{slug}/edit', name: 'app_organization_edit')]
    public function edit(string $slug, Request $request, SluggerInterface $slugger): Response
    {
        $org = $this->getOrganization($slug);
        $this->denyAccessUnlessGranted(OrganizationVoter::EDIT, $org);

        $form = $this->createForm(OrganizationFormType::class, $org);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleLogoUpload($form, $org, $slugger);
            $this->em->flush();
            $this->addFlash('success', 'Organisation aktualisiert.');
            return $this->redirectToRoute('app_organization_show', ['slug' => $org->getSlug()]);
        }

        return $this->render('organization/edit.html.twig', ['form' => $form, 'organization' => $org]);
    }

    #[Route('/{slug}/members', name: 'app_organization_members')]
    public function members(string $slug): Response
    {
        $org = $this->getOrganization($slug);
        $this->denyAccessUnlessGranted(OrganizationVoter::VIEW, $org);

        return $this->render('organization/members.html.twig', ['organization' => $org]);
    }

    #[Route('/{slug}/invite', name: 'app_organization_invite')]
    public function invite(string $slug, Request $request): Response
    {
        $org = $this->getOrganization($slug);
        $this->denyAccessUnlessGranted(OrganizationVoter::INVITE, $org);

        $form = $this->createForm(InviteFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $email = $data['email'];

            // Check if already member
            $existingUser = $this->em->getRepository(\App\Entity\User::class)->findOneBy(['email' => $email]);
            if ($existingUser && $org->isMember($existingUser)) {
                $this->addFlash('warning', 'Dieser Benutzer ist bereits Mitglied.');
                return $this->redirectToRoute('app_organization_invite', ['slug' => $slug]);
            }

            $invitation = new OrganizationInvitation();
            $invitation->setOrganization($org);
            $invitation->setEmail($email);
            $invitation->setRole($data['role']);
            $invitation->setInvitedBy($this->getUser());
            $this->em->persist($invitation);
            $this->em->flush();

            $this->addFlash('success', "Einladung an {$email} wurde gespeichert. Einladungslink: " .
                $this->generateUrl('app_invitation_accept', ['token' => $invitation->getToken()], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL));

            return $this->redirectToRoute('app_organization_members', ['slug' => $slug]);
        }

        return $this->render('organization/invite.html.twig', ['form' => $form, 'organization' => $org]);
    }

    #[Route('/{slug}/members/{userId}/remove', name: 'app_organization_member_remove', methods: ['POST'])]
    public function removeMember(string $slug, int $userId, Request $request): Response
    {
        $org = $this->getOrganization($slug);
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE_MEMBERS, $org);

        $member = $this->memberRepo->findOneBy(['organization' => $org, 'user' => $userId]);
        if (!$member || $member->getUser() === $org->getOwner()) {
            $this->addFlash('error', 'Dieses Mitglied kann nicht entfernt werden.');
            return $this->redirectToRoute('app_organization_members', ['slug' => $slug]);
        }

        if ($this->isCsrfTokenValid('remove_member_' . $userId, $request->getPayload()->get('_token'))) {
            $this->memberRepo->remove($member, true);
            $this->addFlash('success', 'Mitglied wurde entfernt.');
        }

        return $this->redirectToRoute('app_organization_members', ['slug' => $slug]);
    }

    #[Route('/{slug}/members/{userId}/role', name: 'app_organization_member_role', methods: ['POST'])]
    public function changeRole(string $slug, int $userId, Request $request): Response
    {
        $org = $this->getOrganization($slug);
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE_MEMBERS, $org);

        $member = $this->memberRepo->findOneBy(['organization' => $org, 'user' => $userId]);
        if (!$member) {
            throw $this->createNotFoundException();
        }

        if ($this->isCsrfTokenValid('role_member_' . $userId, $request->getPayload()->get('_token'))) {
            $newRole = $member->getRole() === OrganizationMember::ROLE_ADMIN
                ? OrganizationMember::ROLE_MEMBER
                : OrganizationMember::ROLE_ADMIN;
            $member->setRole($newRole);
            $this->em->flush();
            $this->addFlash('success', 'Rolle geandert.');
        }

        return $this->redirectToRoute('app_organization_members', ['slug' => $slug]);
    }

    private function getOrganization(string $slug): Organization
    {
        $org = $this->orgRepo->findOneBySlug($slug);
        if (!$org) {
            throw $this->createNotFoundException('Organisation nicht gefunden.');
        }
        return $org;
    }

    private function handleLogoUpload(\Symfony\Component\Form\FormInterface $form, Organization $org, SluggerInterface $slugger): void
    {
        $logoFile = $form->get('logoFile')->getData();
        if ($logoFile) {
            $safeFilename = $slugger->slug(pathinfo($logoFile->getClientOriginalName(), PATHINFO_FILENAME));
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $logoFile->guessExtension();
            try {
                $dir = $this->getParameter('kernel.project_dir') . '/public/uploads/logos';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $logoFile->move($dir, $newFilename);
                $org->setLogo('uploads/logos/' . $newFilename);
            } catch (FileException) {
                $this->addFlash('error', 'Fehler beim Hochladen des Logos.');
            }
        }
    }
}
