<?php

declare(strict_types=1);

namespace Lexal\LaravelSteppedForm\Entity;

class RulesDefinition
{
    public function __construct(
        /**
         * @var array<string, mixed>
         */
        private array $rules,
        /**
         * @var array<string, string>
         */
        private array $messages = [],
        /**
         * @var array<string, string>
         */
        private array $customAttributes = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * @return array<string, string>
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * @return array<string, string>
     */
    public function getCustomAttributes(): array
    {
        return $this->customAttributes;
    }
}
