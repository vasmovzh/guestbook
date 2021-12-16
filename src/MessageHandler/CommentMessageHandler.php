<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Comment;
use App\Message\CommentMessage;
use App\Repository\CommentRepository;
use App\SpamChecker;
use Doctrine\ORM\EntityManagerInterface;
use Enum\SpamCheckScoreEnum;
use Enum\WorkflowTransitionEnum;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\WorkflowInterface;

final class CommentMessageHandler implements MessageHandlerInterface
{
    private CommentRepository      $commentRepository;
    private EntityManagerInterface $entityManager;
    private LoggerInterface        $logger;
    private MailerInterface        $mailer;
    private MessageBusInterface    $bus;
    private SpamChecker            $spamChecker;
    private string                 $adminEmail;
    private WorkflowInterface      $workflow;

    public function __construct(
        CommentRepository      $commentRepository,
        EntityManagerInterface $entityManager,
        LoggerInterface        $logger,
        MailerInterface        $mailer,
        MessageBusInterface    $bus,
        SpamChecker            $spamChecker,
        string                 $adminEmail,
        WorkflowInterface      $commentStateMachine
    )
    {
        $this->adminEmail        = $adminEmail;
        $this->bus               = $bus;
        $this->commentRepository = $commentRepository;
        $this->entityManager     = $entityManager;
        $this->logger            = $logger;
        $this->mailer            = $mailer;
        $this->spamChecker       = $spamChecker;
        $this->workflow          = $commentStateMachine;
    }

    public function __invoke(CommentMessage $message)
    {
        $comment = $this->commentRepository->find($message->getId());
        if (! $comment) {
            return;
        }

        if (! $this->workflow->can($comment, WorkflowTransitionEnum::ACCEPT)) {
            $this->logger->debug('Dropping comment message', [
                'comment_id' => $comment->getId(),
                'state'      => $comment->getState(),
            ]);
        }

        if ($this->isCommentRequiresAdminReview($comment)){
            return;
        }

        $spamScore  = $this->spamChecker->getSpamScore($comment, $message->getContext());
        $transition = $this->defineTransition($spamScore);

        if ($transition === null) {
            return;
        }

        $this->workflow->apply($comment, $transition);
        $this->entityManager->flush();
        $this->bus->dispatch($message);
    }

    private function isCommentRequiresAdminReview(Comment $comment): bool
    {
        $isPublishAvailable    = $this->workflow->can($comment, WorkflowTransitionEnum::PUBLISH);
        $isPublishHamAvailable = $this->workflow->can($comment, WorkflowTransitionEnum::PUBLISH_HAM);

        if ($isPublishAvailable || $isPublishHamAvailable) {
            $this->sendAdminEmail($comment);

            return true;
        }

        return false;
    }

    private function sendAdminEmail(Comment $comment): void
    {
        $message = new NotificationEmail();

        $message->context(['comment' => $comment]);
        $message->from($this->adminEmail);
        $message->htmlTemplate('emails/comment_notification.html.twig');
        $message->subject('New comment posted');
        $message->to($this->adminEmail);

        $this->mailer->send($message);
    }

    private function defineTransition(int $spamScore): ?string
    {
        switch ($spamScore) {
            case SpamCheckScoreEnum::NOT_SPAM:
                return WorkflowTransitionEnum::ACCEPT;

            case SpamCheckScoreEnum::MAYBE_SPAM:
                return WorkflowTransitionEnum::MIGHT_BE_SPAM;

            case SpamCheckScoreEnum::SPAM:
                return WorkflowTransitionEnum::REJECT_SPAM;

            default:
                $this->logger->warning('Undefined spam score: ' . $spamScore);

                return null;
        }
    }
}
