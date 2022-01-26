<?php

declare(strict_types=1);

namespace Lexal\LaravelSteppedForm\Tests\Validator;

use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Contracts\Validation\Validator as LaravelValidator;
use Illuminate\Support\MessageBag;
use Lexal\LaravelSteppedForm\Entity\RulesDefinition;
use Lexal\LaravelSteppedForm\Validator\Exception\ValidatorException;
use Lexal\LaravelSteppedForm\Validator\Validator;
use Lexal\LaravelSteppedForm\Validator\ValidatorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    private MockObject $validatorFactory;
    private ValidatorInterface $validator;

    public function testValidate(): void
    {
        $validator = $this->createMock(LaravelValidator::class);

        $validator->expects($this->once())
            ->method('fails')
            ->willReturn(false);

        $this->validatorFactory->expects($this->once())
            ->method('make')
            ->with(['data' => 'test'], ['data' => 'required'], [], [])
            ->willReturn($validator);

        $this->validator->validate(['data' => 'test'], new RulesDefinition(['data' => 'required']));
    }

    public function testValidateWithException(): void
    {
        /** @phpstan-ignore-next-line */
        $this->expectExceptionObject(new ValidatorException(['data' => ['required message']]));

        $validator = $this->createMock(LaravelValidator::class);

        $validator->expects($this->once())
            ->method('fails')
            ->willReturn(true);

        $validator->expects($this->once())
            ->method('errors')
            ->willReturn(new MessageBag(['data' => 'required message']));

        $this->validatorFactory->expects($this->once())
            ->method('make')
            ->with(['data' => 'test'], ['data' => 'required'], ['data' => 'test message'], [])
            ->willReturn($validator);

        $this->validator->validate(
            ['data' => 'test'],
            new RulesDefinition(['data' => 'required'], ['data' => 'test message']),
        );
    }

    protected function setUp(): void
    {
        $this->validatorFactory = $this->createMock(ValidationFactory::class);

        $this->validator = new Validator($this->validatorFactory);

        parent::setUp();
    }
}
