<?php

declare(strict_types=1);

namespace ReactBundle\Tests\Performance;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReactBundle\Service\ReactRenderer;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

class RenderingBenchmarkTest extends TestCase
{
    private ReactRenderer $renderer;
    private array $logs = [];

    protected function setUp(): void
    {
        $loader = new ArrayLoader([
            '@React/react_component.html.twig' => '<div id="{{ component_id }}" data-react-component="{{ component_name }}" data-react-props="{{ props }}"></div>',
        ]);

        $twig = new Environment($loader);

        // Create mock logger to capture metrics
        $logger = $this->createMock(LoggerInterface::class);
        $logger->method('info')->willReturnCallback(function ($msg, $context) {
            $this->logs[] = ['message' => $msg, 'context' => $context];
        });
        $logger->method('error')->willReturnCallback(function ($msg, $context) {
            $this->logs[] = ['message' => $msg, 'context' => $context];
        });

        $this->renderer = new ReactRenderer($twig, 'build', $logger);
    }

    /**
     * Test that rendering metrics are logged
     */
    public function testRenderingMetricsAreLogged(): void
    {
        $this->renderer->render('TestComponent', ['name' => 'test']);

        // Verify metrics were logged
        $this->assertNotEmpty($this->logs);
        $lastLog = end($this->logs);
        $this->assertEquals('React component rendered', $lastLog['message']);
        $this->assertArrayHasKey('duration_ms', $lastLog['context']);
        $this->assertArrayHasKey('memory_kb', $lastLog['context']);
    }

    /**
     * Test rendering speed for simple components
     */
    public function testRenderingSpeedSimpleComponent(): void
    {
        $startTime = microtime(true);
        $this->renderer->render('SimpleComponent', []);
        $duration = (microtime(true) - $startTime) * 1000;

        // Should complete within reasonable time (< 100ms)
        $this->assertLessThan(100, $duration);
    }

    /**
     * Test rendering speed with complex props
     */
    public function testRenderingSpeedComplexProps(): void
    {
        $complexProps = [
            'title' => 'Complex Component',
            'items' => array_map(fn($i) => ['id' => $i, 'name' => "Item $i"], range(1, 50)),
            'metadata' => ['created' => date('Y-m-d'), 'author' => 'test'],
            'nested' => [
                'level1' => ['level2' => ['level3' => 'deep value']],
            ],
        ];

        $startTime = microtime(true);
        $this->renderer->render('ComplexComponent', $complexProps);
        $duration = (microtime(true) - $startTime) * 1000;

        // Complex props should still complete in reasonable time (< 200ms)
        $this->assertLessThan(200, $duration);
    }

    /**
     * Test rendering multiple components
     */
    public function testMultipleComponentsRendering(): void
    {
        $startTime = microtime(true);

        for ($i = 0; $i < 10; $i++) {
            $this->renderer->render("Component$i", ['index' => $i]);
        }

        $totalDuration = (microtime(true) - $startTime) * 1000;

        // 10 components should complete in reasonable time (< 500ms)
        $this->assertLessThan(500, $totalDuration);
    }

    /**
     * Test that props count is correctly logged
     */
    public function testPropsCountLogging(): void
    {
        $props = ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5];
        $this->renderer->render('PropsTestComponent', $props);

        $lastLog = end($this->logs);
        $this->assertEquals(5, $lastLog['context']['props_count']);
    }

    /**
     * Test that HTML length is logged
     */
    public function testHtmlLengthLogging(): void
    {
        $this->renderer->render('LengthTestComponent', ['data' => 'test']);

        $lastLog = end($this->logs);
        $this->assertArrayHasKey('html_length', $lastLog['context']);
        $this->assertGreaterThan(0, $lastLog['context']['html_length']);
    }

    /**
     * Test memory usage metrics
     */
    public function testMemoryUsageMetrics(): void
    {
        $this->renderer->render('MemoryTestComponent', ['size' => 'large']);

        $lastLog = end($this->logs);
        $this->assertArrayHasKey('memory_kb', $lastLog['context']);

        // Memory usage should be positive (or slightly negative for optimization)
        $this->assertIsNumeric($lastLog['context']['memory_kb']);
    }
}
