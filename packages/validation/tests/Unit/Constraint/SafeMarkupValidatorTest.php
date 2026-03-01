<?php

declare(strict_types=1);

namespace Waaseyaa\Validation\Tests\Unit\Constraint;

use Waaseyaa\Validation\Constraint\SafeMarkup;
use Waaseyaa\Validation\Constraint\SafeMarkupValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

final class SafeMarkupValidatorTest extends TestCase
{
    private SafeMarkupValidator $validator;
    private ExecutionContextInterface $context;
    private ConstraintViolationBuilderInterface $violationBuilder;

    protected function setUp(): void
    {
        $this->validator = new SafeMarkupValidator();

        $this->violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $this->violationBuilder->method('setParameter')->willReturnSelf();
        $this->violationBuilder->method('setCode')->willReturnSelf();
        // addViolation() returns void; no willReturn needed.

        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->validator->initialize($this->context);
    }

    public function testScriptTagTriggersViolation(): void
    {
        $this->context->expects($this->once())
            ->method('buildViolation')
            ->willReturn($this->violationBuilder);

        $this->validator->validate(
            '<script>alert("xss")</script>',
            new SafeMarkup(),
        );
    }

    public function testEventHandlerTriggersViolation(): void
    {
        $this->context->expects($this->once())
            ->method('buildViolation')
            ->willReturn($this->violationBuilder);

        $this->validator->validate(
            '<img src="x" onerror="alert(1)">',
            new SafeMarkup(),
        );
    }

    public function testJavascriptUriTriggersViolation(): void
    {
        $this->context->expects($this->once())
            ->method('buildViolation')
            ->willReturn($this->violationBuilder);

        $this->validator->validate(
            '<a href="javascript:void(0)">click</a>',
            new SafeMarkup(),
        );
    }

    public function testIframeTriggersViolation(): void
    {
        $this->context->expects($this->once())
            ->method('buildViolation')
            ->willReturn($this->violationBuilder);

        $this->validator->validate(
            '<iframe src="https://evil.com"></iframe>',
            new SafeMarkup(),
        );
    }

    public function testOnClickTriggersViolation(): void
    {
        $this->context->expects($this->once())
            ->method('buildViolation')
            ->willReturn($this->violationBuilder);

        $this->validator->validate(
            '<div onclick="doEvil()">click me</div>',
            new SafeMarkup(),
        );
    }

    public function testCleanHtmlPasses(): void
    {
        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate(
            '<p>Hello <strong>world</strong></p><ul><li>item</li></ul>',
            new SafeMarkup(),
        );
    }

    public function testPlainTextPasses(): void
    {
        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate('Just some plain text.', new SafeMarkup());
    }

    public function testNullValueIsSkipped(): void
    {
        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate(null, new SafeMarkup());
    }

    public function testEmptyStringIsSkipped(): void
    {
        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate('', new SafeMarkup());
    }

    public function testDataUriBase64TriggersViolation(): void
    {
        $this->context->expects($this->once())
            ->method('buildViolation')
            ->willReturn($this->violationBuilder);

        $this->validator->validate(
            '<img src="data:text/html;base64,PHNjcmlwdD4=">',
            new SafeMarkup(),
        );
    }

    public function testMetaRefreshTriggersViolation(): void
    {
        $this->context->expects($this->once())
            ->method('buildViolation')
            ->willReturn($this->violationBuilder);

        $this->validator->validate(
            '<meta http-equiv="refresh" content="0;url=https://evil.com">',
            new SafeMarkup(),
        );
    }
}
