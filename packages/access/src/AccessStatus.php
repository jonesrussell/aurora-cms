<?php

declare(strict_types=1);

namespace Aurora\Access;

enum AccessStatus: string
{
    case ALLOWED = 'allowed';
    case NEUTRAL = 'neutral';
    case FORBIDDEN = 'forbidden';
}
