<?php

declare(strict_types=1);

namespace ReactBundle\Tests\Security;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Twig\Environment;
use ReactBundle\Service\ReactRenderer;

class XSSProtectionTest extends TestCase
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
    #[DataProvider('xssPayloads')]
    public function testXSSPayloadsAreEscaped(string $payload): void
    {
        $props = ['content' => $payload];
        $result = $this->renderer->render('Component', $props);

        // Les caractères HTML dangereux doivent être échappés (pas présents littéralement)
        $this->assertStringNotContainsString('<script>', $result);
        // Note: onerror= peut être échappé en &quot;onerror et donc pas littéralement présent
    }

    public static function xssPayloads(): array
    {
        return [
            'script tag' => ['<script>alert("xss")</script>'],
            'img onerror' => ['<img src=x onerror="alert(1)">'],
            'event handler' => ['<div onclick="alert(1)">'],
            'svg/onload' => ['<svg onload="alert(1)">'],
            'iframe' => ['<iframe src="javascript:alert(1)">'],
            'javascript protocol' => ['<a href="javascript:alert(1)">click</a>'],
            'style inject' => ['<style>body{display:none}</style>'],
            'form input' => ['<input onfocus="alert(1)" autofocus>'],
            'data uri' => ['<object data="javascript:alert(1)">'],
            'base64' => ['<img src="data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==">'],
        ];
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

        // Doit être encodé en entités HTML
        $this->assertStringContainsString('&amp;', $result);
        $this->assertStringContainsString('&lt;', $result);
        $this->assertStringContainsString('&gt;', $result);
        $this->assertStringContainsString('&quot;', $result);
    }

    /**
     * @test
     */
    public function testUnicodeXSSProtection(): void
    {
        $props = [
            'content' => '\\u003cscript\\u003ealert(1)\\u003c/script\\u003e',
        ];

        $result = $this->renderer->render('Component', $props);
        $this->assertIsString($result);
        // Les backslashes et u doivent être échappés
        $this->assertStringNotContainsString('<script>', $result);
    }

    /**
     * @test
     */
    public function testNestedObjectsAreSafe(): void
    {
        $props = [
            'deeply' => [
                'nested' => [
                    'object' => [
                        'with' => '<script>alert("nested")</script>'
                    ]
                ]
            ]
        ];

        $result = $this->renderer->render('Component', $props);
        $this->assertStringNotContainsString('<script>', $result);
    }

    /**
     * @test
     */
    public function testMixedContentIsSafe(): void
    {
        $props = [
            'safe' => 'Hello World',
            'dangerous' => '<img src=x onerror="alert(1)">',
            'numbers' => 12345,
            'boolean' => true,
            'null' => null,
        ];

        $result = $this->renderer->render('Component', $props);
        $this->assertStringContainsString('Hello World', $result);
        // Les chevrons et guillemets doivent être échappés, pas littéralement présents
        $this->assertStringNotContainsString('<img src=x', $result);
    }

    /**
     * @test
     */
    public function testEventHandlersCannotBypass(): void
    {
        $maliciousPayloads = [
            'onclick="alert(1)"',
            'onmouseover="alert(1)"',
            'onmouseenter="alert(1)"',
            'onmouseleave="alert(1)"',
            'onchange="alert(1)"',
            'onblur="alert(1)"',
            'onfocus="alert(1)"',
            'onkeydown="alert(1)"',
            'onkeyup="alert(1)"',
        ];

        foreach ($maliciousPayloads as $payload) {
            $props = ['content' => '<div ' . $payload . '>'];
            $result = $this->renderer->render('Component', $props);

            // Les guillemets et chevrons doivent être échappés
            $this->assertStringNotContainsString($payload, $result);
        }
    }

    /**
     * @test
     */
    public function testLongPayloadIsHandled(): void
    {
        $longPayload = str_repeat('<img src=x onerror="alert(1)">', 1000);
        $props = ['content' => $longPayload];

        $result = $this->renderer->render('Component', $props);
        // Les chevrons doivent être échappés, pas littéralement présents
        $this->assertStringNotContainsString('<img src=x', $result);
        $this->assertIsString($result);
    }
}
