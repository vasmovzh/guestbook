<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Comment;
use App\Enum\SpamCheckScoreEnum;
use App\Enum\WorkflowTransitionEnum;
use App\ImageOptimizer;
use App\Message\CommentMessage;
use App\Notification\CommentReviewNotification;
use App\Notification\UserCommentPublishedNotification;
use App\Notification\UserCommentRejectedNotification;
use App\Repository\CommentRepository;
use App\SpamChecker;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Notifier\Recipient\Recipient;
use Symfony\Component\Workflow\WorkflowInterface;

final class CommentMessageHandler implements MessageHandlerInterface
{
    private CommentRepository      $commentRepository;
    private EntityManagerInterface $entityManager;
    private ImageOptimizer         $imageOptimizer;
    private LoggerInterface        $logger;
    private MessageBusInterface    $bus;
    private NotifierInterface      $notifier;
    private SpamChecker            $spamChecker;
    private string                 $photoDir;
    private WorkflowInterface      $workflow;

    public function __construct(
        CommentRepository      $commentRepository,
        EntityManagerInterface $entityManager,
        ImageOptimizer         $imageOptimizer,
        LoggerInterface        $logger,
        MessageBusInterface    $bus,
        NotifierInterface      $notifier,
        SpamChecker            $spamChecker,
        string                 $photoDir,
        WorkflowInterface      $commentStateMachine
    )
    {
        $this->bus               = $bus;
        $this->commentRepository = $commentRepository;
        $this->entityManager     = $entityManager;
        $this->imageOptimizer    = $imageOptimizer;
        $this->logger            = $logger;
        $this->notifier          = $notifier;
        $this->photoDir          = $photoDir;
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

        $this->notifyUser($message, $comment);

        if ($this->workflow->can($comment, WorkflowTransitionEnum::OPTIMIZE)) {
            $filename = $comment->getPhotoFilename();
            if ($filename) {
                $this->imageOptimizer->resize($this->photoDir . '/' . $filename);
            }

            $this->workflow->apply($comment, WorkflowTransitionEnum::OPTIMIZE);
            $this->entityManager->flush();

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
            $this->notifier->send(new CommentReviewNotification($comment), ...$this->notifier->getAdminRecipients());

            return true;
        }

        return false;
    }

    private function notifyUser(CommentMessage $message, Comment $comment): void
    {
        $isAccepted = $message->getContext()['isAccepted'] ?? false;
        $recipient  = new Recipient($comment->getEmail());

        if ($isAccepted && $comment->isPublished()) {
            $this->notifier->send(new UserCommentPublishedNotification($comment), $recipient);
        }

        if (! $isAccepted && $comment->isRejected()) {
            $this->notifier->send(new UserCommentRejectedNotification($comment), $recipient);
        }
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
