<?php

declare(strict_types=1);

namespace App\Enum;

final class WorkflowFinalStateEnum
{
    public const PUBLISHED = 'published';
    public const READY     = 'ready';
    public const REJECTED  = 'rejected';
}
