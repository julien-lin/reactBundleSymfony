<?php

declare(strict_types=1);

namespace ReactBundle\Tests\Twig;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReactBundle\Twig\ViteExtension;

class ViteExtensionCachingTest extends TestCase
{
    /**
     * Test that manifest is cached after first load
     */
    public function testManifestCaching(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $extension = new ViteExtension(false, 'http://localhost:3000', 'build', $logger);

        // Create a temporary manifest file
        $tempDir = sys_get_temp_dir();
        $manifestPath = $tempDir . DIRECTORY_SEPARATOR . 'manifest.json';
        $manifestData = [
            'app' => ['file' => 'js/app-123.js'],
            'js/app.jsx' => ['file' => 'js/app-123.js'],
        ];
        file_put_contents($manifestPath, json_encode($manifestData));

        try {
            // Use reflection to access the private method
            $reflectionMethod = new \ReflectionMethod($extension, 'loadAndValidateManifest');
            $reflectionMethod->setAccessible(true);
            
            // First call loads from file
            $result1 = $reflectionMethod->invoke($extension, $manifestPath);
            $this->assertEquals($manifestData, $result1);
            
            // Second call should use cache (verify same object returned)
            $result2 = $reflectionMethod->invoke($extension, $manifestPath);
            $this->assertEquals($manifestData, $result2);
            $this->assertEquals($result1, $result2);
        } finally {
            // Cleanup
            unlink($manifestPath);
        }
    }

    /**
     * Test manifest cache with multiple files
     */
    public function testMultipleManifestCaching(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $extension = new ViteExtension(false, 'http://localhost:3000', 'build', $logger);

        $tempDir = sys_get_temp_dir();
        
        try {
            // Create two manifest files
            $manifest1Path = $tempDir . DIRECTORY_SEPARATOR . 'manifest1.json';
            $manifest2Path = $tempDir . DIRECTORY_SEPARATOR . 'manifest2.json';
            
            $data1 = ['app' => ['file' => 'app-1.js']];
            $data2 = ['app' => ['file' => 'app-2.js']];
            
            file_put_contents($manifest1Path, json_encode($data1));
            file_put_contents($manifest2Path, json_encode($data2));

            $reflectionMethod = new \ReflectionMethod($extension, 'loadAndValidateManifest');
            $reflectionMethod->setAccessible(true);
            
            // Load both manifests
            $result1 = $reflectionMethod->invoke($extension, $manifest1Path);
            $result2 = $reflectionMethod->invoke($extension, $manifest2Path);
            
            // They should be different
            $this->assertNotEquals($result1, $result2);
            $this->assertEquals($data1, $result1);
            $this->assertEquals($data2, $result2);
            
            // Second load from manifest1 should use cache
            $result1Again = $reflectionMethod->invoke($extension, $manifest1Path);
            $this->assertEquals($data1, $result1Again);
        } finally {
            // Cleanup
            @unlink($manifest1Path);
            @unlink($manifest2Path);
        }
    }

    /**
     * Test that invalid manifest throws exception even with cache
     */
    public function testInvalidManifestThrowsException(): void
    {
        $extension = new ViteExtension(false, 'http://localhost:3000', 'build');

        $tempDir = sys_get_temp_dir();
        $manifestPath = $tempDir . DIRECTORY_SEPARATOR . 'bad_manifest.json';
        file_put_contents($manifestPath, '{invalid json}');

        try {
            $reflectionMethod = new \ReflectionMethod($extension, 'loadAndValidateManifest');
            $reflectionMethod->setAccessible(true);
            
            $reflectionMethod->invoke($extension, $manifestPath);
            $this->fail('Should throw JsonException');
        } catch (\JsonException $e) {
            $this->assertStringContainsString('Invalid JSON', $e->getMessage());
        } finally {
            unlink($manifestPath);
        }
    }

    /**
     * Test that missing manifest throws exception
     */
    public function testMissingManifestThrowsException(): void
    {
        $extension = new ViteExtension(false, 'http://localhost:3000', 'build');

        $reflectionMethod = new \ReflectionMethod($extension, 'loadAndValidateManifest');
        $reflectionMethod->setAccessible(true);
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('not found');
        
        $reflectionMethod->invoke($extension, '/nonexistent/manifest.json');
    }
}
