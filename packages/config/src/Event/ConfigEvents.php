<?php

declare(strict_types=1);

namespace Waaseyaa\Config\Event;

enum ConfigEvents: string
{
    case PRE_SAVE = 'aurora.config.pre_save';
    case POST_SAVE = 'aurora.config.post_save';
    case PRE_DELETE = 'aurora.config.pre_delete';
    case POST_DELETE = 'aurora.config.post_delete';
    case IMPORT = 'aurora.config.import';
}
