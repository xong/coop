<?php

namespace App\Twig;

use App\Repository\AppNotificationRepository;
use App\Repository\ConversationRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class MessengerExtension extends AbstractExtension
{
    public function __construct(
        private readonly ConversationRepository $conversationRepo,
        private readonly AppNotificationRepository $notificationRepo,
        private readonly Security $security,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('unread_message_count', $this->getUnreadMessageCount(...)),
            new TwigFunction('unread_notification_count', $this->getUnreadNotificationCount(...)),
        ];
    }

    public function getUnreadMessageCount(): int
    {
        $user = $this->security->getUser();
        if (!$user) return 0;
        return $this->conversationRepo->countUnreadForUser($user);
    }

    public function getUnreadNotificationCount(): int
    {
        $user = $this->security->getUser();
        if (!$user) return 0;
        return $this->notificationRepo->countUnreadForUser($user);
    }
}
