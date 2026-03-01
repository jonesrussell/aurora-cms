<?php

declare(strict_types=1);

namespace Waaseyaa\Api;

use Waaseyaa\Entity\TranslatableInterface;

/**
 * Extension of TranslatableInterface that supports creating new translations.
 *
 * TranslatableInterface defines read-only translation access. This interface
 * adds addTranslation() for controllers that need to create new translations
 * without ambiguity: getTranslation() retrieves an existing translation while
 * addTranslation() explicitly creates one.
 */
interface MutableTranslatableInterface extends TranslatableInterface
{
    /**
     * Create a new translation for the given language code.
     *
     * The implementation MUST NOT allow creating a translation that already
     * exists; callers should check hasTranslation() first and handle the
     * conflict themselves.
     *
     * @param string $langcode BCP-47 language code (e.g. 'fr', 'de').
     * @return static The newly created translation object.
     */
    public function addTranslation(string $langcode): static;
}
