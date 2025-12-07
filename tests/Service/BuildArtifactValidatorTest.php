<?php

declare(strict_types=1);

namespace ReactBundle\Tests\Service;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReactBundle\Service\BuildArtifactValidator;

class BuildArtifactValidatorTest extends TestCase
{
    private string $tempDir;
    private BuildArtifactValidator $validator;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'react-build-' . uniqid();
        mkdir($this->tempDir, 0755, true);
        $this->validator = new BuildArtifactValidator();
    }

    protected function tearDown(): void
    {
        // Cleanup temp files
        array_map('unlink', glob($this->tempDir . '/*'));
        @rmdir($this->tempDir);
    }

    /**
     * Test validation of complete build artifacts
     */
    public function testValidateCompleteBuildArtifacts(): void
    {
        // Create mock build artifacts
        file_put_contents($this->tempDir . '/app.123abc.js', 'console.log("app");');
        file_put_contents($this->tempDir . '/app.123abc.css', 'body { color: black; }');
        file_put_contents($this->tempDir . '/manifest.json', json_encode([
            'app' => ['file' => 'app.123abc.js', 'css' => ['app.123abc.css']],
        ]));

        $result = $this->validator->validateBuildArtifacts($this->tempDir);

        $this->assertTrue($result['manifest_exists']);
        $this->assertEquals(1, $result['js_bundles']);
        $this->assertEquals(1, $result['css_bundles']);
        $this->assertStringEndsWith('manifest.json', $result['manifest_path']);
    }

    /**
     * Test that missing manifest throws exception
     */
    public function testMissingManifestThrowsException(): void
    {
        // Create only JS bundles, no manifest
        file_put_contents($this->tempDir . '/app.123abc.js', 'console.log("app");');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Manifest file not found');

        $this->validator->validateBuildArtifacts($this->tempDir);
    }

    /**
     * Test validation with multiple bundles
     */
    public function testValidateMultipleBundles(): void
    {
        // Create multiple bundles
        file_put_contents($this->tempDir . '/app.js', '');
        file_put_contents($this->tempDir . '/vendor.js', '');
        file_put_contents($this->tempDir . '/app.css', '');
        file_put_contents($this->tempDir . '/manifest.json', json_encode([
            'app' => ['file' => 'app.js'],
            'vendor' => ['file' => 'vendor.js'],
        ]));

        $result = $this->validator->validateBuildArtifacts($this->tempDir);

        $this->assertEquals(2, $result['js_bundles']);
        $this->assertEquals(1, $result['css_bundles']);
    }

    /**
     * Test manifest format validation
     */
    public function testValidateManifestFormat(): void
    {
        $manifestPath = $this->tempDir . '/manifest.json';
        $manifestData = [
            'app' => ['file' => 'app.js'],
            'vendor' => ['file' => 'vendor.js'],
        ];
        file_put_contents($manifestPath, json_encode($manifestData));

        $result = $this->validator->validateManifestFormat($manifestPath);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('app', $result);
        $this->assertArrayHasKey('vendor', $result);
    }

    /**
     * Test invalid manifest JSON throws exception
     */
    public function testInvalidManifestJsonThrowsException(): void
    {
        $manifestPath = $this->tempDir . '/manifest.json';
        file_put_contents($manifestPath, '{invalid json}');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid JSON');

        $this->validator->validateManifestFormat($manifestPath);
    }

    /**
     * Test with logger
     */
    public function testValidationWithLogger(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $validator = new BuildArtifactValidator($logger);

        file_put_contents($this->tempDir . '/app.js', '');
        file_put_contents($this->tempDir . '/manifest.json', json_encode(['app' => ['file' => 'app.js']]));

        $logger->expects($this->once())
            ->method('info')
            ->with('Build artifacts validation successful', $this->anything());

        $validator->validateBuildArtifacts($this->tempDir);
    }
}
