<?php

declare(strict_types=1);

namespace Lexal\LaravelSteppedForm\Storage;

use Illuminate\Contracts\Session\Session;
use Lexal\SteppedForm\Data\Storage\StorageInterface;

use function array_keys;
use function array_map;

class SessionStorage implements StorageInterface
{
    public function __construct(private string $namespace, private Session $session)
    {
    }

    public function put(string $key, mixed $data): StorageInterface
    {
        $this->session->put($this->getStorageKey($key), $data);

        return $this;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->session->get($this->getStorageKey($key), $default);
    }

    public function keys(): array
    {
        return array_map('strval', array_keys($this->session->get($this->namespace, [])));
    }

    public function has(string $key): bool
    {
        return $this->session->has($this->getStorageKey($key));
    }

    public function forget(string $key): StorageInterface
    {
        $this->session->forget($this->getStorageKey($key));

        return $this;
    }

    public function clear(): StorageInterface
    {
        $this->session->forget($this->namespace);

        return $this;
    }

    private function getStorageKey(string $key): string
    {
        return "$this->namespace.$key";
    }
}
