<?php

namespace App\Service;

use App\Entity\DashboardWidget;
use App\Entity\User;
use App\Repository\CalendarEventRepository;
use App\Repository\ConversationRepository;
use App\Repository\DashboardWidgetRepository;
use App\Repository\IncomingEmailRepository;
use App\Repository\OrganizationRepository;
use App\Repository\SharedFileRepository;
use App\Repository\TaskRepository;
use App\Repository\TopicRepository;
use Doctrine\ORM\EntityManagerInterface;

class DashboardService
{
    public function __construct(
        private readonly DashboardWidgetRepository $widgetRepo,
        private readonly EntityManagerInterface $em,
        private readonly OrganizationRepository $orgRepo,
        private readonly TaskRepository $taskRepo,
        private readonly CalendarEventRepository $calendarRepo,
        private readonly TopicRepository $topicRepo,
        private readonly SharedFileRepository $fileRepo,
        private readonly ConversationRepository $convRepo,
        private readonly IncomingEmailRepository $emailRepo,
    ) {
    }

    /** Returns widgets for user, creating defaults on first visit */
    public function getWidgets(User $user): array
    {
        $widgets = $this->widgetRepo->findForUser($user);

        if (empty($widgets)) {
            $widgets = $this->createDefaults($user);
        }

        return $widgets;
    }

    /** Returns data payload for an enabled widget */
    public function getWidgetData(User $user, string $type): array
    {
        $orgs = array_filter(
            $this->orgRepo->findAll(),
            fn($o) => $o->isMember($user)
        );

        return match ($type) {
            DashboardWidget::WIDGET_ORGANIZATIONS   => $this->dataOrganizations($user, $orgs),
            DashboardWidget::WIDGET_MY_TASKS        => $this->dataTasks($user),
            DashboardWidget::WIDGET_UPCOMING_EVENTS => $this->dataEvents($user, $orgs),
            DashboardWidget::WIDGET_RECENT_TOPICS   => $this->dataTopics($orgs),
            DashboardWidget::WIDGET_RECENT_FILES    => $this->dataFiles($orgs),
            DashboardWidget::WIDGET_MESSAGES        => $this->dataMessages($user),
            DashboardWidget::WIDGET_RECENT_EMAILS   => $this->dataEmails($orgs),
            default                                  => [],
        };
    }

    public function moveWidget(DashboardWidget $widget, string $direction): void
    {
        $widgets = $this->widgetRepo->findForUser($widget->getUser());

        $positions = array_map(fn($w) => $w->getId(), $widgets);
        $idx = array_search($widget->getId(), $positions);

        if ($direction === 'up' && $idx > 0) {
            $swap = $widgets[$idx - 1];
        } elseif ($direction === 'down' && $idx < count($widgets) - 1) {
            $swap = $widgets[$idx + 1];
        } else {
            return;
        }

        $tmpPos = $widget->getPosition();
        $widget->setPosition($swap->getPosition());
        $swap->setPosition($tmpPos);
        $this->em->flush();
    }

    // -------------------------------------------------------------------------

    private function createDefaults(User $user): array
    {
        $widgets = [];
        foreach (DashboardWidget::ALL_TYPES as $pos => $type) {
            $w = new DashboardWidget();
            $w->setUser($user);
            $w->setWidgetType($type);
            $w->setPosition($pos);
            $w->setIsEnabled(true);
            $this->em->persist($w);
            $widgets[] = $w;
        }
        $this->em->flush();
        return $widgets;
    }

    private function dataOrganizations(User $user, array $orgs): array
    {
        return ['orgs' => array_values($orgs)];
    }

    private function dataTasks(User $user): array
    {
        $tasks = $this->taskRepo->findAssignedToUser($user);
        $open = array_filter($tasks, fn($t) => !in_array($t->getStatus(), ['done', 'cancelled']));
        usort($open, function ($a, $b) {
            if ($a->isOverdue() !== $b->isOverdue()) return $a->isOverdue() ? -1 : 1;
            if ($a->getDueDate() && $b->getDueDate()) return $a->getDueDate() <=> $b->getDueDate();
            return $b->getPriority() <=> $a->getPriority();
        });
        return ['tasks' => array_slice(array_values($open), 0, 8)];
    }

    private function dataEvents(User $user, array $orgs): array
    {
        $now  = new \DateTimeImmutable();
        $end  = $now->modify('+14 days');
        $events = [];
        foreach ($orgs as $org) {
            foreach ($this->calendarRepo->findForCalendar($org, $now, $end) as $e) {
                $events[] = $e;
            }
        }
        usort($events, fn($a, $b) => $a->getStartAt() <=> $b->getStartAt());
        return ['events' => array_slice($events, 0, 6)];
    }

    private function dataTopics(array $orgs): array
    {
        $topics = [];
        foreach ($orgs as $org) {
            foreach ($this->topicRepo->findBy(['organization' => $org], ['lastActivityAt' => 'DESC'], 5) as $t) {
                $topics[] = $t;
            }
        }
        usort($topics, fn($a, $b) => $b->getLastActivityAt() <=> $a->getLastActivityAt());
        return ['topics' => array_slice($topics, 0, 6)];
    }

    private function dataFiles(array $orgs): array
    {
        $files = [];
        foreach ($orgs as $org) {
            foreach ($this->fileRepo->findBy(['organization' => $org, 'project' => null], ['createdAt' => 'DESC'], 5) as $f) {
                $files[] = $f;
            }
        }
        usort($files, fn($a, $b) => $b->getCreatedAt() <=> $a->getCreatedAt());
        return ['files' => array_slice($files, 0, 6)];
    }

    private function dataEmails(array $orgs): array
    {
        return ['emails' => $this->emailRepo->findRecentForOrganizations(array_values($orgs), 6)];
    }

    private function dataMessages(User $user): array
    {
        $convs = $this->convRepo->findForUser($user);
        $withUnread = array_filter($convs, fn($c) => $c->getUnreadCount($user) > 0);
        return [
            'conversations' => array_slice(array_values($convs), 0, 5),
            'unread'        => count($withUnread),
        ];
    }
}
