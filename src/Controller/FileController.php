<?php

namespace App\Controller;

use App\Entity\FileCategory;
use App\Entity\FileComment;
use App\Entity\SharedFile;
use App\Form\FileCategoryFormType;
use App\Form\FileCommentFormType;
use App\Form\SharedFileFormType;
use App\Repository\FileCategoryRepository;
use App\Repository\FileCommentRepository;
use App\Repository\OrganizationRepository;
use App\Repository\ProjectRepository;
use App\Repository\SharedFileRepository;
use App\Security\Voter\OrganizationVoter;
use App\Security\Voter\ProjectVoter;
use App\Service\FileUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class FileController extends AbstractController
{
    public function __construct(
        private OrganizationRepository $orgRepo,
        private ProjectRepository $projectRepo,
        private FileCategoryRepository $categoryRepo,
        private SharedFileRepository $fileRepo,
        private FileCommentRepository $commentRepo,
        private EntityManagerInterface $em,
        private FileUploadService $uploadService,
    ) {}

    // ── Organization file routes ──────────────────────────────────────────

    #[Route('/organizations/{slug}/files', name: 'app_org_files')]
    public function orgFiles(string $slug, Request $request): Response
    {
        $org = $this->getOrg($slug);
        $this->denyAccessUnlessGranted(OrganizationVoter::VIEW, $org);

        $categoryId = $request->query->getInt('category', 0);
        $category = $categoryId ? $this->categoryRepo->find($categoryId) : null;

        $categories = $this->getAllCategories($this->categoryRepo->findRootByOrganization($org));
        $files = $this->fileRepo->findByOrganization($org, $category);

        return $this->render('files/org_files.html.twig', [
            'organization' => $org,
            'categories' => $categories,
            'currentCategory' => $category,
            'files' => $files,
            'rootCategories' => $this->categoryRepo->findRootByOrganization($org),
        ]);
    }

    #[Route('/organizations/{slug}/files/upload', name: 'app_org_file_upload')]
    public function orgFileUpload(string $slug, Request $request): Response
    {
        $org = $this->getOrg($slug);
        $this->denyAccessUnlessGranted(OrganizationVoter::VIEW, $org);

        $allCats = $this->getAllCategories($this->categoryRepo->findRootByOrganization($org));
        $form = $this->createForm(SharedFileFormType::class, null, ['categories' => $allCats]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $uploadedFile = $form->get('file')->getData();
            $info = $this->uploadService->upload($uploadedFile, 'org-' . $org->getId());

            $sharedFile = new SharedFile();
            $sharedFile->setOriginalName($info['originalName']);
            $sharedFile->setStoragePath($info['storagePath']);
            $sharedFile->setMimeType($info['mimeType']);
            $sharedFile->setFileSize($info['fileSize']);
            $sharedFile->setDescription($form->get('description')->getData());
            $sharedFile->setOrganization($org);
            $sharedFile->setUploadedBy($this->getUser());

            if ($allCats && $form->has('category')) {
                $sharedFile->setCategory($form->get('category')->getData());
            }

            $this->em->persist($sharedFile);
            $this->em->flush();

            $this->addFlash('success', 'Datei "' . $info['originalName'] . '" wurde hochgeladen.');
            return $this->redirectToRoute('app_org_files', ['slug' => $slug]);
        }

        return $this->render('files/upload.html.twig', [
            'form' => $form,
            'organization' => $org,
            'project' => null,
            'backUrl' => $this->generateUrl('app_org_files', ['slug' => $slug]),
        ]);
    }

    #[Route('/organizations/{slug}/files/categories/new', name: 'app_org_category_new')]
    public function orgCategoryNew(string $slug, Request $request): Response
    {
        $org = $this->getOrg($slug);
        $this->denyAccessUnlessGranted(OrganizationVoter::VIEW, $org);

        $allCats = $this->getAllCategories($this->categoryRepo->findRootByOrganization($org));
        $category = new FileCategory();
        $form = $this->createForm(FileCategoryFormType::class, $category, ['categories' => $allCats]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $category->setOrganization($org);
            $category->setCreatedBy($this->getUser());
            $this->em->persist($category);
            $this->em->flush();
            $this->addFlash('success', 'Kategorie erstellt.');
            return $this->redirectToRoute('app_org_files', ['slug' => $slug]);
        }

        return $this->render('files/category_form.html.twig', [
            'form' => $form,
            'organization' => $org,
            'project' => null,
            'backUrl' => $this->generateUrl('app_org_files', ['slug' => $slug]),
        ]);
    }

    // ── Project file routes ───────────────────────────────────────────────

    #[Route('/organizations/{slug}/projects/{projectSlug}/files', name: 'app_project_files')]
    public function projectFiles(string $slug, string $projectSlug, Request $request): Response
    {
        [$org, $project] = $this->getOrgAndProject($slug, $projectSlug);
        $this->denyAccessUnlessGranted(ProjectVoter::VIEW, $project);

        $categoryId = $request->query->getInt('category', 0);
        $category = $categoryId ? $this->categoryRepo->find($categoryId) : null;

        $categories = $this->getAllCategories($this->categoryRepo->findRootByProject($project));
        $files = $this->fileRepo->findByProject($project, $category);

        return $this->render('files/project_files.html.twig', [
            'organization' => $org,
            'project' => $project,
            'categories' => $categories,
            'currentCategory' => $category,
            'files' => $files,
            'rootCategories' => $this->categoryRepo->findRootByProject($project),
        ]);
    }

    #[Route('/organizations/{slug}/projects/{projectSlug}/files/upload', name: 'app_project_file_upload')]
    public function projectFileUpload(string $slug, string $projectSlug, Request $request): Response
    {
        [$org, $project] = $this->getOrgAndProject($slug, $projectSlug);
        $this->denyAccessUnlessGranted(ProjectVoter::VIEW, $project);

        $allCats = $this->getAllCategories($this->categoryRepo->findRootByProject($project));
        $form = $this->createForm(SharedFileFormType::class, null, ['categories' => $allCats]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $uploadedFile = $form->get('file')->getData();
            $info = $this->uploadService->upload($uploadedFile, 'project-' . $project->getId());

            $sharedFile = new SharedFile();
            $sharedFile->setOriginalName($info['originalName']);
            $sharedFile->setStoragePath($info['storagePath']);
            $sharedFile->setMimeType($info['mimeType']);
            $sharedFile->setFileSize($info['fileSize']);
            $sharedFile->setDescription($form->get('description')->getData());
            $sharedFile->setProject($project);
            $sharedFile->setOrganization($org);
            $sharedFile->setUploadedBy($this->getUser());

            if ($allCats && $form->has('category')) {
                $sharedFile->setCategory($form->get('category')->getData());
            }

            $this->em->persist($sharedFile);
            $this->em->flush();

            $this->addFlash('success', 'Datei hochgeladen.');
            return $this->redirectToRoute('app_project_files', ['slug' => $slug, 'projectSlug' => $projectSlug]);
        }

        return $this->render('files/upload.html.twig', [
            'form' => $form,
            'organization' => $org,
            'project' => $project,
            'backUrl' => $this->generateUrl('app_project_files', ['slug' => $slug, 'projectSlug' => $projectSlug]),
        ]);
    }

    #[Route('/organizations/{slug}/projects/{projectSlug}/files/categories/new', name: 'app_project_category_new')]
    public function projectCategoryNew(string $slug, string $projectSlug, Request $request): Response
    {
        [$org, $project] = $this->getOrgAndProject($slug, $projectSlug);
        $this->denyAccessUnlessGranted(ProjectVoter::VIEW, $project);

        $allCats = $this->getAllCategories($this->categoryRepo->findRootByProject($project));
        $category = new FileCategory();
        $form = $this->createForm(FileCategoryFormType::class, $category, ['categories' => $allCats]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $category->setProject($project);
            $category->setOrganization($org);
            $category->setCreatedBy($this->getUser());
            $this->em->persist($category);
            $this->em->flush();
            $this->addFlash('success', 'Kategorie erstellt.');
            return $this->redirectToRoute('app_project_files', ['slug' => $slug, 'projectSlug' => $projectSlug]);
        }

        return $this->render('files/category_form.html.twig', [
            'form' => $form,
            'organization' => $org,
            'project' => $project,
            'backUrl' => $this->generateUrl('app_project_files', ['slug' => $slug, 'projectSlug' => $projectSlug]),
        ]);
    }

    // ── Shared file actions ───────────────────────────────────────────────

    #[Route('/files/{id}', name: 'app_file_show')]
    public function show(int $id, Request $request): Response
    {
        $file = $this->fileRepo->find($id);
        if (!$file) throw $this->createNotFoundException();

        $this->checkFileAccess($file);

        $commentForm = $this->createForm(FileCommentFormType::class);
        $commentForm->handleRequest($request);

        if ($commentForm->isSubmitted() && $commentForm->isValid()) {
            $comment = new FileComment();
            $comment->setFile($file);
            $comment->setAuthor($this->getUser());
            $comment->setContent($commentForm->get('content')->getData());
            $this->em->persist($comment);
            $this->em->flush();
            $this->addFlash('success', 'Kommentar hinzugefügt.');
            return $this->redirectToRoute('app_file_show', ['id' => $id]);
        }

        return $this->render('files/show.html.twig', [
            'file' => $file,
            'commentForm' => $commentForm,
        ]);
    }

    #[Route('/files/{id}/download', name: 'app_file_download')]
    public function download(int $id): BinaryFileResponse
    {
        $file = $this->fileRepo->find($id);
        if (!$file) throw $this->createNotFoundException();

        $this->checkFileAccess($file);

        $publicDir = $this->getParameter('kernel.project_dir') . '/public';
        $fullPath = $publicDir . '/' . $file->getStoragePath();

        if (!file_exists($fullPath)) {
            throw $this->createNotFoundException('Datei nicht gefunden.');
        }

        return $this->file($fullPath, $file->getOriginalName(), ResponseHeaderBag::DISPOSITION_ATTACHMENT);
    }

    #[Route('/files/{id}/delete', name: 'app_file_delete', methods: ['POST'])]
    public function delete(int $id, Request $request): Response
    {
        $file = $this->fileRepo->find($id);
        if (!$file) throw $this->createNotFoundException();

        $this->checkFileAccess($file, true);

        if (!$this->isCsrfTokenValid('delete_file_' . $id, $request->getPayload()->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $redirectUrl = $this->getFileListUrl($file);

        $publicDir = $this->getParameter('kernel.project_dir') . '/public';
        $this->uploadService->delete($file->getStoragePath(), $publicDir);

        $this->fileRepo->remove($file, true);
        $this->addFlash('success', 'Datei gelöscht.');

        return $this->redirect($redirectUrl);
    }

    #[Route('/files/comments/{id}/delete', name: 'app_file_comment_delete', methods: ['POST'])]
    public function deleteComment(int $id, Request $request): Response
    {
        $comment = $this->commentRepo->find($id);
        if (!$comment) throw $this->createNotFoundException();

        if ($comment->getAuthor() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete_comment_' . $id, $request->getPayload()->get('_token'))) {
            $fileId = $comment->getFile()->getId();
            $this->commentRepo->remove($comment, true);
            $this->addFlash('success', 'Kommentar gelöscht.');
        }

        return $this->redirectToRoute('app_file_show', ['id' => $comment->getFile()->getId()]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function checkFileAccess(SharedFile $file, bool $requireOwnerOrAdmin = false): void
    {
        $user = $this->getUser();

        if ($file->getProject()) {
            $this->denyAccessUnlessGranted(ProjectVoter::VIEW, $file->getProject());
            if ($requireOwnerOrAdmin && $file->getUploadedBy() !== $user) {
                $this->denyAccessUnlessGranted(ProjectVoter::EDIT, $file->getProject());
            }
        } elseif ($file->getOrganization()) {
            $this->denyAccessUnlessGranted(OrganizationVoter::VIEW, $file->getOrganization());
            if ($requireOwnerOrAdmin && $file->getUploadedBy() !== $user) {
                $this->denyAccessUnlessGranted(OrganizationVoter::EDIT, $file->getOrganization());
            }
        }
    }

    private function getFileListUrl(SharedFile $file): string
    {
        if ($file->getProject()) {
            $org = $file->getOrganization();
            return $this->generateUrl('app_project_files', [
                'slug' => $org->getSlug(),
                'projectSlug' => $file->getProject()->getSlug(),
            ]);
        }
        return $this->generateUrl('app_org_files', ['slug' => $file->getOrganization()->getSlug()]);
    }

    private function getAllCategories(array $roots): array
    {
        $result = [];
        foreach ($roots as $cat) {
            $result[] = $cat;
            foreach ($cat->getChildren() as $child) {
                $result[] = $child;
            }
        }
        return $result;
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
