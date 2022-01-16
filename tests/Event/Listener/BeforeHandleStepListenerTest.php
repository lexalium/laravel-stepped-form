<?php

declare(strict_types=1);

namespace Lexal\LaravelSteppedForm\Tests\Event\Listener;

use Illuminate\Contracts\Validation\Factory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\MessageBag;
use Lexal\LaravelSteppedForm\Event\Listener\BeforeHandleStepListener;
use Lexal\LaravelSteppedForm\Steps\ValidateStepInterface;
use Lexal\SteppedForm\EventDispatcher\Event\BeforeHandleStep;
use Lexal\SteppedForm\Exception\EventDispatcherException;
use Lexal\SteppedForm\Steps\Collection\Step;
use Lexal\SteppedForm\Steps\StepInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BeforeHandleStepListenerTest extends TestCase
{
    private MockObject $validationFactory;
    private BeforeHandleStepListener $listener;

    public function testHandle(): void
    {
        $validator = $this->createMock(Validator::class);

        $validator->expects($this->once())
            ->method('fails')
            ->willReturn(false);

        $this->validationFactory->expects($this->once())
            ->method('make')
            ->with(['data' => 'test'], ['data' => 'required'])
            ->willReturn($validator);

        $step = $this->createMock(ValidatableStepInterface::class);

        $step->expects($this->once())
            ->method('getRules')
            ->willReturn(['data' => 'required']);

        $event = new BeforeHandleStep(['data' => 'test'], ['entity'], new Step('key', $step));

        $this->listener->handle($event);
    }

    public function testHandleWithErrors(): void
    {
        /** @phpstan-ignore-next-line */
        $this->expectExceptionObject(new EventDispatcherException(['data' => ['required message']]));

        $validator = $this->createMock(Validator::class);

        $validator->expects($this->once())
            ->method('fails')
            ->willReturn(true);

        $validator->expects($this->once())
            ->method('errors')
            ->willReturn(new MessageBag(['data' => 'required message']));

        $this->validationFactory->expects($this->once())
            ->method('make')
            ->with(['data' => 'test'], ['data' => 'required'])
            ->willReturn($validator);

        $step = $this->createMock(ValidatableStepInterface::class);

        $step->expects($this->once())
            ->method('getRules')
            ->willReturn(['data' => 'required']);

        $event = new BeforeHandleStep(['data' => 'test'], ['entity'], new Step('key', $step));

        $this->listener->handle($event);
    }

    public function testHandleDataIsNorArray(): void
    {
        $this->validationFactory->expects($this->never())
            ->method('make');

        $step = $this->createMock(ValidatableStepInterface::class);

        $step->expects($this->never())
            ->method('getRules');

        $event = new BeforeHandleStep('data', ['entity'], new Step('key', $step));

        $this->listener->handle($event);
    }

    public function testHandleStepIsNotValidatable(): void
    {
        $this->validationFactory->expects($this->never())
            ->method('make');

        $step = $this->createMock(StepInterface::class);

        $event = new BeforeHandleStep(['data' => 'test'], ['entity'], new Step('key', $step));

        $this->listener->handle($event);
    }

    protected function setUp(): void
    {
        $this->validationFactory = $this->createMock(Factory::class);

        $this->listener = new BeforeHandleStepListener($this->validationFactory);

        parent::setUp();
    }
}
