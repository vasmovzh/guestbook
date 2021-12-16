<?php

declare(strict_types=1);

namespace Enum;

final class WorkflowTransitionEnum
{
    public const ACCEPT        = 'accept';
    public const MIGHT_BE_SPAM = 'might_be_spam';
    public const PUBLISH       = 'publish';
    public const PUBLISH_HAM   = 'publish_ham';
    public const REJECT        = 'reject';
    public const REJECT_HAM    = 'reject_ham';
    public const REJECT_SPAM   = 'reject_spam';
}
