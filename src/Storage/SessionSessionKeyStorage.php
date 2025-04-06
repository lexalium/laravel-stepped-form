<?php

declare(strict_types=1);

namespace Lexal\LaravelSteppedForm\Storage;

use Illuminate\Contracts\Session\Session;
use Lexal\SteppedForm\Form\Storage\SessionStorageInterface;

use function sprintf;

final class SessionSessionKeyStorage implements SessionStorageInterface
{
    public function __construct(private readonly Session $session, private readonly string $namespace)
    {
    }

    public function get(string $key): ?string
    {
        return $this->session->get($this->getStorageKey($key));
    }

    public function put(string $key, string $sessionKey): void
    {
        $this->session->put($this->getStorageKey($key), $sessionKey);
    }

    private function getStorageKey(string $key): string
    {
        return sprintf("%s.%s", $this->namespace, $key);
    }
}
