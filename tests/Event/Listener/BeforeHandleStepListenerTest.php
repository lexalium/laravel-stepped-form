<?php

declare(strict_types=1);

namespace Lexal\LaravelSteppedForm\Tests\Event\Listener;

use Illuminate\Contracts\Validation\Factory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\MessageBag;
use Lexal\LaravelSteppedForm\Entity\RulesDefinition;
use Lexal\LaravelSteppedForm\Event\Listener\BeforeHandleStepListener;
use Lexal\LaravelSteppedForm\Steps\ValidateStepInterface;
use Lexal\LaravelSteppedForm\Validator\Exception\ValidatorException;
use Lexal\LaravelSteppedForm\Validator\ValidatorInterface;
use Lexal\SteppedForm\EventDispatcher\Event\BeforeHandleStep;
use Lexal\SteppedForm\Exception\EventDispatcherException;
use Lexal\SteppedForm\Steps\Collection\Step;
use Lexal\SteppedForm\Steps\StepInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BeforeHandleStepListenerTest extends TestCase
{
    private MockObject $validator;
    private BeforeHandleStepListener $listener;

    public function testHandle(): void
    {
        $this->validator->expects($this->once())
            ->method('validate')
            ->with(['data' => 'test'], new RulesDefinition(['data' => 'required'], ['data' => 'test message']));

        $step = $this->createMock(ValidatableStepInterface::class);

        $step->expects($this->once())
            ->method('getRulesDefinition')
            ->with(['entity'])
            ->willReturn(new RulesDefinition(['data' => 'required'], ['data' => 'test message']));

        $event = new BeforeHandleStep(['data' => 'test'], ['entity'], new Step('key', $step));

        $this->listener->handle($event);
    }

    public function testHandleWithErrors(): void
    {
        $this->expectExceptionObject(new ValidatorException(['data' => 'required message']));

        $this->validator->expects($this->once())
            ->method('validate')
            ->with(['data' => 'test'], new RulesDefinition(['data' => 'required']))
            ->willThrowException(new ValidatorException(['data' => 'required message']));

        $step = $this->createMock(ValidatableStepInterface::class);

        $step->expects($this->once())
            ->method('getRulesDefinition')
            ->with(['entity'])
            ->willReturn(new RulesDefinition(['data' => 'required']));

        $event = new BeforeHandleStep(['data' => 'test'], ['entity'], new Step('key', $step));

        $this->listener->handle($event);
    }

    public function testHandleDataIsNorArray(): void
    {
        $this->validator->expects($this->never())
            ->method('validate');

        $step = $this->createMock(ValidatableStepInterface::class);

        $step->expects($this->never())
            ->method('getRulesDefinition');

        $event = new BeforeHandleStep('data', ['entity'], new Step('key', $step));

        $this->listener->handle($event);
    }

    public function testHandleStepIsNotValidatable(): void
    {
        $this->validator->expects($this->never())
            ->method('validate');

        $step = $this->createMock(StepInterface::class);

        $event = new BeforeHandleStep(['data' => 'test'], ['entity'], new Step('key', $step));

        $this->listener->handle($event);
    }

    protected function setUp(): void
    {
        $this->validator = $this->createMock(ValidatorInterface::class);

        $this->listener = new BeforeHandleStepListener($this->validator);

        parent::setUp();
    }
}
