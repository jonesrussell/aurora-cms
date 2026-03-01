<?php

declare(strict_types=1);

namespace Waaseyaa\Config\Exception;

final class ImmutableConfigException extends \LogicException
{
    public static function fromConfigName(string $name): self
    {
        return new self(sprintf(
            'Config "%s" is immutable. Use ConfigFactory::getEditable() to get a mutable instance.',
            $name,
        ));
    }
}
