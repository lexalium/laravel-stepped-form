<?php

declare(strict_types=1);

namespace Lexal\LaravelSteppedForm\Renderer;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Lexal\HttpSteppedForm\Renderer\RendererInterface;
use Lexal\SteppedForm\Entity\TemplateDefinition;
use Symfony\Component\HttpFoundation\Response;

class Renderer implements RendererInterface
{
    public function __construct(private ViewFactory $view)
    {
    }

    public function render(TemplateDefinition $definition): Response
    {
        $view = $this->view->make($definition->getTemplate(), $definition->getData());

        return new Response($view->render());
    }
}
