<?php

declare(strict_types=1);

namespace ReactBundle\Tests\Twig;

use PHPUnit\Framework\TestCase;
use ReactBundle\Twig\ViteExtension;

class ViteExtensionErrorHandlingTest extends TestCase
{
    /**
     * Test rendering with missing manifest file
     */
    public function testRenderWithMissingManifest(): void
    {
        $extension = new ViteExtension(false, 'http://localhost:3000', 'build');
        $html = $extension->renderViteScriptTags('app');

        // Should return graceful error message
        $this->assertStringContainsString('Vite manifest not found', $html);
    }

    /**
     * Test rendering in dev mode without manifest
     */
    public function testRenderInDevModeWithoutManifest(): void
    {
        $extension = new ViteExtension(true, 'http://localhost:3000', 'build');
        $html = $extension->renderViteScriptTags('app');

        // Should return Vite dev server script tags
        $this->assertStringContainsString('@vite/client', $html);
        $this->assertStringContainsString('js/app.jsx', $html);
    }

    /**
     * Test rendering in dev mode with custom Vite server URL
     */
    public function testRenderInDevModeWithCustomViteServer(): void
    {
        $extension = new ViteExtension(true, 'https://vite.example.com:5173', 'build');
        $html = $extension->renderViteScriptTags('app');

        // Should use custom Vite server URL
        $this->assertStringContainsString('https://vite.example.com:5173', $html);
        $this->assertStringNotContainsString('localhost', $html);
    }

    /**
     * Test rendering with various entry names
     */
    public function testRenderWithVariousEntryNames(): void
    {
        $extension = new ViteExtension(true, 'http://localhost:3000', 'build');

        // Test app entry
        $html = $extension->renderViteScriptTags('app');
        $this->assertStringContainsString('app', $html);

        // Test js entry
        $html = $extension->renderViteScriptTags('js/other');
        $this->assertStringContainsString('js/other', $html);
    }

    /**
     * Test renderViteLinkTags returns empty in dev mode
     */
    public function testRenderLinkTagsEmptyInDevMode(): void
    {
        $extension = new ViteExtension(true, 'http://localhost:3000', 'build');
        $html = $extension->renderViteLinkTags('app');

        // Should return empty string in dev mode
        $this->assertEmpty($html);
    }

    /**
     * Test renderViteLinkTags without manifest returns empty
     */
    public function testRenderLinkTagsWithoutManifest(): void
    {
        $extension = new ViteExtension(false, 'http://localhost:3000', 'build');
        $html = $extension->renderViteLinkTags('app');

        // Should return empty string if no manifest
        $this->assertEmpty($html);
    }
}
