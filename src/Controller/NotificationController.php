<?php

namespace App\Controller;

use App\Entity\NotificationSetting;
use App\Repository\AppNotificationRepository;
use App\Repository\NotificationSettingRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class NotificationController extends AbstractController
{
    #[Route('/notifications', name: 'app_notifications')]
    public function index(AppNotificationRepository $notifRepo): Response
    {
        $notifications = $notifRepo->findForUser($this->getUser());

        return $this->render('notifications/list.html.twig', [
            'notifications' => $notifications,
        ]);
    }

    #[Route('/notifications/mark-all-read', name: 'app_notifications_mark_read', methods: ['POST'])]
    public function markAllRead(
        Request $request,
        AppNotificationRepository $notifRepo,
    ): Response {
        if ($this->isCsrfTokenValid('notif_mark_read', $request->request->get('_token'))) {
            $notifRepo->markAllReadForUser($this->getUser());
        }

        return $this->redirectToRoute('app_notifications');
    }

    #[Route('/notifications/{id}/read', name: 'app_notification_read', methods: ['POST'])]
    public function markRead(
        int $id,
        Request $request,
        AppNotificationRepository $notifRepo,
        EntityManagerInterface $em,
    ): Response {
        $notif = $notifRepo->find($id);
        if (!$notif || $notif->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('notif_read_' . $id, $request->request->get('_token'))) {
            $notif->setIsRead(true);
            $em->flush();
        }

        return $this->redirect($notif->getUrl() ?? $this->generateUrl('app_notifications'));
    }

    #[Route('/notifications/settings', name: 'app_notification_settings')]
    public function settings(
        Request $request,
        NotificationSettingRepository $settingRepo,
        NotificationService $notifService,
        EntityManagerInterface $em,
    ): Response {
        $user = $this->getUser();
        $notifService->ensureDefaultSettings($user);
        $settings = $settingRepo->findMapForUser($user);

        if ($request->isMethod('POST') && $this->isCsrfTokenValid('notif_settings', $request->request->get('_token'))) {
            foreach (array_keys(NotificationSetting::EVENT_LABELS) as $eventType) {
                if (!isset($settings[$eventType])) continue;
                $s = $settings[$eventType];
                $s->setInApp((bool) $request->request->get('inapp_' . $eventType));
                $s->setEmail((bool) $request->request->get('email_' . $eventType));
                $freq = $request->request->get('freq_' . $eventType, NotificationSetting::FREQ_IMMEDIATE);
                $s->setEmailFrequency(in_array($freq, [NotificationSetting::FREQ_IMMEDIATE, NotificationSetting::FREQ_DIGEST]) ? $freq : NotificationSetting::FREQ_IMMEDIATE);
            }
            $em->flush();
            $this->addFlash('success', 'Benachrichtigungseinstellungen gespeichert.');
            return $this->redirectToRoute('app_notification_settings');
        }

        return $this->render('notifications/settings.html.twig', [
            'settings'   => $settings,
            'eventTypes' => array_keys(NotificationSetting::EVENT_LABELS),
        ]);
    }
}
