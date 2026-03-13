<?php

declare(strict_types=1);

namespace Waaseyaa\Telescope\CodifiedContext\Schema;

final class SessionEventSchema
{
    /**
     * Validate a session event data array against required keys and types.
     *
     * @param array<string, mixed> $data
     */
    public static function validate(array $data): bool
    {
        if (!isset($data['session_id']) || !is_string($data['session_id'])) {
            return false;
        }

        if (!isset($data['event_type']) || !is_string($data['event_type'])) {
            return false;
        }

        if (!isset($data['occurred_at']) || !is_string($data['occurred_at'])) {
            return false;
        }

        if (!array_key_exists('payload', $data) || !is_array($data['payload'])) {
            return false;
        }

        return true;
    }
}
