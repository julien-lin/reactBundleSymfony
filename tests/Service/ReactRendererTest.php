<?php

declare(strict_types=1);

namespace ReactBundle\Tests\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Twig\Environment;
use ReactBundle\Service\ReactRenderer;

class ReactRendererTest extends TestCase
{
    private Environment $twig;
    private LoggerInterface $logger;
    private ReactRenderer $renderer;

    protected function setUp(): void
    {
        $this->twig = $this->createMock(Environment::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        // Mock du rendu Twig
        $this->twig->method('render')
            ->willReturnCallback(function ($template, $context) {
                return sprintf(
                    '<div id="%s" data-react-component="%s" data-react-props="%s"></div>',
                    $context['component_id'] ?? '',
                    $context['component_name'] ?? '',
                    $context['props'] ?? ''
                );
            });

        $this->renderer = new ReactRenderer($this->twig, 'build', $this->logger);
    }

    /**
     * @test
     */
    public function testRenderWithValidComponent(): void
    {
        $props = ['text' => 'Hello', 'count' => 42];
        $result = $this->renderer->render('TestComponent', $props);

        $this->assertIsString($result);
        $this->assertStringContainsString('TestComponent', $result);
        $this->assertStringContainsString('data-react-props=', $result);
    }

    /**
     * @test
     */
    #[DataProvider('invalidComponentNameProvider')]
    public function testInvalidComponentNameThrowsException(string $invalidName): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->renderer->render($invalidName);
    }

    public static function invalidComponentNameProvider(): array
    {
        return [
            'with spaces' => ['Invalid Component'],
            'with special chars' => ['Component<>'],
            'with slash' => ['Component/Other'],
            'with backslash' => ['Component\\Other'],
            'empty string' => [''],
            'too long' => ['x' . str_repeat('a', 300)],
            'with dot' => ['Component.Name'],
            'with comma' => ['Component,Name'],
        ];
    }

    /**
     * @test
     */
    #[DataProvider('validComponentNameProvider')]
    public function testValidComponentNames(string $validName): void
    {
        // Ne doit pas lever d'exception
        $result = $this->renderer->render($validName);
        $this->assertIsString($result);
    }

    public static function validComponentNameProvider(): array
    {
        return [
            'simple' => ['TestComponent'],
            'with underscore' => ['Test_Component'],
            'with hyphen' => ['Test-Component'],
            'with numbers' => ['TestComponent123'],
            'camelCase' => ['MyAwesomeComponent'],
            'single letter' => ['C'],
            'max 255' => [str_repeat('a', 255)],
        ];
    }

    /**
     * @test
     * Test XSS Protection - injection via props
     */
    public function testXSSProtectionInProps(): void
    {
        $maliciousProps = [
            'payload' => '<img src=x onerror="alert(\'xss\')">',
            'script' => '<script>alert("xss")</script>',
            'event' => 'test" onmouseover="alert(1)',
        ];

        // Doit être échappé correctement
        $result = $this->renderer->render('SafeComponent', $maliciousProps);

        // Les caractères dangereux doivent être échappés en entités HTML
        // Les < deviennent &lt;, les > deviennent &gt;, les " deviennent &quot; ou &#034;
        $this->assertStringNotContainsString('<img', $result);
        $this->assertStringNotContainsString('<script', $result);
        $this->assertStringContainsString('&lt;img', $result);
        $this->assertStringContainsString('&lt;script', $result);
    }

    /**
     * @test
     */
    public function testUniqueIdGeneration(): void
    {
        $result1 = $this->renderer->render('Component1');
        $result2 = $this->renderer->render('Component2');

        // Les IDs doivent être différents
        $this->assertNotEquals($result1, $result2);

        // Chaque résultat doit avoir un ID unique
        $this->assertStringContainsString('react-component-', $result1);
        $this->assertStringContainsString('react-component-', $result2);

        // Les IDs doivent être différents
        preg_match('/id="([^"]+)"/', $result1, $match1);
        preg_match('/id="([^"]+)"/', $result2, $match2);
        $this->assertNotEquals($match1[1] ?? null, $match2[1] ?? null);
    }

    /**
     * @test
     */
    public function testCustomIdIsUsed(): void
    {
        $customId = 'my-custom-component-id';
        $result = $this->renderer->render('Component', [], $customId);

        $this->assertStringContainsString($customId, $result);
    }

    /**
     * @test
     */
    public function testPropsAreJsonEncoded(): void
    {
        $props = [
            'string' => 'value',
            'number' => 42,
            'boolean' => true,
            'array' => [1, 2, 3],
            'nested' => ['key' => 'value'],
        ];

        $result = $this->renderer->render('Component', $props);

        // Vérifier que les props sont JSON dans l'attribut
        $this->assertStringContainsString('data-react-props=', $result);
    }

    /**
     * @test
     */
    public function testGetBuildDir(): void
    {
        $this->assertEquals('build', $this->renderer->getBuildDir());
    }

    /**
     * @test
     */
    public function testBuildDirCanBeConfigured(): void
    {
        $renderer = new ReactRenderer($this->twig, 'custom-build', $this->logger);
        $this->assertEquals('custom-build', $renderer->getBuildDir());
    }

    /**
     * @test
     */
    public function testSetLogger(): void
    {
        $newLogger = $this->createMock(LoggerInterface::class);
        $this->renderer->setLogger($newLogger);

        // Vérifier que le logger est utilisé
        $newLogger->expects($this->never())->method('error');
        $this->renderer->render('ValidComponent');
    }

    /**
     * @test
     */
    public function testSpecialCharactersInProps(): void
    {
        $props = [
            'accents' => 'café, naïve',
            'unicode' => '你好世界',
            'emoji' => '😀🎉',
            'quotes' => 'It\'s "quoted"',
            'backslash' => 'C:\\Users\\test',
        ];

        $result = $this->renderer->render('Component', $props);
        $this->assertIsString($result);
        $this->assertStringContainsString('data-react-props=', $result);
    }

    /**
     * @test
     */
    public function testHTMLEntitiesAreEscaped(): void
    {
        $props = [
            'text' => '&<>"\'',
        ];

        $result = $this->renderer->render('Component', $props);

        // Vérifier que les entités HTML sont bien présentes
        $this->assertStringContainsString('&amp;', $result);
        $this->assertStringContainsString('&lt;', $result);
        $this->assertStringContainsString('&gt;', $result);
    }

    /**
     * @test
     */
    public function testEmptyProps(): void
    {
        $result = $this->renderer->render('Component', []);
        $this->assertStringContainsString('[]', $result);
    }

    /**
     * @test
     */
    public function testRenderWithDefault(): void
    {
        // Tester avec les paramètres par défaut
        $result = $this->renderer->render('Component');
        $this->assertIsString($result);
        $this->assertStringContainsString('Component', $result);
    }
}
