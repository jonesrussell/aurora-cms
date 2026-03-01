<?php

declare(strict_types=1);

namespace Aurora\Api\Tests\Fixtures;

use Aurora\Api\MutableTranslatableInterface;
use Aurora\Entity\ContentEntityBase;

/**
 * Test entity that supports translations.
 *
 * Each TranslatableTestEntity object represents one language. Translations
 * are stored as separate entity objects tracked by the original entity.
 */
class TranslatableTestEntity extends ContentEntityBase implements MutableTranslatableInterface
{
    /**
     * Translation storage: langcode => TranslatableTestEntity.
     *
     * @var array<string, TranslatableTestEntity>
     */
    private array $translations = [];

    public function __construct(
        array $values = [],
        string $entityTypeId = 'article',
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

        // Set default langcode if not provided.
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
        $languages = [$this->language()];
        foreach (array_keys($this->translations) as $langcode) {
            if (!in_array($langcode, $languages, true)) {
                $languages[] = $langcode;
            }
        }

        return $languages;
    }

    public function hasTranslation(string $langcode): bool
    {
        if ($langcode === $this->language()) {
            return true;
        }

        return isset($this->translations[$langcode]);
    }

    public function getTranslation(string $langcode): static
    {
        if ($langcode === $this->language()) {
            return $this;
        }

        if (isset($this->translations[$langcode])) {
            return $this->translations[$langcode];
        }

        throw new \InvalidArgumentException(
            "Translation '{$langcode}' does not exist. Use addTranslation() to create it.",
        );
    }

    public function addTranslation(string $langcode): static
    {
        if ($this->hasTranslation($langcode)) {
            throw new \InvalidArgumentException(
                "Translation '{$langcode}' already exists. Use getTranslation() to retrieve it.",
            );
        }

        // Create a new translation object seeded with the base entity's values.
        $values = $this->values;
        $langcodeKey = $this->entityKeys['langcode'] ?? 'langcode';
        $values[$langcodeKey] = $langcode;

        $translation = new static(
            values: $values,
            entityTypeId: $this->entityTypeId,
            entityKeys: $this->entityKeys,
            fieldDefinitions: $this->fieldDefinitions,
        );

        // Share the same ID and UUID as the source entity.
        $idKey = $this->entityKeys['id'] ?? 'id';
        if (isset($this->values[$idKey])) {
            $translation->values[$idKey] = $this->values[$idKey];
        }

        $uuidKey = $this->entityKeys['uuid'] ?? 'uuid';
        $translation->values[$uuidKey] = $this->values[$uuidKey];

        // Register the new translation so hasTranslation() and getTranslation()
        // return it from now on.
        $this->translations[$langcode] = $translation;

        return $translation;
    }

    /**
     * Remove a translation by language code.
     */
    public function removeTranslation(string $langcode): void
    {
        unset($this->translations[$langcode]);
    }
}
