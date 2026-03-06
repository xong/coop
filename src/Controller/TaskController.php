<?php

namespace App\Controller;

use App\Entity\Task;
use App\Form\TaskFormType;
use App\Repository\OrganizationRepository;
use App\Repository\ProjectRepository;
use App\Repository\TaskRepository;
use App\Entity\NotificationSetting;
use App\Security\Voter\OrganizationVoter;
use App\Security\Voter\ProjectVoter;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class TaskController extends AbstractController
{
    public function __construct(
        private OrganizationRepository $orgRepo,
        private ProjectRepository $projectRepo,
        private TaskRepository $taskRepo,
        private EntityManagerInterface $em,
        private NotificationService $notifService,
    ) {}

    // ── Organization tasks ────────────────────────────────────────────────

    #[Route('/organizations/{slug}/tasks', name: 'app_org_tasks')]
    public function orgTasks(string $slug, Request $request): Response
    {
        $org = $this->getOrg($slug);
        $this->denyAccessUnlessGranted(OrganizationVoter::VIEW, $org);

        $status = $request->query->get('status');
        $tasks = $this->taskRepo->findByOrganization($org, $status ?: null);

        return $this->render('tasks/list.html.twig', [
            'organization' => $org,
            'project' => null,
            'tasks' => $tasks,
            'currentStatus' => $status,
            'newUrl' => $this->generateUrl('app_org_task_new', ['slug' => $slug]),
            'calendarUrl' => $this->generateUrl('app_org_calendar', ['slug' => $slug]),
        ]);
    }

    #[Route('/organizations/{slug}/tasks/new', name: 'app_org_task_new')]
    public function orgTaskNew(string $slug, Request $request): Response
    {
        $org = $this->getOrg($slug);
        $this->denyAccessUnlessGranted(OrganizationVoter::VIEW, $org);

        $task = new Task();
        $members = $this->getOrgMembers($org);
        $form = $this->createForm(TaskFormType::class, $task, ['members' => $members]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $task->setOrganization($org);
            $task->setCreatedBy($this->getUser());
            $this->em->persist($task);
            $this->em->flush();
            $this->notifyAssignees($task);
            $this->addFlash('success', 'Aufgabe erstellt.');
            return $this->redirectToRoute('app_org_tasks', ['slug' => $slug]);
        }

        return $this->render('tasks/form.html.twig', [
            'form' => $form,
            'organization' => $org,
            'project' => null,
            'task' => $task,
            'backUrl' => $this->generateUrl('app_org_tasks', ['slug' => $slug]),
        ]);
    }

    // ── Project tasks ─────────────────────────────────────────────────────

    #[Route('/organizations/{slug}/projects/{projectSlug}/tasks', name: 'app_project_tasks')]
    public function projectTasks(string $slug, string $projectSlug, Request $request): Response
    {
        [$org, $project] = $this->getOrgAndProject($slug, $projectSlug);
        $this->denyAccessUnlessGranted(ProjectVoter::VIEW, $project);

        $status = $request->query->get('status');
        $tasks = $this->taskRepo->findByProject($project, $status ?: null);

        return $this->render('tasks/list.html.twig', [
            'organization' => $org,
            'project' => $project,
            'tasks' => $tasks,
            'currentStatus' => $status,
            'newUrl' => $this->generateUrl('app_project_task_new', ['slug' => $slug, 'projectSlug' => $projectSlug]),
            'calendarUrl' => $this->generateUrl('app_org_calendar', ['slug' => $slug]),
        ]);
    }

    #[Route('/organizations/{slug}/projects/{projectSlug}/tasks/new', name: 'app_project_task_new')]
    public function projectTaskNew(string $slug, string $projectSlug, Request $request): Response
    {
        [$org, $project] = $this->getOrgAndProject($slug, $projectSlug);
        $this->denyAccessUnlessGranted(ProjectVoter::VIEW, $project);

        $task = new Task();
        $members = $this->getProjectMembers($project);
        $form = $this->createForm(TaskFormType::class, $task, ['members' => $members]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $task->setOrganization($org);
            $task->setProject($project);
            $task->setCreatedBy($this->getUser());
            $this->em->persist($task);
            $this->em->flush();
            $this->addFlash('success', 'Aufgabe erstellt.');
            return $this->redirectToRoute('app_project_tasks', ['slug' => $slug, 'projectSlug' => $projectSlug]);
        }

        return $this->render('tasks/form.html.twig', [
            'form' => $form,
            'organization' => $org,
            'project' => $project,
            'task' => $task,
            'backUrl' => $this->generateUrl('app_project_tasks', ['slug' => $slug, 'projectSlug' => $projectSlug]),
        ]);
    }

    // ── Task actions ──────────────────────────────────────────────────────

    #[Route('/tasks/{id}', name: 'app_task_show')]
    public function show(int $id): Response
    {
        $task = $this->findTask($id);
        return $this->render('tasks/show.html.twig', ['task' => $task]);
    }

    #[Route('/tasks/{id}/edit', name: 'app_task_edit')]
    public function edit(int $id, Request $request): Response
    {
        $task = $this->findTask($id);
        $org = $task->getOrganization();
        $project = $task->getProject();

        $members = $project ? $this->getProjectMembers($project) : $this->getOrgMembers($org);
        $form = $this->createForm(TaskFormType::class, $task, ['members' => $members]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($task->isDone() && !$task->getCompletedAt()) {
                $task->setCompletedAt(new \DateTimeImmutable());
            } elseif (!$task->isDone()) {
                $task->setCompletedAt(null);
            }
            $this->em->flush();
            $this->addFlash('success', 'Aufgabe aktualisiert.');
            return $this->redirectToRoute('app_task_show', ['id' => $id]);
        }

        $backUrl = $project
            ? $this->generateUrl('app_project_tasks', ['slug' => $org->getSlug(), 'projectSlug' => $project->getSlug()])
            : $this->generateUrl('app_org_tasks', ['slug' => $org->getSlug()]);

        return $this->render('tasks/form.html.twig', [
            'form' => $form,
            'organization' => $org,
            'project' => $project,
            'task' => $task,
            'backUrl' => $backUrl,
        ]);
    }

    #[Route('/tasks/{id}/status/{status}', name: 'app_task_status', methods: ['POST'])]
    public function updateStatus(int $id, string $status, Request $request): Response
    {
        $task = $this->findTask($id);

        $allowed = [Task::STATUS_OPEN, Task::STATUS_IN_PROGRESS, Task::STATUS_DONE, Task::STATUS_CANCELLED];
        if (!in_array($status, $allowed)) throw $this->createNotFoundException();

        if ($this->isCsrfTokenValid('task_status_' . $id, $request->getPayload()->get('_token'))) {
            $task->setStatus($status);
            if ($status === Task::STATUS_DONE) $task->setCompletedAt(new \DateTimeImmutable());
            else $task->setCompletedAt(null);
            $this->em->flush();
        }

        return $this->redirectToRoute('app_task_show', ['id' => $id]);
    }

    #[Route('/tasks/{id}/delete', name: 'app_task_delete', methods: ['POST'])]
    public function delete(int $id, Request $request): Response
    {
        $task = $this->findTask($id);
        $org = $task->getOrganization();
        $project = $task->getProject();

        $redirectUrl = $project
            ? $this->generateUrl('app_project_tasks', ['slug' => $org->getSlug(), 'projectSlug' => $project->getSlug()])
            : $this->generateUrl('app_org_tasks', ['slug' => $org->getSlug()]);

        if ($this->isCsrfTokenValid('delete_task_' . $id, $request->getPayload()->get('_token'))) {
            $this->taskRepo->remove($task, true);
            $this->addFlash('success', 'Aufgabe gelöscht.');
        }

        return $this->redirect($redirectUrl);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function findTask(int $id): Task
    {
        $task = $this->taskRepo->find($id);
        if (!$task) throw $this->createNotFoundException();

        if ($task->getProject()) {
            $this->denyAccessUnlessGranted(ProjectVoter::VIEW, $task->getProject());
        } else {
            $this->denyAccessUnlessGranted(OrganizationVoter::VIEW, $task->getOrganization());
        }
        return $task;
    }

    private function getOrgMembers(\App\Entity\Organization $org): array
    {
        return array_map(fn($m) => $m->getUser(), $org->getMembers()->toArray());
    }

    private function getProjectMembers(\App\Entity\Project $project): array
    {
        return array_map(fn($m) => $m->getUser(), $project->getMembers()->toArray());
    }

    private function notifyAssignees(Task $task): void
    {
        $assignees = $task->getAssignees()->toArray();
        if (empty($assignees)) return;

        $url = $this->generateUrl('app_task_show', ['id' => $task->getId()], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);
        $this->notifService->notifyMany(
            $assignees,
            $this->getUser(),
            NotificationSetting::EVENT_TASK_ASSIGNED,
            'Neue Aufgabe: ' . $task->getTitle(),
            sprintf('%s hat dir die Aufgabe "%s" zugewiesen.', $this->getUser()->getFullName(), $task->getTitle()),
            $url,
        );
    }

    private function getOrg(string $slug): \App\Entity\Organization
    {
        $org = $this->orgRepo->findOneBySlug($slug);
        if (!$org) throw $this->createNotFoundException();
        return $org;
    }

    private function getOrgAndProject(string $slug, string $projectSlug): array
    {
        $org = $this->getOrg($slug);
        $project = $this->projectRepo->findOneByOrganizationAndSlug($org, $projectSlug);
        if (!$project) throw $this->createNotFoundException();
        return [$org, $project];
    }
}
