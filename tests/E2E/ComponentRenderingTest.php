<?php

declare(strict_types=1);

namespace ReactBundle\Tests\E2E;

use PHPUnit\Framework\TestCase;
use ReactBundle\Service\ReactRenderer;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

/**
 * Tests E2E pour le rendu des composants React
 * ✅ P1-3: Tests du rendu des composants
 */
class ComponentRenderingTest extends TestCase
{
    private ReactRenderer $renderer;
    private Environment $twig;

    protected function setUp(): void
    {
        $loader = new ArrayLoader([
            '@React/react_component.html.twig' => '<div id="{{ component_id }}" data-react-component="{{ component_name }}" data-react-props="{{ props }}"></div>',
        ]);
        
        $this->twig = new Environment($loader);
        $this->renderer = new ReactRenderer($this->twig, 'build');
    }

    public function testRenderSimpleComponent(): void
    {
        $html = $this->renderer->render('TestComponent', []);
        
        $this->assertStringContainsString('data-react-component="TestComponent"', $html);
        $this->assertStringContainsString('data-react-props', $html);
        $this->assertStringContainsString('id="react-component-', $html);
    }

    public function testRenderComponentWithProps(): void
    {
        $props = [
            'title' => 'Test Title',
            'count' => 42,
            'active' => true
        ];
        
        $html = $this->renderer->render('TestComponent', $props);
        
        $this->assertStringContainsString('data-react-component="TestComponent"', $html);
        $this->assertStringContainsString('Test Title', $html);
        $this->assertStringContainsString('42', $html);
    }

    public function testRenderComponentWithComplexProps(): void
    {
        $props = [
            'user' => [
                'name' => 'John Doe',
                'email' => 'john@example.com'
            ],
            'items' => ['item1', 'item2', 'item3']
        ];
        
        $html = $this->renderer->render('TestComponent', $props);
        
        $this->assertStringContainsString('data-react-component="TestComponent"', $html);
        // Les props sont encodées en JSON, donc vérifier la structure
        $this->assertStringContainsString('John Doe', $html);
    }

    public function testRenderComponentWithCustomId(): void
    {
        $html = $this->renderer->render('TestComponent', [], 'custom-id-123');
        
        $this->assertStringContainsString('id="custom-id-123"', $html);
        $this->assertStringContainsString('data-react-component="TestComponent"', $html);
    }

    public function testRenderComponentEscapesXSS(): void
    {
        $props = [
            'title' => '<script>alert("xss")</script>',
            'message' => 'Hello & "World"'
        ];
        
        $html = $this->renderer->render('TestComponent', $props);
        
        // Vérifier que le script est échappé (double échappement car JSON puis HTML)
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&amp;lt;script&amp;gt;', $html);
        // Vérifier que les guillemets sont échappés
        $this->assertStringContainsString('&amp;quot;', $html);
    }

    public function testRenderComponentValidatesComponentName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->renderer->render('Invalid Component Name!', []);
    }

    public function testRenderComponentValidatesComponentNameLength(): void
    {
        $longName = str_repeat('A', 256); // 256 caractères
        
        $this->expectException(\InvalidArgumentException::class);
        $this->renderer->render($longName, []);
    }

    public function testRenderMultipleComponents(): void
    {
        $html1 = $this->renderer->render('Component1', ['id' => 1]);
        $html2 = $this->renderer->render('Component2', ['id' => 2]);
        
        $this->assertStringContainsString('Component1', $html1);
        $this->assertStringContainsString('Component2', $html2);
        $this->assertNotEquals($html1, $html2);
    }

    public function testRenderComponentWithEmptyProps(): void
    {
        $html = $this->renderer->render('TestComponent', []);
        
        // Les props vides sont encodées en JSON, donc "[]" pour un tableau vide
        $this->assertStringContainsString('data-react-props', $html);
        $this->assertStringContainsString('[]', $html);
    }

    public function testRenderComponentWithNullProps(): void
    {
        $props = [
            'value' => null,
            'optional' => null
        ];
        
        $html = $this->renderer->render('TestComponent', $props);
        
        // null devrait être encodé en JSON
        $this->assertStringContainsString('null', $html);
    }
}

