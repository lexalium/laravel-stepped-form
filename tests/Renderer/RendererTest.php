<?php

declare(strict_types=1);

namespace Lexal\LaravelSteppedForm\Tests\Renderer;

use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Lexal\HttpSteppedForm\Renderer\RendererInterface;
use Lexal\LaravelSteppedForm\Renderer\Renderer;
use Lexal\SteppedForm\Entity\TemplateDefinition;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class RendererTest extends TestCase
{
    private MockObject $view;
    private RendererInterface $renderer;

    public function testRender(): void
    {
        $view = $this->createMock(View::class);

        $view->expects($this->once())
            ->method('render')
            ->willReturn('Test content');

        $this->view->expects($this->once())
            ->method('make')
            ->with('test.template', ['data' => 'hello'])
            ->willReturn($view);

        $actual = $this->renderer->render(new TemplateDefinition('test.template', ['data' => 'hello']));

        $this->assertEquals(new Response('Test content'), $actual);
    }

    protected function setUp(): void
    {
        $this->view = $this->createMock(Factory::class);

        $this->renderer = new Renderer($this->view);

        parent::setUp();
    }
}
