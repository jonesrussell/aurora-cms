<?php

declare(strict_types=1);

namespace Waaseyaa\User;

use Waaseyaa\Entity\EntityType;
use Waaseyaa\Foundation\ServiceProvider\ServiceProvider;

final class UserServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->entityType(new EntityType(
            id: 'user',
            label: 'User',
            class: User::class,
            keys: ['id' => 'uid', 'uuid' => 'uuid', 'label' => 'name'],
            group: 'people',
            fieldDefinitions: [
                'mail' => [
                    'type' => 'email',
                    'label' => 'Email address',
                    'description' => 'The email address of the user.',
                    'weight' => 5,
                ],
                'status' => [
                    'type' => 'boolean',
                    'label' => 'Active',
                    'description' => 'Whether the user account is active.',
                    'weight' => 10,
                ],
                'created' => [
                    'type' => 'timestamp',
                    'label' => 'Member since',
                    'description' => 'The date the user account was created.',
                    'weight' => 20,
                ],
            ],
        ));
    }
}
