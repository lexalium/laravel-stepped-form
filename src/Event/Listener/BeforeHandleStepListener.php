<?php

declare(strict_types=1);

namespace Lexal\LaravelSteppedForm\Event\Listener;

use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Lexal\LaravelSteppedForm\Steps\ValidateStepInterface;
use Lexal\SteppedForm\EventDispatcher\Event\BeforeHandleStep;
use Lexal\SteppedForm\Exception\EventDispatcherException;

use function is_array;

class BeforeHandleStepListener
{
    public function __construct(private ValidationFactory $validatorFactory)
    {
    }

    /**
     * @throws EventDispatcherException
     */
    public function handle(BeforeHandleStep $event): void
    {
        $step = $event->getStep()->getStep();

        if ($step instanceof ValidateStepInterface && is_array($event->getData())) {
            $validator = $this->validatorFactory->make($event->getData(), $step->getRules());

            if ($validator->fails()) {
                throw new EventDispatcherException($validator->errors()->messages());
            }
        }
    }
}
