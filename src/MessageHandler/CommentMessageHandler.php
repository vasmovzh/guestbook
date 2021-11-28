<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Repository\CommentRepository;
use App\SpamChecker;
use Doctrine\ORM\EntityManagerInterface;
use Message\CommentMessage;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class CommentMessageHandler implements MessageHandlerInterface
{
    private EntityManagerInterface $entityManager;
    private CommentRepository      $commentRepository;
    private SpamChecker            $spamChecker;

    public function __construct(EntityManagerInterface $entityManager, CommentRepository $commentRepository, SpamChecker $spamChecker)
    {
        $this->entityManager     = $entityManager;
        $this->commentRepository = $commentRepository;
        $this->spamChecker       = $spamChecker;
    }

    public function __invoke(CommentMessage $message)
    {
        $comment = $this->commentRepository->find($message->getId());
        if (! $comment) {
            return;
        }

        $spamScore = $this->spamChecker->getSpamScore($comment, $message->getContext());
        $state     = $spamScore === 2 ? 'spam' : 'published';

        $comment->setState($state);

        $this->entityManager->flush();
    }
}
