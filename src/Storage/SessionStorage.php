<?php

declare(strict_types=1);

namespace Lexal\LaravelSteppedForm\Storage;

use Illuminate\Contracts\Session\Session;
use Lexal\SteppedForm\Form\Storage\StorageInterface;

use function sprintf;

final class SessionStorage implements StorageInterface
{
    public function __construct(private readonly Session $session, private readonly string $namespace)
    {
    }

    public function get(string $key, string $sessionKey, mixed $default = null): mixed
    {
        return $this->session->get($this->getStorageKey($key, $sessionKey), $default);
    }

    public function put(string $key, string $sessionKey, mixed $data): void
    {
        $this->session->put($this->getStorageKey($key, $sessionKey), $data);
    }

    public function clear(string $sessionKey): void
    {
        $this->session->forget(sprintf("%s.%s", $this->namespace, $sessionKey));
    }

    private function getStorageKey(string $key, string $sessionKey): string
    {
        return sprintf("%s.%s.%s", $this->namespace, $sessionKey, $key);
    }
}
