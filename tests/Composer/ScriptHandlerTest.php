<?php

namespace ReactBundle\Tests\Composer;

use PHPUnit\Framework\TestCase;
use ReactBundle\Composer\ScriptHandler;

class ScriptHandlerTest extends TestCase
{
    public function testScriptHandlerClassExists(): void
    {
        $this->assertTrue(class_exists(ScriptHandler::class));
    }

    public function testInstallAssetsMethodExists(): void
    {
        $this->assertTrue(method_exists(ScriptHandler::class, 'installAssets'));
    }

    public function testInstallAssetsIsStatic(): void
    {
        $reflection = new \ReflectionMethod(ScriptHandler::class, 'installAssets');
        $this->assertTrue($reflection->isStatic());
    }

    public function testInstallAssetsIsPublic(): void
    {
        $reflection = new \ReflectionMethod(ScriptHandler::class, 'installAssets');
        $this->assertTrue($reflection->isPublic());
    }

    public function testFindNpmMethodExists(): void
    {
        $this->assertTrue(method_exists(ScriptHandler::class, 'findNpm'));
    }

    public function testPrepareInstallCommandMethodExists(): void
    {
        $this->assertTrue(method_exists(ScriptHandler::class, 'prepareInstallCommand'));
    }

    public function testScriptHandlerHasRequiredMethods(): void
    {
        $requiredMethods = ['installAssets', 'findNpm', 'prepareInstallCommand'];
        
        foreach ($requiredMethods as $method) {
            $this->assertTrue(method_exists(ScriptHandler::class, $method), 
                sprintf('ScriptHandler must have method: %s', $method));
        }
    }
}
