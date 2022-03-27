<?php

declare(strict_types=1);

namespace App\Notification;

use App\Entity\Comment;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Component\Notifier\Message\EmailMessage;
use Symfony\Component\Notifier\Notification\EmailNotificationInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Recipient\EmailRecipientInterface;

final class CommentReviewNotification extends Notification implements EmailNotificationInterface
{
    private Comment $comment;

    public function __construct(Comment $comment)
    {
        parent::__construct('New comment posted');

        $this->comment = $comment;
    }

    public function asEmailMessage(EmailRecipientInterface $recipient, string $transport = null): ?EmailMessage
    {
        $message = EmailMessage::fromNotification($this, $recipient);

        $message->transport($transport);

        /* @var NotificationEmail $rawMessage */
        $rawMessage = $message->getMessage();

        $rawMessage->context(['comment' => $this->comment]);
        $rawMessage->htmlTemplate('emails/comment_notification.html.twig');

        return $message;
    }
}
