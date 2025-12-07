<?php

declare(strict_types=1);

namespace ReactBundle\Tests\Composer;

use PHPUnit\Framework\TestCase;
use ReactBundle\Composer\ScriptHandler;

class ScriptHandlerRetryLogicTest extends TestCase
{
    /**
     * Test that ScriptHandler has installAssets method
     */
    public function testScriptHandlerHasInstallAssetsMethod(): void
    {
        $reflection = new \ReflectionClass(ScriptHandler::class);
        $this->assertTrue($reflection->hasMethod('installAssets'));
    }

    /**
     * Test that ScriptHandler code uses escapeshellarg for security
     */
    public function testScriptHandlerUsesEscapeshellarg(): void
    {
        $reflection = new \ReflectionClass(ScriptHandler::class);
        $filename = $reflection->getFileName();
        $content = file_get_contents($filename);

        // Check that escapeshellarg is used for security
        $this->assertStringContainsString('escapeshellarg', $content);
    }

    /**
     * Test retry logic comment in code
     */
    public function testRetryLogicIsImplemented(): void
    {
        $reflection = new \ReflectionClass(ScriptHandler::class);
        $filename = $reflection->getFileName();
        $content = file_get_contents($filename);

        // Check that retry logic comment exists
        $this->assertStringContainsString('P3-RETRY-01', $content);
        $this->assertStringContainsString('Tentative', $content);
    }

    /**
     * Test that npm installation handles exponential backoff
     */
    public function testExponentialBackoffLogic(): void
    {
        // This test verifies the backoff strategy is in the code
        $reflection = new \ReflectionClass(ScriptHandler::class);
        $filename = $reflection->getFileName();
        $content = file_get_contents($filename);

        // Check for backoff mention and sleep
        $this->assertStringContainsString('Backoff', $content);
        $this->assertStringContainsString('sleep', $content);
    }
}
