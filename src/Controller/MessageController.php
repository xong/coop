<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\ConversationParticipant;
use App\Entity\DirectMessage;
use App\Repository\ConversationRepository;
use App\Repository\OrganizationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class MessageController extends AbstractController
{
    #[Route('/messages', name: 'app_messages')]
    public function index(ConversationRepository $convRepo): Response
    {
        $me = $this->getUser();
        $conversations = $convRepo->findForUser($me);

        return $this->render('messages/list.html.twig', [
            'conversations' => $conversations,
        ]);
    }

    #[Route('/messages/new', name: 'app_message_new')]
    public function new(
        Request $request,
        UserRepository $userRepo,
        OrganizationRepository $orgRepo,
        ConversationRepository $convRepo,
        EntityManagerInterface $em,
    ): Response {
        $me = $this->getUser();

        // Gather all users the current user shares an org with
        $candidates = $this->getContactableUsers($me, $orgRepo);

        if ($request->isMethod('POST') && $this->isCsrfTokenValid('new_conversation', $request->request->get('_token'))) {
            $type = $request->request->get('type', Conversation::TYPE_DIRECT);
            $title = trim($request->request->get('title', ''));
            $recipientIds = (array) $request->request->all('recipients');
            $firstMessage = trim($request->request->get('message', ''));

            if (empty($recipientIds) || !$firstMessage) {
                $this->addFlash('error', 'Bitte wähle mindestens einen Empfänger und schreibe eine Nachricht.');
                goto render;
            }

            // For direct: find or create
            if ($type === Conversation::TYPE_DIRECT && count($recipientIds) === 1) {
                $recipient = $userRepo->find($recipientIds[0]);
                if ($recipient) {
                    $existing = $convRepo->findDirectBetween($me, $recipient);
                    if ($existing) {
                        // Add message to existing conversation
                        $msg = new DirectMessage();
                        $msg->setConversation($existing);
                        $msg->setSender($me);
                        $msg->setContent($firstMessage);
                        $existing->setLastMessageAt(new \DateTimeImmutable());
                        $em->persist($msg);
                        $em->flush();
                        return $this->redirectToRoute('app_message_show', ['id' => $existing->getId()]);
                    }
                }
            }

            $conv = new Conversation();
            $conv->setType(count($recipientIds) > 1 ? Conversation::TYPE_GROUP : $type);
            $conv->setCreatedBy($me);
            $conv->setLastMessageAt(new \DateTimeImmutable());
            if ($title && $conv->isGroup()) {
                $conv->setTitle($title);
            }

            $myParticipant = new ConversationParticipant();
            $myParticipant->setConversation($conv);
            $myParticipant->setUser($me);
            $myParticipant->markRead();
            $em->persist($myParticipant);

            foreach ($recipientIds as $rid) {
                $recipient = $userRepo->find((int) $rid);
                if ($recipient && $recipient !== $me) {
                    $p = new ConversationParticipant();
                    $p->setConversation($conv);
                    $p->setUser($recipient);
                    $em->persist($p);
                }
            }

            $msg = new DirectMessage();
            $msg->setConversation($conv);
            $msg->setSender($me);
            $msg->setContent($firstMessage);

            $em->persist($conv);
            $em->persist($msg);
            $em->flush();

            return $this->redirectToRoute('app_message_show', ['id' => $conv->getId()]);
        }

        render:
        return $this->render('messages/new.html.twig', [
            'candidates' => $candidates,
            'preselect'  => $request->query->get('user'),
        ]);
    }

    #[Route('/messages/{id}', name: 'app_message_show')]
    public function show(
        int $id,
        Request $request,
        ConversationRepository $convRepo,
        EntityManagerInterface $em,
    ): Response {
        $me = $this->getUser();
        $conv = $convRepo->find($id);

        if (!$conv || !$conv->hasParticipant($me)) {
            throw $this->createAccessDeniedException();
        }

        // Mark as read
        $participant = $conv->getParticipantFor($me);
        if ($participant) {
            $participant->markRead();
            $em->flush();
        }

        // Handle new message
        if ($request->isMethod('POST') && $this->isCsrfTokenValid('msg_' . $id, $request->request->get('_token'))) {
            $content = trim($request->request->get('content', ''));
            if ($content) {
                $msg = new DirectMessage();
                $msg->setConversation($conv);
                $msg->setSender($me);
                $msg->setContent($content);
                $conv->setLastMessageAt(new \DateTimeImmutable());
                $em->persist($msg);

                // Mark sender as read immediately
                if ($participant) {
                    $participant->markRead();
                }

                $em->flush();
                return $this->redirectToRoute('app_message_show', ['id' => $id]);
            }
        }

        return $this->render('messages/show.html.twig', [
            'conversation' => $conv,
        ]);
    }

    #[Route('/messages/{id}/delete-message/{msgId}', name: 'app_message_delete_msg', methods: ['POST'])]
    public function deleteMessage(
        int $id,
        int $msgId,
        Request $request,
        ConversationRepository $convRepo,
        EntityManagerInterface $em,
    ): Response {
        $me = $this->getUser();
        $conv = $convRepo->find($id);

        if (!$conv || !$conv->hasParticipant($me)) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete_msg_' . $msgId, $request->request->get('_token'))) {
            foreach ($conv->getMessages() as $msg) {
                if ($msg->getId() === $msgId && $msg->getSender() === $me) {
                    $msg->setIsDeleted(true);
                    $em->flush();
                    break;
                }
            }
        }

        return $this->redirectToRoute('app_message_show', ['id' => $id]);
    }

    #[Route('/messages/{id}/leave', name: 'app_message_leave', methods: ['POST'])]
    public function leave(
        int $id,
        Request $request,
        ConversationRepository $convRepo,
        EntityManagerInterface $em,
    ): Response {
        $me = $this->getUser();
        $conv = $convRepo->find($id);

        if (!$conv || !$conv->hasParticipant($me)) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('leave_' . $id, $request->request->get('_token'))) {
            $participant = $conv->getParticipantFor($me);
            if ($participant) {
                $em->remove($participant);
                $em->flush();
            }
            $this->addFlash('success', 'Du hast die Unterhaltung verlassen.');
        }

        return $this->redirectToRoute('app_messages');
    }

    #[Route('/messages/{id}/add-participant', name: 'app_message_add_participant', methods: ['POST'])]
    public function addParticipant(
        int $id,
        Request $request,
        ConversationRepository $convRepo,
        UserRepository $userRepo,
        EntityManagerInterface $em,
    ): Response {
        $me = $this->getUser();
        $conv = $convRepo->find($id);

        if (!$conv || !$conv->hasParticipant($me) || !$conv->isGroup()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('add_participant_' . $id, $request->request->get('_token'))) {
            $userId = (int) $request->request->get('user_id');
            $user = $userRepo->find($userId);
            if ($user && !$conv->hasParticipant($user)) {
                $p = new ConversationParticipant();
                $p->setConversation($conv);
                $p->setUser($user);
                $em->persist($p);
                $em->flush();
                $this->addFlash('success', $user->getFullName() . ' wurde hinzugefügt.');
            }
        }

        return $this->redirectToRoute('app_message_show', ['id' => $id]);
    }

    private function getContactableUsers(mixed $me, OrganizationRepository $orgRepo): array
    {
        $userSet = [];
        $orgs = $orgRepo->findAll(); // simplified: find orgs where $me is member
        foreach ($orgs as $org) {
            if (!$org->isMember($me)) continue;
            foreach ($org->getMembers() as $member) {
                $u = $member->getUser();
                if ($u !== $me) {
                    $userSet[$u->getId()] = $u;
                }
            }
        }
        usort($userSet, fn($a, $b) => strcmp($a->getFullName(), $b->getFullName()));
        return array_values($userSet);
    }
}
