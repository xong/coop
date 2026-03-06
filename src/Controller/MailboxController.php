<?php

namespace App\Controller;

use App\Entity\EmailComment;
use App\Entity\IncomingEmail;
use App\Entity\MailboxConfig;
use App\Form\MailboxConfigFormType;
use App\Repository\IncomingEmailRepository;
use App\Repository\MailboxConfigRepository;
use App\Repository\OrganizationRepository;
use App\Repository\ProjectRepository;
use App\Repository\ContactRepository;
use App\Repository\TopicRepository;
use App\Service\ImapService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class MailboxController extends AbstractController
{
    // =========================================================
    // Organisation mailboxes
    // =========================================================

    #[Route('/organizations/{slug}/mailboxes', name: 'app_org_mailboxes')]
    public function orgIndex(
        string $slug,
        OrganizationRepository $orgRepo,
        MailboxConfigRepository $mailboxRepo,
    ): Response {
        $organization = $orgRepo->findOneBy(['slug' => $slug]);
        if (!$organization) throw $this->createNotFoundException();

        $this->denyAccessUnlessGranted('ORG_VIEW', $organization);

        $mailboxes = $mailboxRepo->findByOrganization($organization);

        return $this->render('mailbox/index.html.twig', [
            'organization' => $organization,
            'project'      => null,
            'mailboxes'    => $mailboxes,
        ]);
    }

    #[Route('/organizations/{slug}/mailboxes/new', name: 'app_org_mailbox_new')]
    public function orgNew(
        string $slug,
        Request $request,
        OrganizationRepository $orgRepo,
        EntityManagerInterface $em,
    ): Response {
        $organization = $orgRepo->findOneBy(['slug' => $slug]);
        if (!$organization) throw $this->createNotFoundException();

        $this->denyAccessUnlessGranted('ORG_MANAGE_MEMBERS', $organization);

        $config = new MailboxConfig();
        $config->setOrganization($organization);
        $config->setCreatedBy($this->getUser());

        $form = $this->createForm(MailboxConfigFormType::class, $config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($config);
            $em->flush();
            $this->addFlash('success', 'Postfach wurde eingerichtet.');
            return $this->redirectToRoute('app_org_mailboxes', ['slug' => $slug]);
        }

        return $this->render('mailbox/config_form.html.twig', [
            'organization' => $organization,
            'project'      => null,
            'form'         => $form,
            'config'       => $config,
            'backUrl'      => $this->generateUrl('app_org_mailboxes', ['slug' => $slug]),
        ]);
    }

    #[Route('/organizations/{slug}/mailboxes/{id}/edit', name: 'app_org_mailbox_edit')]
    public function orgEdit(
        string $slug,
        int $id,
        Request $request,
        OrganizationRepository $orgRepo,
        MailboxConfigRepository $mailboxRepo,
        EntityManagerInterface $em,
    ): Response {
        $organization = $orgRepo->findOneBy(['slug' => $slug]);
        if (!$organization) throw $this->createNotFoundException();

        $this->denyAccessUnlessGranted('ORG_MANAGE_MEMBERS', $organization);

        $config = $mailboxRepo->find($id);
        if (!$config || $config->getOrganization() !== $organization) throw $this->createNotFoundException();

        $form = $this->createForm(MailboxConfigFormType::class, $config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Postfach wurde aktualisiert.');
            return $this->redirectToRoute('app_org_mailboxes', ['slug' => $slug]);
        }

        return $this->render('mailbox/config_form.html.twig', [
            'organization' => $organization,
            'project'      => null,
            'form'         => $form,
            'config'       => $config,
            'backUrl'      => $this->generateUrl('app_org_mailboxes', ['slug' => $slug]),
        ]);
    }

    #[Route('/organizations/{slug}/mailboxes/{id}/delete', name: 'app_org_mailbox_delete', methods: ['POST'])]
    public function orgDelete(
        string $slug,
        int $id,
        Request $request,
        OrganizationRepository $orgRepo,
        MailboxConfigRepository $mailboxRepo,
        EntityManagerInterface $em,
    ): Response {
        $organization = $orgRepo->findOneBy(['slug' => $slug]);
        if (!$organization) throw $this->createNotFoundException();

        $this->denyAccessUnlessGranted('ORG_MANAGE_MEMBERS', $organization);

        $config = $mailboxRepo->find($id);
        if (!$config || $config->getOrganization() !== $organization) throw $this->createNotFoundException();

        if ($this->isCsrfTokenValid('delete_mailbox_' . $id, $request->request->get('_token'))) {
            $em->remove($config);
            $em->flush();
            $this->addFlash('success', 'Postfach wurde gelöscht.');
        }

        return $this->redirectToRoute('app_org_mailboxes', ['slug' => $slug]);
    }

    #[Route('/organizations/{slug}/mailboxes/{id}/sync', name: 'app_org_mailbox_sync', methods: ['POST'])]
    public function orgSync(
        string $slug,
        int $id,
        Request $request,
        OrganizationRepository $orgRepo,
        MailboxConfigRepository $mailboxRepo,
        ImapService $imapService,
    ): Response {
        $organization = $orgRepo->findOneBy(['slug' => $slug]);
        if (!$organization) throw $this->createNotFoundException();

        $this->denyAccessUnlessGranted('ORG_MANAGE_MEMBERS', $organization);

        $config = $mailboxRepo->find($id);
        if (!$config || $config->getOrganization() !== $organization) throw $this->createNotFoundException();

        if ($this->isCsrfTokenValid('sync_mailbox_' . $id, $request->request->get('_token'))) {
            [$fetched, $skipped, $error] = $imapService->syncMailbox($config);
            if ($error) {
                $this->addFlash('error', 'Synchronisierungsfehler: ' . $error);
            } else {
                $this->addFlash('success', sprintf('%d neue E-Mail(s) abgerufen, %d übersprungen.', $fetched, $skipped));
            }
        }

        return $this->redirectToRoute('app_org_mailbox_inbox', ['slug' => $slug, 'id' => $id]);
    }

    #[Route('/organizations/{slug}/mailboxes/{id}/inbox', name: 'app_org_mailbox_inbox')]
    public function orgInbox(
        string $slug,
        int $id,
        Request $request,
        OrganizationRepository $orgRepo,
        MailboxConfigRepository $mailboxRepo,
        IncomingEmailRepository $emailRepo,
    ): Response {
        $organization = $orgRepo->findOneBy(['slug' => $slug]);
        if (!$organization) throw $this->createNotFoundException();

        $this->denyAccessUnlessGranted('ORG_VIEW', $organization);

        $config = $mailboxRepo->find($id);
        if (!$config || $config->getOrganization() !== $organization) throw $this->createNotFoundException();

        $status = $request->query->get('status');
        $emails = $emailRepo->findByMailbox($config, $status ?: null);

        return $this->render('mailbox/inbox.html.twig', [
            'organization' => $organization,
            'project'      => null,
            'config'       => $config,
            'emails'       => $emails,
            'status'       => $status,
        ]);
    }

    // =========================================================
    // Project mailboxes
    // =========================================================

    #[Route('/organizations/{slug}/projects/{projectSlug}/mailboxes', name: 'app_project_mailboxes')]
    public function projectIndex(
        string $slug,
        string $projectSlug,
        OrganizationRepository $orgRepo,
        ProjectRepository $projectRepo,
        MailboxConfigRepository $mailboxRepo,
    ): Response {
        [$organization, $project] = $this->loadProject($slug, $projectSlug, $orgRepo, $projectRepo);
        $this->denyAccessUnlessGranted('PROJECT_VIEW', $project);

        $mailboxes = $mailboxRepo->findByProject($project);

        return $this->render('mailbox/index.html.twig', [
            'organization' => $organization,
            'project'      => $project,
            'mailboxes'    => $mailboxes,
        ]);
    }

    #[Route('/organizations/{slug}/projects/{projectSlug}/mailboxes/new', name: 'app_project_mailbox_new')]
    public function projectNew(
        string $slug,
        string $projectSlug,
        Request $request,
        OrganizationRepository $orgRepo,
        ProjectRepository $projectRepo,
        EntityManagerInterface $em,
    ): Response {
        [$organization, $project] = $this->loadProject($slug, $projectSlug, $orgRepo, $projectRepo);
        $this->denyAccessUnlessGranted('PROJECT_MANAGE_MEMBERS', $project);

        $config = new MailboxConfig();
        $config->setProject($project);
        $config->setOrganization($organization);
        $config->setCreatedBy($this->getUser());

        $form = $this->createForm(MailboxConfigFormType::class, $config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($config);
            $em->flush();
            $this->addFlash('success', 'Postfach wurde eingerichtet.');
            return $this->redirectToRoute('app_project_mailboxes', ['slug' => $slug, 'projectSlug' => $projectSlug]);
        }

        return $this->render('mailbox/config_form.html.twig', [
            'organization' => $organization,
            'project'      => $project,
            'form'         => $form,
            'config'       => $config,
            'backUrl'      => $this->generateUrl('app_project_mailboxes', ['slug' => $slug, 'projectSlug' => $projectSlug]),
        ]);
    }

    #[Route('/organizations/{slug}/projects/{projectSlug}/mailboxes/{id}/edit', name: 'app_project_mailbox_edit')]
    public function projectEdit(
        string $slug,
        string $projectSlug,
        int $id,
        Request $request,
        OrganizationRepository $orgRepo,
        ProjectRepository $projectRepo,
        MailboxConfigRepository $mailboxRepo,
        EntityManagerInterface $em,
    ): Response {
        [$organization, $project] = $this->loadProject($slug, $projectSlug, $orgRepo, $projectRepo);
        $this->denyAccessUnlessGranted('PROJECT_MANAGE_MEMBERS', $project);

        $config = $mailboxRepo->find($id);
        if (!$config || $config->getProject() !== $project) throw $this->createNotFoundException();

        $form = $this->createForm(MailboxConfigFormType::class, $config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Postfach wurde aktualisiert.');
            return $this->redirectToRoute('app_project_mailboxes', ['slug' => $slug, 'projectSlug' => $projectSlug]);
        }

        return $this->render('mailbox/config_form.html.twig', [
            'organization' => $organization,
            'project'      => $project,
            'form'         => $form,
            'config'       => $config,
            'backUrl'      => $this->generateUrl('app_project_mailboxes', ['slug' => $slug, 'projectSlug' => $projectSlug]),
        ]);
    }

    #[Route('/organizations/{slug}/projects/{projectSlug}/mailboxes/{id}/delete', name: 'app_project_mailbox_delete', methods: ['POST'])]
    public function projectDelete(
        string $slug,
        string $projectSlug,
        int $id,
        Request $request,
        OrganizationRepository $orgRepo,
        ProjectRepository $projectRepo,
        MailboxConfigRepository $mailboxRepo,
        EntityManagerInterface $em,
    ): Response {
        [$organization, $project] = $this->loadProject($slug, $projectSlug, $orgRepo, $projectRepo);
        $this->denyAccessUnlessGranted('PROJECT_MANAGE_MEMBERS', $project);

        $config = $mailboxRepo->find($id);
        if (!$config || $config->getProject() !== $project) throw $this->createNotFoundException();

        if ($this->isCsrfTokenValid('delete_mailbox_' . $id, $request->request->get('_token'))) {
            $em->remove($config);
            $em->flush();
            $this->addFlash('success', 'Postfach wurde gelöscht.');
        }

        return $this->redirectToRoute('app_project_mailboxes', ['slug' => $slug, 'projectSlug' => $projectSlug]);
    }

    #[Route('/organizations/{slug}/projects/{projectSlug}/mailboxes/{id}/sync', name: 'app_project_mailbox_sync', methods: ['POST'])]
    public function projectSync(
        string $slug,
        string $projectSlug,
        int $id,
        Request $request,
        OrganizationRepository $orgRepo,
        ProjectRepository $projectRepo,
        MailboxConfigRepository $mailboxRepo,
        ImapService $imapService,
    ): Response {
        [$organization, $project] = $this->loadProject($slug, $projectSlug, $orgRepo, $projectRepo);
        $this->denyAccessUnlessGranted('PROJECT_MANAGE_MEMBERS', $project);

        $config = $mailboxRepo->find($id);
        if (!$config || $config->getProject() !== $project) throw $this->createNotFoundException();

        if ($this->isCsrfTokenValid('sync_mailbox_' . $id, $request->request->get('_token'))) {
            [$fetched, $skipped, $error] = $imapService->syncMailbox($config);
            if ($error) {
                $this->addFlash('error', 'Synchronisierungsfehler: ' . $error);
            } else {
                $this->addFlash('success', sprintf('%d neue E-Mail(s) abgerufen, %d übersprungen.', $fetched, $skipped));
            }
        }

        return $this->redirectToRoute('app_project_mailbox_inbox', [
            'slug'        => $slug,
            'projectSlug' => $projectSlug,
            'id'          => $id,
        ]);
    }

    #[Route('/organizations/{slug}/projects/{projectSlug}/mailboxes/{id}/inbox', name: 'app_project_mailbox_inbox')]
    public function projectInbox(
        string $slug,
        string $projectSlug,
        int $id,
        Request $request,
        OrganizationRepository $orgRepo,
        ProjectRepository $projectRepo,
        MailboxConfigRepository $mailboxRepo,
        IncomingEmailRepository $emailRepo,
    ): Response {
        [$organization, $project] = $this->loadProject($slug, $projectSlug, $orgRepo, $projectRepo);
        $this->denyAccessUnlessGranted('PROJECT_VIEW', $project);

        $config = $mailboxRepo->find($id);
        if (!$config || $config->getProject() !== $project) throw $this->createNotFoundException();

        $status = $request->query->get('status');
        $emails = $emailRepo->findByMailbox($config, $status ?: null);

        return $this->render('mailbox/inbox.html.twig', [
            'organization' => $organization,
            'project'      => $project,
            'config'       => $config,
            'emails'       => $emails,
            'status'       => $status,
        ]);
    }

    // =========================================================
    // Individual email actions (work for both org & project)
    // =========================================================

    #[Route('/emails/{id}', name: 'app_email_show')]
    public function show(
        int $id,
        Request $request,
        IncomingEmailRepository $emailRepo,
        TopicRepository $topicRepo,
        ProjectRepository $projectRepo,
        EntityManagerInterface $em,
    ): Response {
        $email = $emailRepo->find($id);
        if (!$email) throw $this->createNotFoundException();

        $config = $email->getMailbox();
        $organization = $config->getOrganization();
        $project = $config->getProject();

        if ($project) {
            $this->denyAccessUnlessGranted('PROJECT_VIEW', $project);
        } else {
            $this->denyAccessUnlessGranted('ORG_VIEW', $organization);
        }

        // Mark as read
        if (!$email->isRead()) {
            $email->setIsRead(true);
            $em->flush();
        }

        // Determine back URL
        if ($project) {
            $backUrl = $this->generateUrl('app_project_mailbox_inbox', [
                'slug'        => $organization->getSlug(),
                'projectSlug' => $project->getSlug(),
                'id'          => $config->getId(),
            ]);
        } else {
            $backUrl = $this->generateUrl('app_org_mailbox_inbox', [
                'slug' => $organization->getSlug(),
                'id'   => $config->getId(),
            ]);
        }

        // Get topics for linking (same context)
        $topics = $project
            ? $topicRepo->findBy(['project' => $project], ['lastActivityAt' => 'DESC'])
            : $topicRepo->findBy(['organization' => $organization, 'project' => null], ['lastActivityAt' => 'DESC']);

        // Get projects for linking
        $projects = $organization->getProjects()->toArray();

        return $this->render('mailbox/email_show.html.twig', [
            'email'        => $email,
            'organization' => $organization,
            'project'      => $project,
            'backUrl'      => $backUrl,
            'topics'       => $topics,
            'projects'     => $projects,
        ]);
    }

    #[Route('/emails/{id}/status/{status}', name: 'app_email_status', methods: ['POST'])]
    public function updateStatus(
        int $id,
        string $status,
        Request $request,
        IncomingEmailRepository $emailRepo,
        EntityManagerInterface $em,
    ): Response {
        $email = $emailRepo->find($id);
        if (!$email) throw $this->createNotFoundException();

        $allowed = [IncomingEmail::STATUS_NEW, IncomingEmail::STATUS_IN_PROGRESS, IncomingEmail::STATUS_DONE, IncomingEmail::STATUS_ARCHIVED];
        if (!in_array($status, $allowed, true)) throw $this->createNotFoundException();

        $config = $email->getMailbox();
        $project = $config->getProject();
        $organization = $config->getOrganization();

        if ($project) {
            $this->denyAccessUnlessGranted('PROJECT_VIEW', $project);
        } else {
            $this->denyAccessUnlessGranted('ORG_VIEW', $organization);
        }

        if ($this->isCsrfTokenValid('email_status_' . $id, $request->request->get('_token'))) {
            $email->setStatus($status);
            $em->flush();
        }

        return $this->redirect($request->headers->get('referer') ?: $this->generateUrl('app_dashboard'));
    }

    #[Route('/emails/{id}/assign', name: 'app_email_assign', methods: ['POST'])]
    public function assign(
        int $id,
        Request $request,
        IncomingEmailRepository $emailRepo,
        OrganizationRepository $orgRepo,
        EntityManagerInterface $em,
    ): Response {
        $email = $emailRepo->find($id);
        if (!$email) throw $this->createNotFoundException();

        $config = $email->getMailbox();
        $project = $config->getProject();
        $organization = $config->getOrganization();

        if ($project) {
            $this->denyAccessUnlessGranted('PROJECT_VIEW', $project);
        } else {
            $this->denyAccessUnlessGranted('ORG_VIEW', $organization);
        }

        if ($this->isCsrfTokenValid('email_assign_' . $id, $request->request->get('_token'))) {
            $userId = $request->request->get('user_id');
            if ($userId) {
                // Find user among members
                $members = $project
                    ? $project->getMembers()
                    : $organization->getMembers();

                foreach ($members as $member) {
                    if ($member->getUser()->getId() == $userId) {
                        $email->setAssignedTo($member->getUser());
                        break;
                    }
                }
            } else {
                $email->setAssignedTo(null);
            }
            $em->flush();
        }

        return $this->redirect($request->headers->get('referer') ?: $this->generateUrl('app_dashboard'));
    }

    #[Route('/emails/{id}/link', name: 'app_email_link', methods: ['POST'])]
    public function link(
        int $id,
        Request $request,
        IncomingEmailRepository $emailRepo,
        TopicRepository $topicRepo,
        ProjectRepository $projectRepo,
        EntityManagerInterface $em,
    ): Response {
        $email = $emailRepo->find($id);
        if (!$email) throw $this->createNotFoundException();

        $config = $email->getMailbox();
        $project = $config->getProject();
        $organization = $config->getOrganization();

        if ($project) {
            $this->denyAccessUnlessGranted('PROJECT_VIEW', $project);
        } else {
            $this->denyAccessUnlessGranted('ORG_VIEW', $organization);
        }

        if ($this->isCsrfTokenValid('email_link_' . $id, $request->request->get('_token'))) {
            $topicId = $request->request->get('topic_id');
            $projectId = $request->request->get('project_id');

            if ($topicId) {
                $topic = $topicRepo->find($topicId);
                $email->setLinkedTopic($topic ?: null);
            } else {
                $email->setLinkedTopic(null);
            }

            if ($projectId) {
                $linkedProject = $projectRepo->find($projectId);
                $email->setLinkedProject($linkedProject ?: null);
            } else {
                $email->setLinkedProject(null);
            }

            $em->flush();
            $this->addFlash('success', 'Verlinkung aktualisiert.');
        }

        return $this->redirectToRoute('app_email_show', ['id' => $id]);
    }

    #[Route('/emails/{id}/comment', name: 'app_email_comment', methods: ['POST'])]
    public function addComment(
        int $id,
        Request $request,
        IncomingEmailRepository $emailRepo,
        EntityManagerInterface $em,
    ): Response {
        $email = $emailRepo->find($id);
        if (!$email) throw $this->createNotFoundException();

        $config = $email->getMailbox();
        $project = $config->getProject();
        $organization = $config->getOrganization();

        if ($project) {
            $this->denyAccessUnlessGranted('PROJECT_VIEW', $project);
        } else {
            $this->denyAccessUnlessGranted('ORG_VIEW', $organization);
        }

        if ($this->isCsrfTokenValid('email_comment_' . $id, $request->request->get('_token'))) {
            $content = trim($request->request->get('content', ''));
            if ($content) {
                $comment = new EmailComment();
                $comment->setEmail($email);
                $comment->setAuthor($this->getUser());
                $comment->setContent($content);
                $em->persist($comment);
                $em->flush();
            }
        }

        return $this->redirectToRoute('app_email_show', ['id' => $id]);
    }

    #[Route('/emails/{id}/reply', name: 'app_email_reply', methods: ['GET', 'POST'])]
    public function reply(
        int $id,
        Request $request,
        IncomingEmailRepository $emailRepo,
        MailerInterface $mailer,
        EntityManagerInterface $em,
    ): Response {
        $email = $emailRepo->find($id);
        if (!$email) throw $this->createNotFoundException();

        $config = $email->getMailbox();
        $project = $config->getProject();
        $organization = $config->getOrganization();

        if ($project) {
            $this->denyAccessUnlessGranted('PROJECT_VIEW', $project);
        } else {
            $this->denyAccessUnlessGranted('ORG_VIEW', $organization);
        }

        if ($request->isMethod('POST')) {
            $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

            if ($this->isCsrfTokenValid('email_reply_' . $id, $request->request->get('_token'))) {
                $body = trim($request->request->get('body', ''));
                $subject = trim($request->request->get('subject', 'Re: ' . $email->getSubject()));

                if ($body) {
                    $message = (new Email())
                        ->to($email->getFromEmail())
                        ->subject($subject)
                        ->text($body)
                        ->html(nl2br(htmlspecialchars($body)));

                    if ($email->getMessageId()) {
                        $message->getHeaders()->addTextHeader('In-Reply-To', $email->getMessageId());
                        $message->getHeaders()->addTextHeader('References', $email->getMessageId());
                    }

                    $error = $this->sendEmail($config, $message);
                    if ($error) {
                        $this->addFlash('error', 'Fehler beim Senden: ' . $error);
                    } else {
                        $this->addFlash('success', 'Antwort wurde gesendet.');
                    }
                }
            }

            return $this->redirectToRoute('app_email_show', ['id' => $id]);
        }

        return $this->render('mailbox/reply.html.twig', [
            'email'        => $email,
            'organization' => $organization,
            'project'      => $project,
            'config'       => $config,
        ]);
    }

    #[Route('/emails/{id}/delete', name: 'app_email_delete', methods: ['POST'])]
    public function delete(
        int $id,
        Request $request,
        IncomingEmailRepository $emailRepo,
        EntityManagerInterface $em,
    ): Response {
        $email = $emailRepo->find($id);
        if (!$email) throw $this->createNotFoundException();

        $config = $email->getMailbox();
        $project = $config->getProject();
        $organization = $config->getOrganization();

        if ($project) {
            $this->denyAccessUnlessGranted('PROJECT_MANAGE_MEMBERS', $project);
        } else {
            $this->denyAccessUnlessGranted('ORG_MANAGE_MEMBERS', $organization);
        }

        if ($this->isCsrfTokenValid('delete_email_' . $id, $request->request->get('_token'))) {
            $em->remove($email);
            $em->flush();
            $this->addFlash('success', 'E-Mail wurde gelöscht.');
        }

        return $this->redirect($request->headers->get('referer') ?: $this->generateUrl('app_dashboard'));
    }

    // =========================================================
    // Compose (new email, not a reply)
    // =========================================================

    /**
     * Smart compose: picks the right mailbox automatically or lets user choose.
     * Linked from contact pages via ?contact_id=X or ?to=email&to_name=name.
     */
    #[Route('/organizations/{slug}/compose', name: 'app_org_compose')]
    public function orgCompose(
        string $slug,
        Request $request,
        OrganizationRepository $orgRepo,
        MailboxConfigRepository $mailboxRepo,
        ContactRepository $contactRepo,
    ): Response {
        $organization = $orgRepo->findOneBy(['slug' => $slug]);
        if (!$organization) throw $this->createNotFoundException();
        $this->denyAccessUnlessGranted('ORG_VIEW', $organization);

        $mailboxes = $mailboxRepo->findByOrganization($organization);

        // Pre-fill from contact or query params
        $to = $request->query->get('to', '');
        $toName = $request->query->get('to_name', '');
        $subject = $request->query->get('subject', '');
        $contactId = $request->query->get('contact_id');

        if ($contactId) {
            $contact = $contactRepo->find($contactId);
            if ($contact && $contact->getOrganization() === $organization) {
                $to = $contact->getEmail() ?? $to;
                $toName = $contact->getFullName();
            }
        }

        // If exactly one mailbox, go directly to compose
        if (count($mailboxes) === 1) {
            return $this->redirectToRoute('app_org_mailbox_compose', [
                'slug' => $slug,
                'id'   => $mailboxes[0]->getId(),
                'to'   => $to,
                'to_name' => $toName,
                'subject' => $subject,
            ]);
        }

        // Multiple mailboxes: show picker
        $contacts = $contactRepo->search($organization);

        return $this->render('mailbox/compose_pick.html.twig', [
            'organization' => $organization,
            'project'      => null,
            'mailboxes'    => $mailboxes,
            'to'           => $to,
            'toName'       => $toName,
            'subject'      => $subject,
            'contacts'     => $contacts,
        ]);
    }

    #[Route('/organizations/{slug}/mailboxes/{id}/compose', name: 'app_org_mailbox_compose')]
    public function orgMailboxCompose(
        string $slug,
        int $id,
        Request $request,
        OrganizationRepository $orgRepo,
        MailboxConfigRepository $mailboxRepo,
        ContactRepository $contactRepo,
    ): Response {
        $organization = $orgRepo->findOneBy(['slug' => $slug]);
        if (!$organization) throw $this->createNotFoundException();
        $this->denyAccessUnlessGranted('ORG_VIEW', $organization);

        $config = $mailboxRepo->find($id);
        if (!$config || $config->getOrganization() !== $organization) throw $this->createNotFoundException();

        $contacts = $contactRepo->search($organization);

        if ($request->isMethod('POST')) {
            if ($this->isCsrfTokenValid('compose_' . $id, $request->request->get('_token'))) {
                $error = $this->handleCompose($config, $request);
                if ($error) {
                    $this->addFlash('error', 'Fehler beim Senden: ' . $error);
                } else {
                    $this->addFlash('success', 'E-Mail wurde gesendet.');
                    return $this->redirectToRoute('app_org_mailbox_inbox', ['slug' => $slug, 'id' => $id]);
                }
            }
        }

        return $this->render('mailbox/compose.html.twig', [
            'organization' => $organization,
            'project'      => null,
            'config'       => $config,
            'contacts'     => $contacts,
            'to'           => $request->query->get('to', ''),
            'toName'       => $request->query->get('to_name', ''),
            'subject'      => $request->query->get('subject', ''),
            'backUrl'      => $this->generateUrl('app_org_mailbox_inbox', ['slug' => $slug, 'id' => $id]),
        ]);
    }

    #[Route('/organizations/{slug}/projects/{projectSlug}/compose', name: 'app_project_compose')]
    public function projectCompose(
        string $slug,
        string $projectSlug,
        Request $request,
        OrganizationRepository $orgRepo,
        ProjectRepository $projectRepo,
        MailboxConfigRepository $mailboxRepo,
        ContactRepository $contactRepo,
    ): Response {
        [$organization, $project] = $this->loadProject($slug, $projectSlug, $orgRepo, $projectRepo);
        $this->denyAccessUnlessGranted('PROJECT_VIEW', $project);

        $mailboxes = $mailboxRepo->findByProject($project);
        if (empty($mailboxes)) {
            $mailboxes = $mailboxRepo->findByOrganization($organization);
        }

        $to = $request->query->get('to', '');
        $toName = $request->query->get('to_name', '');
        $subject = $request->query->get('subject', '');
        $contactId = $request->query->get('contact_id');

        if ($contactId) {
            $contact = $contactRepo->find($contactId);
            if ($contact && $contact->getOrganization() === $organization) {
                $to = $contact->getEmail() ?? $to;
                $toName = $contact->getFullName();
            }
        }

        if (count($mailboxes) === 1) {
            $mb = $mailboxes[0];
            $route = $mb->getProject() ? 'app_project_mailbox_compose' : 'app_org_mailbox_compose';
            $params = $mb->getProject()
                ? ['slug' => $slug, 'projectSlug' => $projectSlug, 'id' => $mb->getId(), 'to' => $to, 'to_name' => $toName, 'subject' => $subject]
                : ['slug' => $slug, 'id' => $mb->getId(), 'to' => $to, 'to_name' => $toName, 'subject' => $subject];
            return $this->redirectToRoute($route, $params);
        }

        $contacts = $contactRepo->search($organization);

        return $this->render('mailbox/compose_pick.html.twig', [
            'organization' => $organization,
            'project'      => $project,
            'mailboxes'    => $mailboxes,
            'to'           => $to,
            'toName'       => $toName,
            'subject'      => $subject,
            'contacts'     => $contacts,
        ]);
    }

    #[Route('/organizations/{slug}/projects/{projectSlug}/mailboxes/{id}/compose', name: 'app_project_mailbox_compose')]
    public function projectMailboxCompose(
        string $slug,
        string $projectSlug,
        int $id,
        Request $request,
        OrganizationRepository $orgRepo,
        ProjectRepository $projectRepo,
        MailboxConfigRepository $mailboxRepo,
        ContactRepository $contactRepo,
    ): Response {
        [$organization, $project] = $this->loadProject($slug, $projectSlug, $orgRepo, $projectRepo);
        $this->denyAccessUnlessGranted('PROJECT_VIEW', $project);

        $config = $mailboxRepo->find($id);
        if (!$config || $config->getProject() !== $project) throw $this->createNotFoundException();

        $contacts = $contactRepo->search($organization);

        if ($request->isMethod('POST')) {
            if ($this->isCsrfTokenValid('compose_' . $id, $request->request->get('_token'))) {
                $error = $this->handleCompose($config, $request);
                if ($error) {
                    $this->addFlash('error', 'Fehler beim Senden: ' . $error);
                } else {
                    $this->addFlash('success', 'E-Mail wurde gesendet.');
                    return $this->redirectToRoute('app_project_mailbox_inbox', [
                        'slug'        => $slug,
                        'projectSlug' => $projectSlug,
                        'id'          => $id,
                    ]);
                }
            }
        }

        return $this->render('mailbox/compose.html.twig', [
            'organization' => $organization,
            'project'      => $project,
            'config'       => $config,
            'contacts'     => $contacts,
            'to'           => $request->query->get('to', ''),
            'toName'       => $request->query->get('to_name', ''),
            'subject'      => $request->query->get('subject', ''),
            'backUrl'      => $this->generateUrl('app_project_mailbox_inbox', [
                'slug' => $slug, 'projectSlug' => $projectSlug, 'id' => $id,
            ]),
        ]);
    }

    // =========================================================
    // Helpers
    // =========================================================

    private function handleCompose(MailboxConfig $config, Request $request): ?string
    {
        $to = trim($request->request->get('to', ''));
        $toName = trim($request->request->get('to_name', ''));
        $subject = trim($request->request->get('subject', '(kein Betreff)'));
        $body = trim($request->request->get('body', ''));

        if (!$to || !$body) {
            return 'Empfänger und Nachricht dürfen nicht leer sein.';
        }

        $toAddress = $toName ? new Address($to, $toName) : new Address($to);

        $message = (new Email())
            ->to($toAddress)
            ->subject($subject)
            ->text($body)
            ->html(nl2br(htmlspecialchars($body)));

        return $this->sendEmail($config, $message);
    }

    private function sendEmail(MailboxConfig $config, Email $message): ?string
    {
        try {
            $scheme = match ($config->getSmtpEncryption()) {
                'ssl'   => 'smtps',
                'none'  => 'smtp',
                default => 'smtp',
            };

            $dsn = sprintf(
                '%s://%s:%s@%s:%d',
                $scheme,
                urlencode($config->getSmtpUsername()),
                urlencode($config->getSmtpPassword()),
                $config->getSmtpHost(),
                $config->getSmtpPort(),
            );

            $from = new Address($config->getFromEmail(), $config->getFromName());
            $message->from($from);

            $transport = Transport::fromDsn($dsn);
            $mailer = new Mailer($transport);
            $mailer->send($message);

            return null;
        } catch (\Throwable $e) {
            return $e->getMessage();
        }
    }

    private function loadProject(string $slug, string $projectSlug, $orgRepo, $projectRepo): array
    {
        $organization = $orgRepo->findOneBy(['slug' => $slug]);
        if (!$organization) throw $this->createNotFoundException();

        $project = $projectRepo->findOneBy(['organization' => $organization, 'slug' => $projectSlug]);
        if (!$project) throw $this->createNotFoundException();

        return [$organization, $project];
    }
}
