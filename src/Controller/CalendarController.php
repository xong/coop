<?php

namespace App\Controller;

use App\Entity\CalendarEvent;
use App\Form\CalendarEventFormType;
use App\Repository\CalendarEventRepository;
use App\Repository\OrganizationRepository;
use App\Repository\TaskRepository;
use App\Security\Voter\OrganizationVoter;
use App\Service\CalendarService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/organizations/{slug}/calendar')]
class CalendarController extends AbstractController
{
    public function __construct(
        private OrganizationRepository $orgRepo,
        private CalendarEventRepository $eventRepo,
        private TaskRepository $taskRepo,
        private EntityManagerInterface $em,
        private CalendarService $calendarService,
    ) {}

    #[Route('', name: 'app_org_calendar')]
    public function index(string $slug, Request $request): Response
    {
        $org = $this->getOrg($slug);
        $this->denyAccessUnlessGranted(OrganizationVoter::VIEW, $org);

        $year  = $request->query->getInt('year', (int) date('Y'));
        $month = $request->query->getInt('month', (int) date('m'));

        // Clamp month
        if ($month < 1) { $month = 12; $year--; }
        if ($month > 12) { $month = 1; $year++; }

        $from = new \DateTimeImmutable("{$year}-{$month}-01 00:00:00");
        $to   = new \DateTimeImmutable("{$year}-{$month}-" . $from->format('t') . " 23:59:59");

        $events = $this->eventRepo->findForCalendar($org, $from, $to);
        $tasks  = $this->taskRepo->findForCalendar($org, $from, $to);

        $grid = $this->calendarService->buildMonthGrid($year, $month);

        $eventsByDate = $this->calendarService->groupByDate($events, fn($e) => $e->getStartAt());
        $tasksByDate  = $this->calendarService->groupByDate($tasks, fn($t) => $t->getDueDate());

        return $this->render('calendar/index.html.twig', [
            'organization' => $org,
            'year' => $year,
            'month' => $month,
            'monthName' => $from->format('F Y'),
            'grid' => $grid,
            'eventsByDate' => $eventsByDate,
            'tasksByDate' => $tasksByDate,
            'prevYear' => $month === 1 ? $year - 1 : $year,
            'prevMonth' => $month === 1 ? 12 : $month - 1,
            'nextYear' => $month === 12 ? $year + 1 : $year,
            'nextMonth' => $month === 12 ? 1 : $month + 1,
            'upcomingEvents' => $this->eventRepo->findByOrganization($org),
        ]);
    }

    #[Route('/events/new', name: 'app_org_event_new')]
    public function newEvent(string $slug, Request $request): Response
    {
        $org = $this->getOrg($slug);
        $this->denyAccessUnlessGranted(OrganizationVoter::VIEW, $org);

        $event = new CalendarEvent();
        $members = array_map(fn($m) => $m->getUser(), $org->getMembers()->toArray());
        $form = $this->createForm(CalendarEventFormType::class, $event, ['members' => $members]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $event->setOrganization($org);
            $event->setCreatedBy($this->getUser());
            $this->em->persist($event);
            $this->em->flush();
            $this->addFlash('success', 'Termin erstellt.');
            return $this->redirectToRoute('app_org_calendar', ['slug' => $slug]);
        }

        return $this->render('calendar/event_form.html.twig', [
            'form' => $form,
            'organization' => $org,
            'event' => $event,
            'backUrl' => $this->generateUrl('app_org_calendar', ['slug' => $slug]),
        ]);
    }

    #[Route('/events/{id}/edit', name: 'app_org_event_edit')]
    public function editEvent(string $slug, int $id, Request $request): Response
    {
        $org = $this->getOrg($slug);
        $this->denyAccessUnlessGranted(OrganizationVoter::VIEW, $org);

        $event = $this->eventRepo->find($id);
        if (!$event || $event->getOrganization() !== $org) throw $this->createNotFoundException();

        $members = array_map(fn($m) => $m->getUser(), $org->getMembers()->toArray());
        $form = $this->createForm(CalendarEventFormType::class, $event, ['members' => $members]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $this->addFlash('success', 'Termin aktualisiert.');
            return $this->redirectToRoute('app_org_calendar', ['slug' => $slug]);
        }

        return $this->render('calendar/event_form.html.twig', [
            'form' => $form,
            'organization' => $org,
            'event' => $event,
            'backUrl' => $this->generateUrl('app_org_calendar', ['slug' => $slug]),
        ]);
    }

    #[Route('/events/{id}/delete', name: 'app_org_event_delete', methods: ['POST'])]
    public function deleteEvent(string $slug, int $id, Request $request): Response
    {
        $org = $this->getOrg($slug);
        $event = $this->eventRepo->find($id);
        if (!$event || $event->getOrganization() !== $org) throw $this->createNotFoundException();

        if ($this->isCsrfTokenValid('delete_event_' . $id, $request->getPayload()->get('_token'))) {
            $this->eventRepo->remove($event, true);
            $this->addFlash('success', 'Termin gelöscht.');
        }

        return $this->redirectToRoute('app_org_calendar', ['slug' => $slug]);
    }

    private function getOrg(string $slug): \App\Entity\Organization
    {
        $org = $this->orgRepo->findOneBySlug($slug);
        if (!$org) throw $this->createNotFoundException();
        return $org;
    }
}
