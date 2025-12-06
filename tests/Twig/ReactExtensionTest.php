<?php

namespace ReactBundle\Tests\Twig;

use PHPUnit\Framework\TestCase;
use ReactBundle\Service\ReactRenderer;
use ReactBundle\Twig\ReactExtension;
use Twig\Extension\AbstractExtension;

class ReactExtensionTest extends TestCase
{
    private ReactExtension $reactExtension;
    private ReactRenderer $reactRenderer;

    protected function setUp(): void
    {
        $this->reactRenderer = $this->createMock(ReactRenderer::class);
        $this->reactExtension = new ReactExtension($this->reactRenderer);
    }

    public function testReactExtensionIsAbstractExtension(): void
    {
        $this->assertInstanceOf(AbstractExtension::class, $this->reactExtension);
    }

    public function testReactExtensionConstructor(): void
    {
        $this->assertInstanceOf(ReactExtension::class, $this->reactExtension);
    }

    public function testReactExtensionGetFunctions(): void
    {
        $functions = $this->reactExtension->getFunctions();
        $this->assertIsArray($functions);
        $this->assertNotEmpty($functions);
    }

    public function testReactExtensionHasReactComponentFunction(): void
    {
        $functions = $this->reactExtension->getFunctions();
        $functionNames = array_map(fn($func) => $func->getName(), $functions);
        
        $this->assertContains('react_component', $functionNames);
    }

    public function testRenderComponentMethod(): void
    {
        $this->assertTrue(method_exists($this->reactExtension, 'renderComponent'));
    }

    public function testRenderComponentReturnsString(): void
    {
        $this->reactRenderer->expects($this->once())
            ->method('render')
            ->with('TestComponent', [], null)
            ->willReturn('<div>Test</div>');

        $result = $this->reactExtension->renderComponent('TestComponent', [], null);
        $this->assertIsString($result);
    }

    public function testRenderComponentWithProps(): void
    {
        $props = ['title' => 'Test', 'count' => 42];
        
        $this->reactRenderer->expects($this->once())
            ->method('render')
            ->with('TestComponent', $props, 'test-id')
            ->willReturn('<div>Test</div>');

        $result = $this->reactExtension->renderComponent('TestComponent', $props, 'test-id');
        $this->assertIsString($result);
    }

    public function testReactExtensionDependencyInjection(): void
    {
        $extension = new ReactExtension($this->reactRenderer);
        $this->assertInstanceOf(ReactExtension::class, $extension);
    }
}
