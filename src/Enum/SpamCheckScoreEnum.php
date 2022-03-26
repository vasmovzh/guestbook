<?php

declare(strict_types=1);

namespace App\Enum;

final class SpamCheckScoreEnum
{
    public const MAYBE_SPAM = 1;
    public const NOT_SPAM   = 0;
    public const SPAM       = 2;
}
