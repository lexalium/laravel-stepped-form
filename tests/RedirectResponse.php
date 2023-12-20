<?php

declare(strict_types=1);

namespace Lexal\LaravelSteppedForm\Tests;

use Illuminate\Http\RedirectResponse as BaseRedirectResponse;

final class RedirectResponse extends BaseRedirectResponse
{
    public bool $withErrors = false;
    public bool $withInputs = false;

    /**
     * @param string|array<string, string> $provider
     * @param string $key
     */
    public function withErrors($provider, $key = 'default'): self
    {
        $this->withErrors = true;

        return $this;
    }

    /**
     * @param array<string|int, mixed> $input
     */
    public function withInput(array $input = null): self
    {
        $this->withInputs = true;

        return $this;
    }
}
