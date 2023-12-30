<?php

declare(strict_types=1);

namespace Lexal\LaravelSteppedForm\Tests\Routing;

use Illuminate\Routing\Redirector as LaravelRedirector;
use Lexal\HttpSteppedForm\Routing\RedirectorInterface;
use Lexal\LaravelSteppedForm\Routing\Redirector;
use Lexal\LaravelSteppedForm\Tests\RedirectResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

final class RedirectorTest extends TestCase
{
    private LaravelRedirector&Stub $laravelRedirector;
    private RedirectorInterface $redirector;

    protected function setUp(): void
    {
        $this->laravelRedirector = $this->createStub(LaravelRedirector::class);

        $this->redirector = new Redirector($this->laravelRedirector);
    }

    /**
     * @param array<string, string> $errors
     */
    #[DataProvider('redirectDataProvider')]
    public function testRedirect(array $errors, bool $withErrors, bool $withInputs): void
    {
        $response = new RedirectResponse('test.com/test/url');

        $this->laravelRedirector->method('to')
            ->willReturn($response);

        $this->redirector->redirect('test.com/test/url', $errors);

        $this->assertEquals($withErrors, $response->withErrors);
        $this->assertEquals($withInputs, $response->withInputs);
    }

    /**
     * @return iterable<string, array{array<string, string>, bool, bool}>
     */
    public static function redirectDataProvider(): iterable
    {
        yield 'without errors' => [[], false, false];
        yield 'with errors' => [['error' => 'message'], true, true];
    }
}
