<?php

declare(strict_types=1);

namespace Lexal\LaravelSteppedForm\Tests;

use Lexal\SteppedForm\Form\Builder\FormBuilderInterface;
use Lexal\SteppedForm\Step\Builder\StepsBuilderInterface;
use Lexal\SteppedForm\Step\StepInterface;
use Lexal\SteppedForm\Step\Steps;

/**
 * @template-implements FormBuilderInterface<object>
 */
final readonly class FormBuilder implements FormBuilderInterface
{
    public function __construct(private StepsBuilderInterface $stepsBuilder)
    {
    }

    public function isDynamic(): bool
    {
        return false;
    }

    public function build(object $entity): Steps
    {
        $this->stepsBuilder->add('step1', new class () implements StepInterface {
            public function handle(object $entity, mixed $data): object
            {
                return $entity;
            }
        });

        return $this->stepsBuilder->get();
    }
}
