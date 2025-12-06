<?php

declare(strict_types=1);

namespace ReactBundle\Service;

/**
 * Helper service for resolving bundle and project paths
 *
 * This service centralizes logic for finding the bundle directory and project root,
 * avoiding code duplication across ViteExtension and ReactAssetsBuildCommand.
 */
final class BundlePathResolver
{
    /**
     * Get the absolute path to the ReactBundle directory
     *
     * @return string The bundle path (either in vendor/ or in src/)
     */
    public static function getBundlePath(): string
    {
        // __DIR__ points to src/Service/, so go up 2 levels to reach the bundle root
        $dir = dirname(__DIR__, 2);

        return self::normalizePath($dir);
    }

    /**
     * Get the absolute path to the project root
     *
     * The logic depends on whether the bundle is installed via Composer (in vendor/)
     * or exists as a local package (in src/).
     *
     * @return string The project root path
     */
    public static function getProjectRoot(): string
    {
        $bundlePath = self::getBundlePath();

        // Calculate project root: if in vendor/, go up 3 levels; otherwise 2
        $vendorSeparator = DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR;

        if (strpos($bundlePath, $vendorSeparator) !== false) {
            $projectRoot = dirname($bundlePath, 3); // vendor/vendor/package -> project root
        } else {
            $projectRoot = dirname($bundlePath, 2); // src/ReactBundle -> project root
        }

        return self::normalizePath($projectRoot);
    }

    /**
     * Normalize a file path (convert backslashes to forward slashes)
     *
     * @param string $path The path to normalize
     *
     * @return string The normalized path
     */
    public static function normalizePath(string $path): string
    {
        return str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path);
    }

    /**
     * Get the path to the public/build directory
     *
     * @param string $buildDir The build directory name (default: 'build')
     *
     * @return string The path to public/build
     */
    public static function getPublicBuildPath(string $buildDir = 'build'): string
    {
        $projectRoot = self::getProjectRoot();
        $buildDir = self::normalizePath($buildDir);

        return $projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . $buildDir;
    }

    /**
     * Get the path to the Vite manifest file
     *
     * The manifest can be in .vite/manifest.json or directly in manifest.json
     *
     * @param string $buildDir The build directory name (default: 'build')
     *
     * @return string|null The path to the manifest file, or null if not found
     */
    public static function getManifestPath(string $buildDir = 'build'): ?string
    {
        $buildPath = self::getPublicBuildPath($buildDir);

        // Try .vite/manifest.json first
        $manifestPath = $buildPath . DIRECTORY_SEPARATOR . '.vite' . DIRECTORY_SEPARATOR . 'manifest.json';
        if (file_exists($manifestPath)) {
            return $manifestPath;
        }

        // Try manifest.json directly
        $manifestPath = $buildPath . DIRECTORY_SEPARATOR . 'manifest.json';
        if (file_exists($manifestPath)) {
            return $manifestPath;
        }

        return null;
    }
}
