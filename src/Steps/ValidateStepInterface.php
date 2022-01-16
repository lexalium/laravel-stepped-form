<?php

declare(strict_types=1);

namespace Lexal\LaravelSteppedForm\Steps;

interface ValidateStepInterface
{
    /**
     * Returns Laravel validation rules that the validator will use to validate data.
     *
     * @return array<string, mixed>
     */
    public function getRules(): array;
}
