<?php

declare(strict_types=1);

namespace Lexal\LaravelSteppedForm\Tests\Event\Listener;

use Lexal\LaravelSteppedForm\Steps\ValidateStepInterface;
use Lexal\SteppedForm\Steps\StepInterface;

interface ValidatableStepInterface extends StepInterface, ValidateStepInterface
{
}
