<?php

declare(strict_types=1);

namespace Waaseyaa\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates that text does not contain dangerous HTML/scripts.
 *
 * Detects script tags, event handler attributes, javascript: URIs,
 * data: URIs in certain contexts, and other common XSS vectors.
 */
final class SafeMarkupValidator extends ConstraintValidator
{
    /**
     * Regex patterns matching dangerous markup constructs.
     *
     * @var string[]
     */
    private const array DANGEROUS_PATTERNS = [
        // Script tags (including variations).
        '/<script\b[^>]*>.*?<\/script>/is',
        '/<script\b[^>]*>/is',

        // Event handler attributes (on*="...").
        '/\bon\w+\s*=/is',

        // javascript: and vbscript: URIs.
        '/\bjavascript\s*:/is',
        '/\bvbscript\s*:/is',

        // data: URIs (can contain scripts).
        '/\bdata\s*:[^,]*;base64/is',

        // Expression() in CSS (IE-specific XSS).
        '/expression\s*\(/is',

        // Import directives that can load external resources.
        '/@import\b/is',

        // Embedded objects and iframes.
        '/<\s*(iframe|object|embed|applet|form)\b/is',

        // Meta refresh redirects.
        '/<\s*meta[^>]+http-equiv\s*=/is',

        // SVG event handlers and script.
        '/<\s*svg\b[^>]*\bon\w+/is',
    ];

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof SafeMarkup) {
            throw new UnexpectedTypeException($constraint, SafeMarkup::class);
        }

        // Allow null values to pass through.
        if ($value === null || $value === '') {
            return;
        }

        if (!\is_string($value)) {
            return;
        }

        foreach (self::DANGEROUS_PATTERNS as $pattern) {
            if (preg_match($pattern, $value)) {
                $this->context->buildViolation($constraint->message)
                    ->addViolation();

                return;
            }
        }
    }
}
