<?php

declare(strict_types=1);

namespace Waaseyaa\Tests\Support;

/**
 * Deterministic workflow fixture corpus for v0.8 lifecycle tests.
 */
final class WorkflowFixturePack
{
    public const int FIXED_TIMESTAMP = 1735689600; // 2025-01-01T00:00:00Z

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function editorialNodesForSsr(): array
    {
        return [
            'published_water' => [
                'title' => 'Water Is Life',
                'type' => 'article',
                'uid' => 7,
                'created' => self::FIXED_TIMESTAMP,
                'changed' => self::FIXED_TIMESTAMP,
                'status' => 1,
                'workflow_state' => 'published',
            ],
            'draft_story' => [
                'title' => 'Draft Node',
                'type' => 'article',
                'uid' => 7,
                'created' => self::FIXED_TIMESTAMP,
                'changed' => self::FIXED_TIMESTAMP,
                'status' => 0,
                'workflow_state' => 'draft',
            ],
            'review_story' => [
                'title' => 'Review Node',
                'type' => 'article',
                'uid' => 7,
                'created' => self::FIXED_TIMESTAMP,
                'changed' => self::FIXED_TIMESTAMP,
                'status' => 0,
                'workflow_state' => 'review',
            ],
            'archived_story' => [
                'title' => 'Archived Node',
                'type' => 'article',
                'uid' => 7,
                'created' => self::FIXED_TIMESTAMP,
                'changed' => self::FIXED_TIMESTAMP,
                'status' => 0,
                'workflow_state' => 'archived',
            ],
        ];
    }

    /**
     * @return list<array{alias: string, path: string, langcode: string, status: int}>
     */
    public static function pathAliasesForSsr(): array
    {
        return [
            ['alias' => '/node/1', 'path' => '/node/1', 'langcode' => 'en', 'status' => 1],
            ['alias' => '/teaching/water-is-life', 'path' => '/node/1', 'langcode' => 'en', 'status' => 1],
            ['alias' => '/node/2', 'path' => '/node/2', 'langcode' => 'en', 'status' => 1],
            ['alias' => '/node/3', 'path' => '/node/3', 'langcode' => 'en', 'status' => 1],
            ['alias' => '/node/4', 'path' => '/node/4', 'langcode' => 'en', 'status' => 1],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function aiMcpNodes(): array
    {
        return [
            'teaching_published' => [
                'title' => 'teaching A',
                'body' => 'water wisdom',
                'type' => 'teaching',
                'status' => 1,
                'workflow_state' => 'published',
            ],
            'teaching_draft' => [
                'title' => 'teaching B',
                'body' => 'fire wisdom',
                'type' => 'teaching',
                'status' => 0,
                'workflow_state' => 'draft',
            ],
        ];
    }

    /**
     * @return list<array{
     *   name: string,
     *   bundle: string,
     *   from: string,
     *   to: string,
     *   permissions: list<string>,
     *   roles: list<string>,
     *   expected_allowed: bool
     * }>
     */
    public static function transitionAccessScenarios(): array
    {
        return [
            [
                'name' => 'contributor submit for review',
                'bundle' => 'article',
                'from' => 'draft',
                'to' => 'review',
                'permissions' => ['submit article for review'],
                'roles' => ['contributor'],
                'expected_allowed' => true,
            ],
            [
                'name' => 'reviewer publish from review',
                'bundle' => 'article',
                'from' => 'review',
                'to' => 'published',
                'permissions' => ['publish article content'],
                'roles' => ['reviewer'],
                'expected_allowed' => true,
            ],
            [
                'name' => 'editor archive from published',
                'bundle' => 'article',
                'from' => 'published',
                'to' => 'archived',
                'permissions' => ['archive article content'],
                'roles' => ['editor'],
                'expected_allowed' => true,
            ],
            [
                'name' => 'contributor cannot publish',
                'bundle' => 'article',
                'from' => 'review',
                'to' => 'published',
                'permissions' => ['publish article content'],
                'roles' => ['contributor'],
                'expected_allowed' => false,
            ],
            [
                'name' => 'reviewer cannot archive',
                'bundle' => 'article',
                'from' => 'published',
                'to' => 'archived',
                'permissions' => ['archive article content'],
                'roles' => ['reviewer'],
                'expected_allowed' => false,
            ],
        ];
    }

    /**
     * @return list<array{name: string, from: string, to: string}>
     */
    public static function invalidTransitionScenarios(): array
    {
        return [
            ['name' => 'draft cannot archive directly', 'from' => 'draft', 'to' => 'archived'],
            ['name' => 'published cannot go to review directly', 'from' => 'published', 'to' => 'review'],
        ];
    }
}
