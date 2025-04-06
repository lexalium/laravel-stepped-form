<?php

declare(strict_types=1);

namespace Lexal\LaravelSteppedForm\Tests\Storage;

use Illuminate\Contracts\Session\Session;
use Lexal\LaravelSteppedForm\Storage\SessionStorage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SessionStorageTest extends TestCase
{
    private MockObject $session;
    private SessionStorage $storage;

    protected function setUp(): void
    {
        $this->session = $this->createMock(Session::class);

        $this->storage = new SessionStorage($this->session, 'form');
    }

    public function testGet(): void
    {
        $this->session->expects(self::once())
            ->method('get')
            ->with('form.main.key', ['default'])
            ->willReturn(['data' => 'test']);

        $this->assertEquals(['data' => 'test'], $this->storage->get('key', 'main', ['default']));
    }

    public function testPut(): void
    {
        $this->session->expects(self::once())
            ->method('put')
            ->with('form.main.key', ['data' => 'test']);

        $this->storage->put('key', 'main', ['data' => 'test']);
    }

    public function testClear(): void
    {
        $this->session->expects(self::once())
            ->method('forget')
            ->with('form.main');

        $this->storage->clear('main');
    }
}
