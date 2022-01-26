<?php

declare(strict_types=1);

namespace Lexal\LaravelSteppedForm\Validator;

use Lexal\LaravelSteppedForm\Entity\RulesDefinition;
use Lexal\LaravelSteppedForm\Validator\Exception\ValidatorException;

interface ValidatorInterface
{
    /**
     * Validates step data.
     *
     * @param array<int|string, mixed> $data
     *
     * @throws ValidatorException
     */
    public function validate(array $data, RulesDefinition $definition): void;
}
