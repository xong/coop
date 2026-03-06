<?php

namespace App\Service;

use App\Entity\IncomingEmail;
use App\Entity\MailboxConfig;
use App\Entity\NotificationSetting;
use App\Repository\IncomingEmailRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Exceptions\ConnectionFailedException;

class ImapService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly IncomingEmailRepository $emailRepository,
        private readonly NotificationService $notifService,
        private readonly UrlGeneratorInterface $router,
        private readonly string $uploadDir,
    ) {
    }

    /**
     * Fetch new emails from the given mailbox and store them in the database.
     * Returns [fetched, skipped, error].
     */
    public function syncMailbox(MailboxConfig $config): array
    {
        if (!extension_loaded('imap')) {
            return [0, 0, 'PHP IMAP-Erweiterung ist nicht geladen. Bitte aktivieren Sie extension=imap in php.ini.'];
        }

        try {
            $cm = new ClientManager();
            $client = $cm->make([
                'host'          => $config->getImapHost(),
                'port'          => $config->getImapPort(),
                'encryption'    => $config->getImapEncryption() === 'none' ? false : $config->getImapEncryption(),
                'validate_cert' => false,
                'username'      => $config->getImapUsername(),
                'password'      => $config->getImapPassword(),
                'protocol'      => 'imap',
            ]);

            $client->connect();
            $folder = $client->getFolder($config->getImapFolder());

            // Fetch last 100 unseen messages
            $messages = $folder->query()->unseen()->limit(100)->get();

            $fetched = 0;
            $skipped = 0;
            $newEmails = [];
            $attachmentDir = $this->uploadDir . '/email-attachments';
            if (!is_dir($attachmentDir)) {
                mkdir($attachmentDir, 0755, true);
            }

            foreach ($messages as $message) {
                $messageId = (string) $message->getMessageId();

                // Skip already imported
                if ($this->emailRepository->findByMessageId($messageId)) {
                    $skipped++;
                    continue;
                }

                $email = new IncomingEmail();
                $email->setMailbox($config);
                $email->setMessageId($messageId ?: uniqid('noid-', true));
                $email->setSubject((string) $message->getSubject());

                $from = $message->getFrom();
                if ($from && $from->count() > 0) {
                    $fromAddr = $from->first();
                    $email->setFromEmail($fromAddr->mail ?? '');
                    $email->setFromName($fromAddr->personal ?? $fromAddr->mail ?? '');
                }

                $toList = [];
                foreach ($message->getTo() as $to) {
                    $toList[] = $to->mail;
                }
                $email->setToAddresses(implode(', ', $toList));

                $ccList = [];
                foreach ($message->getCc() as $cc) {
                    $ccList[] = $cc->mail;
                }
                $email->setCcAddresses(implode(', ', $ccList));

                $email->setBodyText((string) ($message->getTextBody() ?? $message->getHtmlBody() ?? ''));
                $email->setBodyHtml((string) ($message->getHtmlBody() ?? ''));

                $receivedAt = $message->getDate();
                if ($receivedAt) {
                    $email->setReceivedAt(\DateTimeImmutable::createFromMutable($receivedAt->toDate()));
                }

                $email->setInReplyTo((string) ($message->getInReplyTo() ?? ''));

                // Handle attachments
                $attachmentData = [];
                foreach ($message->getAttachments() as $attachment) {
                    $origName = (string) $attachment->getName();
                    $safeName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $origName);
                    $destPath = $attachmentDir . '/' . $safeName;
                    $attachment->save($attachmentDir, $safeName);

                    $attachmentData[] = [
                        'name'     => $origName,
                        'path'     => 'uploads/files/email-attachments/' . $safeName,
                        'mimeType' => (string) $attachment->getMimeType(),
                        'size'     => (int) $attachment->getSize(),
                    ];
                }
                $email->setAttachments($attachmentData);

                $this->em->persist($email);
                $newEmails[] = $email;
                $fetched++;
            }

            $this->em->flush();
            foreach ($newEmails as $email) {
                $this->notifyMailboxMembers($config, $email);
            }

            $client->disconnect();

            $config->setLastSyncAt(new \DateTimeImmutable());
            $config->setLastSyncError(null);
            $this->em->flush();

            return [$fetched, $skipped, null];

        } catch (\Throwable $e) {
            $error = $e->getMessage();
            $config->setLastSyncAt(new \DateTimeImmutable());
            $config->setLastSyncError($error);
            $this->em->flush();
            return [0, 0, $error];
        }
    }

    public function isImapAvailable(): bool
    {
        return extension_loaded('imap');
    }

    private function notifyMailboxMembers(MailboxConfig $config, IncomingEmail $email): void
    {
        $org     = $config->getOrganization();
        $project = $config->getProject();

        if ($project) {
            $recipients = array_map(fn($m) => $m->getUser(), $project->getMembers()->toArray());
        } elseif ($org) {
            $recipients = array_map(fn($m) => $m->getUser(), $org->getMembers()->toArray());
        } else {
            return;
        }

        if (empty($recipients)) return;

        $url = $this->router->generate(
            'app_mailbox_show',
            ['id' => $email->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $this->notifService->notifyMany(
            $recipients,
            null,
            NotificationSetting::EVENT_EMAIL_RECEIVED,
            'Neue E-Mail: ' . $email->getSubject(),
            'Von: ' . ($email->getFromName() ?: $email->getFromEmail()),
            $url,
        );
    }
}
