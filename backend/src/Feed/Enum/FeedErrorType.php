<?php

declare(strict_types=1);

namespace App\Feed\Enum;

enum FeedErrorType: string
{
    case MESSAGE = 'message';
    case WARNING = 'warning';
    case ERROR = 'error';
    case FATAL = 'fatal';
}
