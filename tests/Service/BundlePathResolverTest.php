<?php

namespace ReactBundle\Tests\Service;

use PHPUnit\Framework\TestCase;
use ReactBundle\Service\BundlePathResolver;

class BundlePathResolverTest extends TestCase
{
    public function testGetBundlePathReturnsString(): void
    {
        $bundlePath = BundlePathResolver::getBundlePath();
        $this->assertIsString($bundlePath);
        $this->assertNotEmpty($bundlePath);
    }

    public function testGetBundlePathEndsWithReactBundle(): void
    {
        $bundlePath = BundlePathResolver::getBundlePath();
        $this->assertTrue(
            str_ends_with($bundlePath, 'ReactBundle') || str_ends_with($bundlePath, 'reactBundleSymfony')
        );
    }

    public function testGetProjectRootReturnsString(): void
    {
        $projectRoot = BundlePathResolver::getProjectRoot();
        $this->assertIsString($projectRoot);
        $this->assertNotEmpty($projectRoot);
    }

    public function testGetProjectRootIsDifferentFromBundlePath(): void
    {
        $bundlePath = BundlePathResolver::getBundlePath();
        $projectRoot = BundlePathResolver::getProjectRoot();

        // Project root should be a parent of bundle path
        $this->assertTrue(
            strpos($bundlePath, $projectRoot) === 0,
            'Bundle path should be under project root'
        );
    }

    public function testNormalizePathConvertsBackslashes(): void
    {
        $path = 'path\\to\\file';
        $normalized = BundlePathResolver::normalizePath($path);
        $this->assertStringNotContainsString('\\', $normalized);
    }

    public function testGetPublicBuildPathReturnsValidPath(): void
    {
        $buildPath = BundlePathResolver::getPublicBuildPath('build');
        $this->assertIsString($buildPath);
        $this->assertStringContainsString('public', $buildPath);
        $this->assertStringContainsString('build', $buildPath);
    }

    public function testGetPublicBuildPathWithCustomBuildDir(): void
    {
        $buildPath = BundlePathResolver::getPublicBuildPath('dist');
        $this->assertStringContainsString('dist', $buildPath);
    }

    public function testGetManifestPathReturnsNullWhenNotFound(): void
    {
        $manifestPath = BundlePathResolver::getManifestPath('nonexistent-build-dir');
        $this->assertNull($manifestPath);
    }

    public function testPathsAreAbsolute(): void
    {
        $bundlePath = BundlePathResolver::getBundlePath();
        $projectRoot = BundlePathResolver::getProjectRoot();
        $buildPath = BundlePathResolver::getPublicBuildPath();

        // Paths should start with / on Unix or be absolute on all systems
        $this->assertTrue(
            (strpos($bundlePath, '/') === 0 || strpos($bundlePath, ':') === 1),
            'Bundle path should be absolute'
        );

        $this->assertTrue(
            (strpos($projectRoot, '/') === 0 || strpos($projectRoot, ':') === 1),
            'Project root should be absolute'
        );

        $this->assertTrue(
            (strpos($buildPath, '/') === 0 || strpos($buildPath, ':') === 1),
            'Build path should be absolute'
        );
    }
}
