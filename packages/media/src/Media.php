<?php

declare(strict_types=1);

namespace Aurora\Media;

use Aurora\Entity\ContentEntityBase;

/**
 * Defines the media content entity.
 *
 * Media entities represent pieces of media content (images, videos, files, etc.)
 * that can be reused across the site. Each media entity belongs to a media type
 * (bundle) which determines the source plugin and field configuration.
 */
final class Media extends ContentEntityBase
{
    public function __construct(array $values = [])
    {
        parent::__construct(
            values: $values,
            entityTypeId: 'media',
            entityKeys: [
                'id' => 'mid',
                'uuid' => 'uuid',
                'label' => 'name',
                'bundle' => 'bundle',
            ],
        );
    }

    /**
     * Gets the media name.
     */
    public function getName(): string
    {
        return (string) ($this->get('name') ?? '');
    }

    /**
     * Sets the media name.
     */
    public function setName(string $name): static
    {
        $this->set('name', $name);

        return $this;
    }

    /**
     * Gets the media type (bundle) machine name.
     */
    public function getBundle(): string
    {
        return $this->bundle();
    }

    /**
     * Gets the owner (author) user ID.
     */
    public function getOwnerId(): ?int
    {
        $ownerId = $this->get('uid');

        return $ownerId !== null ? (int) $ownerId : null;
    }

    /**
     * Sets the owner (author) user ID.
     */
    public function setOwnerId(int $ownerId): static
    {
        $this->set('uid', $ownerId);

        return $this;
    }

    /**
     * Returns whether this media entity is published.
     */
    public function isPublished(): bool
    {
        return (bool) ($this->get('status') ?? true);
    }

    /**
     * Sets the published status.
     */
    public function setPublished(bool $published): static
    {
        $this->set('status', $published);

        return $this;
    }

    /**
     * Gets the creation timestamp.
     */
    public function getCreatedTime(): ?int
    {
        $created = $this->get('created');

        return $created !== null ? (int) $created : null;
    }

    /**
     * Sets the creation timestamp.
     */
    public function setCreatedTime(int $timestamp): static
    {
        $this->set('created', $timestamp);

        return $this;
    }

    /**
     * Gets the last changed timestamp.
     */
    public function getChangedTime(): ?int
    {
        $changed = $this->get('changed');

        return $changed !== null ? (int) $changed : null;
    }

    /**
     * Sets the last changed timestamp.
     */
    public function setChangedTime(int $timestamp): static
    {
        $this->set('changed', $timestamp);

        return $this;
    }
}
