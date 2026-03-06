<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Entity\ContactComment;
use App\Form\ContactFormType;
use App\Repository\ContactRepository;
use App\Repository\MailboxConfigRepository;
use App\Repository\OrganizationRepository;
use App\Repository\ProjectRepository;
use App\Repository\TopicRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class ContactController extends AbstractController
{
    #[Route('/organizations/{slug}/contacts', name: 'app_org_contacts')]
    public function index(
        string $slug,
        Request $request,
        OrganizationRepository $orgRepo,
        ContactRepository $contactRepo,
    ): Response {
        $organization = $orgRepo->findOneBy(['slug' => $slug]);
        if (!$organization) throw $this->createNotFoundException();

        $this->denyAccessUnlessGranted('ORG_VIEW', $organization);

        $query = trim($request->query->get('q', ''));
        $contacts = $contactRepo->search($organization, $query);

        return $this->render('contact/list.html.twig', [
            'organization' => $organization,
            'contacts'     => $contacts,
            'query'        => $query,
        ]);
    }

    #[Route('/organizations/{slug}/contacts/new', name: 'app_org_contact_new')]
    public function new(
        string $slug,
        Request $request,
        OrganizationRepository $orgRepo,
        ProjectRepository $projectRepo,
        TopicRepository $topicRepo,
        EntityManagerInterface $em,
    ): Response {
        $organization = $orgRepo->findOneBy(['slug' => $slug]);
        if (!$organization) throw $this->createNotFoundException();

        $this->denyAccessUnlessGranted('ORG_VIEW', $organization);

        $contact = new Contact();
        $contact->setOrganization($organization);
        $contact->setCreatedBy($this->getUser());

        [$projects, $topics] = $this->getProjectsAndTopics($organization, $projectRepo, $topicRepo);

        $form = $this->createForm(ContactFormType::class, $contact, [
            'projects' => $projects,
            'topics'   => $topics,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($contact->getCustomFields() as $field) {
                $field->setContact($contact);
            }
            $em->persist($contact);
            $em->flush();
            $this->addFlash('success', 'Kontakt wurde angelegt.');
            return $this->redirectToRoute('app_org_contact_show', ['slug' => $slug, 'id' => $contact->getId()]);
        }

        return $this->render('contact/form.html.twig', [
            'organization' => $organization,
            'contact'      => $contact,
            'form'         => $form,
            'backUrl'      => $this->generateUrl('app_org_contacts', ['slug' => $slug]),
        ]);
    }

    #[Route('/organizations/{slug}/contacts/{id}', name: 'app_org_contact_show')]
    public function show(
        string $slug,
        int $id,
        OrganizationRepository $orgRepo,
        ContactRepository $contactRepo,
        MailboxConfigRepository $mailboxRepo,
    ): Response {
        $organization = $orgRepo->findOneBy(['slug' => $slug]);
        if (!$organization) throw $this->createNotFoundException();

        $this->denyAccessUnlessGranted('ORG_VIEW', $organization);

        $contact = $contactRepo->find($id);
        if (!$contact || $contact->getOrganization() !== $organization) throw $this->createNotFoundException();

        $mailboxes = $mailboxRepo->findByOrganization($organization);

        return $this->render('contact/show.html.twig', [
            'organization' => $organization,
            'contact'      => $contact,
            'mailboxes'    => $mailboxes,
        ]);
    }

    #[Route('/organizations/{slug}/contacts/{id}/edit', name: 'app_org_contact_edit')]
    public function edit(
        string $slug,
        int $id,
        Request $request,
        OrganizationRepository $orgRepo,
        ContactRepository $contactRepo,
        ProjectRepository $projectRepo,
        TopicRepository $topicRepo,
        EntityManagerInterface $em,
    ): Response {
        $organization = $orgRepo->findOneBy(['slug' => $slug]);
        if (!$organization) throw $this->createNotFoundException();

        $this->denyAccessUnlessGranted('ORG_VIEW', $organization);

        $contact = $contactRepo->find($id);
        if (!$contact || $contact->getOrganization() !== $organization) throw $this->createNotFoundException();

        [$projects, $topics] = $this->getProjectsAndTopics($organization, $projectRepo, $topicRepo);

        $form = $this->createForm(ContactFormType::class, $contact, [
            'projects' => $projects,
            'topics'   => $topics,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($contact->getCustomFields() as $field) {
                $field->setContact($contact);
            }
            $em->flush();
            $this->addFlash('success', 'Kontakt wurde aktualisiert.');
            return $this->redirectToRoute('app_org_contact_show', ['slug' => $slug, 'id' => $id]);
        }

        return $this->render('contact/form.html.twig', [
            'organization' => $organization,
            'contact'      => $contact,
            'form'         => $form,
            'backUrl'      => $this->generateUrl('app_org_contact_show', ['slug' => $slug, 'id' => $id]),
        ]);
    }

    #[Route('/organizations/{slug}/contacts/{id}/delete', name: 'app_org_contact_delete', methods: ['POST'])]
    public function delete(
        string $slug,
        int $id,
        Request $request,
        OrganizationRepository $orgRepo,
        ContactRepository $contactRepo,
        EntityManagerInterface $em,
    ): Response {
        $organization = $orgRepo->findOneBy(['slug' => $slug]);
        if (!$organization) throw $this->createNotFoundException();

        $this->denyAccessUnlessGranted('ORG_VIEW', $organization);

        $contact = $contactRepo->find($id);
        if (!$contact || $contact->getOrganization() !== $organization) throw $this->createNotFoundException();

        if ($this->isCsrfTokenValid('delete_contact_' . $id, $request->request->get('_token'))) {
            $em->remove($contact);
            $em->flush();
            $this->addFlash('success', 'Kontakt wurde gelöscht.');
        }

        return $this->redirectToRoute('app_org_contacts', ['slug' => $slug]);
    }

    #[Route('/organizations/{slug}/contacts/{id}/comment', name: 'app_org_contact_comment', methods: ['POST'])]
    public function addComment(
        string $slug,
        int $id,
        Request $request,
        OrganizationRepository $orgRepo,
        ContactRepository $contactRepo,
        EntityManagerInterface $em,
    ): Response {
        $organization = $orgRepo->findOneBy(['slug' => $slug]);
        if (!$organization) throw $this->createNotFoundException();

        $this->denyAccessUnlessGranted('ORG_VIEW', $organization);

        $contact = $contactRepo->find($id);
        if (!$contact || $contact->getOrganization() !== $organization) throw $this->createNotFoundException();

        if ($this->isCsrfTokenValid('contact_comment_' . $id, $request->request->get('_token'))) {
            $content = trim($request->request->get('content', ''));
            if ($content) {
                $comment = new ContactComment();
                $comment->setContact($contact);
                $comment->setAuthor($this->getUser());
                $comment->setContent($content);
                $em->persist($comment);
                $em->flush();
            }
        }

        return $this->redirectToRoute('app_org_contact_show', ['slug' => $slug, 'id' => $id]);
    }

    #[Route('/organizations/{slug}/contacts/{id}/comment/{commentId}/delete', name: 'app_org_contact_comment_delete', methods: ['POST'])]
    public function deleteComment(
        string $slug,
        int $id,
        int $commentId,
        Request $request,
        OrganizationRepository $orgRepo,
        ContactRepository $contactRepo,
        EntityManagerInterface $em,
    ): Response {
        $organization = $orgRepo->findOneBy(['slug' => $slug]);
        if (!$organization) throw $this->createNotFoundException();

        $this->denyAccessUnlessGranted('ORG_VIEW', $organization);

        $contact = $contactRepo->find($id);
        if (!$contact || $contact->getOrganization() !== $organization) throw $this->createNotFoundException();

        if ($this->isCsrfTokenValid('delete_contact_comment_' . $commentId, $request->request->get('_token'))) {
            foreach ($contact->getComments() as $comment) {
                if ($comment->getId() === $commentId && $comment->getAuthor() === $this->getUser()) {
                    $em->remove($comment);
                    $em->flush();
                    break;
                }
            }
        }

        return $this->redirectToRoute('app_org_contact_show', ['slug' => $slug, 'id' => $id]);
    }

    private function getProjectsAndTopics(
        $organization,
        ProjectRepository $projectRepo,
        TopicRepository $topicRepo,
    ): array {
        $projects = $projectRepo->findBy(['organization' => $organization], ['name' => 'ASC']);
        $topics = $topicRepo->findBy(['organization' => $organization], ['lastActivityAt' => 'DESC']);
        return [$projects, $topics];
    }
}
