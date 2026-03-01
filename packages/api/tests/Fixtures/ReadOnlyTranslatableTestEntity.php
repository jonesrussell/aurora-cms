<?php

declare(strict_types=1);

namespace Aurora\Api\Tests\Fixtures;

use Aurora\Entity\ContentEntityBase;
use Aurora\Entity\TranslatableInterface;

/**
 * A translatable entity that intentionally does NOT implement
 * MutableTranslatableInterface. Used to verify that TranslationController
 * returns a 422 when store() is called on an entity that only supports
 * reading translations, not creating them.
 */
class ReadOnlyTranslatableTestEntity extends ContentEntityBase implements TranslatableInterface
{
    public function __construct(
        array $values = [],
        string $entityTypeId = 'readonly',
        array $entityKeys = [],
        array $fieldDefinitions = [],
    ) {
        $defaultKeys = [
            'id' => 'id',
            'uuid' => 'uuid',
            'label' => 'title',
            'bundle' => 'type',
            'langcode' => 'langcode',
        ];

        if (!isset($values['langcode'])) {
            $values['langcode'] = 'en';
        }

        parent::__construct(
            $values,
            $entityTypeId,
            $entityKeys !== [] ? $entityKeys : $defaultKeys,
            $fieldDefinitions,
        );
    }

    public function language(): string
    {
        $langcodeKey = $this->entityKeys['langcode'] ?? 'langcode';

        return (string) ($this->values[$langcodeKey] ?? 'en');
    }

    /** @return string[] */
    public function getTranslationLanguages(): array
    {
        return [$this->language()];
    }

    public function hasTranslation(string $langcode): bool
    {
        return $langcode === $this->language();
    }

    public function getTranslation(string $langcode): static
    {
        if ($langcode !== $this->language()) {
            throw new \InvalidArgumentException(
                "Translation '{$langcode}' does not exist.",
            );
        }

        return $this;
    }
}
