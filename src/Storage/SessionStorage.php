<?php

declare(strict_types=1);

namespace Lexal\LaravelSteppedForm\Storage;

use Illuminate\Contracts\Session\Session;
use Lexal\SteppedForm\Form\Storage\StorageInterface;

final class SessionStorage implements StorageInterface
{
    public function __construct(private readonly Session $session, private readonly string $namespace)
    {
    }

    public function has(string $key): bool
    {
        return $this->session->has($this->getStorageKey($key));
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->session->get($this->getStorageKey($key), $default);
    }

    public function put(string $key, mixed $data): void
    {
        $this->session->put($this->getStorageKey($key), $data);
    }

    public function clear(): void
    {
        $this->session->forget($this->namespace);
    }

    private function getStorageKey(string $key): string
    {
        return "$this->namespace.$key";
    }
}
