<?php

declare(strict_types=1);

namespace Lexal\LaravelSteppedForm\Event\Listener;

use Lexal\LaravelSteppedForm\Steps\ValidateStepInterface;
use Lexal\LaravelSteppedForm\Validator\Exception\ValidatorException;
use Lexal\LaravelSteppedForm\Validator\ValidatorInterface;
use Lexal\SteppedForm\EventDispatcher\Event\BeforeHandleStep;

use function is_array;

class BeforeHandleStepListener
{
    public function __construct(private ValidatorInterface $validator)
    {
    }

    /**
     * @throws ValidatorException
     */
    public function handle(BeforeHandleStep $event): void
    {
        $step = $event->getStep()->getStep();

        if ($step instanceof ValidateStepInterface && is_array($event->getData())) {
            $this->validator->validate($event->getData(), $step->getRulesDefinition($event->getEntity()));
        }
    }
}
