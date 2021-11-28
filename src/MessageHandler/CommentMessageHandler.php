<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\CommentMessage;
use App\Repository\CommentRepository;
use App\SpamChecker;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\WorkflowInterface;

final class CommentMessageHandler implements MessageHandlerInterface
{
    private const TRANSITION_ACCEPT        = 'accept';
    private const TRANSITION_MIGHT_BE_SPAM = 'might_be_spam';
    private const TRANSITION_PUBLISH       = 'publish';
    private const TRANSITION_PUBLISH_HAM   = 'publish_ham';
    private const TRANSITION_REJECT        = 'reject';
    private const TRANSITION_REJECT_HAM    = 'reject_ham';
    private const TRANSITION_REJECT_SPAM   = 'reject_spam';

    private CommentRepository      $commentRepository;
    private EntityManagerInterface $entityManager;
    private LoggerInterface        $logger;
    private MessageBusInterface    $bus;
    private SpamChecker            $spamChecker;
    private WorkflowInterface      $workflow;

    public function __construct(
        EntityManagerInterface $entityManager,
        CommentRepository      $commentRepository,
        SpamChecker            $spamChecker,
        MessageBusInterface    $bus,
        WorkflowInterface      $commentStateMachine,
        LoggerInterface        $logger
    )
    {
        $this->bus               = $bus;
        $this->commentRepository = $commentRepository;
        $this->entityManager     = $entityManager;
        $this->logger            = $logger;
        $this->spamChecker       = $spamChecker;
        $this->workflow          = $commentStateMachine;
    }

    public function __invoke(CommentMessage $message)
    {
        $comment = $this->commentRepository->find($message->getId());
        if (! $comment) {
            return;
        }

        if (! $this->workflow->can($comment, self::TRANSITION_ACCEPT)) {
            $this->logger->debug('Dropping comment message', [
                'comment_id' => $comment->getId(),
                'state'      => $comment->getState(),
            ]);
        }

        $isPublishAvailable    = $this->workflow->can($comment, self::TRANSITION_PUBLISH);
        $isPublishHamAvailable = $this->workflow->can($comment, self::TRANSITION_PUBLISH_HAM);

        if ($isPublishAvailable || $isPublishHamAvailable) {
            $transition = $isPublishAvailable ? self::TRANSITION_PUBLISH : self::TRANSITION_PUBLISH_HAM;

            $this->workflow->apply($comment, $transition);
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

    private function defineTransition(int $spamScore): ?string
    {
        switch ($spamScore) {
            case SpamChecker::NOT_SPAM_SCORE:
                return self::TRANSITION_ACCEPT;

            case SpamChecker::MAYBE_SPAM_SCORE:
                return self::TRANSITION_MIGHT_BE_SPAM;

            case SpamChecker::SPAM_SCORE:
                return self::TRANSITION_REJECT_SPAM;

            default:
                $this->logger->warning('Undefined spam score: ' . $spamScore);

                return null;
        }
    }
}
