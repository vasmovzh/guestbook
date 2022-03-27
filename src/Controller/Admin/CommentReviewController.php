<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Comment;
use App\Message\CommentMessage;
use Doctrine\ORM\EntityManagerInterface;
use App\Enum\WorkflowTransitionEnum;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\Registry;
use Twig\Environment;

final class CommentReviewController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private Environment            $twig;
    private MessageBusInterface    $bus;

    public function __construct(EntityManagerInterface $entityManager, Environment $twig, MessageBusInterface $bus)
    {
        $this->entityManager = $entityManager;
        $this->twig          = $twig;
        $this->bus           = $bus;
    }

    /**
     * @Route("/admin/comment/review/{id}", name="review_comment")
     */
    public function reviewComment(Request $request, Comment $comment, Registry $registry): Response
    {
        $isAccepted = ! $request->query->get(WorkflowTransitionEnum::REJECT);
        $machine    = $registry->get($comment);
        
        if ($machine->can($comment, WorkflowTransitionEnum::PUBLISH)) {
            $transition = $isAccepted ? WorkflowTransitionEnum::PUBLISH : WorkflowTransitionEnum::REJECT;
        } elseif ($machine->can($comment, WorkflowTransitionEnum::PUBLISH_HAM)) {
            $transition = $isAccepted ? WorkflowTransitionEnum::PUBLISH_HAM : WorkflowTransitionEnum::REJECT_HAM;
        } else {
            return new Response('Comment already reviewed or has wrong state');
        }

        $machine->apply($comment, $transition);
        $this->entityManager->flush();
        $this->bus->dispatch(new CommentMessage($comment->getId(), ['isAccepted' => $isAccepted]));

        return $this->render('admin/review.html.twig', compact('transition', 'comment'));
    }
}
