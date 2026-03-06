<?php

namespace App\Controller;

use App\Entity\Topic;
use App\Entity\TopicPost;
use App\Entity\TopicPostAttachment;
use App\Form\TopicFormType;
use App\Form\TopicPostFormType;
use App\Repository\OrganizationRepository;
use App\Repository\ProjectRepository;
use App\Repository\TopicPostRepository;
use App\Repository\TopicRepository;
use App\Security\Voter\OrganizationVoter;
use App\Security\Voter\ProjectVoter;
use App\Service\FileUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class TopicController extends AbstractController
{
    public function __construct(
        private OrganizationRepository $orgRepo,
        private ProjectRepository $projectRepo,
        private TopicRepository $topicRepo,
        private TopicPostRepository $postRepo,
        private EntityManagerInterface $em,
        private FileUploadService $uploadService,
    ) {}

    // ── Organization topics ───────────────────────────────────────────────

    #[Route('/organizations/{slug}/topics', name: 'app_org_topics')]
    public function orgTopics(string $slug): Response
    {
        $org = $this->getOrg($slug);
        $this->denyAccessUnlessGranted(OrganizationVoter::VIEW, $org);

        return $this->render('topics/list.html.twig', [
            'organization' => $org,
            'project' => null,
            'topics' => $this->topicRepo->findByOrganization($org),
            'newTopicUrl' => $this->generateUrl('app_org_topic_new', ['slug' => $slug]),
            'backUrl' => $this->generateUrl('app_organization_show', ['slug' => $slug]),
        ]);
    }

    #[Route('/organizations/{slug}/topics/new', name: 'app_org_topic_new')]
    public function orgTopicNew(string $slug, Request $request): Response
    {
        $org = $this->getOrg($slug);
        $this->denyAccessUnlessGranted(OrganizationVoter::VIEW, $org);

        $topic = new Topic();
        $form = $this->createForm(TopicFormType::class, $topic);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            $topic->setOrganization($org);
            $topic->setCreatedBy($user);
            $this->em->persist($topic);

            $post = $this->createPost($topic, $user, $form->get('content')->getData());
            $this->handleAttachments($form, $post, 'org-topics-' . $org->getId());

            $this->em->flush();
            $this->addFlash('success', 'Thema erstellt.');
            return $this->redirectToRoute('app_topic_show', ['id' => $topic->getId()]);
        }

        return $this->render('topics/new.html.twig', [
            'form' => $form,
            'organization' => $org,
            'project' => null,
            'backUrl' => $this->generateUrl('app_org_topics', ['slug' => $slug]),
        ]);
    }

    // ── Project topics ────────────────────────────────────────────────────

    #[Route('/organizations/{slug}/projects/{projectSlug}/topics', name: 'app_project_topics')]
    public function projectTopics(string $slug, string $projectSlug): Response
    {
        [$org, $project] = $this->getOrgAndProject($slug, $projectSlug);
        $this->denyAccessUnlessGranted(ProjectVoter::VIEW, $project);

        return $this->render('topics/list.html.twig', [
            'organization' => $org,
            'project' => $project,
            'topics' => $this->topicRepo->findByProject($project),
            'newTopicUrl' => $this->generateUrl('app_project_topic_new', ['slug' => $slug, 'projectSlug' => $projectSlug]),
            'backUrl' => $this->generateUrl('app_project_show', ['slug' => $slug, 'projectSlug' => $projectSlug]),
        ]);
    }

    #[Route('/organizations/{slug}/projects/{projectSlug}/topics/new', name: 'app_project_topic_new')]
    public function projectTopicNew(string $slug, string $projectSlug, Request $request): Response
    {
        [$org, $project] = $this->getOrgAndProject($slug, $projectSlug);
        $this->denyAccessUnlessGranted(ProjectVoter::VIEW, $project);

        $topic = new Topic();
        $form = $this->createForm(TopicFormType::class, $topic);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            $topic->setOrganization($org);
            $topic->setProject($project);
            $topic->setCreatedBy($user);
            $this->em->persist($topic);

            $post = $this->createPost($topic, $user, $form->get('content')->getData());
            $this->handleAttachments($form, $post, 'project-topics-' . $project->getId());

            $this->em->flush();
            $this->addFlash('success', 'Thema erstellt.');
            return $this->redirectToRoute('app_topic_show', ['id' => $topic->getId()]);
        }

        return $this->render('topics/new.html.twig', [
            'form' => $form,
            'organization' => $org,
            'project' => $project,
            'backUrl' => $this->generateUrl('app_project_topics', ['slug' => $slug, 'projectSlug' => $projectSlug]),
        ]);
    }

    // ── Topic detail ──────────────────────────────────────────────────────

    #[Route('/topics/{id}', name: 'app_topic_show')]
    public function show(int $id, Request $request): Response
    {
        $topic = $this->topicRepo->find($id);
        if (!$topic) throw $this->createNotFoundException();

        $this->checkTopicAccess($topic);

        $replyForm = null;
        if (!$topic->isClosed()) {
            $replyForm = $this->createForm(TopicPostFormType::class);
            $replyForm->handleRequest($request);

            if ($replyForm->isSubmitted() && $replyForm->isValid()) {
                $subDir = $topic->getProject()
                    ? 'project-topics-' . $topic->getProject()->getId()
                    : 'org-topics-' . $topic->getOrganization()->getId();

                $post = $this->createPost($topic, $this->getUser(), $replyForm->get('content')->getData());
                $this->handleAttachments($replyForm, $post, $subDir);

                $topic->setLastActivityAt(new \DateTimeImmutable());
                $this->em->flush();

                $this->addFlash('success', 'Antwort hinzugefügt.');
                return $this->redirectToRoute('app_topic_show', ['id' => $id]);
            }
        }

        return $this->render('topics/show.html.twig', [
            'topic' => $topic,
            'replyForm' => $replyForm,
        ]);
    }

    #[Route('/topics/{id}/pin', name: 'app_topic_pin', methods: ['POST'])]
    public function pin(int $id, Request $request): Response
    {
        $topic = $this->topicRepo->find($id);
        if (!$topic) throw $this->createNotFoundException();

        $this->checkTopicAdminAccess($topic);

        if ($this->isCsrfTokenValid('pin_topic_' . $id, $request->getPayload()->get('_token'))) {
            $topic->setIsPinned(!$topic->isPinned());
            $this->em->flush();
        }

        return $this->redirectToRoute('app_topic_show', ['id' => $id]);
    }

    #[Route('/topics/{id}/close', name: 'app_topic_close', methods: ['POST'])]
    public function close(int $id, Request $request): Response
    {
        $topic = $this->topicRepo->find($id);
        if (!$topic) throw $this->createNotFoundException();

        $this->checkTopicAdminAccess($topic);

        if ($this->isCsrfTokenValid('close_topic_' . $id, $request->getPayload()->get('_token'))) {
            $topic->setIsClosed(!$topic->isClosed());
            $this->em->flush();
        }

        return $this->redirectToRoute('app_topic_show', ['id' => $id]);
    }

    #[Route('/topics/{id}/delete', name: 'app_topic_delete', methods: ['POST'])]
    public function deleteTopic(int $id, Request $request): Response
    {
        $topic = $this->topicRepo->find($id);
        if (!$topic) throw $this->createNotFoundException();

        $this->checkTopicAdminAccess($topic);

        $org = $topic->getOrganization();
        $project = $topic->getProject();

        if ($this->isCsrfTokenValid('delete_topic_' . $id, $request->getPayload()->get('_token'))) {
            $this->topicRepo->remove($topic, true);
            $this->addFlash('success', 'Thema gelöscht.');
        }

        if ($project) {
            return $this->redirectToRoute('app_project_topics', ['slug' => $org->getSlug(), 'projectSlug' => $project->getSlug()]);
        }
        return $this->redirectToRoute('app_org_topics', ['slug' => $org->getSlug()]);
    }

    #[Route('/topics/posts/{id}/delete', name: 'app_topic_post_delete', methods: ['POST'])]
    public function deletePost(int $id, Request $request): Response
    {
        $post = $this->postRepo->find($id);
        if (!$post) throw $this->createNotFoundException();

        $topic = $post->getTopic();
        $this->checkTopicAccess($topic);

        if ($post->getAuthor() !== $this->getUser()) {
            $this->checkTopicAdminAccess($topic);
        }

        if ($this->isCsrfTokenValid('delete_post_' . $id, $request->getPayload()->get('_token'))) {
            // Delete attachment files
            $publicDir = $this->getParameter('kernel.project_dir') . '/public';
            foreach ($post->getAttachments() as $attachment) {
                $this->uploadService->delete($attachment->getStoragePath(), $publicDir);
            }
            $this->postRepo->remove($post, true);
            $this->addFlash('success', 'Beitrag gelöscht.');
        }

        return $this->redirectToRoute('app_topic_show', ['id' => $topic->getId()]);
    }

    #[Route('/topics/posts/{id}/edit', name: 'app_topic_post_edit')]
    public function editPost(int $id, Request $request): Response
    {
        $post = $this->postRepo->find($id);
        if (!$post) throw $this->createNotFoundException();

        $this->checkTopicAccess($post->getTopic());

        if ($post->getAuthor() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(TopicPostFormType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $post->setUpdatedAt(new \DateTimeImmutable());
            $this->em->flush();
            $this->addFlash('success', 'Beitrag aktualisiert.');
            return $this->redirectToRoute('app_topic_show', ['id' => $post->getTopic()->getId()]);
        }

        return $this->render('topics/edit_post.html.twig', [
            'form' => $form,
            'post' => $post,
            'topic' => $post->getTopic(),
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function createPost(Topic $topic, $user, string $content): TopicPost
    {
        $post = new TopicPost();
        $post->setTopic($topic);
        $post->setAuthor($user);
        $post->setContent($content);
        $this->em->persist($post);
        return $post;
    }

    private function handleAttachments(\Symfony\Component\Form\FormInterface $form, TopicPost $post, string $subDir): void
    {
        $files = $form->get('attachments')->getData();
        if (!$files) return;

        foreach ($files as $uploadedFile) {
            if (!$uploadedFile) continue;
            $info = $this->uploadService->upload($uploadedFile, 'topics/' . $subDir);

            $attachment = new TopicPostAttachment();
            $attachment->setPost($post);
            $attachment->setOriginalName($info['originalName']);
            $attachment->setStoragePath($info['storagePath']);
            $attachment->setMimeType($info['mimeType']);
            $attachment->setFileSize($info['fileSize']);
            $this->em->persist($attachment);
        }
    }

    private function checkTopicAccess(Topic $topic): void
    {
        if ($topic->getProject()) {
            $this->denyAccessUnlessGranted(ProjectVoter::VIEW, $topic->getProject());
        } else {
            $this->denyAccessUnlessGranted(OrganizationVoter::VIEW, $topic->getOrganization());
        }
    }

    private function checkTopicAdminAccess(Topic $topic): void
    {
        if ($topic->getProject()) {
            $this->denyAccessUnlessGranted(ProjectVoter::EDIT, $topic->getProject());
        } else {
            $this->denyAccessUnlessGranted(OrganizationVoter::EDIT, $topic->getOrganization());
        }
    }

    private function getOrg(string $slug): \App\Entity\Organization
    {
        $org = $this->orgRepo->findOneBySlug($slug);
        if (!$org) throw $this->createNotFoundException('Organisation nicht gefunden.');
        return $org;
    }

    private function getOrgAndProject(string $slug, string $projectSlug): array
    {
        $org = $this->getOrg($slug);
        $project = $this->projectRepo->findOneByOrganizationAndSlug($org, $projectSlug);
        if (!$project) throw $this->createNotFoundException('Projekt nicht gefunden.');
        return [$org, $project];
    }
}
