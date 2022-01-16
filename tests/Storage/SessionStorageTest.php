<?php

declare(strict_types=1);

namespace Lexal\LaravelSteppedForm\Tests\Storage;

use Illuminate\Contracts\Session\Session;
use Lexal\LaravelSteppedForm\Storage\SessionStorage;
use Lexal\SteppedForm\Data\Storage\StorageInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SessionStorageTest extends TestCase
{
    private MockObject $session;
    private StorageInterface $storage;

    public function testPut(): void
    {
        $this->session->expects($this->once())
            ->method('put')
            ->with('form.key', ['data' => 'test']);

        $this->storage->put('key', ['data' => 'test']);
    }

    public function testGet(): void
    {
        $this->session->expects($this->once())
            ->method('get')
            ->with('form.key', ['default'])
            ->willReturn(['data' => 'test']);

        $this->assertEquals(['data' => 'test'], $this->storage->get('key', ['default']));
    }

    public function testKeys(): void
    {
        $this->session->expects($this->once())
            ->method('get')
            ->with('form')
            ->willReturn(['key' => 'data', 'key2' => 'data']);

        $this->assertEquals(['key', 'key2'], $this->storage->keys());
    }

    public function testHas(): void
    {
        $this->session->expects($this->exactly(2))
            ->method('has')
            ->withConsecutive(['form.key'], ['form.key2'])
            ->willReturnOnConsecutiveCalls(true, false);

        $this->assertTrue($this->storage->has('key'));
        $this->assertFalse($this->storage->has('key2'));
    }

    public function testForget(): void
    {
        $this->session->expects($this->once())
            ->method('forget')
            ->with('form.key');

        $this->storage->forget('key');
    }

    public function testClear(): void
    {
        $this->session->expects($this->once())
            ->method('forget')
            ->with('form');

        $this->storage->clear();
    }

    protected function setUp(): void
    {
        $this->session = $this->createMock(Session::class);

        $this->storage = new SessionStorage('form', $this->session);

        parent::setUp();
    }
}
