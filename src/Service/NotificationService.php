<?php

namespace App\Service;

use App\Entity\AppNotification;
use App\Entity\NotificationSetting;
use App\Entity\User;
use App\Repository\NotificationSettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class NotificationService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly NotificationSettingRepository $settingRepo,
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $router,
        private readonly string $appName = 'coop',
        private readonly string $fromEmail = 'noreply@coop.local',
    ) {
    }

    /**
     * Notify a single user about an event.
     *
     * @param User   $recipient  The user to notify
     * @param string $eventType  One of NotificationSetting::EVENT_* constants
     * @param string $title      Short notification title
     * @param string $body       Notification message
     * @param string|null $url   Optional deep-link
     */
    public function notify(User $recipient, string $eventType, string $title, string $body, ?string $url = null): void
    {
        $setting = $this->getOrCreateSetting($recipient, $eventType);

        // In-app notification
        if ($setting->isInApp()) {
            $notif = new AppNotification();
            $notif->setUser($recipient);
            $notif->setEventType($eventType);
            $notif->setTitle($title);
            $notif->setBody($body);
            $notif->setUrl($url);
            $this->em->persist($notif);
            $this->em->flush();
        }

        // E-mail notification (immediate only — digest would need a queue/scheduler)
        if ($setting->isEmail() && $setting->getEmailFrequency() === NotificationSetting::FREQ_IMMEDIATE) {
            $this->sendEmail($recipient, $title, $body, $url);
        }
    }

    /**
     * Notify multiple users at once (skips $sender if present in recipients).
     *
     * @param User[]  $recipients
     * @param User|null $sender   Excluded from notifications
     */
    public function notifyMany(array $recipients, ?User $sender, string $eventType, string $title, string $body, ?string $url = null): void
    {
        foreach ($recipients as $recipient) {
            if ($sender && $recipient->getId() === $sender->getId()) continue;
            $this->notify($recipient, $eventType, $title, $body, $url);
        }
    }

    public function ensureDefaultSettings(User $user): void
    {
        $existing = $this->settingRepo->findMapForUser($user);

        foreach (NotificationSetting::DEFAULTS as $eventType => [$inApp, $email, $freq]) {
            if (isset($existing[$eventType])) continue;

            $s = new NotificationSetting();
            $s->setUser($user);
            $s->setEventType($eventType);
            $s->setInApp($inApp);
            $s->setEmail($email);
            $s->setEmailFrequency($freq);
            $this->em->persist($s);
        }
        $this->em->flush();
    }

    private function getOrCreateSetting(User $user, string $eventType): NotificationSetting
    {
        $map = $this->settingRepo->findMapForUser($user);
        if (isset($map[$eventType])) {
            return $map[$eventType];
        }

        // Create from defaults
        $defaults = NotificationSetting::DEFAULTS[$eventType] ?? [true, false, NotificationSetting::FREQ_IMMEDIATE];
        $s = new NotificationSetting();
        $s->setUser($user);
        $s->setEventType($eventType);
        $s->setInApp($defaults[0]);
        $s->setEmail($defaults[1]);
        $s->setEmailFrequency($defaults[2]);
        $this->em->persist($s);
        $this->em->flush();
        return $s;
    }

    private function sendEmail(User $recipient, string $title, string $body, ?string $url): void
    {
        try {
            $html = '<p>' . nl2br(htmlspecialchars($body)) . '</p>';
            if ($url) {
                $html .= '<p><a href="' . htmlspecialchars($url) . '">Jetzt ansehen</a></p>';
            }
            $html .= '<hr><p style="color:#999;font-size:12px;">Diese Benachrichtigung wurde von ' . $this->appName . ' gesendet.</p>';

            $email = (new Email())
                ->from(new Address($this->fromEmail, $this->appName))
                ->to(new Address($recipient->getEmail(), $recipient->getFullName()))
                ->subject('[' . $this->appName . '] ' . $title)
                ->text(strip_tags($body) . ($url ? "\n\n" . $url : ''))
                ->html($html);

            $this->mailer->send($email);
        } catch (\Throwable) {
            // Don't break app flow on mail failure
        }
    }
}
