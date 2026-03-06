<?php

namespace App\Controller;

use App\Entity\DashboardWidget;
use App\Repository\DashboardWidgetRepository;
use App\Service\DashboardService;
use App\Service\NotificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard')]
    public function index(
        DashboardService $dashboard,
        NotificationService $notifService,
    ): Response {
        $user = $this->getUser();
        $notifService->ensureDefaultSettings($user);

        $widgets = $dashboard->getWidgets($user);
        $widgetData = [];
        foreach ($widgets as $widget) {
            if ($widget->isEnabled()) {
                $widgetData[$widget->getWidgetType()] = $dashboard->getWidgetData($user, $widget->getWidgetType());
            }
        }

        return $this->render('dashboard/index.html.twig', [
            'widgets'    => $widgets,
            'widgetData' => $widgetData,
        ]);
    }

    #[Route('/dashboard/widgets/toggle/{id}', name: 'app_dashboard_widget_toggle', methods: ['POST'])]
    public function toggle(
        int $id,
        Request $request,
        DashboardWidgetRepository $widgetRepo,
    ): Response {
        $widget = $widgetRepo->find($id);
        if (!$widget || $widget->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('widget_toggle_' . $id, $request->request->get('_token'))) {
            $widget->setIsEnabled(!$widget->isEnabled());
            $widgetRepo->getEntityManager()->flush();
        }

        return $this->redirectToRoute('app_dashboard_settings');
    }

    #[Route('/dashboard/widgets/move/{id}/{direction}', name: 'app_dashboard_widget_move', methods: ['POST'])]
    public function move(
        int $id,
        string $direction,
        Request $request,
        DashboardWidgetRepository $widgetRepo,
        DashboardService $dashboard,
    ): Response {
        $widget = $widgetRepo->find($id);
        if (!$widget || $widget->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if (!in_array($direction, ['up', 'down'], true)) {
            throw $this->createNotFoundException();
        }

        if ($this->isCsrfTokenValid('widget_move_' . $id, $request->request->get('_token'))) {
            $dashboard->moveWidget($widget, $direction);
        }

        return $this->redirectToRoute('app_dashboard_settings');
    }

    #[Route('/dashboard/settings', name: 'app_dashboard_settings')]
    public function settings(DashboardService $dashboard): Response
    {
        $widgets = $dashboard->getWidgets($this->getUser());

        return $this->render('dashboard/settings.html.twig', [
            'widgets' => $widgets,
        ]);
    }
}
