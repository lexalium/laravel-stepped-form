<?php

declare(strict_types=1);

namespace Lexal\LaravelSteppedForm\Steps;

use Lexal\LaravelSteppedForm\Entity\RulesDefinition;

interface ValidateStepInterface
{
    /**
     * Returns Laravel validation rules that the validator will use to validate data.
     */
    public function getRulesDefinition(mixed $entity): RulesDefinition;
}
