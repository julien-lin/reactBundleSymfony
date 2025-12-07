<?php

declare(strict_types=1);

namespace ReactBundle\Tests\Service;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReactBundle\Service\ReactRenderer;
use Twig\Environment;

class RenderingLoggingTest extends TestCase
{
    private Environment $twig;
    private ReactRenderer $renderer;

    protected function setUp(): void
    {
        $this->twig = $this->createMock(Environment::class);
        $this->renderer = new ReactRenderer($this->twig);
    }

    /**
     * Test that rendering logs enriched context with request_id
     */
    public function testRenderingLogsEnrichedContext(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $this->renderer->setLogger($logger);

        $this->twig->expects($this->once())
            ->method('render')
            ->with('@React/react_component.html.twig')
            ->willReturn('<div id="react-component">Hello</div>');

        // Capture the context passed to logger
        $capturedContext = [];
        $logger->expects($this->once())
            ->method('info')
            ->willReturnCallback(function ($message, $context) use (&$capturedContext) {
                $capturedContext = $context;
            });

        $this->renderer->render('TestComponent', ['name' => 'John', 'age' => 30]);

        // Verify enriched context
        $this->assertArrayHasKey('component_id', $capturedContext);
        $this->assertArrayHasKey('props_keys', $capturedContext);
        $this->assertArrayHasKey('request_id', $capturedContext);
        $this->assertArrayHasKey('timestamp', $capturedContext);
        $this->assertArrayHasKey('memory_peak_mb', $capturedContext);
        $this->assertArrayHasKey('duration_ms', $capturedContext);
        
        // Verify values
        $this->assertEquals('TestComponent', $capturedContext['component']);
        $this->assertStringContainsString('name', $capturedContext['props_keys']);
        $this->assertStringContainsString('age', $capturedContext['props_keys']);
        $this->assertEquals(2, $capturedContext['props_count']);
    }

    /**
     * Test that invalid component names still log errors properly
     */
    public function testInvalidComponentNameHandling(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $this->renderer->setLogger($logger);

        // The exception is thrown before logging, so just verify no logs are created
        $logger->expects($this->never())->method('info');

        // Try to render with invalid component name (will fail)
        try {
            $this->renderer->render('Invalid Component!', []);
        } catch (\InvalidArgumentException $e) {
            // Expected
            $this->assertStringContainsString('Invalid component name', $e->getMessage());
        }
    }

    /**
     * Test props keys are correctly logged
     */
    public function testPropsKeysAreLogged(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $this->renderer->setLogger($logger);

        $this->twig->expects($this->once())
            ->method('render')
            ->willReturn('<div>Test</div>');

        $props = [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john@example.com',
        ];

        $capturedContext = [];
        $logger->expects($this->once())
            ->method('info')
            ->willReturnCallback(function ($message, $context) use (&$capturedContext) {
                $capturedContext = $context;
            });

        $this->renderer->render('Component', $props);

        // Props keys should be comma-separated
        $propsKeysStr = $capturedContext['props_keys'];
        $this->assertStringContainsString('firstName', $propsKeysStr);
        $this->assertStringContainsString('lastName', $propsKeysStr);
        $this->assertStringContainsString('email', $propsKeysStr);
    }

    /**
     * Test memory peak usage is logged
     */
    public function testMemoryPeakUsageIsLogged(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $this->renderer->setLogger($logger);

        $this->twig->expects($this->once())
            ->method('render')
            ->willReturn('<div>Test</div>');

        $capturedContext = [];
        $logger->expects($this->once())
            ->method('info')
            ->willReturnCallback(function ($message, $context) use (&$capturedContext) {
                $capturedContext = $context;
            });

        $this->renderer->render('Component', []);

        $this->assertArrayHasKey('memory_peak_mb', $capturedContext);
        $this->assertIsNumeric($capturedContext['memory_peak_mb']);
        $this->assertGreaterThan(0, $capturedContext['memory_peak_mb']);
    }

    /**
     * Test timestamp format is ISO-8601
     */
    public function testTimestampFormatIsISO8601(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $this->renderer->setLogger($logger);

        $this->twig->expects($this->once())
            ->method('render')
            ->willReturn('<div>Test</div>');

        $capturedContext = [];
        $logger->expects($this->once())
            ->method('info')
            ->willReturnCallback(function ($message, $context) use (&$capturedContext) {
                $capturedContext = $context;
            });

        $this->renderer->render('Component', []);

        $timestamp = $capturedContext['timestamp'];
        // Verify ISO-8601 like format: YYYY-MM-DD HH:MM:SS.mmmmm
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\.\d+/', $timestamp);
    }
}
