<?php

declare(strict_types=1);

namespace App\Entity\Enum;

enum ErrorType: string
{
    case MESSAGE = 'message';
    case WARNING = 'warning';
    case ERROR = 'error';
    case FATAL = 'fatal';
}
